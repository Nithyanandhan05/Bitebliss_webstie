<?php
// The password you want to hash for id 2
$plainPassword = 'GU@ni2004';

// Hash the password using the standard BCRYPT algorithm
$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

// Print the result to the screen
echo 'Your new hashed password is: <br><br>';
echo $hashedPassword;
?>