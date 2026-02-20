<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "tech_blaze";

$conn = mysqli_connect($host,$user,$pass,$db);

if(!$conn){
die("Database connection failed");
}

?>