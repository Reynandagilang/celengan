<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';
require_once '../classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$data = json_decode(file_get_contents("php://input"));
$action = $_GET['action'] ?? '';

if ($action === 'login' && !empty($data->email) && !empty($data->password)) {
    if ($user->login($data->email, $data->password)) {
        echo json_encode([
            "status" => "success",
            "message" => "Login berhasil",
            "data" => [
                "user_id" => $_SESSION['user_id'],
                "name" => $_SESSION['user_name']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Email atau password salah."]);
    }
} elseif ($action === 'register' && !empty($data->name) && !empty($data->email) && !empty($data->password)) {
    if ($user->register($data->name, $data->email, $data->password)) {
        echo json_encode(["status" => "success", "message" => "Registrasi berhasil."]);
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Email sudah digunakan atau terjadi kesalahan."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Aksi tidak valid atau data tidak lengkap."]);
}
?>
