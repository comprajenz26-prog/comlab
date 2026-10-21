<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/db.php';

$message = '';
$messageType = 'error';
$userId = (int) $_SESSION['user']['id'];
$today = new DateTimeImmutable('today', new DateTimeZone('Asia/Manila'));
$minimumDueDate = $today->format('Y-m-d');
$defaultDueDate = $today->modify('+7 days')->format('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!validCsrfToken($_POST['csrf_token'] ?? null)) {
		http_response_code(419);
		$message = 'Your form expired. Refresh the page and try again.';
	} else {
		$action = $_POST['action'] ?? '';

		if ($action === 'borrow') {
			$equipmentId = filter_var($_POST['equipment_id'] ?? null, FILTER_VALIDATE_INT);
			$dueDate = $_POST['due_date'] ?? '';
			$parsedDueDate = DateTime::createFromFormat('Y-m-d', $dueDate);
			$validDueDate = $parsedDueDate && $parsedDueDate->format('Y-m-d') === $dueDate
				&& $dueDate >= $minimumDueDate;

			if (!$equipmentId || !$validDueDate) {
				$message = 'Choose an available item and a valid due date.';
			} else {
				try {
					$conn->beginTransaction();
					$equipmentQuery = $conn->prepare(
						'SELECT Equipment_ID, Status FROM equipment WHERE Equipment_ID = :id FOR UPDATE'
					);
					$equipmentQuery->execute(['id' => $equipmentId]);
					$item = $equipmentQuery->fetch();

					$openQuery = $conn->prepare(
						'SELECT Transaction_ID FROM equipment_transactions
						 WHERE Equipment_ID = :id AND Returned_At IS NULL LIMIT 1'
					);
					$openQuery->execute(['id' => $equipmentId]);

					if (!$item || $item['Status'] !== 'Working' || $openQuery->fetch()) {
						$conn->rollBack();
						$message = 'That equipment is not available to borrow.';
					} else {
						$insert = $conn->prepare(
							'INSERT INTO equipment_transactions (Equipment_ID, User_ID, Due_Date)
							 VALUES (:equipment_id, :user_id, :due_date)'
						);
						$insert->execute([
							'equipment_id' => $equipmentId,
							'user_id' => $userId,
							'due_date' => $dueDate,
						]);
						$conn->commit();
						$message = 'Equipment borrowed successfully.';
						$messageType = 'success';
					}
				} catch (PDOException $e) {
					if ($conn->inTransaction()) {
						$conn->rollBack();
					}
					error_log('Equipment borrow failed: ' . $e->getMessage());
					$message = 'The borrow could not be recorded. Please try again.';
				}
			}
		} elseif ($action === 'return') {
			$transactionId = filter_var($_POST['transaction_id'] ?? null, FILTER_VALIDATE_INT);

			if (!$transactionId) {
				$message = 'Invalid transaction.';
			} else {
				$return = $conn->prepare(
					'UPDATE equipment_transactions
					 SET Returned_At = CURRENT_TIMESTAMP
					 WHERE Transaction_ID = :transaction_id
					   AND User_ID = :user_id
					   AND Returned_At IS NULL'
				);
				$return->execute([
					'transaction_id' => $transactionId,
					'user_id' => $userId,
				]);
				if ($return->rowCount() === 1) {
					$message = 'Equipment returned successfully.';
					$messageType = 'success';
				} else {
					$message = 'The transaction was already returned or does not belong to your account.';
				}
			}
		} else {
			http_response_code(400);
			$message = 'Invalid transaction action.';
		}
	}
}

$availableQuery = $conn->prepare(
	"SELECT e.Equipment_ID, e.Equipment_Name
	 FROM equipment e
	 WHERE e.Status = 'Working'
	   AND NOT EXISTS (
	       SELECT 1 FROM equipment_transactions t
	       WHERE t.Equipment_ID = e.Equipment_ID AND t.Returned_At IS NULL
	   )
	 ORDER BY e.Equipment_Name"
);
$availableQuery->execute();
$availableEquipment = $availableQuery->fetchAll();

$historyQuery = $conn->prepare(
	'SELECT t.Transaction_ID, t.User_ID, t.Borrowed_At, t.Due_Date, t.Returned_At,
	        e.Equipment_Name, u.Full_Name
	 FROM equipment_transactions t
	 JOIN equipment e ON e.Equipment_ID = t.Equipment_ID
	 JOIN users u ON u.User_ID = t.User_ID
	 ORDER BY t.Borrowed_At DESC'
);
$historyQuery->execute();
$transactions = $historyQuery->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Transactions | Lab Inventory</title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-full text-slate-100">
	<main class="mx-auto max-w-6xl space-y-8 p-6 lg:p-10">
		<header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
			<div>
				<a href="index.php" class="text-sm text-indigo-400 hover:text-indigo-300">&larr; Dashboard</a>
				<h1 class="mt-2 text-3xl font-bold text-white">Borrow &amp; Return</h1>
				<p class="mt-1 text-sm text-slate-400">Borrow working equipment and track return history.</p>
			</div>
			<div class="text-sm text-slate-400">Signed in as <?php echo htmlspecialchars($_SESSION['user']['name'], ENT_QUOTES, 'UTF-8'); ?></div>
		</header>

		<?php if ($message !== ''): ?>
			<div role="status" class="rounded-xl border p-4 <?php echo $messageType === 'success' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-rose-500/30 bg-rose-500/10 text-rose-300'; ?>">
				<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
			</div>
		<?php endif; ?>

		<section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-6">
			<h2 class="text-lg font-bold text-white">Borrow equipment</h2>
			<?php if ($availableEquipment): ?>
				<form method="POST" class="mt-4 grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
					<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
					<input type="hidden" name="action" value="borrow">
					<label class="text-sm text-slate-300">Available equipment
						<select name="equipment_id" required class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white">
							<option value="">Select equipment</option>
							<?php foreach ($availableEquipment as $item): ?>
								<option value="<?php echo (int) $item['Equipment_ID']; ?>"><?php echo htmlspecialchars($item['Equipment_Name'], ENT_QUOTES, 'UTF-8'); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<div>
						<label for="due-date" class="block text-sm text-slate-300">Due date</label>
						<div class="mt-1 flex gap-2">
							<input id="due-date" type="date" name="due_date" min="<?php echo htmlspecialchars($minimumDueDate, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($defaultDueDate, ENT_QUOTES, 'UTF-8'); ?>" required style="color-scheme: dark" class="min-w-0 flex-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white">
							<button type="button" onclick="openDueDatePicker()" class="shrink-0 rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-300 hover:bg-slate-800" aria-label="Open due date calendar">Choose date</button>
						</div>
					</div>
					<button class="rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white hover:bg-indigo-500">Borrow</button>
				</form>
			<?php else: ?>
				<p class="mt-3 text-sm text-slate-400">No working equipment is currently available to borrow.</p>
			<?php endif; ?>
		</section>

		<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/70">
			<div class="border-b border-slate-800 p-5">
				<h2 class="font-bold text-white">Transaction history</h2>
			</div>
			<div class="overflow-x-auto">
				<table class="w-full text-left text-sm">
					<thead class="bg-slate-950/70 text-xs uppercase tracking-wide text-slate-400">
						<tr>
							<th class="px-5 py-3">Equipment</th>
							<th class="px-5 py-3">Borrower</th>
							<th class="px-5 py-3">Borrowed</th>
							<th class="px-5 py-3">Due</th>
							<th class="px-5 py-3">Returned</th>
							<th class="px-5 py-3 text-right">Action</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-800">
						<?php if ($transactions): ?>
							<?php foreach ($transactions as $transaction): ?>
								<tr>
									<td class="px-5 py-4 font-medium text-white"><?php echo htmlspecialchars($transaction['Equipment_Name'], ENT_QUOTES, 'UTF-8'); ?></td>
									<td class="px-5 py-4 text-slate-300"><?php echo htmlspecialchars($transaction['Full_Name'], ENT_QUOTES, 'UTF-8'); ?></td>
									<td class="px-5 py-4 text-slate-400"><?php echo htmlspecialchars($transaction['Borrowed_At'], ENT_QUOTES, 'UTF-8'); ?></td>
									<td class="px-5 py-4 text-slate-400"><?php echo htmlspecialchars($transaction['Due_Date'], ENT_QUOTES, 'UTF-8'); ?></td>
									<td class="px-5 py-4 text-slate-400"><?php echo $transaction['Returned_At'] ? htmlspecialchars($transaction['Returned_At'], ENT_QUOTES, 'UTF-8') : 'Not returned'; ?></td>
									<td class="px-5 py-4 text-right">
										<?php if (!$transaction['Returned_At'] && (int) $transaction['User_ID'] === $userId): ?>
											<form method="POST" class="inline">
												<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
												<input type="hidden" name="action" value="return">
												<input type="hidden" name="transaction_id" value="<?php echo (int) $transaction['Transaction_ID']; ?>">
												<button class="rounded-lg border border-emerald-500/30 px-3 py-1.5 text-emerald-300 hover:bg-emerald-500/10">Return</button>
											</form>
										<?php elseif (!$transaction['Returned_At']): ?>
											<span class="text-slate-500">Borrowed</span>
										<?php else: ?>
											<span class="text-slate-500">Complete</span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else: ?>
							<tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">No transactions recorded yet.</td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</section>
	</main>
	<script>
		function openDueDatePicker() {
			const dueDate = document.getElementById('due-date');
			if (typeof dueDate.showPicker === 'function') {
				dueDate.showPicker();
			} else {
				dueDate.focus();
			}
		}
	</script>
</body>
</html>
