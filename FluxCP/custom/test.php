<?php
	ini_set('display_errors', 1);
	$host = "localhost";
	$user = "root";
	$pass = "61fb00e03d0f8645";
	$dbname = "ragnarok_main";
	$dsn = 'mysql:host=' . $host . ';dbname=' . $dbname.';';
	// Set options
	$options = array(
		PDO::ATTR_PERSISTENT    => true,
		PDO::ATTR_ERRMODE       => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_EMULATE_PREPARES	=> false,
		PDO::ATTR_DEFAULT_FETCH_MODE	=> PDO::FETCH_ASSOC
	);
	// Create a new PDO instanace
	try {
		$pdo = new PDO($dsn, $user, $pass, $options);
		$stmt = $pdo->prepare("SELECT * FROM item_db");
		$stmt->execute();
		print_r($stmt->fetchAll());
	} catch (PDOException $e) {
		print_r($e);
	}
	