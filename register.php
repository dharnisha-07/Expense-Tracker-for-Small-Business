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
$messageType = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['register'])) {
        // Validate input
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if(empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
            $message = "All fields are required";
            $messageType = "danger";
        } elseif($password !== $confirm_password) {
            $message = "Passwords do not match";
            $messageType = "danger";
        } elseif(strlen($password) < 6) {
            $message = "Password must be at least 6 characters long";
            $messageType = "danger";
        } elseif($user->emailExists($email)) {
            $message = "Email already exists";
            $messageType = "danger";
        } else {
            // Handle profile photo upload
            $profile_photo = "default.png"; // Default image
            if(isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $file_info = pathinfo($_FILES['profile_photo']['name']);
                $ext = strtolower($file_info['extension']);
                
                // Validate file type
                $allowed = array('jpg', 'jpeg', 'png', 'gif');
                if(in_array($ext, $allowed)) {
                    $profile_photo = uniqid() . '.' . $ext;
                    $target = "uploads/" . $profile_photo;
                    
                    if(move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target)) {
                        // File uploaded successfully
                    } else {
                        $message = "Error uploading file";
                        $messageType = "danger";
                    }
                } else {
                    $message = "Invalid file type. Allowed types: " . implode(', ', $allowed);
                    $messageType = "danger";
                }
            }
            
            if(empty($message)) {
                $user->name = $name;
                $user->email = $email;
                $user->password = $password;
                $user->profile_photo = $profile_photo;
                
                if($user->create()) {
                    $message = "Registration successful. Please login.";
                    $messageType = "success";
                    header("refresh:2;url=index.php");
                } else {
                    $message = "Registration failed";
                    $messageType = "danger";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Biz-Track</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6">
                <div class="text-center mb-4">
                    <h1 class="display-4 fw-bold text-primary">Biz-Track</h1>
                    <p class="text-muted">Create your account</p>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="card-title text-center mb-4">Register</h2>
                        
                        <?php if($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" class="form-control" id="name" name="name" required
                                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                           placeholder="Enter your full name">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                           placeholder="Enter your email">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" required
                                           placeholder="Enter your password">
                                </div>
                                <div class="form-text">Password must be at least 6 characters long</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock-fill"></i>
                                    </span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required
                                           placeholder="Confirm your password">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="profile_photo" class="form-label">Profile Photo</label>
                                <div class="custom-file-upload" id="drop_zone" onclick="document.getElementById('profile_photo').click();">
                                    <i class="bi bi-cloud-upload display-4"></i>
                                    <p class="mb-0">Click or drag photo here</p>
                                    <small class="text-muted">Supported formats: JPG, PNG, GIF</small>
                                </div>
                                <input type="file" class="d-none" id="profile_photo" name="profile_photo" 
                                       accept="image/jpeg,image/png,image/gif">
                                <div id="preview" class="mt-2"></div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="register" class="btn btn-primary btn-lg">
                                    <i class="bi bi-person-plus me-2"></i>Create Account
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="mb-0">Already have an account? 
                                <a href="index.php" class="text-primary text-decoration-none">Login here</a>
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
    <script>
        // File upload preview
        document.getElementById('profile_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if(file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview').innerHTML = `
                        <img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px;">
                    `;
                }
                reader.readAsDataURL(file);
            }
        });

        // Drag and drop functionality
        const dropZone = document.getElementById('drop_zone');
        
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropZone.classList.add('border-primary');
        });

        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            dropZone.classList.remove('border-primary');
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropZone.classList.remove('border-primary');
            
            const file = e.dataTransfer.files[0];
            if(file && file.type.startsWith('image/')) {
                document.getElementById('profile_photo').files = e.dataTransfer.files;
                const event = new Event('change');
                document.getElementById('profile_photo').dispatchEvent(event);
            }
        });
    </script>
</body>
</html> 