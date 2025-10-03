<?php
$password = 'October082024'; // <-- The password you want to use for login
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo "Hashed Password: " . $hashed_password . "<br>";
echo "Copy this entire long string and paste it into your 'password' column in phpMyAdmin.";
?>