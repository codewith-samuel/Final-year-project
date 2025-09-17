<?php
// Include the database connection
include "db_connect.php";  // This brings in $conn

// M-Pesa API credentials
$consumerKey = "HHsPA9DLKVAUD68uqSQakKSSrpliXBkbSDOvw6hHnh2QYylu";    //  consumer key
$consumerSecret = "QTLoQ2hFVA7wNSW1tGj5H46ab2vqOQPjLusU2h33VYsMi8weJUTWtuIxz1se1Fv8";  //  secret key
$passKey = "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919";             //   passkey
$callbackURL = "https://50c953f2ac5c.ngrok-free.app/Chama-management-system/mpesa_callback.php";

// Optional: Base URL for redirects 
$baseUrl = "https://50c953f2ac5c.ngrok-free.app/Chama-management-system";
// C:\Users\HomePC\Downloads\ngrok-v3-stable-windows-amd64
?>
