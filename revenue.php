<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Revenue.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$revenue = new Revenue($db);
$revenue->user_id = $_SESSION['user_id'];

$message = '';
$messageType = '';

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_revenue'])) {
        $revenue->total_revenue = $_POST['total_revenue'];
        $revenue->description = $_POST['description'];
        $revenue->revenue_date = $_POST['revenue_date'];
        
        if($revenue->create()) {
            $message = "Revenue added successfully";
            $messageType = "success";
        } else {
            $message = "Error adding revenue";
            $messageType = "danger";
        }
    } elseif(isset($_POST['update_revenue'])) {
        $revenue->revenue_id = $_POST['revenue_id'];
        $revenue->total_revenue = $_POST['total_revenue'];
        $revenue->description = $_POST['description'];
        $revenue->revenue_date = $_POST['revenue_date'];
        
        if($revenue->update()) {
            $message = "Revenue updated successfully";
            $messageType = "success";
        } else {
            $message = "Error updating revenue";
            $messageType = "danger";
        }
    } elseif(isset($_POST['delete_revenue'])) {
        $revenue->revenue_id = $_POST['revenue_id'];
        
        if($revenue->delete()) {
            $message = "Revenue deleted successfully";
            $messageType = "success";
        } else {
            $message = "Error deleting revenue";
            $messageType = "danger";
        }
    }
}

// Get revenue records
$revenue_records = $revenue->read();

// Get date range for summary
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get total revenue and revenue by category
$total_revenue = $revenue->getTotalRevenue($start_date, $end_date);
$revenue_by_category = $revenue->getRevenueByCategory($start_date, $end_date);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Management - Biz-Track</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        <a class="nav-link" href="staff.php">
                            <i class="bi bi-people me-1"></i>Staff
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="revenue.php">
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
                        <h5 class="card-title">Add New Revenue</h5>
                        
                        <?php if($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="total_revenue" class="form-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="total_revenue" name="total_revenue" 
                                           step="0.01" min="0" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="revenue_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="revenue_date" name="revenue_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="add_revenue" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Add Revenue
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Revenue Summary -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Revenue Summary</h5>
                        
                        <form method="GET" action="" class="mb-3">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="<?php echo $start_date; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           value="<?php echo $end_date; ?>">
                                </div>
                            </div>
                            <div class="d-grid mt-2">
                                <button type="submit" class="btn btn-outline-primary">Update Summary</button>
                            </div>
                        </form>
                        
                        <div class="text-center mb-3">
                            <h3 class="text-primary">₹<?php echo number_format($total_revenue, 2); ?></h3>
                            <p class="text-muted">Total Revenue</p>
                        </div>
                        
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Revenue History</h5>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $revenue_records->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($row['revenue_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                                            <td>₹<?php echo number_format($row['total_revenue'], 2); ?></td>
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
                                                                    data-bs-target="#editModal<?php echo $row['revenue_id']; ?>">
                                                                <i class="bi bi-pencil me-2"></i>Edit
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <form method="POST" action="" 
                                                                  onsubmit="return confirm('Are you sure you want to delete this revenue record?');">
                                                                <input type="hidden" name="revenue_id" value="<?php echo $row['revenue_id']; ?>">
                                                                <button type="submit" name="delete_revenue" class="dropdown-item text-danger">
                                                                    <i class="bi bi-trash me-2"></i>Delete
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                                
                                                <!-- Edit Modal -->
                                                <div class="modal fade" id="editModal<?php echo $row['revenue_id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit Revenue</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="POST" action="">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="revenue_id" value="<?php echo $row['revenue_id']; ?>">
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="edit_total_revenue<?php echo $row['revenue_id']; ?>" class="form-label">Amount</label>
                                                                        <div class="input-group">
                                                                            <span class="input-group-text">₹</span>
                                                                            <input type="number" class="form-control" id="edit_total_revenue<?php echo $row['revenue_id']; ?>" 
                                                                                   name="total_revenue" step="0.01" min="0" 
                                                                                   value="<?php echo $row['total_revenue']; ?>" required>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="edit_description<?php echo $row['revenue_id']; ?>" class="form-label">Description</label>
                                                                        <textarea class="form-control" id="edit_description<?php echo $row['revenue_id']; ?>" 
                                                                                  name="description" rows="3"><?php echo htmlspecialchars($row['description']); ?></textarea>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="edit_revenue_date<?php echo $row['revenue_id']; ?>" class="form-label">Date</label>
                                                                        <input type="date" class="form-control" id="edit_revenue_date<?php echo $row['revenue_id']; ?>" 
                                                                               name="revenue_date" value="<?php echo $row['revenue_date']; ?>" required>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="update_revenue" class="btn btn-primary">Save Changes</button>
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
    <script>
        // Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueData = <?php echo json_encode($revenue_by_category); ?>;
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: revenueData.map(item => item.category_name),
                datasets: [{
                    data: revenueData.map(item => item.total_revenue),
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                        '#858796', '#5a5c69', '#2e59d9', '#17a673', '#2c9faf'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html> 