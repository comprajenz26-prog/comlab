<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
	|| !validCsrfToken($_POST['csrf_token'] ?? null)) {
	http_response_code(400);
	exit('Invalid logout request.');
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', [
		'expires' => time() - 42000,
		'path' => $params['path'],
		'domain' => $params['domain'],
		'secure' => $params['secure'],
		'httponly' => $params['httponly'],
		'samesite' => 'Lax',
	]);
}

session_destroy();
header('Location: login.php');
exit;
