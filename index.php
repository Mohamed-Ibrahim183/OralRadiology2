<?php

// Example script to test password hashing and verification
$password = "testpassword";
$hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

echo "Plain text password: $password\n";
echo "Hashed password: $hashed_password\n";

if (password_verify($password, $hashed_password)) {
  echo "Password verification successful.\n";
} else {
  echo "Password verification failed.\n";
}
