<?php
class Bill {
    private $conn;
    private $table_name = "bills";
    
    public $bill_number;
    public $user_id;
    public $client_name;
    public $category;
    public $amount;
    public $issue_date;
    public $due_date;
    public $status;
    public $payment_date;
    public $method_id;
    public $description;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (user_id, client_name, category, amount, issue_date, due_date, status, description)
                VALUES
                (:user_id, :client_name, :category, :amount, :issue_date, :due_date, 'Unpaid', :description)";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize and bind values
        $this->client_name = htmlspecialchars(strip_tags($this->client_name));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->description = htmlspecialchars(strip_tags($this->description));
        
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":client_name", $this->client_name);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":issue_date", $this->issue_date);
        $stmt->bindParam(":due_date", $this->due_date);
        $stmt->bindParam(":description", $this->description);
        
        if($stmt->execute()) {
            $this->bill_number = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
    
    public function markAsPaid() {
        $query = "UPDATE " . $this->table_name . "
                SET status = 'Paid', payment_date = :payment_date, method_id = :method_id
                WHERE bill_number = :bill_number AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":payment_date", $this->payment_date);
        $stmt->bindParam(":method_id", $this->method_id);
        $stmt->bindParam(":bill_number", $this->bill_number);
        $stmt->bindParam(":user_id", $this->user_id);
        
        if($stmt->execute()) {
            // Fetch the bill's category and description
            $query = "SELECT category, description FROM bills WHERE bill_number = :bill_number AND user_id = :user_id";
            $catStmt = $this->conn->prepare($query);
            $catStmt->bindParam(":bill_number", $this->bill_number);
            $catStmt->bindParam(":user_id", $this->user_id);
            $catStmt->execute();
            $catRow = $catStmt->fetch(PDO::FETCH_ASSOC);
            $this->category = $catRow ? $catRow['category'] : null;
            $this->description = $catRow ? $catRow['description'] : null;

            // Add to revenue table
            $query = "INSERT INTO revenue (user_id, bill_number, category, total_revenue, revenue_date, method_id, description)
                    VALUES (:user_id, :bill_number, :category, :amount, :payment_date, :method_id, :description)";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":bill_number", $this->bill_number);
            $stmt->bindParam(":category", $this->category);
            $stmt->bindParam(":amount", $this->amount);
            $stmt->bindParam(":payment_date", $this->payment_date);
            $stmt->bindParam(":method_id", $this->method_id);
            $stmt->bindParam(":description", $this->description);
            
            return $stmt->execute();
        }
        return false;
    }
    
    public function read() {
        $query = "SELECT b.*, pm.method_name 
                FROM " . $this->table_name . " b
                LEFT JOIN payment_methods pm ON b.method_id = pm.method_id
                WHERE b.user_id = :user_id
                ORDER BY b.issue_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    public function getUnpaidBills() {
        $query = "SELECT * FROM " . $this->table_name . "
                WHERE user_id = :user_id AND status = 'Unpaid'
                ORDER BY due_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    public function getPaidBills() {
        $query = "SELECT b.*, pm.method_name 
                FROM " . $this->table_name . " b
                LEFT JOIN payment_methods pm ON b.method_id = pm.method_id
                WHERE b.user_id = :user_id AND b.status = 'Paid'
                ORDER BY b.payment_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    public function delete() {
        // First, delete related revenue records
        $query = "DELETE FROM revenue WHERE bill_number = :bill_number AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":bill_number", $this->bill_number);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();
        // Debug: Output number of deleted revenue records
        error_log("Deleted revenue rows: " . $stmt->rowCount());

        // Now, delete the bill
        $query = "DELETE FROM " . $this->table_name . "
                WHERE bill_number = :bill_number AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":bill_number", $this->bill_number);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }
    
    public function updateOverdueBills() {
        $query = "UPDATE " . $this->table_name . "
                SET status = 'Overdue'
                WHERE user_id = :user_id 
                AND status = 'Unpaid' 
                AND due_date < CURRENT_DATE";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        
        return $stmt->execute();
    }
}
?> 