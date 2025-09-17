<?php
// Database connection
$servername = "localhost"; 
$username = "root";        
$password = "";            
$dbname = "chama_management";  

// Create a new MySQLi connection object to establish a connection to the MySQL database.
// The mysqli constructor takes four parameters: server name, username, password, and database name.
// This creates a connection to the 'chama_management' database on the local MySQL server
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if the connection attempt was successful.
// The 'connect_error' property contains an error message if the connection fails.
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
