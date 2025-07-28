<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Expense.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$expense = new Expense($db);
$expense->user_id = $_SESSION['user_id'];

$message = '';
$messageType = '';

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_expense'])) {
        $expense->category_id = $_POST['category_id'];
        $expense->amount = $_POST['amount'];
        $expense->description = $_POST['description'];
        $expense->expense_date = $_POST['expense_date'];
        
        // Handle receipt upload
        if(isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $file_info = pathinfo($_FILES['receipt']['name']);
            $ext = strtolower($file_info['extension']);
            
            // Validate file type
            $allowed = array('pdf', 'jpg', 'jpeg', 'png');
            if(in_array($ext, $allowed)) {
                $receipt_file = uniqid() . '.' . $ext;
                $target = "uploads/receipts/" . $receipt_file;
                
                if(move_uploaded_file($_FILES['receipt']['tmp_name'], $target)) {
                    $expense->receipt_file = $receipt_file;
                } else {
                    $message = "Error uploading receipt";
                    $messageType = "danger";
                }
            } else {
                $message = "Invalid file type. Allowed types: " . implode(', ', $allowed);
                $messageType = "danger";
            }
        }
        
        if(empty($message)) {
            if($expense->create()) {
                $message = "Expense added successfully";
                $messageType = "success";
            } else {
                $message = "Error adding expense";
                $messageType = "danger";
            }
        }
    } elseif(isset($_POST['delete_expense'])) {
        $expense->expense_id = $_POST['expense_id'];
        if($expense->delete()) {
            $message = "Expense deleted successfully";
            $messageType = "success";
        } else {
            $message = "Error deleting expense";
            $messageType = "danger";
        }
    }
}

// Get expense categories
$query = "SELECT * FROM categories WHERE type = 'expense' ORDER BY category_name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get expenses
$expenses = $expense->read();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - Biz-Track</title>
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
                        <a class="nav-link active" href="expenses.php">
                            <i class="bi bi-cash me-1"></i>Expenses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bills.php">
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
                        <h5 class="card-title">Add New Expense</h5>
                        
                        <?php if($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
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
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="expense_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="expense_date" name="expense_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="receipt" class="form-label">Receipt</label>
                                <input type="file" class="form-control" id="receipt" name="receipt" 
                                       accept=".pdf,.jpg,.jpeg,.png">
                                <div class="form-text">Supported formats: PDF, JPG, PNG</div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="add_expense" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Add Expense
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Expense History</h5>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Receipt</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $expenses->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($row['expense_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                                            <td>₹<?php echo number_format($row['amount'], 2); ?></td>
                                            <td>
                                                <?php if($row['receipt_file']): ?>
                                                    <a href="uploads/receipts/<?php echo $row['receipt_file']; ?>" 
                                                       target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-file-earmark-text"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" action="" class="d-inline" 
                                                      onsubmit="return confirm('Are you sure you want to delete this expense?');">
                                                    <input type="hidden" name="expense_id" value="<?php echo $row['expense_id']; ?>">
                                                    <button type="submit" name="delete_expense" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
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