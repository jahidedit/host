<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$host = "sql103.byethost15.com"; // Your actual host
$dbname = "b15_39038603_user";
$username = "b15_39038603";
$password = "Jahid@5662";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}else{
    //echo "connect ok";
}
?>