<?php
include 'config.php';

$service_number = '474652';
$password = password_hash('12345', PASSWORD_DEFAULT);

$sql = "INSERT INTO users (service_number, password) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $service_number, $password);
$stmt->execute();

echo "Test user inserted.";
?>
