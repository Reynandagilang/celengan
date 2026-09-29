<?php
class Goal {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getGoalsByUser($user_id) {
        $query = "SELECT * FROM savings_goals WHERE user_id = :user_id ORDER BY status ASC, created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalSavingsByUser($user_id) {
        $query = "SELECT SUM(current_amount) as total_saved, SUM(target_amount) as total_target FROM savings_goals WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'total_saved' => $row['total_saved'] ? $row['total_saved'] : 0,
            'total_target' => $row['total_target'] ? $row['total_target'] : 0
        ];
    }

    public function getGoalById($goal_id, $user_id) {
        $query = "SELECT * FROM savings_goals WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $goal_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getTransactionsByGoal($goal_id) {
        $query = "SELECT * FROM savings_transactions WHERE savings_goal_id = :goal_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':goal_id', $goal_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addGoal($user_id, $title, $target_amount, $target_date, $icon = '🎯', $image = null) {
        $query = "INSERT INTO savings_goals (user_id, title, target_amount, target_date, icon, image) VALUES (:user_id, :title, :target_amount, :target_date, :icon, :image)";
        $stmt = $this->conn->prepare($query);
        
        $title = htmlspecialchars(strip_tags($title));
        $icon = htmlspecialchars(strip_tags($icon));
        if (empty($icon)) $icon = '🎯';
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':target_amount', $target_amount);
        $stmt->bindParam(':target_date', $target_date);
        $stmt->bindParam(':icon', $icon);
        $stmt->bindParam(':image', $image);
        
        return $stmt->execute();
    }

    public function addDeposit($goal_id, $user_id, $amount, $note) {
        if ($amount <= 0) {
            return false; // Realistis: Tidak bisa setor minus atau 0
        }

        try {
            $this->conn->beginTransaction();
            
            // Check if goal belongs to user
            $goal = $this->getGoalById($goal_id, $user_id);
            if (!$goal) {
                throw new Exception("Goal not found");
            }
            if ($goal['status'] === 'completed') {
                throw new Exception("Goal already completed");
            }
            
            // Insert transaction
            $query = "INSERT INTO savings_transactions (savings_goal_id, amount, note) VALUES (:goal_id, :amount, :note)";
            $stmt = $this->conn->prepare($query);
            $note = htmlspecialchars(strip_tags($note));
            
            $stmt->bindParam(':goal_id', $goal_id);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':note', $note);
            $stmt->execute();
            
            // Update current amount
            $new_amount = $goal['current_amount'] + $amount;
            $status = ($new_amount >= $goal['target_amount']) ? 'completed' : 'active';
            
            $query_update = "UPDATE savings_goals SET current_amount = :current_amount, status = :status WHERE id = :goal_id";
            $stmt_update = $this->conn->prepare($query_update);
            $stmt_update->bindParam(':current_amount', $new_amount);
            $stmt_update->bindParam(':status', $status);
            $stmt_update->bindParam(':goal_id', $goal_id);
            $stmt_update->execute();
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}
?>
