<?php
include('db_config.php');
session_start();

class DairyBoxManager {
    private $conn;
    private $centerCode;
    private $dairyBoxes = [];
    private $totalPages = 1;
    private $totalFilteredBoxes = 0;
    
    // List of available products (limited to selecting 3)
    private $productOptions = [
        'Pasteurized Milk',
        'Choco Milk',
        'Pandan Milk',
        'Ube Milk',
        'Lacto Juice',
        'Yoghurt Strawberry',
        'Yoghurt Pineapple',
        'Yoghurt Blueberry',
        'Yoghurt Mango',
        'Ice Cream',
        'Ice Candy',
        'Pastillas',
        'Cheese',
        'Paneer',
        'Consignment Bread and Pastry'
    ];
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->centerCode = $_SESSION['center_code'] ?? '';
    }
    
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePostRequest();
        }
        
        $this->getDairyBoxes();
        return $this->dairyBoxes;
    }
    
    private function handlePostRequest() {
        if (isset($_POST['add'])) {
            $this->addDairyBox();
        } elseif (isset($_POST['edit'])) {
            $this->updateDairyBox();
        } elseif (isset($_POST['delete'])) {
            $this->deleteDairyBox();
        } elseif (isset($_POST['toggle_status'])) {
            $this->toggleDairyBoxStatus();
        } elseif (isset($_POST['export'])) {
            $this->exportDairyBoxes();
        }
    }
    
    private function addDairyBox() {
        $data = $this->sanitizeInput($_POST);
        
        try {
            $query = "INSERT INTO dairy_boxes 
                      (name, is_operational, products, num_resellers, jobs_created, cosignors, center_code) 
                      VALUES 
                      (:name, :is_operational, :products, :num_resellers, :jobs_created, :cosignors, :center_code)";
            $stmt = $this->conn->prepare($query);
            
            $stmt->execute([
                ':name' => $data['name'],
                ':is_operational' => $data['is_operational'],
                ':products' => implode(',', $data['products']),
                ':num_resellers' => $data['num_resellers'],
                ':jobs_created' => $data['jobs_created'],
                ':cosignors' => $data['cosignors'],
                ':center_code' => $this->centerCode
            ]);
            
            $this->setMessage("Store added successfully!", "success");
            $this->redirect();
        } catch (PDOException $e) {
            $this->setMessage("Error adding Store: " . $e->getMessage(), "danger");
            $this->redirect();
        }
    }
    
    private function updateDairyBox() {
        $data = $this->sanitizeInput($_POST);
        $box_id = $_POST['box_id'];
        
        try {
            $query = "UPDATE dairy_boxes SET 
                      name = :name, 
                      is_operational = :is_operational, 
                      products = :products, 
                      num_resellers = :num_resellers,
                      jobs_created = :jobs_created,
                      cosignors = :cosignors 
                      WHERE id = :box_id AND center_code = :center_code";
            $stmt = $this->conn->prepare($query);
            
            $stmt->execute([
                ':name' => $data['name'],
                ':is_operational' => $data['is_operational'],
                ':products' => implode(',', $data['products']),
                ':num_resellers' => $data['num_resellers'],
                ':jobs_created' => $data['jobs_created'],
                ':cosignors' => $data['cosignors'],
                ':box_id' => $box_id,
                ':center_code' => $this->centerCode
            ]);
            
            $this->setMessage("Store updated successfully!", "success");
            $this->redirect();
        } catch (PDOException $e) {
            $this->setMessage("Error updating Store: " . $e->getMessage(), "danger");
            $this->redirect();
        }
    }
    
    private function deleteDairyBox() {
        $box_id = $_POST['box_id'];
        
        try {
            $query = "DELETE FROM dairy_boxes WHERE id = :box_id AND center_code = :center_code";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':box_id' => $box_id,
                ':center_code' => $this->centerCode
            ]);
            
            $this->setMessage("Store deleted successfully!", "success");
        } catch (PDOException $e) {
            $this->setMessage("Error deleting Store: " . $e->getMessage(), "danger");
        }
        
        $this->redirect();
    }
    
    private function toggleDairyBoxStatus() {
        $box_id = $_POST['box_id'];
        
        try {
            $query = "UPDATE dairy_boxes SET is_operational = NOT is_operational WHERE id = :box_id AND center_code = :center_code";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':box_id' => $box_id,
                ':center_code' => $this->centerCode
            ]);
            
            // Get the new status
            $query = "SELECT is_operational FROM dairy_boxes WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id' => $box_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'new_status' => $result['is_operational']
            ]);
            exit;
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'error' => 'Database error: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    private function exportDairyBoxes() {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="stores_export_'.date('Y-m-d').'.csv"');
        $output = fopen("php://output", "w");
        fputcsv($output, ['ID', 'Store Name', 'Status', 'Products', 'Resellers', 'Jobs Created', 'Cosignors']);
        
        foreach ($this->dairyBoxes as $row) {
            fputcsv($output, [
                $row['id'],
                $row['name'],
                $row['is_operational'] ? 'Operational' : 'Stopped',
                str_replace(',', ', ', $row['products']),
                $row['num_resellers'],
                $row['jobs_created'],
                $row['cosignors']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    private function getDairyBoxes() {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Sorting logic
        $sort = $_GET['sort'] ?? 'name';
        $order = $_GET['order'] ?? 'ASC';
        $search = $_GET['search'] ?? '';
        
        $validColumns = ['name', 'is_operational', 'num_resellers', 'jobs_created'];
        $sort = in_array($sort, $validColumns) ? $sort : 'name';
        $order = in_array(strtoupper($order), ['ASC', 'DESC']) ? strtoupper($order) : 'ASC';
        
        // Build base query
        $baseQuery = "SELECT * FROM dairy_boxes WHERE center_code = :center_code";
        $countQuery = "SELECT COUNT(*) FROM dairy_boxes WHERE center_code = :center_code";
        $params = [':center_code' => $this->centerCode];
        
        // Search handling
        if (!empty($search)) {
            $searchTerms = array_map('trim', explode(',', $search));
            $searchConditions = [];
            
            foreach ($searchTerms as $index => $term) {
                if (strtolower($term) === 'operational') {
                    $searchConditions[] = "is_operational = 1";
                } elseif (strtolower($term) === 'stopped') {
                    $searchConditions[] = "is_operational = 0";
                } else {
                    $paramName = ":search$index";
                    $searchConditions[] = "(name LIKE $paramName OR products LIKE $paramName OR cosignors LIKE $paramName)";
                    $params[$paramName] = "%$term%";
                }
            }
            
            if (!empty($searchConditions)) {
                $baseQuery .= " AND (" . implode(" OR ", $searchConditions) . ")";
                $countQuery .= " AND (" . implode(" OR ", $searchConditions) . ")";
            }
        }
        
        // Get total count
        try {
            $stmt = $this->conn->prepare($countQuery);
            $stmt->execute($params);
            $this->totalFilteredBoxes = $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->totalFilteredBoxes = 0;
        }
        
        $this->totalPages = ceil($this->totalFilteredBoxes / $limit);
        
        // Add sorting and pagination
        $baseQuery .= " ORDER BY $sort $order LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        // Fetch dairy boxes
        try {
            $stmt = $this->conn->prepare($baseQuery);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $this->dairyBoxes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->setMessage("Error fetching stores: " . $e->getMessage(), "danger");
            $this->dairyBoxes = [];
        }
    }
    
    private function sanitizeInput($data) {
        // Ensure only 3 products are selected
        $products = $data['products'] ?? [];
        if (count($products) > 3) {
            $products = array_slice($products, 0, 3);
        }
        
        return [
            'name' => trim($data['name']),
            'is_operational' => isset($data['is_operational']) ? 1 : 0,
            'products' => $products,
            'num_resellers' => (int)trim($data['num_resellers']),
            'jobs_created' => (int)trim($data['jobs_created']),
            'cosignors' => trim($data['cosignors'])
        ];
    }
    
    private function setMessage($message, $type) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
    
    private function redirect() {
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
    
    public function getTotalPages() {
        return $this->totalPages;
    }
    
    public function getTotalFilteredBoxes() {
        return $this->totalFilteredBoxes;
    }
    
    public function getProductOptions() {
        return $this->productOptions;
    }
}

// Initialize and handle request
$dairyBoxManager = new DairyBoxManager($conn);
$dairyBoxes = $dairyBoxManager->handleRequest();
$totalPages = $dairyBoxManager->getTotalPages();
$totalFilteredBoxes = $dairyBoxManager->getTotalFilteredBoxes();
$productOptions = $dairyBoxManager->getProductOptions();

// Get current page and calculate offset
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="css/center.css">
    <link rel="stylesheet" href="css/partners.css"> 

    <style>
        .products-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* 3 columns */
            gap: 10px;
            margin-top: 10px;
            max-height: 300px;
            overflow-y: auto;
            padding: 5px;
        }
        
        .product-checkbox {
            display: flex;
            align-items: center;
            padding: 5px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .product-checkbox input {
            margin-right: 8px;
        }
        
        .products-display {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 5px;
        }
        
        .product-tag {
            background-color: #e9ecef;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
        }

        /* Modal sizing adjustments */
        .modal-dialog {
            max-width: 800px;
            margin: 1.75rem auto;
        }
        
        .modal-body {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

                /* Pagination Styles */
        .pagination-container {
            margin-top: 20px;
            display: flex;
            justify-content: center;
        }

        .pagination {
            display: flex;
            list-style: none;
            padding: 0;
            gap: 5px;
        }

        .page-item {
            margin: 0 2px;
        }

        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 10px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            color: #0056b3;
            text-decoration: none;
            transition: all 0.3s;
        }

        .page-link:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
        }

        .page-item.active .page-link {
            background-color: #0056b3;
            border-color: #0056b3;
            color: white;
        }

        .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }

        .page-link i {
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .products-container {
                grid-template-columns: repeat(2, 1fr); /* 2 columns on smaller screens */
            }
            
            .modal-dialog {
                max-width: 95%;
                margin: 1rem auto;
            }

            .pagination {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .page-item {
                margin: 2px;
            }
            
            .page-link {
                min-width: 35px;
                height: 35px;
                padding: 0 8px;
                font-size: 14px;
            }
        }
        
        .product-selection-limit {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 5px;
            display: none;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <!-- User Profile Section -->
        <div class="user-profile">
            <div class="profile-picture">
                <?php if (!empty($_SESSION['user']['profile_image'])): ?>
                    <img src="uploads/profile_images/<?= htmlspecialchars($_SESSION['user']['profile_image']) ?>" alt="Profile Picture">
                <?php else: ?>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user']['full_name']) ?>&background=0056b3&color=fff&size=128" alt="Profile Picture">
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h3 class="user-name"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></h3>
                <p class="user-email"><?= htmlspecialchars($_SESSION['user']['email']) ?></p>
            </div>
        </div>

        <nav>
            <ul>
                <li><a href="services.php" class="nav-link"><i class="fas fa-dashboard"></i> Back to quickfacts</a></li>
                <li><a href="dbox_dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="dairybox.php" class="nav-link active"><i class="fas fa-store"></i> Stores</a></li>
                <li><a href="dbox_report.php" class="nav-link"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="logout.php" class="logout-btn" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <h1>Welcome to <?= htmlspecialchars($_SESSION['user']['center_name']) ?></h1>
            </div>
            
            <div class="header-right">
                <div class="notification-container">
                    <button class="notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>
                    <div class="notification-dropdown">
                        <div class="notification-header">
                            <h4>Notifications</h4>
                            <span class="mark-all-read">Mark all as read</span>
                        </div>
                        <div class="notification-list">
                            <a href="#" class="notification-item unread">
                                <div class="notification-icon">
                                    <i class="fas fa-users text-primary"></i>
                                </div>
                                <div class="notification-content">
                                    <p>5 new farmers registered today</p>
                                    <small>2 hours ago</small>
                                </div>
                            </a>
                            <a href="#" class="notification-item unread">
                                <div class="notification-icon">
                                    <i class="fas fa-paw text-success"></i>
                                </div>
                                <div class="notification-content">
                                    <p>New carabao health report available</p>
                                    <small>5 hours ago</small>
                                </div>
                            </a>
                            <a href="#" class="notification-item">
                                <div class="notification-icon">
                                    <i class="fas fa-exclamation-triangle text-danger"></i>
                                </div>
                                <div class="notification-content">
                                    <p>3 pending requests need approval</p>
                                    <small>Yesterday</small>
                                </div>
                            </a>
                        </div>
                        <div class="notification-footer">
                            <a href="#">View all notifications</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] === 'success' ? 'success' : 'danger' ?>">
                <i class="fas fa-<?= $_SESSION['message_type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <span><?= $_SESSION['message'] ?></span>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Stores List</h2>
                <div>
                    <button id="createBtn" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Store
                    </button>
                    <form method="POST" style="display: inline-block;">
                        <button type="submit" name="export" class="btn btn-success">
                            <i class="fas fa-file-export"></i> Export
                        </button>
                    </form>
                </div>
            </div>

            <div class="card-body">
                <div class="search-filter-bar">
                    <form id="searchForm" class="d-flex gap-2" style="flex-grow: 1;">
                        <div class="input-group" style="max-width: 500px;">
                            <input type="text" id="searchInput" name="search" class="form-control" 
                                placeholder="Search by name, products, or status..." 
                                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            <div class="input-group-append">
                                <button id="clearSearch" class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div id="resultsCount" class="mt-3">
                    <h4>Showing <?= count($dairyBoxes) ?> of <?= $totalFilteredBoxes ?> stores</h4>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="sortable-header" data-sort="name">
                                    Store Name
                                    <?php if (($_GET['sort'] ?? '') === 'name'): ?>
                                    <span class="sort-indicator">
                                        <?= ($_GET['order'] ?? '') === 'ASC' ? '↑' : '↓' ?>
                                    </span>
                                    <?php endif; ?>
                                </th>
                                <th>Products</th>
                                <th class="sortable-header" data-sort="num_resellers">
                                    Resellers
                                    <?php if (($_GET['sort'] ?? '') === 'num_resellers'): ?>
                                    <span class="sort-indicator">
                                        <?= ($_GET['order'] ?? '') === 'ASC' ? '↑' : '↓' ?>
                                    </span>
                                    <?php endif; ?>
                                </th>
                                <th class="sortable-header" data-sort="jobs_created">
                                    Jobs Created
                                    <?php if (($_GET['sort'] ?? '') === 'jobs_created'): ?>
                                    <span class="sort-indicator">
                                        <?= ($_GET['order'] ?? '') === 'ASC' ? '↑' : '↓' ?>
                                    </span>
                                    <?php endif; ?>
                                </th>
                                <th class="sortable-header" data-sort="is_operational">
                                    Status
                                    <?php if (($_GET['sort'] ?? '') === 'is_operational'): ?>
                                    <span class="sort-indicator">
                                        <?= ($_GET['order'] ?? '') === 'ASC' ? '↑' : '↓' ?>
                                    </span>
                                    <?php endif; ?>
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="dairyBoxesTableBody">
                            <?php if (empty($dairyBoxes)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No stores found. Add your first store!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dairyBoxes as $box): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($box['name']) ?></td>
                                        <td>
                                            <div class="products-display">
                                                <?php 
                                                $products = explode(',', $box['products']);
                                                foreach ($products as $product): 
                                                    if (!empty($product)):
                                                ?>
                                                    <span class="product-tag"><?= htmlspecialchars($product) ?></span>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($box['num_resellers']) ?></td>
                                        <td><?= htmlspecialchars($box['jobs_created']) ?></td>
                                        <td>
                                            <form class="toggle-status-form" method="POST">
                                                <input type="hidden" name="box_id" value="<?= $box['id'] ?>">
                                                <button type="submit" name="toggle_status" class="btn btn-sm status-toggle <?= $box['is_operational'] ? 'btn-success' : 'btn-danger' ?>">
                                                    <?= $box['is_operational'] ? 'Operational' : 'Stopped' ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-info btn-sm edit-btn"
                                                        data-id="<?= $box['id'] ?>"
                                                        data-name="<?= htmlspecialchars($box['name']) ?>"
                                                        data-operational="<?= $box['is_operational'] ?>"
                                                        data-products="<?= htmlspecialchars($box['products']) ?>"
                                                        data-resellers="<?= $box['num_resellers'] ?>"
                                                        data-jobs-created="<?= $box['jobs_created'] ?>"
                                                        data-cosignors="<?= htmlspecialchars($box['cosignors']) ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this store?');" style="display:inline;">
                                                    <input type="hidden" name="box_id" value="<?= $box['id'] ?>">
                                                    <button type="submit" name="delete" class="btn btn-danger btn-sm delete-btn">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>                            
                    </table>
                </div>
            </div>

        <!-- Pagination Section -->
        <div class="pagination-container">
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <!-- Previous Button -->
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>

                    <!-- Page Numbers -->
                    <?php 
                    // Show first page and ellipsis if needed
                    if ($page > 3): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                        </li>
                        <?php if ($page > 4): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php 
                    // Show pages around current page
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <!-- Show last page and ellipsis if needed -->
                    <?php if ($page < $totalPages - 2): ?>
                        <?php if ($page < $totalPages - 3): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>"><?= $totalPages ?></a>
                        </li>
                    <?php endif; ?>

                    <!-- Next Button -->
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

            <!-- Pagination Summary -->
            <div class="text-center text-muted small">
                Showing <?= ($offset + 1) ?> to <?= min($offset + $limit, $totalFilteredBoxes) ?> of <?= $totalFilteredBoxes ?> entries
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div id="createModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">Add New Store</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name" class="form-label">Store Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status </label>
                        <div class="form-check">
                            <input type="checkbox" id="is_operational" name="is_operational" class="form-check-input" checked>
                            <label for="is_operational" class="form-check-label">Operational</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Products (Select up to 3)</label>
                        <small class="text-muted d-block">Select all products available at this store</small>
                        <div class="products-container">
                            <?php foreach ($productOptions as $product): ?>
                                <div class="product-checkbox">
                                    <input type="checkbox" id="product_<?= str_replace(' ', '_', strtolower($product)) ?>" 
                                        name="products[]" value="<?= htmlspecialchars($product) ?>"
                                        class="product-checkbox-input">
                                    <label for="product_<?= str_replace(' ', '_', strtolower($product)) ?>"><?= htmlspecialchars($product) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="product-selection-limit" id="productLimitMessage">
                            You can only select up to 3 products
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="num_resellers" class="form-label">Number of Resellers</label>
                            <input type="number" id="num_resellers" name="num_resellers" class="form-control" min="0" value="0">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="jobs_created" class="form-label">Jobs Created</label>
                            <input type="number" id="jobs_created" name="jobs_created" class="form-control" min="0" value="0">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="cosignors" class="form-label">Name of Cosignors</label>
                        <textarea id="cosignors" name="cosignors" class="form-control" rows="3"></textarea>
                        <small class="text-muted">List names separated by commas if multiple</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger modal-cancel">Cancel</button>
                    <button type="submit" name="add" class="btn btn-primary">Save Store</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">Edit Store</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" id="edit_id" name="box_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_name" class="form-label">Store Name</label>
                        <input type="text" id="edit_name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status </label>
                        <div class="form-check">
                            <input type="checkbox" id="edit_operational" name="is_operational" class="form-check-input">
                            <label for="edit_operational" class="form-check-label">Operational</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Products (Select up to 3)</label>
                        <small class="text-muted d-block">Select all products available at this store</small>
                        <div class="products-container" id="edit_products_container">
                            <?php foreach ($productOptions as $product): ?>
                                <div class="product-checkbox">
                                    <input type="checkbox" id="edit_product_<?= str_replace(' ', '_', strtolower($product)) ?>" 
                                        name="products[]" value="<?= htmlspecialchars($product) ?>"
                                        class="product-checkbox-input">
                                    <label for="edit_product_<?= str_replace(' ', '_', strtolower($product)) ?>"><?= htmlspecialchars($product) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="product-selection-limit" id="editProductLimitMessage">
                            You can only select up to 3 products
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_resellers" class="form-label">Number of Resellers</label>
                            <input type="number" id="edit_resellers" name="num_resellers" class="form-control" min="0">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="edit_jobs_created" class="form-label">Jobs Created</label>
                            <input type="number" id="edit_jobs_created" name="jobs_created" class="form-control" min="0">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_cosignors" class="form-label">Name of Cosignors</label>
                        <textarea id="edit_cosignors" name="cosignors" class="form-control" rows="3"></textarea>
                        <small class="text-muted">List names separated by commas if multiple</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger modal-cancel">Cancel</button>
                    <button type="submit" name="edit" class="btn btn-primary">Update Store</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // DOM elements
        const createBtn = document.getElementById('createBtn');
        const createModal = document.getElementById('createModal');
        const editModal = document.getElementById('editModal');
        const modalCloses = document.querySelectorAll('.modal-close');
        const editBtns = document.querySelectorAll('.edit-btn');
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');
        const sortableHeaders = document.querySelectorAll('.sortable-header');
        const toggleStatusForms = document.querySelectorAll('.toggle-status-form');
        const productCheckboxes = document.querySelectorAll('.product-checkbox-input');
        const productLimitMessage = document.getElementById('productLimitMessage');
        const editProductLimitMessage = document.getElementById('editProductLimitMessage');

        // Product selection limit (3 products)
        function setupProductSelectionLimit(containerId, limitMessageId) {
            const checkboxes = document.querySelectorAll(`#${containerId} .product-checkbox-input`);
            const limitMessage = document.getElementById(limitMessageId);
            
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const checkedCount = document.querySelectorAll(`#${containerId} .product-checkbox-input:checked`).length;
                    
                    if (checkedCount > 3) {
                        this.checked = false;
                        limitMessage.style.display = 'block';
                        setTimeout(() => {
                            limitMessage.style.display = 'none';
                        }, 3000);
                    }
                });
            });
        }

        // Initialize product selection limit for both modals
        setupProductSelectionLimit('edit_products_container', 'editProductLimitMessage');
        document.querySelectorAll('.product-checkbox-input').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checkedCount = document.querySelectorAll('.product-checkbox-input:checked').length;
                
                if (checkedCount > 3) {
                    this.checked = false;
                    productLimitMessage.style.display = 'block';
                    setTimeout(() => {
                        productLimitMessage.style.display = 'none';
                    }, 3000);
                }
            });
        });

        // Event listeners
        createBtn.addEventListener('click', () => {
            createModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        });

        editBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Set form values
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_name').value = btn.dataset.name;
                document.getElementById('edit_operational').checked = btn.dataset.operational === '1';
                document.getElementById('edit_resellers').value = btn.dataset.resellers;
                document.getElementById('edit_jobs_created').value = btn.dataset.jobsCreated;
                document.getElementById('edit_cosignors').value = btn.dataset.cosignors;
                
                // Clear all product checkboxes first
                document.querySelectorAll('#edit_products_container input[type="checkbox"]').forEach(checkbox => {
                    checkbox.checked = false;
                });
                
                // Check the products that this store has
                const products = btn.dataset.products.split(',');
                products.forEach(product => {
                    const checkbox = document.querySelector(`#edit_products_container input[value="${product.trim()}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
                
                editModal.classList.add('show');
                document.body.style.overflow = 'hidden';
            });
        });

        // Search functionality
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const params = new URLSearchParams(window.location.search);
                params.set('search', this.value);
                params.delete('page'); // Reset to first page when searching
                window.location.href = `${window.location.pathname}?${params.toString()}`;
            }, 500);
        });

        // Clear search button
        document.getElementById('clearSearch')?.addEventListener('click', function() {
            searchInput.value = '';
            const params = new URLSearchParams(window.location.search);
            params.delete('search');
            window.location.href = `${window.location.pathname}?${params.toString()}`;
        });

        // Sortable headers
        sortableHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const sortField = this.dataset.sort;
                const params = new URLSearchParams(window.location.search);
                
                if (params.get('sort') === sortField) {
                    // Toggle order if same field
                    params.set('order', params.get('order') === 'ASC' ? 'DESC' : 'ASC');
                } else {
                    // New field, default to ASC
                    params.set('sort', sortField);
                    params.set('order', 'ASC');
                }
                
                window.location.href = `${window.location.pathname}?${params.toString()}`;
            });
        });

        // Toggle status forms
        toggleStatusForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('toggle_status', '1');
                
                fetch(window.location.href.split('?')[0], {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const button = this.querySelector('button');
                        if (data.new_status == 1) {
                            button.classList.remove('btn-danger');
                            button.classList.add('btn-success');
                            button.textContent = 'Operational';
                        } else {
                            button.classList.remove('btn-success');
                            button.classList.add('btn-danger');
                            button.textContent = 'Stopped';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating status. Please try again.');
                });
            });
        });

        // Modal close handlers
        modalCloses.forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.classList.remove('show');
                });
                document.body.style.overflow = 'auto';
            });
        });

        document.querySelectorAll('.modal-cancel').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.classList.remove('show');
                });
                document.body.style.overflow = 'auto';
            });
        });

        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        });

        // Responsive sidebar toggle
        const sidebarToggle = document.createElement('button');
        sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
        sidebarToggle.style.position = 'fixed';
        sidebarToggle.style.bottom = '20px';
        sidebarToggle.style.right = '20px';
        sidebarToggle.style.zIndex = '999';
        sidebarToggle.style.width = '50px';
        sidebarToggle.style.height = '50px';
        sidebarToggle.style.borderRadius = '50%';
        sidebarToggle.style.backgroundColor = 'var(--primary)';
        sidebarToggle.style.color = 'white';
        sidebarToggle.style.border = 'none';
        sidebarToggle.style.boxShadow = 'var(--shadow-md)';
        sidebarToggle.style.cursor = 'pointer';
        sidebarToggle.style.display = 'none';
        
        document.body.appendChild(sidebarToggle);
        
        sidebarToggle.addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('show');
        });

        function handleResize() {
            if (window.innerWidth <= 992) {
                sidebarToggle.style.display = 'flex';
                sidebarToggle.style.alignItems = 'center';
                sidebarToggle.style.justifyContent = 'center';
            } else {
                sidebarToggle.style.display = 'none';
                document.querySelector('.sidebar').classList.remove('show');
            }
        }
        
        window.addEventListener('resize', handleResize);
        handleResize();

        // Logout confirmation
        document.getElementById('logoutLink')?.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Logout Confirmation',
                text: 'Are you sure you want to logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, logout!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = this.href;
                }
            });
        });

        // Highlight active nav link
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = window.location.pathname.split('/').pop();
            document.querySelectorAll('nav .nav-link').forEach(link => {
                const href = link.getAttribute('href');
                if (href && currentPage === href) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>