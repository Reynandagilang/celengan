<?php
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/Goal.php';

session_start();
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

if (!$user->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$goalObj = new Goal($db);
$user_id = $_SESSION['user_id'];
$goals = $goalObj->getGoalsByUser($user_id);
$totals = $goalObj->getTotalSavingsByUser($user_id);

function formatRupiah($angka){
    return "Rp " . number_format($angka,0,',','.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Celenganku</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .modal-enter { opacity: 0; transform: scale(0.9); }
        .modal-enter-active { opacity: 1; transform: scale(1); transition: opacity 300ms, transform 300ms; }
        .modal-leave { opacity: 1; transform: scale(1); }
        .modal-leave-active { opacity: 0; transform: scale(0.9); transition: opacity 300ms, transform 300ms; }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .toast-enter { animation: slideInRight 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">
    <div class="max-w-md mx-auto min-h-screen bg-slate-50 shadow-2xl relative pb-24">
        
        <!-- Header -->
        <div class="bg-emerald-600 rounded-b-3xl p-6 text-white shadow-md">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <p class="text-emerald-100 text-sm">Halo, selamat datang 👋</p>
                    <h2 class="text-xl font-bold"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h2>
                </div>
                <a href="logout.php" class="bg-emerald-700 p-2 rounded-lg text-sm hover:bg-emerald-800 transition shadow-sm">Keluar</a>
            </div>
            <div class="mb-2">
                <p class="text-emerald-100 text-sm mb-1 font-medium">Total Uang Terkumpul</p>
                <h1 class="text-3xl font-extrabold tracking-tight"><?php echo formatRupiah($totals['total_saved']); ?></h1>
                <?php if($totals['total_target'] > 0): ?>
                    <p class="text-emerald-200 text-xs mt-1">Sisa kekurangan: <?php echo formatRupiah(max(0, $totals['total_target'] - $totals['total_saved'])); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="bg-emerald-100 text-emerald-700 p-3 rounded-xl mb-4 text-sm font-medium border border-emerald-200">
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 text-red-700 p-3 rounded-xl mb-4 text-sm font-medium border border-red-200">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="flex justify-between items-center mb-5">
                <h3 class="text-lg font-bold text-slate-800">Target Celengan</h3>
                <button onclick="openModal('addGoalModal')" class="bg-emerald-100 text-emerald-700 px-4 py-2 rounded-full text-sm font-bold hover:bg-emerald-200 transition">+ Tambah</button>
            </div>

            <div class="space-y-4">
                <?php if (count($goals) > 0): ?>
                    <?php foreach ($goals as $goal): ?>
                        <?php 
                            $percent = ($goal['target_amount'] > 0) ? ($goal['current_amount'] / $goal['target_amount']) * 100 : 0;
                            if($percent > 100) $percent = 100;
                            $isCompleted = $goal['status'] === 'completed';
                            $remaining = max(0, $goal['target_amount'] - $goal['current_amount']);
                            $days_left = (strtotime($goal['target_date']) - time()) / (60 * 60 * 24);
                        ?>
                        <div class="block bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative group cursor-pointer">
                            <a href="goal-detail.php?id=<?php echo $goal['id']; ?>" class="block">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex items-center gap-3">
                                        <?php if(!empty($goal['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($goal['image']); ?>" class="w-12 h-12 object-cover rounded-xl shadow-sm border border-slate-100">
                                        <?php else: ?>
                                            <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-xl">
                                                <?php echo htmlspecialchars($goal['icon']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h4 class="font-bold text-slate-800 text-md"><?php echo htmlspecialchars($goal['title']); ?></h4>
                                            <?php if($isCompleted): ?>
                                                <span class="text-emerald-600 text-xs font-bold">Tercapai 🎉</span>
                                            <?php else: ?>
                                                <span class="text-slate-500 text-xs font-medium">Sisa <?php echo formatRupiah($remaining); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-between text-sm mb-2 mt-4">
                                    <span class="font-bold text-emerald-600"><?php echo formatRupiah($goal['current_amount']); ?></span>
                                    <span class="text-slate-500 font-medium"><?php echo formatRupiah($goal['target_amount']); ?></span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2.5 mb-2 overflow-hidden">
                                    <div class="bg-emerald-500 h-2.5 rounded-full transition-all duration-500" style="width: <?php echo $percent; ?>%"></div>
                                </div>
                                <div class="flex justify-between items-center mt-3">
                                    <p class="text-xs font-semibold <?php echo $isCompleted ? 'text-emerald-600' : 'text-slate-400'; ?>"><?php echo round($percent, 1); ?>% Terkumpul</p>
                                    <?php if(!$isCompleted): ?>
                                        <p class="text-[11px] font-medium <?php echo $days_left < 0 ? 'text-red-500' : 'text-slate-400'; ?>">
                                            <?php echo $days_left < 0 ? 'Terlambat ' . abs(round($days_left)) . ' hari' : 'Deadline: ' . date('d M Y', strtotime($goal['target_date'])); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </a>
                            
                            <?php if(!$isCompleted): ?>
                            <!-- Aksi Cepat Setor -->
                            <div class="mt-4 pt-3 border-t border-slate-100 flex justify-end">
                                <button onclick="openDepositModal(<?php echo $goal['id']; ?>, '<?php echo addslashes(htmlspecialchars($goal['title'])); ?>')" class="text-emerald-600 font-bold text-sm bg-emerald-50 px-4 py-1.5 rounded-full hover:bg-emerald-100 transition">
                                    + Setor Cepat
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-12 bg-white rounded-2xl border border-slate-100 shadow-sm">
                        <div class="text-5xl mb-4">🐷</div>
                        <h4 class="font-bold text-slate-700 mb-1">Belum Ada Target</h4>
                        <p class="text-slate-500 text-sm">Yuk mulai menabung dan wujudkan mimpimu!</p>
                        <button onclick="openModal('addGoalModal')" class="mt-4 bg-emerald-600 text-white px-5 py-2 rounded-full text-sm font-bold shadow-md hover:bg-emerald-700">Buat Target Pertama</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add Goal Modal -->
        <div id="addGoalModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal('addGoalModal')"></div>
            <div class="bg-white rounded-2xl w-full max-w-sm mx-4 relative z-10 p-6 modal-enter shadow-2xl" id="addGoalModalContent">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800">Target Celengan Baru</h3>
                    <button onclick="closeModal('addGoalModal')" class="text-slate-400 hover:text-slate-600 bg-slate-100 rounded-full p-1">&times;</button>
                </div>
                <form action="process.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="add_goal">
                    <div class="flex gap-3">
                        <div class="w-1/4">
                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Ikon</label>
                            <input type="text" name="icon" value="🎯" required class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none text-center text-xl">
                        </div>
                        <div class="w-3/4">
                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Nama Impian</label>
                            <input type="text" name="title" required placeholder="Cth: Beli iPhone 15" class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none font-medium">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Foto Impian (Opsional)</label>
                        <input type="file" name="image" accept="image/*" class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none text-sm text-slate-600 bg-white file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Target Nominal (Rp)</label>
                        <input type="text" name="target_amount" id="targetAmountInput" required placeholder="10.000.000" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none font-bold text-lg text-emerald-700 bg-slate-50">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Kapan ingin tercapai?</label>
                        <input type="date" name="target_date" required class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none text-slate-700">
                    </div>
                    <button type="submit" class="w-full bg-emerald-600 text-white py-3.5 rounded-xl font-bold hover:bg-emerald-700 transition mt-4 shadow-lg text-sm">Simpan Target Impian</button>
                </form>
            </div>
        </div>

        <!-- Quick Deposit Modal -->
        <div id="quickDepositModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal('quickDepositModal')"></div>
            <div class="bg-white rounded-2xl w-full max-w-sm mx-4 relative z-10 p-6 modal-enter shadow-2xl" id="quickDepositModalContent">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800">Setor Cepat</h3>
                    <button onclick="closeModal('quickDepositModal')" class="text-slate-400 hover:text-slate-600 bg-slate-100 rounded-full p-1">&times;</button>
                </div>
                <p class="text-sm text-slate-500 mb-4">Setor untuk: <strong id="quickDepositTitle" class="text-slate-700"></strong></p>
                <form action="process.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="deposit">
                    <input type="hidden" name="redirect_to" value="index.php">
                    <input type="hidden" name="goal_id" id="quickDepositGoalId" value="">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Nominal (Rp)</label>
                        <input type="text" name="amount" id="quickDepositAmountInput" required placeholder="50.000" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none font-bold text-lg text-emerald-700 bg-slate-50">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Catatan (Opsional)</label>
                        <input type="text" name="note" placeholder="Uang jajan sisa" class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none text-sm text-slate-700">
                    </div>
                    <button type="submit" class="w-full bg-emerald-600 text-white py-3.5 rounded-xl font-bold hover:bg-emerald-700 transition mt-4 shadow-lg text-sm">Masuk Celengan</button>
                </form>
            </div>
        </div>

    </div>

    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script>
        const socket = io('http://localhost:3000');
        const currentUserId = '<?php echo $_SESSION['user_id']; ?>';
        
        socket.on(`notification_${currentUserId}`, (data) => {
            // 1. Tembakkan Animasi Confetti (Pesta) 🎊
            confetti({
                particleCount: 150,
                spread: 80,
                origin: { y: 0.6 },
                colors: ['#059669', '#10b981', '#fbbf24', '#ffffff'],
                zIndex: 9999
            });

            // 2. Munculkan Animasi Toast Slide-In
            const toast = document.createElement('div');
            toast.className = 'fixed top-6 right-4 bg-emerald-700 text-white p-4 rounded-2xl shadow-2xl z-50 toast-enter flex items-center gap-4 border-2 border-emerald-400';
            toast.innerHTML = '<span class="text-3xl animate-pulse">🎉</span> <div><p class="text-xs text-emerald-200 font-medium tracking-wide uppercase">Notifikasi Uang Masuk</p><p class="font-bold text-md">' + data.message + '</p></div>';
            document.body.appendChild(toast);
            
            // Animasi menghilang (Fade out)
            setTimeout(() => {
                toast.style.transition = 'opacity 0.6s ease';
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 600);
            }, 5000);
        });

        function openModal(id) {
            const modal = document.getElementById(id);
            const content = document.getElementById(id + 'Content');
            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.add('modal-enter-active');
            }, 10);
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            const content = document.getElementById(id + 'Content');
            content.classList.remove('modal-enter-active');
            content.classList.add('modal-leave-active');
            setTimeout(() => {
                modal.classList.add('hidden');
                content.classList.remove('modal-leave-active');
            }, 300);
        }

        function openDepositModal(id, title) {
            document.getElementById('quickDepositGoalId').value = id;
            document.getElementById('quickDepositTitle').innerText = title;
            openModal('quickDepositModal');
        }

        const inputs = ['targetAmountInput', 'quickDepositAmountInput'];
        inputs.forEach(id => {
            const el = document.getElementById(id);
            if(el) {
                el.addEventListener('keyup', function(e) {
                    this.value = formatRupiah(this.value);
                });
            }
        });

        function formatRupiah(angka) {
            let number_string = angka.replace(/[^,\d]/g, '').toString(),
                split = number_string.split(','),
                sisa  = split[0].length % 3,
                rupiah  = split[0].substr(0, sisa),
                ribuan  = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }
            return split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        }
    </script>
</body>
</html>
