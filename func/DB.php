<?php
    define('HOST_NAME','db559501865.db.1and1.com');
    define('DATABASE','db559501865');
    define('DB_USER','dbo559501865');
    define('DB_PASS','Henry226');

	$mysqli = new mysqli(HOST_NAME, DB_USER, DB_PASS, DATABASE);
	if ($mysqli->connect_errno) {
		echo "Error: Failed to make a MySQL connection, here is why: \n";
		echo "Errno: " . $mysqli->connect_errno . "\n";
   		echo "Error: " . $mysqli->connect_error . "\n";
   	}
?>