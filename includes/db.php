<?php

$config = [
	'host' => 'localhost',
	'dbname' => 'computer_lab_db',
	'username' => 'root',
	'password' => '',
	'create_database' => true,
];

$localConfigFile = __DIR__ . '/config.local.php';
if (is_file($localConfigFile)) {
	$localConfig = require $localConfigFile;
	if (!is_array($localConfig)) {
		http_response_code(500);
		die('Database configuration must return an array.');
	}
	$config = array_replace($config, $localConfig);
}

try {
	$options = [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	];

	if ($config['create_database']) {
		$serverConnection = new PDO(
			'mysql:host=' . $config['host'] . ';charset=utf8mb4',
			$config['username'],
			$config['password'],
			$options
		);
		$databaseName = str_replace('`', '``', $config['dbname']);
		$serverConnection->exec(
			"CREATE DATABASE IF NOT EXISTS `$databaseName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
		);
	}

	$conn = new PDO(
		'mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'] . ';charset=utf8mb4',
		$config['username'],
		$config['password'],
		$options
	);

	$conn->exec("CREATE TABLE IF NOT EXISTS equipment (
		Equipment_ID INT AUTO_INCREMENT PRIMARY KEY,
		Equipment_Name VARCHAR(150) NOT NULL,
		Status ENUM('Working', 'Under Maintenance', 'Damaged') NOT NULL,
		Purchase_Date DATE NOT NULL,
		Created_At TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
	) ENGINE=InnoDB");

	$conn->exec("CREATE TABLE IF NOT EXISTS users (
		User_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		Full_Name VARCHAR(120) NOT NULL,
		Email VARCHAR(190) NOT NULL UNIQUE,
		Password_Hash VARCHAR(255) NOT NULL,
		Created_At TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
	) ENGINE=InnoDB");

	$conn->exec("CREATE TABLE IF NOT EXISTS equipment_transactions (
		Transaction_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		Equipment_ID INT NOT NULL,
		User_ID INT UNSIGNED NOT NULL,
		Borrowed_At TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		Due_Date DATE NOT NULL,
		Returned_At TIMESTAMP NULL DEFAULT NULL,
		CONSTRAINT fk_transactions_equipment FOREIGN KEY (Equipment_ID)
			REFERENCES equipment (Equipment_ID) ON DELETE RESTRICT,
		CONSTRAINT fk_transactions_user FOREIGN KEY (User_ID)
			REFERENCES users (User_ID) ON DELETE RESTRICT,
		INDEX idx_transactions_open_equipment (Equipment_ID, Returned_At)
	) ENGINE=InnoDB");
} catch (PDOException $e) {
	error_log('Database initialization failed: ' . $e->getMessage());
	http_response_code(500);
	die('Database connection failed. Check the PHP error log for details.');
}
?>