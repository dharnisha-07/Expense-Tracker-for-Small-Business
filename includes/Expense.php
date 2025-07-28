<?php
class Expense {
    private $conn;
    private $table_name = "expenses";

    public $expense_id;
    public $user_id;
    public $category_id;
    public $amount;
    public $description;
    public $expense_date;
    public $receipt_file;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (user_id, category_id, amount, description, expense_date, receipt_file)
                VALUES
                (:user_id, :category_id, :amount, :description, :expense_date, :receipt_file)";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->expense_date = htmlspecialchars(strip_tags($this->expense_date));
        $this->receipt_file = htmlspecialchars(strip_tags($this->receipt_file));

        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":expense_date", $this->expense_date);
        $stmt->bindParam(":receipt_file", $this->receipt_file);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function read() {
        $query = "SELECT e.*, c.category_name 
                FROM " . $this->table_name . " e
                LEFT JOIN categories c ON e.category_id = c.category_id
                WHERE e.user_id = :user_id
                ORDER BY e.expense_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();

        return $stmt;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET category_id = :category_id,
                    amount = :amount,
                    description = :description,
                    expense_date = :expense_date,
                    receipt_file = :receipt_file
                WHERE expense_id = :expense_id
                AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->expense_date = htmlspecialchars(strip_tags($this->expense_date));
        $this->receipt_file = htmlspecialchars(strip_tags($this->receipt_file));
        $this->expense_id = htmlspecialchars(strip_tags($this->expense_id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Bind values
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":expense_date", $this->expense_date);
        $stmt->bindParam(":receipt_file", $this->receipt_file);
        $stmt->bindParam(":expense_id", $this->expense_id);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . "
                WHERE expense_id = :expense_id
                AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        $this->expense_id = htmlspecialchars(strip_tags($this->expense_id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        $stmt->bindParam(":expense_id", $this->expense_id);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    public function getTotalExpenses($start_date, $end_date) {
        $query = "SELECT COALESCE(SUM(amount), 0) as total
                FROM " . $this->table_name . "
                WHERE user_id = :user_id
                AND expense_date BETWEEN :start_date AND :end_date";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function getExpensesByCategory($start_date, $end_date) {
        $query = "SELECT c.category_name, COALESCE(SUM(e.amount), 0) as total
                FROM categories c
                LEFT JOIN " . $this->table_name . " e ON c.category_id = e.category_id
                AND e.user_id = :user_id
                AND e.expense_date BETWEEN :start_date AND :end_date
                WHERE c.type = 'expense'
                GROUP BY c.category_id, c.category_name";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?> 