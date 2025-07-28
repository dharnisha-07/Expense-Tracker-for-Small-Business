<?php
session_start();
require_once 'config/database.php';
require_once 'includes/User.php';

// Redirect if already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        $result = $user->login($email, $password);
        
        if($result) {
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['name'] = $result['name'];
            $_SESSION['email'] = $result['email'];
            $_SESSION['profile_photo'] = $result['profile_photo'];
            
            header("Location: dashboard.php");
            exit();
        } else {
            $message = "Invalid email or password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biz-Track - Business Expense Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <div class="text-center mb-4">
                    <h1 class="display-4 fw-bold text-primary">Biz-Track</h1>
                    <p class="text-muted">Business Expense Tracker</p>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="card-title text-center mb-4">Login</h2>
                        
                        <?php if($message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" required 
                                           placeholder="Enter your email">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" required
                                           placeholder="Enter your password">
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="login" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="mb-0">Don't have an account? 
                                <a href="register.php" class="text-primary text-decoration-none">Register here</a>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4 text-muted">
                    <small>&copy; <?php echo date('Y'); ?> Biz-Track. All rights reserved.</small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 