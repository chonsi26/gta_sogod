<?php
$conn = new mysqli("localhost", "root", "", "gta");

if($conn -> connect_error) {
    die("Connection Error");
}
?>