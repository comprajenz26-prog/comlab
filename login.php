<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (!empty($_SESSION['user']['id'])) {
	header('Location: index.php');
	exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = strtolower(trim($_POST['email'] ?? ''));
	$password = $_POST['password'] ?? '';

	if (!validCsrfToken($_POST['csrf_token'] ?? null)) {
		http_response_code(419);
		$error = 'Your form expired. Refresh the page and try again.';
	} else {
		$stmt = $conn->prepare(
			'SELECT User_ID, Full_Name, Email, Password_Hash FROM users WHERE Email = :email'
		);
		$stmt->execute(['email' => $email]);
		$user = $stmt->fetch();

		if (!$user || !password_verify($password, $user['Password_Hash'])) {
			$error = 'Invalid email or password.';
		} else {
			session_regenerate_id(true);
			$_SESSION['user'] = [
				'id' => (int) $user['User_ID'],
				'name' => $user['Full_Name'],
				'email' => $user['Email'],
			];
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
			header('Location: index.php');
			exit;
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Sign in | Lab Inventory</title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-full flex items-center justify-center p-6 text-slate-100">
	<main class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-8 shadow-2xl">
		<h1 class="text-2xl font-bold text-white">Sign in</h1>
		<p class="mt-2 text-sm text-slate-400">Access the Computer Lab Inventory.</p>
		<?php if ($error !== ''): ?>
			<p role="alert" class="mt-5 rounded-lg border border-rose-500/30 bg-rose-500/10 p-3 text-sm text-rose-300"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
		<?php endif; ?>
		<form method="POST" class="mt-6 space-y-4">
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
			<label class="block text-sm text-slate-300">Email
				<input type="email" name="email" required autocomplete="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white">
			</label>
			<label class="block text-sm text-slate-300">Password
				<input type="password" name="password" required autocomplete="current-password" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white">
			</label>
			<button class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white hover:bg-indigo-500">Sign in</button>
		</form>
		<p class="mt-5 text-center text-sm text-slate-400">Need an account? <a href="register.php" class="text-indigo-400 hover:text-indigo-300">Register</a></p>
	</main>
</body>
</html>
