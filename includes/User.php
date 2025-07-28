<?php
class User {
    private $conn;
    private $table_name = "users";

    public $user_id;
    public $name;
    public $email;
    public $password;
    public $profile_photo;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (name, email, password, profile_photo)
                VALUES
                (:name, :email, :password, :profile_photo)";

        $stmt = $this->conn->prepare($query);

        // Sanitize and hash password
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        $this->profile_photo = htmlspecialchars(strip_tags($this->profile_photo));

        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":profile_photo", $this->profile_photo);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function login($email, $password) {
        $query = "SELECT user_id, name, email, password, profile_photo 
                FROM " . $this->table_name . "
                WHERE email = :email";

        $stmt = $this->conn->prepare($query);
        $email = htmlspecialchars(strip_tags($email));
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $row['password'])) {
                return $row;
            }
        }
        return false;
    }

    public function updateProfile() {
        $query = "UPDATE " . $this->table_name . "
                SET name = :name,
                    profile_photo = :profile_photo
                WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->profile_photo = htmlspecialchars(strip_tags($this->profile_photo));

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":profile_photo", $this->profile_photo);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    public function changePassword($new_password) {
        $query = "UPDATE " . $this->table_name . "
                SET password = :password
                WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        
        $new_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt->bindParam(":password", $new_password);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    public function emailExists($email) {
        $query = "SELECT user_id FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $email = htmlspecialchars(strip_tags($email));
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function getById($id) {
        $query = "SELECT user_id, name, email, profile_photo, created_at 
                FROM " . $this->table_name . "
                WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?> 