<?php

$hostname = "localhost";
$username = "root";
$password = "";
$database_name = "ukk_login";

$db = mysqli_connect($hostname, $username, $password, $database_name);

if (!$db) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

?>
