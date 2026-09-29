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

$goal_id = $_GET['id'] ?? null;
if (!$goal_id) {
    header("Location: index.php");
    exit;
}

$goalObj = new Goal($db);
$user_id = $_SESSION['user_id'];
$goal = $goalObj->getGoalById($goal_id, $user_id);

if (!$goal) {
    header("Location: index.php");
    exit;
}

$transactions = $goalObj->getTransactionsByGoal($goal_id);

function formatRupiah($angka){
    return "Rp " . number_format($angka,0,',','.');
}

$percent = ($goal['target_amount'] > 0) ? ($goal['current_amount'] / $goal['target_amount']) * 100 : 0;
if($percent > 100) $percent = 100;
$isCompleted = $goal['status'] === 'completed';
$remaining = max(0, $goal['target_amount'] - $goal['current_amount']);
$days_left = (strtotime($goal['target_date']) - time()) / (60 * 60 * 24);

// Mini stats calculation
$avg_deposit = 0;
if(count($transactions) > 0) {
    $total_trx = array_sum(array_column($transactions, 'amount'));
    $avg_deposit = $total_trx / count($transactions);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Celengan - Celenganku</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .modal-enter { opacity: 0; transform: scale(0.9); }
        .modal-enter-active { opacity: 1; transform: scale(1); transition: opacity 300ms, transform 300ms; }
        .modal-leave { opacity: 1; transform: scale(1); }
        .modal-leave-active { opacity: 0; transform: scale(0.9); transition: opacity 300ms, transform 300ms; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">
    <div class="max-w-md mx-auto min-h-screen bg-slate-50 shadow-2xl relative pb-24">
        
        <!-- Header -->
        <div class="bg-white p-4 flex items-center shadow-sm sticky top-0 z-20">
            <a href="index.php" class="mr-3 text-slate-500 hover:bg-slate-100 p-2 rounded-full transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div class="flex-1 flex items-center gap-2 truncate">
                <?php if(!empty($goal['image'])): ?>
                    <img src="<?php echo htmlspecialchars($goal['image']); ?>" class="w-8 h-8 object-cover rounded-full border border-slate-200">
                <?php else: ?>
                    <span class="text-xl"><?php echo htmlspecialchars($goal['icon']); ?></span>
                <?php endif; ?>
                <h1 class="text-lg font-bold text-slate-800 truncate"><?php echo htmlspecialchars($goal['title']); ?></h1>
            </div>
            <?php if($isCompleted): ?>
                <span class="bg-emerald-100 text-emerald-700 text-xs px-2 py-1.5 rounded-lg font-bold shadow-sm">Selesai</span>
            <?php endif; ?>
        </div>

        <div class="p-5">
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

            <!-- Card Progress -->
            <div class="bg-emerald-600 rounded-3xl p-6 text-white shadow-xl mb-6 relative overflow-hidden">
                <!-- Abstract pattern bg -->
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                
                <p class="text-emerald-100 text-sm font-medium mb-1">Terkumpul</p>
                <h2 class="text-3xl font-extrabold mb-5"><?php echo formatRupiah($goal['current_amount']); ?></h2>
                
                <div class="w-full bg-emerald-800/60 rounded-full h-3 mb-2 overflow-hidden shadow-inner">
                    <div class="bg-white h-3 rounded-full transition-all duration-1000" style="width: <?php echo $percent; ?>%"></div>
                </div>
                <div class="flex justify-between text-xs font-medium text-emerald-100 mb-4">
                    <span><?php echo round($percent, 1); ?>%</span>
                    <span>Target: <?php echo formatRupiah($goal['target_amount']); ?></span>
                </div>
                
                <div class="pt-4 border-t border-emerald-500/50 flex justify-between items-center text-xs">
                    <div>
                        <p class="text-emerald-200">Kekurangan</p>
                        <p class="font-bold"><?php echo formatRupiah($remaining); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-emerald-200">Target Tercapai</p>
                        <p class="font-bold"><?php echo date('d M Y', strtotime($goal['target_date'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Mini Stats Row -->
            <div class="grid grid-cols-2 gap-3 mb-6">
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100">
                    <div class="text-slate-400 text-xs font-bold uppercase mb-1">Total Setoran</div>
                    <div class="text-lg font-bold text-slate-800"><?php echo count($transactions); ?> <span class="text-sm font-medium text-slate-500">kali</span></div>
                </div>
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100">
                    <div class="text-slate-400 text-xs font-bold uppercase mb-1">Rata-rata Setor</div>
                    <div class="text-md font-bold text-slate-800"><?php echo formatRupiah($avg_deposit); ?></div>
                </div>
            </div>

            <!-- Setor Button -->
            <?php if(!$isCompleted): ?>
            <button onclick="openModal('depositModal')" class="w-full bg-white border-2 border-emerald-500 text-emerald-600 py-3.5 rounded-2xl font-bold hover:bg-emerald-50 transition shadow-sm mb-8 flex justify-center items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Tabungan
            </button>
            <?php endif; ?>

            <!-- AI Prediction Box -->
            <?php if(!$isCompleted && count($transactions) > 1): ?>
            <div id="aiPredictionBox" class="bg-indigo-600 p-4 rounded-2xl shadow-sm text-white mb-6 hidden">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xl">🤖</span>
                    <h4 class="font-bold text-sm">AI Savings Predictor</h4>
                </div>
                <p class="text-xs text-indigo-100" id="aiPredictionText">Menganalisis kebiasaan menabung Anda...</p>
            </div>
            <?php endif; ?>

            <!-- Chart Box -->
            <?php if(count($transactions) > 0): ?>
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 mb-6">
                <h4 class="text-sm font-bold text-slate-800 mb-3">Grafik Tabungan</h4>
                <canvas id="savingsChart" height="200"></canvas>
            </div>
            <?php endif; ?>

            <!-- Riwayat -->
            <div>
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Riwayat Transaksi
                </h3>
                <div class="space-y-3">
                    <?php if (count($transactions) > 0): ?>
                        <?php foreach ($transactions as $trx): ?>
                            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center">
                                <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mr-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold text-slate-800"><?php echo formatRupiah($trx['amount']); ?></p>
                                    <p class="text-xs text-slate-500 font-medium">
                                        <?php echo !empty($trx['note']) ? htmlspecialchars($trx['note']) : 'Tanpa catatan'; ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs text-slate-400 font-medium block"><?php echo date('d M', strtotime($trx['created_at'])); ?></span>
                                    <span class="text-[10px] text-slate-400"><?php echo date('H:i', strtotime($trx['created_at'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-10 bg-white rounded-2xl border border-slate-100 shadow-sm">
                            <p class="text-slate-500 text-sm">Belum ada riwayat setoran.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Deposit Modal -->
        <?php if(!$isCompleted): ?>
        <div id="depositModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
            <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal('depositModal')"></div>
            <div class="bg-white rounded-2xl w-full max-w-sm mx-4 relative z-10 p-6 modal-enter shadow-2xl" id="depositModalContent">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800">Setor Tabungan</h3>
                    <button onclick="closeModal('depositModal')" class="text-slate-400 hover:text-slate-600 bg-slate-100 rounded-full p-1">&times;</button>
                </div>
                <form action="process.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="deposit">
                    <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Nominal Setor (Rp)</label>
                        <input type="text" name="amount" id="depositAmountInput" required placeholder="50.000" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none font-bold text-lg text-emerald-700 bg-slate-50">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1 uppercase">Catatan (Opsional)</label>
                        <input type="text" name="note" placeholder="Cth: Sisa uang jajan" class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none text-slate-700">
                    </div>
                    <button type="submit" class="w-full bg-emerald-600 text-white py-3.5 rounded-xl font-bold hover:bg-emerald-700 transition mt-4 shadow-lg text-sm">Simpan Setoran</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Transaction Data for Chart
        const transactions = <?php echo json_encode($transactions); ?>;
        
        if (transactions.length > 0) {
            // Reverse to chronological order
            const chronData = [...transactions].reverse();
            const labels = chronData.map(t => t.created_at.split(' ')[0]);
            
            // Cumulative Sum
            let sum = 0;
            const dataPoints = chronData.map(t => {
                sum += parseFloat(t.amount);
                return sum;
            });

            const ctx = document.getElementById('savingsChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Total Terkumpul',
                            data: dataPoints,
                            borderColor: '#059669',
                            backgroundColor: 'rgba(5, 150, 105, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }
        }

        // Fetch AI Prediction
        const isCompleted = <?php echo $isCompleted ? 'true' : 'false'; ?>;
        if (!isCompleted && transactions.length > 1) {
            fetch('http://127.0.0.1:5000/predict', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    target_amount: <?php echo $goal['target_amount']; ?>,
                    current_amount: <?php echo $goal['current_amount']; ?>,
                    transactions: transactions
                })
            })
            .then(res => res.json())
            .then(data => {
                const box = document.getElementById('aiPredictionBox');
                const text = document.getElementById('aiPredictionText');
                if (data.predicted_date) {
                    box.classList.remove('hidden');
                    text.innerHTML = `Berdasarkan ritme Anda (rata-rata <b>Rp ${parseFloat(data.avg_per_day).toLocaleString('id-ID')}</b>/hari), target akan tercapai pada <b>${data.predicted_date}</b> (${data.days_needed} hari lagi).`;
                }
            })
            .catch(err => console.log('Predictor offline:', err));
        }

        function openModal(id) {
            const modal = document.getElementById(id);
            if(modal) {
                const content = document.getElementById(id + 'Content');
                modal.classList.remove('hidden');
                setTimeout(() => {
                    content.classList.add('modal-enter-active');
                }, 10);
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if(modal) {
                const content = document.getElementById(id + 'Content');
                content.classList.remove('modal-enter-active');
                content.classList.add('modal-leave-active');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    content.classList.remove('modal-leave-active');
                }, 300);
            }
        }

        const dpInput = document.getElementById('depositAmountInput');
        if(dpInput){
            dpInput.addEventListener('keyup', function(e) {
                this.value = formatRupiah(this.value);
            });
        }

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
