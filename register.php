<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (!empty($_SESSION['user']['id'])) {
	header('Location: index.php');
	exit;
}

$error = '';
$fullName = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$fullName = trim($_POST['full_name'] ?? '');
	$email = strtolower(trim($_POST['email'] ?? ''));
	$password = $_POST['password'] ?? '';
	$confirmPassword = $_POST['confirm_password'] ?? '';

	if (!validCsrfToken($_POST['csrf_token'] ?? null)) {
		http_response_code(419);
		$error = 'Your form expired. Refresh the page and try again.';
	} elseif ($fullName === '' || strlen($fullName) > 120) {
		$error = 'Enter a name no longer than 120 characters.';
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
		$error = 'Enter a valid email address.';
	} elseif (strlen($password) < 8) {
		$error = 'Password must be at least 8 characters long.';
	} elseif ($password !== $confirmPassword) {
		$error = 'The passwords do not match.';
	} else {
		$check = $conn->prepare('SELECT User_ID FROM users WHERE Email = :email');
		$check->execute(['email' => $email]);

		if ($check->fetch()) {
			$error = 'An account with that email already exists.';
		} else {
			try {
				$insert = $conn->prepare(
					'INSERT INTO users (Full_Name, Email, Password_Hash)
					 VALUES (:full_name, :email, :password_hash)'
				);
				$insert->execute([
					'full_name' => $fullName,
					'email' => $email,
					'password_hash' => password_hash($password, PASSWORD_DEFAULT),
				]);

				session_regenerate_id(true);
				$_SESSION['user'] = [
					'id' => (int) $conn->lastInsertId(),
					'name' => $fullName,
					'email' => $email,
				];
				$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
				header('Location: index.php');
				exit;
			} catch (PDOException $e) {
				if ($e->getCode() === '23000') {
					$error = 'An account with that email already exists.';
				} else {
					throw $e;
				}
			}
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Create account | Lab Inventory</title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-full flex items-center justify-center p-6 text-slate-100">
	<main class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-8 shadow-2xl">
		<h1 class="text-2xl font-bold text-white">Create an account</h1>
		<p class="mt-2 text-sm text-slate-400">Register to access the Computer Lab Inventory.</p>
		<?php if ($error !== ''): ?>
			<p role="alert" class="mt-5 rounded-lg border border-rose-500/30 bg-rose-500/10 p-3 text-sm text-rose-300"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
		<?php endif; ?>
		<form method="POST" class="mt-6 space-y-4">
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
			<label class="block text-sm text-slate-300">Full name
				<input name="full_name" maxlength="120" required autocomplete="name" value="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white">
			</label>
			<label class="block text-sm text-slate-300">Email
				<input type="email" name="email" maxlength="190" required autocomplete="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white">
			</label>
			<label class="block text-sm text-slate-300">Password (at least 8 characters)
				<input type="password" name="password" minlength="8" required autocomplete="new-password" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white">
			</label>
			<label class="block text-sm text-slate-300">Confirm password
				<input type="password" name="confirm_password" minlength="8" required autocomplete="new-password" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white">
			</label>
			<button class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white hover:bg-indigo-500">Register</button>
		</form>
		<p class="mt-5 text-center text-sm text-slate-400">Already registered? <a href="login.php" class="text-indigo-400 hover:text-indigo-300">Sign in</a></p>
	</main>
</body>
</html>
