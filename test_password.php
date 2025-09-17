<?php
$stored_hash = '$2y$10$KZ2b1z3k3j4v5w6x7y8z9A.2B3C4D5E6F7G8H9I0J1K'; // Or your new hash
$input_password = 'admin123';

if (password_verify($input_password, $stored_hash)) {
    echo "Password matches!";
} else {
    echo "Password does NOT match.";
}
?>