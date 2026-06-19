<?php
// generate_hash.php
// Run this file once to generate the password hash

$password = 'Fktl@010';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: " . $password . "\n";
echo "Hash: " . $hash . "\n";
echo "\nCopy this hash to your SQL insert statement.\n";
?>