<?php
// /migrantcare/db.php

$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = "";     // Default XAMPP password
$dbname = "migrantcare_db";

// 1. Suppress the default warning, we will handle it manually
mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);

try {
    // 2. Attempt to create the connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // 3. If connection is successful, set the charset
    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    // 4. If connection fails, catch the error and die gracefully
    // This stops the script and shows a clear message.
    die("Connection failed: " . $e->getMessage() . 
        "<br><br><strong>Common Fix:</strong> Please ensure the 'MySQL' service is running in your XAMPP Control Panel.");
}

// If the script reaches this point, the connection is successful.
?>
