<?php
class Revenue {
    private $conn;
    private $table_name = "revenue";

    public $revenue_id;
    public $user_id;
    public $total_revenue;
    public $description;
    public $revenue_date;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (user_id, total_revenue, description, revenue_date)
                VALUES
                (:user_id, :total_revenue, :description, :revenue_date)";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->total_revenue = htmlspecialchars(strip_tags($this->total_revenue));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->revenue_date = htmlspecialchars(strip_tags($this->revenue_date));

        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":total_revenue", $this->total_revenue);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":revenue_date", $this->revenue_date);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function read() {
        $query = "SELECT r.* 
                FROM " . $this->table_name . " r
                WHERE r.user_id = :user_id
                ORDER BY r.revenue_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();

        return $stmt;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET total_revenue = :total_revenue,
                    description = :description,
                    revenue_date = :revenue_date
                WHERE revenue_id = :revenue_id
                AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->total_revenue = htmlspecialchars(strip_tags($this->total_revenue));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->revenue_date = htmlspecialchars(strip_tags($this->revenue_date));
        $this->revenue_id = htmlspecialchars(strip_tags($this->revenue_id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Bind values
        $stmt->bindParam(":total_revenue", $this->total_revenue);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":revenue_date", $this->revenue_date);
        $stmt->bindParam(":revenue_id", $this->revenue_id);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . "
                WHERE revenue_id = :revenue_id
                AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        $this->revenue_id = htmlspecialchars(strip_tags($this->revenue_id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        $stmt->bindParam(":revenue_id", $this->revenue_id);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    public function getTotalRevenue($start_date, $end_date) {
        $query = "SELECT COALESCE(SUM(total_revenue), 0) as total
                FROM " . $this->table_name . "
                WHERE user_id = :user_id
                AND revenue_date BETWEEN :start_date AND :end_date";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function getRevenueByCategory($start_date, $end_date) {
        $query = "SELECT 'General' as category_name, COALESCE(SUM(r.total_revenue), 0) as total
                FROM " . $this->table_name . " r
                WHERE r.user_id = :user_id
                AND r.revenue_date BETWEEN :start_date AND :end_date";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?> 