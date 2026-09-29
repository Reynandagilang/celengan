<?php
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/Goal.php';

session_start();

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        if ($user->register($name, $email, $password)) {
            $_SESSION['message'] = "Registrasi berhasil, silakan login.";
            header("Location: login.php");
            exit;
        } else {
            $_SESSION['error'] = "Email sudah digunakan atau terjadi kesalahan.";
            header("Location: register.php");
            exit;
        }
    } elseif ($action === 'login') {
        $email = $_POST['email'];
        $password = $_POST['password'];

        if ($user->login($email, $password)) {
            header("Location: index.php");
            exit;
        } else {
            $_SESSION['error'] = "Email atau password salah.";
            header("Location: login.php");
            exit;
        }
    } elseif ($action === 'add_goal' && $user->isLoggedIn()) {
        $goal = new Goal($db);
        $title = $_POST['title'];
        $target_amount = str_replace(['.', ','], '', $_POST['target_amount']);
        $target_date = $_POST['target_date'];
        $icon = $_POST['icon'] ?? '🎯';

        // Validasi Logis
        if ($target_amount <= 0) {
            $_SESSION['error'] = "Target nominal harus lebih dari 0.";
            header("Location: index.php");
            exit;
        }
        
        if (strtotime($target_date) < strtotime(date('Y-m-d'))) {
            $_SESSION['error'] = "Tanggal target tidak boleh di masa lalu.";
            header("Location: index.php");
            exit;
        }

        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $new_filename = uniqid() . '.' . $ext;
                $dest = 'uploads/' . $new_filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $image_path = $dest;
                }
            }
        }

        if ($goal->addGoal($_SESSION['user_id'], $title, $target_amount, $target_date, $icon, $image_path)) {
            $_SESSION['message'] = "Target celengan berhasil ditambahkan.";
        } else {
            $_SESSION['error'] = "Gagal menambahkan target celengan.";
        }
        header("Location: index.php");
        exit;
    } elseif ($action === 'deposit' && $user->isLoggedIn()) {
        $goal = new Goal($db);
        $goal_id = $_POST['goal_id'];
        $amount = str_replace(['.', ','], '', $_POST['amount']);
        $note = $_POST['note'] ?? '';

        if ($goal->addDeposit($goal_id, $_SESSION['user_id'], $amount, $note)) {
            $_SESSION['message'] = "Setoran berhasil ditambahkan.";
            
            // Notify Node.js server
            $ch = curl_init('http://127.0.0.1:3000/notify');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'user_id' => $_SESSION['user_id'],
                'message' => 'Hore! Setoran berhasil: Rp ' . number_format($amount, 0, ',', '.'),
                'amount' => $amount
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_exec($ch);
            curl_close($ch);
        } else {
            $_SESSION['error'] = "Gagal memproses setoran.";
        }
        
        $redirect_to = $_POST['redirect_to'] ?? 'goal-detail.php?id=' . $goal_id;
        header("Location: " . $redirect_to);
        exit;
    }
}
header("Location: index.php");
exit;
?>
