<?php
session_start();
require_once 'config/database.php';
require_once 'includes/User.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get current month's data
$start_date = date('Y-m-01');
$end_date = date('Y-m-t');

// Get total revenue
$query = "SELECT COALESCE(SUM(total_revenue), 0) as total_revenue 
        FROM revenue 
        WHERE user_id = :user_id 
        AND revenue_date BETWEEN :start_date AND :end_date";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->bindParam(":start_date", $start_date);
$stmt->bindParam(":end_date", $end_date);
$stmt->execute();
$total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];

// Get total expenses
$query = "SELECT COALESCE(SUM(amount), 0) as total_expenses 
        FROM expenses 
        WHERE user_id = :user_id 
        AND expense_date BETWEEN :start_date AND :end_date";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->bindParam(":start_date", $start_date);
$stmt->bindParam(":end_date", $end_date);
$stmt->execute();
$total_expenses = $stmt->fetch(PDO::FETCH_ASSOC)['total_expenses'];

// Get unpaid bills
$query = "SELECT COALESCE(SUM(amount), 0) as unpaid_bills 
        FROM bills 
        WHERE user_id = :user_id 
        AND status = 'Unpaid'";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$unpaid_bills = $stmt->fetch(PDO::FETCH_ASSOC)['unpaid_bills'];

// Calculate net profit
$query = "SELECT 
            (SELECT COALESCE(SUM(total_revenue), 0) FROM revenue WHERE user_id = :user_id) - 
            (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = :user_id) AS net_profit";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$net_profit = $stmt->fetch(PDO::FETCH_ASSOC)['net_profit'];

// Get paid bills for the current month
$query = "SELECT COUNT(*) as paid_bills_count, COALESCE(SUM(amount), 0) as paid_bills_amount
        FROM bills 
        WHERE user_id = :user_id 
        AND status = 'Paid'
        AND payment_date BETWEEN :start_date AND :end_date";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->bindParam(":start_date", $start_date);
$stmt->bindParam(":end_date", $end_date);
$stmt->execute();
$paid_bills = $stmt->fetch(PDO::FETCH_ASSOC);

// Get expense categories for pie chart
$query = "SELECT c.category_name, COALESCE(SUM(e.amount), 0) as total
        FROM categories c
        LEFT JOIN expenses e ON c.category_id = e.category_id
        AND e.user_id = :user_id
        AND e.expense_date BETWEEN :start_date AND :end_date
        GROUP BY c.category_id, c.category_name";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->bindParam(":start_date", $start_date);
$stmt->bindParam(":end_date", $end_date);
$stmt->execute();
$expense_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get last 6 months revenue vs expenses
$query = "SELECT 
            DATE_FORMAT(dates.date, '%b %Y') as month,
            COALESCE(SUM(r.total_revenue), 0) as revenue,
            COALESCE(SUM(e.amount), 0) as expenses
        FROM (
            SELECT LAST_DAY(CURRENT_DATE) - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) MONTH as date
            FROM (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5) as a
            CROSS JOIN (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5) as b
            CROSS JOIN (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5) as c
        ) dates
        LEFT JOIN revenue r ON DATE_FORMAT(dates.date, '%Y-%m') = DATE_FORMAT(r.revenue_date, '%Y-%m')
        AND r.user_id = :user_id
        LEFT JOIN expenses e ON DATE_FORMAT(dates.date, '%Y-%m') = DATE_FORMAT(e.expense_date, '%Y-%m')
        AND e.user_id = :user_id
        WHERE dates.date > CURRENT_DATE - INTERVAL 6 MONTH
        GROUP BY dates.date
        ORDER BY dates.date ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id']);
$stmt->execute();
$monthly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Biz-Track</title>
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
                        <a class="nav-link active" href="dashboard.php">
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
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Total Revenue</h6>
                                <h3 class="mb-0">₹<?php echo number_format($total_revenue, 2); ?></h3>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Total Expenses</h6>
                                <h3 class="mb-0">₹<?php echo number_format($total_expenses, 2); ?></h3>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-cash"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Unpaid Bills</h6>
                                <h3 class="mb-0">₹<?php echo number_format($unpaid_bills, 2); ?></h3>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-receipt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card <?php echo $net_profit >= 0 ? 'bg-success' : 'bg-danger'; ?> text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Net Profit</h6>
                                <h3 class="mb-0">₹<?php echo number_format($net_profit, 2); ?></h3>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bills Overview -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Unpaid Bills</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Bill #</th>
                                        <th>Client</th>
                                        <th>Amount</th>
                                        <th>Due Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT * FROM bills 
                                            WHERE user_id = :user_id AND status = 'Unpaid' 
                                            ORDER BY due_date ASC LIMIT 5";
                                    $stmt = $db->prepare($query);
                                    $stmt->bindParam(":user_id", $_SESSION['user_id']);
                                    $stmt->execute();
                                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['bill_number']); ?></td>
                                        <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                        <td>₹<?php echo number_format($row['amount'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Recent Paid Bills</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Bill #</th>
                                        <th>Client</th>
                                        <th>Amount</th>
                                        <th>Paid Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT b.*, pm.method_name 
                                            FROM bills b
                                            LEFT JOIN payment_methods pm ON b.method_id = pm.method_id
                                            WHERE b.user_id = :user_id AND b.status = 'Paid' 
                                            ORDER BY b.payment_date DESC LIMIT 5";
                                    $stmt = $db->prepare($query);
                                    $stmt->bindParam(":user_id", $_SESSION['user_id']);
                                    $stmt->execute();
                                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['bill_number']); ?></td>
                                        <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                        <td>₹<?php echo number_format($row['amount'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['payment_date'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Revenue vs Expenses</h5>
                        <div class="chart-container">
                            <canvas id="revenueExpensesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Expense Categories</h5>
                        <div class="chart-container">
                            <canvas id="expenseCategoriesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Revenue vs Expenses Chart
        const revenueExpensesChart = new Chart(
            document.getElementById('revenueExpensesChart'),
            {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($monthly_data, 'month')); ?>,
                    datasets: [
                        {
                            label: 'Revenue',
                            data: <?php echo json_encode(array_column($monthly_data, 'revenue')); ?>,
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1,
                            fill: false
                        },
                        {
                            label: 'Expenses',
                            data: <?php echo json_encode(array_column($monthly_data, 'expenses')); ?>,
                            borderColor: 'rgb(255, 99, 132)',
                            tension: 0.1,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₹' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            }
        );

        // Expense Categories Chart
        const expenseCategoriesChart = new Chart(
            document.getElementById('expenseCategoriesChart'),
            {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_column($expense_categories, 'category_name')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($expense_categories, 'total')); ?>,
                        backgroundColor: [
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4BC0C0',
                            '#9966FF',
                            '#FF9F40'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            }
        );
    </script>
</body>
</html> 