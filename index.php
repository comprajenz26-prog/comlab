<?php
require_once __DIR__ . "/includes/auth.php";
requireLogin();
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/equipment.php";

$equipment = new Equipment($conn);

$keyword = "";

if (isset($_GET["search"])) {
    $keyword = trim($_GET["search"]);
    $records = $equipment->searchEquipments($keyword);
} else {
    $records = $equipment->getEquipments();
}

$totalEquipment = count($records);
$working = 0;
$maintenance = 0;
$damaged = 0;

foreach ($records as $record) {
    if ($record["Status"] == "Working") {
        $working++;
    } elseif ($record["Status"] == "Under Maintenance") {
        $maintenance++;
    } elseif ($record["Status"] == "Damaged") {
        $damaged++;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Computer Laboratory Inventory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>

<body class="h-full text-slate-100 flex flex-col md:flex-row antialiased selection:bg-indigo-500 selection:text-white">

    <!-- SIDEBAR -->
    <aside class="w-full md:w-72 bg-slate-950/80 border-b md:border-b-0 md:border-r border-slate-800/80 p-6 flex flex-col justify-between shrink-0 backdrop-blur-xl">
        <div>
            <!-- LOGO -->
            <div class="flex items-center gap-3 mb-8 px-2">
                <div class="h-10 w-10 rounded-xl bg-indigo-600 flex items-center justify-center font-bold text-white shadow-lg shadow-indigo-500/30">
                    ⚡
                </div>
                <div>
                    <h2 class="font-bold text-lg leading-tight text-white">Lab Inventory</h2>
                    <p class="text-xs text-slate-400">Management System</p>
                </div>
            </div>

            <!-- MENU -->
            <nav class="space-y-6">
                <div>
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-3 px-3">Main Menu</p>
                    <div class="space-y-1">
                        <a href="index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl bg-indigo-600/10 text-indigo-400 font-semibold border border-indigo-500/20 shadow-sm transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            Dashboard
                        </a>
                        <a href="search.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/60 transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            Search
                        </a>
                    </div>
                </div>

                <div>
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-3 px-3">Management</p>
                    <div class="space-y-1">
                        <a href="add.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/60 transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Equipment
                        </a>
                        <a href="transactions.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/60 transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0-4-4m4 4-4 4M16 17H4m0 0 4 4m-4-4 4-4"/></svg>
                            Borrow &amp; Return
                        </a>
                    </div>
                </div>
            </nav>
        </div>

        <div class="pt-6 border-t border-slate-800/80 px-2">
            <p class="truncate text-sm font-medium text-slate-300"><?php echo htmlspecialchars($_SESSION["user"]["name"], ENT_QUOTES, "UTF-8"); ?></p>
            <form method="POST" action="logout.php" class="mt-2">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, "UTF-8"); ?>">
                <button type="submit" class="text-xs text-slate-500 hover:text-white transition">Sign out</button>
            </form>
            <p class="mt-4 text-xs text-slate-500">Computer Lab System v2.0</p>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-6 lg:p-10 overflow-y-auto">
        <div class="max-w-7xl mx-auto space-y-8">

            <!-- FLASH NOTIFICATION -->
            <?php if (isset($_GET["deleted"]) && $_GET["deleted"] == "1"): ?>
                <div class="flex items-center justify-between p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-2xl backdrop-blur-xl">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-sm font-medium">Equipment deleted successfully.</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-200">&times;</button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET["delete_blocked"]) && $_GET["delete_blocked"] == "1"): ?>
                <div role="alert" class="flex items-center justify-between p-4 bg-amber-500/10 border border-amber-500/20 text-amber-300 rounded-2xl backdrop-blur-xl">
                    <span class="text-sm font-medium">This equipment cannot be deleted because it has transaction history.</span>
                    <button onclick="this.parentElement.remove()" class="hover:text-amber-100">&times;</button>
                </div>
            <?php endif; ?>

            <!-- TOPBAR -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-white tracking-tight">Equipment Inventory</h1>
                    <p class="text-slate-400 text-sm mt-1">Manage and track computer laboratory equipment records seamlessly.</p>
                </div>
                <a href="add.php" class="inline-flex items-center justify-center gap-2 px-5 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm rounded-xl shadow-lg shadow-indigo-600/25 transition active:scale-95">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Equipment
                </a>
            </div>

            <!-- STAT CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="bg-slate-800/40 border border-slate-700/50 p-6 rounded-2xl backdrop-blur-xl">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Equipment</p>
                    <p class="text-3xl font-extrabold text-white mt-3"><?php echo $totalEquipment; ?></p>
                </div>
                <div class="bg-slate-800/40 border border-slate-700/50 p-6 rounded-2xl backdrop-blur-xl">
                    <p class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Working Status</p>
                    <p class="text-3xl font-extrabold text-emerald-400 mt-3"><?php echo $working; ?></p>
                </div>
                <div class="bg-slate-800/40 border border-slate-700/50 p-6 rounded-2xl backdrop-blur-xl">
                    <p class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Needs Attention</p>
                    <p class="text-3xl font-extrabold text-amber-400 mt-3"><?php echo $maintenance + $damaged; ?></p>
                </div>
            </div>

            <!-- TABLE CONTAINER -->
            <div class="bg-slate-800/40 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-xl shadow-xl">
                <!-- TABLE HEADER & SEARCH -->
                <div class="p-5 border-b border-slate-700/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <h2 class="text-lg font-bold text-white">Equipment Records</h2>
                    <form method="GET" action="index.php" class="flex items-center gap-2 w-full sm:w-auto">
                        <div class="relative w-full sm:w-72">
                            <input type="text" name="search" placeholder="Search equipment..." value="<?php echo htmlspecialchars($keyword); ?>" class="w-full bg-slate-900/80 border border-slate-700 rounded-xl px-4 py-2.5 pl-10 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                            <svg class="w-4 h-4 text-slate-500 absolute left-3.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <button type="submit" class="px-4 py-2.5 bg-slate-700 hover:bg-slate-600 text-white font-medium text-sm rounded-xl transition shrink-0">Search</button>
                        <?php if ($keyword != ""): ?>
                            <a href="index.php" class="px-3 py-2.5 text-sm text-slate-400 hover:text-white transition">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- TABLE -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-900/60 border-b border-slate-700/50 text-slate-400 text-xs font-semibold uppercase tracking-wider">
                                <th class="py-4 px-6">ID</th>
                                <th class="py-4 px-6">Equipment Name</th>
                                <th class="py-4 px-6">Status</th>
                                <th class="py-4 px-6">Purchase Date</th>
                                <th class="py-4 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/30">
                            <?php if (count($records) > 0): ?>
                                <?php foreach ($records as $record): ?>
                                    <tr class="hover:bg-slate-700/20 transition">
                                        <td class="py-4 px-6 font-mono text-slate-400">#<?php echo htmlspecialchars($record["Equipment_ID"]); ?></td>
                                        <td class="py-4 px-6 font-semibold text-white"><?php echo htmlspecialchars($record["Equipment_Name"]); ?></td>
                                        <td class="py-4 px-6">
                                            <?php
                                            $badgeStyle = "bg-slate-500/10 text-slate-400 border-slate-500/20";
                                            if ($record["Status"] == "Working") {
                                                $badgeStyle = "bg-emerald-500/10 text-emerald-400 border-emerald-500/20";
                                            } elseif ($record["Status"] == "Under Maintenance") {
                                                $badgeStyle = "bg-amber-500/10 text-amber-400 border-amber-500/20";
                                            } elseif ($record["Status"] == "Damaged") {
                                                $badgeStyle = "bg-rose-500/10 text-rose-400 border-rose-500/20";
                                            }
                                            ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border <?php echo $badgeStyle; ?>">
                                                <?php echo htmlspecialchars($record["Status"]); ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-slate-400"><?php echo htmlspecialchars($record["Purchase_Date"]); ?></td>
                                        <td class="py-4 px-6 text-right space-x-2">
                                            <!-- UPDATE BUTTON -->
                                            <a href="edit.php?id=<?php echo urlencode($record["Equipment_ID"]); ?>" 
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 hover:bg-indigo-500/20 hover:text-indigo-300 font-medium text-xs transition">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                Update
                                            </a>

                                            <!-- DELETE BUTTON -->
                                            <button type="button" 
                                                    onclick="openDeleteModal('<?php echo htmlspecialchars($record['Equipment_ID']); ?>')" 
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-400 hover:bg-rose-500/20 hover:text-rose-300 font-medium text-xs transition">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-500">
                                        No equipment records found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <!-- SECURE DELETE CONFIRMATION MODAL -->
    <div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity" onclick="closeDeleteModal()"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center">
            <div class="relative transform overflow-hidden rounded-2xl bg-slate-900 border border-slate-800 text-left shadow-2xl transition-all w-full max-w-md p-6">
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 rounded-full bg-rose-500/10 border border-rose-500/20 flex items-center justify-center shrink-0">
                        <svg class="h-6 w-6 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white" id="modal-title">Confirm Deletion</h3>
                        <p class="text-sm text-slate-400 mt-1">Are you sure you want to delete this equipment record? This action cannot be undone.</p>
                    </div>
                </div>
                
                <form id="deleteForm" method="POST" action="delete.php" class="mt-6 flex items-center justify-end gap-3">
                    <input type="hidden" name="id" id="deleteEquipmentId" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, "UTF-8"); ?>">
                    <button type="button" onclick="closeDeleteModal()" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium text-sm transition">Cancel</button>
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-medium text-sm transition shadow-lg shadow-rose-600/25">Yes, Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openDeleteModal(id) {
            document.getElementById('deleteEquipmentId').value = id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });
    </script>

</body>
</html>