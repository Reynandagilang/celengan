<?php
require_once 'config/database.php';
require_once 'classes/User.php';

session_start();
$database = new Database();
$db = $database->getConnection();
$user = new User($db);

if ($user->isLoggedIn()) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Celenganku</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-8">
        <div class="text-center mb-6">
            <img src="assets/savings.jpg" alt="Celenganku Savings" class="w-32 h-32 object-cover rounded-full mx-auto shadow-lg mb-4 border-4 border-emerald-50">
            <h1 class="text-3xl font-bold text-emerald-600 mb-2">Celenganku</h1>
            <p class="text-slate-500">Masuk ke akun Anda untuk mulai menabung</p>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-emerald-100 text-emerald-700 p-3 rounded-lg mb-4 text-sm">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-sm">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form action="process.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="login">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
            </div>
            <button type="submit" class="w-full bg-emerald-600 text-white py-2 rounded-lg font-semibold hover:bg-emerald-700 transition shadow-md">Masuk</button>
        </form>
        
        <p class="mt-6 text-center text-sm text-slate-500">
            Belum punya akun? <a href="register.php" class="text-emerald-600 hover:underline font-medium">Daftar sekarang</a>
        </p>
    </div>
</body>
</html>
