<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Bill.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Debug: Print user_id
echo "Debug - User ID: " . $_SESSION['user_id'] . "<br>";

$database = new Database();
$db = $database->getConnection();

// Verify if user exists
$query = "SELECT user_id FROM users WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();

if($stmt->rowCount() == 0) {
    echo "Error: User not found in database. Please log in again.";
    header("Location: logout.php");
    exit();
}

$bill = new Bill($db);
$bill->user_id = $_SESSION['user_id'];

$message = '';
$messageType = '';

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_bill'])) {
        $bill->bill_number = $_POST['bill_number'];
        $bill->client_name = $_POST['client_name'];
        $bill->category = $_POST['category'];
        $bill->amount = $_POST['amount'];
        $bill->description = $_POST['description'];
        $bill->issue_date = $_POST['issue_date'];
        $bill->due_date = $_POST['due_date'];
        
        if($bill->create()) {
            $message = "Bill created successfully";
            $messageType = "success";
        } else {
            $message = "Error creating bill";
            $messageType = "danger";
        }
    } elseif(isset($_POST['mark_paid'])) {
        $bill->bill_number = $_POST['bill_number'];
        $bill->payment_date = $_POST['payment_date'];
        $bill->method_id = $_POST['method_id'];

        // Fetch the bill amount from the database
        $query = "SELECT amount FROM bills WHERE bill_number = :bill_number AND user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":bill_number", $bill->bill_number);
        $stmt->bindParam(":user_id", $bill->user_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $bill->amount = $row ? $row['amount'] : 0;

        if($bill->markAsPaid()) {
            $message = "Bill marked as paid successfully";
            $messageType = "success";
        } else {
            $message = "Error marking bill as paid";
            $messageType = "danger";
        }
    } elseif(isset($_POST['delete_bill'])) {
        $bill->bill_number = $_POST['bill_number'];
        
        if($bill->delete()) {
            $message = "Bill deleted successfully";
            $messageType = "success";
        } else {
            $message = "Error deleting bill";
            $messageType = "danger";
        }
    }
}

// Update overdue bills
$bill->updateOverdueBills();

// Get payment methods
$query = "SELECT * FROM payment_methods ORDER BY method_name";
$stmt = $db->prepare($query);
$stmt->execute();
$payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get bills
$bills = $bill->read();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bills - Biz-Track</title>
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
                        <a class="nav-link active" href="bills.php">
                            <i class="bi bi-receipt me-1"></i>Bills
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="staff.php">
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
                        <h5 class="card-title">Create New Bill</h5>
                        
                        <?php if($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="client_name" class="form-label">Client Name</label>
                                <input type="text" class="form-control" id="client_name" name="client_name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Sales">Sales</option>
                                    <option value="Tutorials">Tutorials</option>
                                    <option value="Event Decorations">Event Decorations</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="amount" name="amount" 
                                           step="0.01" min="0" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="issue_date" class="form-label">Issue Date</label>
                                <input type="date" class="form-control" id="issue_date" name="issue_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" 
                                       value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="add_bill" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Create Bill
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Bill List</h5>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Bill #</th>
                                        <th>Client</th>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>Issue Date</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $bills->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['bill_number']); ?></td>
                                            <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                                            <td>₹<?php echo number_format($row['amount'], 2); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['issue_date'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $row['status'] == 'Paid' ? 'success' : 
                                                        ($row['status'] == 'Overdue' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo $row['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" 
                                                            data-bs-toggle="dropdown">
                                                        <i class="bi bi-gear"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <?php if($row['status'] != 'Paid'): ?>
                                                            <li>
                                                                <button type="button" class="dropdown-item" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#markPaidModal<?php echo $row['bill_number']; ?>">
                                                                    <i class="bi bi-check-circle me-2"></i>Mark as Paid
                                                                </button>
                                                            </li>
                                                        <?php endif; ?>
                                                        <li>
                                                            <form method="POST" action="" 
                                                                  onsubmit="return confirm('Are you sure you want to delete this bill?');">
                                                                <input type="hidden" name="bill_number" value="<?php echo $row['bill_number']; ?>">
                                                                <button type="submit" name="delete_bill" class="dropdown-item text-danger">
                                                                    <i class="bi bi-trash me-2"></i>Delete
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                                
                                                <!-- Mark as Paid Modal -->
                                                <div class="modal fade" id="markPaidModal<?php echo $row['bill_number']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Mark Bill as Paid</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="POST" action="">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="bill_number" value="<?php echo $row['bill_number']; ?>">
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="payment_date<?php echo $row['bill_number']; ?>" class="form-label">Payment Date</label>
                                                                        <input type="date" class="form-control" id="payment_date<?php echo $row['bill_number']; ?>" 
                                                                               name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="method_id<?php echo $row['bill_number']; ?>" class="form-label">Payment Method</label>
                                                                        <select class="form-select" id="method_id<?php echo $row['bill_number']; ?>" 
                                                                                name="method_id" required>
                                                                            <option value="">Select Payment Method</option>
                                                                            <?php foreach($payment_methods as $method): ?>
                                                                                <option value="<?php echo $method['method_id']; ?>">
                                                                                    <?php echo htmlspecialchars($method['method_name']); ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="mark_paid" class="btn btn-primary">Mark as Paid</button>
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