<?php
include('conn.php');

if ($conn) {
    echo "Database connection successful!";
} else {
    echo "Database connection failed.";
}
?>
