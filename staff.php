<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Staff.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$staff = new Staff($db);
$staff->user_id = $_SESSION['user_id'];

$message = '';
$messageType = '';

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_staff'])) {
        $staff->name = $_POST['name'];
        $staff->email = $_POST['email'];
        $staff->phone = $_POST['phone'];
        $staff->position = $_POST['position'];
        $staff->salary = $_POST['salary'];
        $staff->joining_date = $_POST['joining_date'];
        
        if($staff->emailExists($staff->email)) {
            $message = "Email already exists";
            $messageType = "danger";
        } else {
            if($staff->create()) {
                $message = "Staff member added successfully";
                $messageType = "success";
            } else {
                $message = "Error adding staff member";
                $messageType = "danger";
            }
        }
    } elseif(isset($_POST['update_staff'])) {
        $staff->staff_id = $_POST['staff_id'];
        $staff->name = $_POST['name'];
        $staff->email = $_POST['email'];
        $staff->phone = $_POST['phone'];
        $staff->position = $_POST['position'];
        $staff->salary = $_POST['salary'];
        $staff->joining_date = $_POST['joining_date'];
        
        if($staff->update()) {
            $message = "Staff member updated successfully";
            $messageType = "success";
        } else {
            $message = "Error updating staff member";
            $messageType = "danger";
        }
    } elseif(isset($_POST['delete_staff'])) {
        $staff->staff_id = $_POST['staff_id'];
        
        if($staff->delete()) {
            $message = "Staff member deleted successfully";
            $messageType = "success";
        } else {
            $message = "Error deleting staff member";
            $messageType = "danger";
        }
    }
}

// Get staff members
$staff_members = $staff->read();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - Biz-Track</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <img src="assets/images/logo.png" alt="Biz-Track Logo" height="40" class="me-2">
                Biz-Track
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="expenses.php">
                            <i class="bi bi-cash me-1"></i>Expenses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bills.php">
                            <i class="bi bi-receipt me-1"></i>Bills
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="staff.php">
                            <i class="bi bi-people me-1"></i>Staff
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="revenue.php">
                            <i class="bi bi-graph-up-arrow me-1"></i>Revenue
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <img src="uploads/<?php echo $_SESSION['profile_photo']; ?>" 
                                 class="rounded-circle me-1" 
                                 style="width: 32px; height: 32px; object-fit: cover;">
                            <?php echo htmlspecialchars($_SESSION['name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="bi bi-person me-2"></i>Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Add New Staff Member</h5>
                        
                        <?php if($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            
                            <div class="mb-3">
                                <label for="position" class="form-label">Position</label>
                                <input type="text" class="form-control" id="position" name="position" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="salary" class="form-label">Salary</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="salary" name="salary" 
                                           step="0.01" min="0" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="joining_date" class="form-label">Joining Date</label>
                                <input type="date" class="form-control" id="joining_date" name="joining_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="add_staff" class="btn btn-primary">
                                    <i class="bi bi-person-plus me-2"></i>Add Staff Member
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Staff List</h5>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Position</th>
                                        <th>Contact</th>
                                        <th>Joining Date</th>
                                        <th>Salary</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $staff_members->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['position']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($row['email']); ?>
                                                <?php if($row['phone']): ?>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($row['phone']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($row['joining_date'])); ?></td>
                                            <td>₹<?php echo number_format($row['salary'], 2); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" 
                                                            data-bs-toggle="dropdown">
                                                        <i class="bi bi-gear"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <button type="button" class="dropdown-item" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#editModal<?php echo $row['staff_id']; ?>">
                                                                <i class="bi bi-pencil me-2"></i>Edit
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <form method="POST" action="" 
                                                                  onsubmit="return confirm('Are you sure you want to delete this staff member?');">
                                                                <input type="hidden" name="staff_id" value="<?php echo $row['staff_id']; ?>">
                                                                <button type="submit" name="delete_staff" class="dropdown-item text-danger">
                                                                    <i class="bi bi-trash me-2"></i>Delete
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                                
                                                <!-- Edit Modal -->
                                                <div class="modal fade" id="editModal<?php echo $row['staff_id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit Staff Member</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="POST" action="">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="staff_id" value="<?php echo $row['staff_id']; ?>">
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="edit_name<?php echo $row['staff_id']; ?>" class="form-label">Full Name</label>
                                                                        <input type="text" class="form-control" id="edit_name<?php echo $row['staff_id']; ?>" 
                                                                               name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="edit_email<?php echo $row['staff_id']; ?>" class="form-label">Email</label>
                                                                        <input type="email" class="form-control" id="edit_email<?php echo $row['staff_id']; ?>" 
                                                                               name="email" value="<?php echo htmlspecialchars($row['email']); ?>" required>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="edit_phone<?php echo $row['staff_id']; ?>" class="form-label">Phone</label>
                                                                        <input type="tel" class="form-control" id="edit_phone<?php echo $row['staff_id']; ?>" 
                                                                               name="phone" value="<?php echo htmlspecialchars($row['phone']); ?>">
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="edit_position<?php echo $row['staff_id']; ?>" class="form-label">Position</label>
                                                                        <input type="text" class="form-control" id="edit_position<?php echo $row['staff_id']; ?>" 
                                                                               name="position" value="<?php echo htmlspecialchars($row['position']); ?>" required>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="edit_salary<?php echo $row['staff_id']; ?>" class="form-label">Salary</label>
                                                                        <div class="input-group">
                                                                            <span class="input-group-text">₹</span>
                                                                            <input type="number" class="form-control" id="edit_salary<?php echo $row['staff_id']; ?>" 
                                                                                   name="salary" step="0.01" min="0" 
                                                                                   value="<?php echo $row['salary']; ?>" required>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="edit_joining_date<?php echo $row['staff_id']; ?>" class="form-label">Joining Date</label>
                                                                        <input type="date" class="form-control" id="edit_joining_date<?php echo $row['staff_id']; ?>" 
                                                                               name="joining_date" value="<?php echo $row['joining_date']; ?>" required>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="update_staff" class="btn btn-primary">Save Changes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 