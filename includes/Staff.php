<?php
class Staff {
    private $conn;
    private $table_name = "staff";

    public $staff_id;
    public $user_id;
    public $name;
    public $email;
    public $phone;
    public $position;
    public $salary;
    public $joining_date;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (user_id, name, email, phone, position, salary, joining_date)
                VALUES
                (:user_id, :name, :email, :phone, :position, :salary, :joining_date)";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->position = htmlspecialchars(strip_tags($this->position));
        $this->salary = htmlspecialchars(strip_tags($this->salary));
        $this->joining_date = htmlspecialchars(strip_tags($this->joining_date));

        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":position", $this->position);
        $stmt->bindParam(":salary", $this->salary);
        $stmt->bindParam(":joining_date", $this->joining_date);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name . "
                WHERE user_id = :user_id
                ORDER BY name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();

        return $stmt;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET name = :name,
                    email = :email,
                    phone = :phone,
                    position = :position,
                    salary = :salary,
                    joining_date = :joining_date
                WHERE staff_id = :staff_id
                AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->position = htmlspecialchars(strip_tags($this->position));
        $this->salary = htmlspecialchars(strip_tags($this->salary));
        $this->joining_date = htmlspecialchars(strip_tags($this->joining_date));
        $this->staff_id = htmlspecialchars(strip_tags($this->staff_id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":position", $this->position);
        $stmt->bindParam(":salary", $this->salary);
        $stmt->bindParam(":joining_date", $this->joining_date);
        $stmt->bindParam(":staff_id", $this->staff_id);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . "
                WHERE staff_id = :staff_id
                AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        $this->staff_id = htmlspecialchars(strip_tags($this->staff_id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));

        $stmt->bindParam(":staff_id", $this->staff_id);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    public function getTotalSalary() {
        $query = "SELECT COALESCE(SUM(salary), 0) as total
                FROM " . $this->table_name . "
                WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function emailExists($email) {
        $query = "SELECT staff_id FROM " . $this->table_name . "
                WHERE email = :email
                AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $email = htmlspecialchars(strip_tags($email));
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
?> 