<?php
// Copy this file to config.php and fill in your database credentials.
$db_host = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "laboratory";

try {
    $conn = new PDO( "mysql:host=$db_host;dbname=$db_name", $db_user, $db_password );
//    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
} catch( PDOException $e ) {
//    echo "Error: " . $e->getMessage();
}

?>
