<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");

require_once '../config/database.php';
require_once '../classes/Goal.php';

$database = new Database();
$db = $database->getConnection();
$goalObj = new Goal($db);

// Simple token authentication via GET for demo purposes
$user_id = $_GET['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $goals = $goalObj->getGoalsByUser($user_id);
    $totals = $goalObj->getTotalSavingsByUser($user_id);
    
    echo json_encode([
        "status" => "success",
        "data" => [
            "totals" => $totals,
            "goals" => $goals
        ]
    ]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->title) && !empty($data->target_amount) && !empty($data->target_date)) {
        $icon = $data->icon ?? '🎯';
        if ($goalObj->addGoal($user_id, $data->title, $data->target_amount, $data->target_date, $icon)) {
            echo json_encode(["status" => "success", "message" => "Target berhasil ditambahkan"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Gagal menyimpan"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Data tidak lengkap"]);
    }
}
?>
