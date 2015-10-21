<?php
    define('HOST_NAME','########');
    define('DATABASE','########');
    define('DB_USER','##########');
    define('DB_PASS','##########');

	$mysqli = new mysqli(HOST_NAME, DB_USER, DB_PASS, DATABASE);
	if ($mysqli->connect_errno) {
		echo "Error: Failed to make a MySQL connection, here is why: \n";
		echo "Errno: " . $mysqli->connect_errno . "\n";
   		echo "Error: " . $mysqli->connect_error . "\n";
   	}
?>