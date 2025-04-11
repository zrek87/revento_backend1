from flask import Flask, request, jsonify
import mysql.connector
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
from dateutil import parser
import random
from datetime import datetime

app = Flask(__name__)

def get_db_connection():
    return mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="revento_app"
    )

def get_user_data(user_uuid):
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    #Fetch user preferences
    cursor.execute("SELECT category, subcategory, city FROM user_preferences WHERE user_uuid = UNHEX(%s)", (user_uuid,))
    preferences = cursor.fetchall()

    #Fetch user's past 5 bookings
    cursor.execute("""
        SELECT bookings.event_id, events.category 
        FROM bookings 
        INNER JOIN events ON bookings.event_id = events.event_id 
        WHERE bookings.user_uuid = UNHEX(%s) 
        ORDER BY bookings.booking_id DESC 
        LIMIT 5
    """, (user_uuid,))
    past_bookings = cursor.fetchall()

    conn.close()
    return preferences, past_bookings


def get_events():
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    cursor.execute("""
        SELECT event_id, title, category, subcategory, date_time, location, event_photo, price, description 
        FROM events 
        WHERE date_time >= NOW()
    """)
    
    events = cursor.fetchall()
    
    conn.close()
    return events

#Build User-Event Matrix (for Collaborative Filtering)
def build_user_event_matrix():
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    cursor.execute("SELECT user_uuid, event_id FROM bookings")
    bookings = cursor.fetchall()

    conn.close()

    df = pd.DataFrame(bookings)
    if df.empty:
        return pd.DataFrame()

    return df.pivot(index="user_uuid", columns="event_id", values="event_id").notnull().astype(int)

#AI Recommendation Function
def recommend_events(user_uuid):
    preferences, past_bookings = get_user_data(user_uuid)
    events = get_events()

    if not events:
        return []

    df = pd.DataFrame(events)
    user_event_matrix = build_user_event_matrix()

    #Get collaborative filtering recommendations
    cf_recommendations = set()
    if not user_event_matrix.empty and user_uuid in user_event_matrix.index:
        similarity_scores = cosine_similarity(user_event_matrix.loc[[user_uuid]], user_event_matrix)[0]
        similar_users = dict(zip(user_event_matrix.index, similarity_scores))
        sorted_users = sorted(similar_users.items(), key=lambda x: x[1], reverse=True)
        top_similar_users = [u[0] for u in sorted_users if u[0] != user_uuid][:5]

        if top_similar_users:
            similar_users_events = user_event_matrix.loc[top_similar_users].sum().sort_values(ascending=False)
            cf_recommendations = set(similar_users_events.index.tolist()[:5])

    #Use TF-IDF to calculate similarity based on categories
    vectorizer = TfidfVectorizer()
    category_matrix = vectorizer.fit_transform(df['category'])
    similarity_matrix = cosine_similarity(category_matrix)

    #Rank events based on multiple factors
    event_scores = {}
    now = datetime.now()

    for i, event in df.iterrows():
        score = 0

        #Preference Match
        for pref in preferences:
            if pref['category'].lower() == event['category'].lower() or pref['subcategory'].lower() == event['subcategory'].lower():
                score += 40  # ✅ Exact match
            elif pref['category'].lower() in event['category'].lower() or pref['subcategory'].lower() in event['subcategory'].lower():
                score += 20  # ✅ Partial match


        for booking in past_bookings:
            if booking['category'].lower() == event['category'].lower():
                score += 30  # 🚀 Moderate boost, avoids over-recommending same category

        #Exact Location Match (+30 points)
        for pref in preferences:
            if pref.get('city', '').lower() == event['location'].lower():
                score += 30

        #Boost for new users (+25)
        if not past_bookings:
            score += 25

        #Event Similarity Score (+0 to +40)
        similarity_score = sum(similarity_matrix[i]) * 10
        score += min(40, similarity_score)

        #Event Popularity Boost (Dynamic scoring)
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT COUNT(*) FROM bookings WHERE event_id = %s", (event['event_id'],))
        event_popularity = cursor.fetchone()[0]
        conn.close()

        if event_popularity > 100:
            score += 30
        elif event_popularity >= 50:
            score += 20
        elif event_popularity >= 20:
            score += 10
        else:
            score += 5

        #Recency Boost (+15 if event in next 7 days, +10 if within 30 days)
        event_date = parser.parse(str(event['date_time']))
        days_until_event = (event_date - now).days
        if days_until_event <= 7:
            score += 15
        elif days_until_event <= 30:
            score += 10

        #Boost if event is in Collaborative Filtering results (+70)
        if event['event_id'] in cf_recommendations:
            score += 70

        # ✅ Random Factor (+0 to +10) to break ties
        score += random.randint(0, 10)

        #Store ranking data
        event_scores[event['event_id']] = score

    #Sort events by highest ranking score
    sorted_events = sorted(event_scores.items(), key=lambda x: x[1], reverse=True)

    #Filter out already booked events & diversify results
    booked_event_ids = {b['event_id'] for b in past_bookings}
    recommended_event_ids = []
    category_count = {}

    for event_id, _ in sorted_events:
        event_category = df.loc[df['event_id'] == event_id, 'category'].values[0]
        
        if event_id not in booked_event_ids:
            if category_count.get(event_category, 0) < 2:
                recommended_event_ids.append(event_id)
                category_count[event_category] = category_count.get(event_category, 0) + 1

        if len(recommended_event_ids) >= 3:
            break

    final_recommendations = [
        {
            **event,
            "ranking_score": event_scores[event["event_id"]]
        }
        for event in events if event['event_id'] in recommended_event_ids
    ]

    return final_recommendations


@app.route('/recommend', methods=['GET'])
def recommend():
    user_uuid = request.args.get('user_uuid')
    if not user_uuid:
        return jsonify({"error": "User ID is required"}), 400

    recommendations = recommend_events(user_uuid)
    return jsonify({"recommendations": recommendations})

if __name__ == '__main__':
    app.run(debug=True)
