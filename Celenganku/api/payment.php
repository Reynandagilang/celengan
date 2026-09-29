<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");

require_once '../config/database.php';
require_once '../classes/Goal.php';

$database = new Database();
$db = $database->getConnection();
$goalObj = new Goal($db);

$action = $_GET['action'] ?? '';
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->goal_id) && !empty($data->amount) && !empty($data->method)) {
        // Generate mock virtual account
        $va = "8899" . rand(10000000, 99999999);
        
        $query = "INSERT INTO payment_transactions (user_id, savings_goal_id, amount, payment_method, virtual_account) VALUES (:uid, :gid, :amt, :meth, :va)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':uid', $user_id);
        $stmt->bindParam(':gid', $data->goal_id);
        $stmt->bindParam(':amt', $data->amount);
        $stmt->bindParam(':meth', $data->method);
        $stmt->bindParam(':va', $va);
        
        if ($stmt->execute()) {
            $trx_id = $db->lastInsertId();
            echo json_encode([
                "status" => "success",
                "message" => "Payment created",
                "data" => [
                    "transaction_id" => $trx_id,
                    "virtual_account" => $va,
                    "amount" => $data->amount,
                    "method" => $data->method,
                    "status" => "pending"
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Server error"]);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'simulate_pay') {
    // Simulasi webhook/callback dari payment gateway yang merubah status transaksi
    $data = json_decode(file_get_contents("php://input"));
    if (!empty($data->transaction_id)) {
        $trx_id = $data->transaction_id;
        
        // Dapatkan data transaksi
        $q_trx = "SELECT * FROM payment_transactions WHERE id = :id AND status = 'pending'";
        $s_trx = $db->prepare($q_trx);
        $s_trx->bindParam(':id', $trx_id);
        $s_trx->execute();
        
        if ($s_trx->rowCount() > 0) {
            $trx = $s_trx->fetch(PDO::FETCH_ASSOC);
            
            // Tambahkan deposit
            $note = "Top-up via " . $trx['payment_method'];
            if ($goalObj->addDeposit($trx['savings_goal_id'], $trx['user_id'], $trx['amount'], $note)) {
                // Update status payment
                $u_q = "UPDATE payment_transactions SET status = 'success' WHERE id = :id";
                $u_s = $db->prepare($u_q);
                $u_s->bindParam(':id', $trx_id);
                $u_s->execute();
                
                echo json_encode(["status" => "success", "message" => "Payment successful and deposit recorded!"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to record deposit"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Transaction not found or already processed"]);
        }
    }
}
?>
