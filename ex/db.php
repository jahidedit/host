<?php
$host = 'sql103.byethost15.com'; // Replace XXX with your server number (see your control panel)
$user = 'b15_39038603';            // Your database username (e.g., b15_1234567)
$pass = 'Jahid@5662';          // Your database password
$dbname = 'b15_39038603_money_tracker';   // Your full database name

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>