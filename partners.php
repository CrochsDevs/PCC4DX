<?php
include('db_config.php');
session_start();

class PartnerManager {
    private $conn;
    private $centerCode;
    private $partners = [];
    private $totalPages = 1;
    private $totalFilteredPartners = 0;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->centerCode = $_SESSION['center_code'] ?? '';
    }
    
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePostRequest();
        }
        
        $this->getPartners();
        return $this->partners;
    }
    
    private function handlePostRequest() {
        if (isset($_POST['add'])) {
            $this->addPartner();
        } elseif (isset($_POST['edit'])) {
            $this->updatePartner();
        } elseif (isset($_POST['delete'])) {
            $this->deletePartner();
        } elseif (isset($_POST['toggle_status'])) {
            $this->togglePartnerStatus();
        } elseif (isset($_POST['export'])) {
            $this->exportPartners();
        }
    }
    
    private function addPartner() {
        $data = $this->sanitizeInput($_POST);
        
        try {
            $query = "INSERT INTO partners (partner_name, herd_code, contact_person, contact_number, barangay, municipality, province, is_active, center_code, coop_type) 
                      VALUES (:partner_name, :herd_code, :contact_person, :contact_number, :barangay, :municipality, :province, :is_active, :center_code, :coop_type)";
            $stmt = $this->conn->prepare($query);
            
            $stmt->execute([
                ':partner_name' => $data['partner_name'],
                ':herd_code' => $data['herd_code'],
                ':contact_person' => $data['contact_person'],
                ':contact_number' => $data['contact_number'],
                ':barangay' => $data['barangay'],
                ':municipality' => $data['municipality'],
                ':province' => $data['province'],
                ':is_active' => $data['is_active'],
                ':center_code' => $this->centerCode,
                ':coop_type' => $data['coop_type']
            ]);
            
            $this->setMessage("Partner added successfully!", "success");
            $this->redirect();
        } catch (PDOException $e) {
            $this->setMessage("Error adding partner: " . $e->getMessage(), "danger");
            $this->redirect();
        }
    }
    
    private function updatePartner() {
        $data = $this->sanitizeInput($_POST);
        $partner_id = $_POST['partner_id'];
        
        try {
            $query = "UPDATE partners SET 
                      partner_name = :partner_name, 
                      herd_code = :herd_code, 
                      contact_person = :contact_person, 
                      contact_number = :contact_number,
                      barangay = :barangay, 
                      municipality = :municipality, 
                      province = :province, 
                      is_active = :is_active,
                      coop_type = :coop_type 
                      WHERE id = :partner_id AND center_code = :center_code";
            $stmt = $this->conn->prepare($query);
            
            $stmt->execute([
                ':partner_name' => $data['partner_name'],
                ':herd_code' => $data['herd_code'],
                ':contact_person' => $data['contact_person'],
                ':contact_number' => $data['contact_number'],
                ':barangay' => $data['barangay'],
                ':municipality' => $data['municipality'],
                ':province' => $data['province'],
                ':is_active' => $data['is_active'],
                ':coop_type' => $data['coop_type'],
                ':partner_id' => $partner_id,
                ':center_code' => $this->centerCode
            ]);
            
            $this->setMessage("Partner updated successfully!", "success");
            $this->redirect();
        } catch (PDOException $e) {
            $this->setMessage("Error updating partner: " . $e->getMessage(), "danger");
            $this->redirect();
        }
    }
    
    private function deletePartner() {
        $partner_id = $_POST['partner_id'];
        
        try {
            $query = "DELETE FROM partners WHERE id = :partner_id AND center_code = :center_code";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':partner_id' => $partner_id,
                ':center_code' => $this->centerCode
            ]);
            
            $this->setMessage("Partner deleted successfully!", "success");
        } catch (PDOException $e) {
            $this->setMessage("Error deleting partner: " . $e->getMessage(), "danger");
        }
        
        $this->redirect();
    }
    
    private function togglePartnerStatus() {
        $partner_id = $_POST['partner_id'];
        
        try {
            $query = "UPDATE partners SET is_active = NOT is_active WHERE id = :partner_id AND center_code = :center_code";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':partner_id' => $partner_id,
                ':center_code' => $this->centerCode
            ]);
            
            // Get the new status
            $query = "SELECT is_active FROM partners WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id' => $partner_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'new_status' => $result['is_active']
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
    
    private function exportPartners() {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="partners_export_'.date('Y-m-d').'.csv"');
        $output = fopen("php://output", "w");
        fputcsv($output, ['ID', 'Partner Name', 'Partner Type', 'Herd Code', 'Contact Person', 'Contact Number', 'Barangay', 'Municipality', 'Province', 'Status']);
        
        foreach ($this->partners as $row) {
            fputcsv($output, [
                $row['id'],
                $row['partner_name'],
                $row['coop_type'],
                $row['herd_code'],
                $row['contact_person'],
                $row['contact_number'],
                $row['barangay'],
                $row['municipality'],
                $row['province'],
                $row['is_active'] ? 'Active' : 'Inactive'
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    private function getPartners() {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Sorting logic
        $sort = $_GET['sort'] ?? 'partner_name';
        $order = $_GET['order'] ?? 'ASC';
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? '';
        
        $validColumns = ['partner_name', 'coop_type', 'herd_code', 'is_active'];
        $sort = in_array($sort, $validColumns) ? $sort : 'partner_name';
        $order = in_array(strtoupper($order), ['ASC', 'DESC']) ? strtoupper($order) : 'ASC';
        
        // Build base query - always sort by is_active DESC first to prioritize active partners
        $baseQuery = "SELECT * FROM partners WHERE center_code = :center_code";
        $countQuery = "SELECT COUNT(*) FROM partners WHERE center_code = :center_code";
        $params = [':center_code' => $this->centerCode];
        
        // Search handling
        if (!empty($search)) {
            $searchTerms = array_map('trim', explode(',', $search));
            $searchConditions = [];
            
            foreach ($searchTerms as $index => $term) {
                if (strtolower($term) === 'active') {
                    $searchConditions[] = "is_active = 1";
                } elseif (strtolower($term) === 'inactive') {
                    $searchConditions[] = "is_active = 0";
                } else {
                    $paramName = ":search$index";
                    $searchConditions[] = "(partner_name LIKE $paramName OR herd_code LIKE $paramName OR contact_person LIKE $paramName OR contact_number LIKE $paramName OR municipality LIKE $paramName OR province LIKE $paramName OR coop_type LIKE $paramName)";
                    $params[$paramName] = "%$term%";
                }
            }
            
            if (!empty($searchConditions)) {
                $baseQuery .= " AND (" . implode(" OR ", $searchConditions) . ")";
                $countQuery .= " AND (" . implode(" OR ", $searchConditions) . ")";
            }
        }
        
        // Filter handling
        if (!empty($filter)) {
            $filterTypes = explode(',', $filter);
            $placeholders = implode(',', array_map(function($i) { 
                return ":filter$i"; 
            }, array_keys($filterTypes)));
            
            $baseQuery .= " AND coop_type IN ($placeholders)";
            $countQuery .= " AND coop_type IN ($placeholders)";
            
            foreach ($filterTypes as $i => $type) {
                $params[":filter$i"] = $type;
            }
        }
        
        // Get total count
        try {
            $stmt = $this->conn->prepare($countQuery);
            $stmt->execute($params);
            $this->totalFilteredPartners = $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->totalFilteredPartners = 0;
        }
        
        $this->totalPages = ceil($this->totalFilteredPartners / $limit);
        
        // Add sorting and pagination - always sort by is_active DESC first, then by the selected column
        $baseQuery .= " ORDER BY is_active DESC, $sort $order LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        // Fetch partners
        try {
            $stmt = $this->conn->prepare($baseQuery);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $this->partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->setMessage("Error fetching partners: " . $e->getMessage(), "danger");
            $this->partners = [];
        }
    }
    
    private function sanitizeInput($data) {
        return [
            'coop_type' => trim($data['coop_type']),
            'partner_name' => trim($data['partner_name']),
            'herd_code' => trim($data['herd_code']),
            'contact_person' => trim($data['contact_person']),
            'contact_number' => trim($data['contact_number']),
            'barangay' => trim($data['barangay']),
            'municipality' => trim($data['municipality']),
            'province' => trim($data['province']),
            'is_active' => isset($data['is_active']) ? 1 : 0
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
    
    public function getTotalFilteredPartners() {
        return $this->totalFilteredPartners;
    }
}

// Initialize and handle request
$partnerManager = new PartnerManager($conn);
$partners = $partnerManager->handleRequest();
$totalPages = $partnerManager->getTotalPages();
$totalFilteredPartners = $partnerManager->getTotalFilteredPartners();

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
    <title>Cooperative Partners Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="css/center.css">
    <link rel="stylesheet" href="css/partners.css">
    <style>
        /* Search Bar Styles */
        .search-container {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-input-container {
            position: relative;
            display: flex;
            align-items: center;
            width: 400px;
            transform: translate(0, 10px)
        }
        
        #searchInput {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
            max-width: 500px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        #searchInput:focus {
            border-color: #0056b3;
            box-shadow: 0 0 0 2px rgba(0, 86, 179, 0.2);
            outline: none;
        }
        
        #clearSearch {
            position: absolute;
            right: 10px;
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            display: none;
        }
        
        #clearSearch:hover {
            color: #666;
        }
        
        .search-terms-display {
            margin-top: 8px;
        }
        
        .search-term {
            display: inline-block;
            background-color: #e9f5ff;
            color: #0056b3;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        .search-term.narrow-term {
            background-color: #f0f0f0;
            color: #555;
        }
        
        /* Filter Buttons */
        .filter-buttons {
            margin: 15px 0;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }
        
        .filter-buttons span {
            margin-right: 10px;
            font-weight: 500;
            color: #555;
        }
        
        .filter-btn {
            padding: 6px 12px;
            border-radius: 4px;
            background-color: #f5f5f5;
            color: #333;
            text-decoration: none;
            font-size: 13px;
            border: 1px solid #ddd;
            transition: all 0.2s;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background-color: #0056b3;
            color: white;
            border-color: #0056b3;
        }
        
        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            margin-bottom: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }
        
        .table th {
            background-color: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr:hover {
            background-color: #f8f9fa;
        }
        
        .sortable-header {
            cursor: pointer;
            position: relative;
            padding-right: 20px !important;
        }
        
        .sort-indicator {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Pagination */
        .pagination-container {
            margin: 20px 0;
            display: flex;
            justify-content: center;
        }
        
        .pagination {
            display: flex;
            list-style: none;
            padding-left: 0;
            flex-wrap: wrap;
        }
        
        .page-item {
            margin: 2px;
        }
        
        .page-link {
            display: block;
            color: #004080;
            background-color: #fff;
            border: 1px solid #dee2e6;
            padding: 6px 12px;
            text-decoration: none;
            min-width: 40px;
            text-align: center;
            border-radius: 4px;
            transition: 0.3s ease;
        }
        
        .page-item.active .page-link {
            background-color: #004080;
            border-color: #004080;
            color: white;
        }
        
        .page-link:hover {
            color: #002b5c;
            background-color: #e9ecef;
        }
        
        .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }
        
        /* Results Count */
        #resultsCount {
            margin-bottom: 15px;
            color: #555;
        }
        
        /* Highlight for search matches */
        .highlight {
            background-color: #fff3cd;
            padding: 1px 3px;
            border-radius: 3px;
        }
        
        /* Mobile Sidebar Toggle */
        .sidebar-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 999;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            border: none;
            box-shadow: var(--shadow-md);
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        @media (max-width: 992px) {
            .sidebar-toggle {
                display: flex;
            }
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
                <li><a href="services.php" class="nav-link"><i class="fa-solid fa-arrow-left"></i> Back to quickfacts</a></li>
                <li><a href="milk_dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="partners.php" class="nav-link active"><i class="fas fa-users"></i> Partners</a></li>
                <li><a href="milk_report.php" class="nav-link"><i class="fas fa-file-alt"></i> Reports</a></li>
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
                <h2 class="card-title">Partners List</h2>
                <div>
                    <form method="POST" style="display: inline-block;">
                        <button type="submit" name="export" class="btn btn-success">
                            <i class="fas fa-file-export"></i> Export
                        </button>
                    </form>
                </div>
            </div>

            <div class="card-body">
                <div class="search-filter-bar">
                    <div class="search-container">
                        <form id="searchForm" class="d-flex gap-2" style="flex-grow: 1;">
                            <div class="search-input-container">
                                <input type="text" id="searchInput" name="search" class="form-control" 
                                    placeholder="Search partners (name, code, location, type)..."
                                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                                <button id="clearSearch" class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </form>
                        <div id="searchTermsDisplay" class="search-terms-display"></div>
                    </div>

                    <div class="filter-buttons">
                        <span>Filter by type:</span>
                        <a href="#" class="filter-btn all-btn <?= empty($_GET['filter']) ? 'active' : '' ?>" data-filter="all">All</a>
                        <?php 
                        $types = ['Cooperatives', 'Associations', 'LGU', 'SCU', 'Family_Module', 'Corporation'];
                        foreach ($types as $type): 
                            $isActive = !empty($_GET['filter']) && in_array($type, explode(',', $_GET['filter']));
                        ?>
                            <a href="#" class="filter-btn type-btn <?= $isActive ? 'active' : '' ?>" data-filter="<?= $type ?>">
                                <?= str_replace('_', ' ', $type) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="resultsCount" class="mt-3">
                    <h4>Showing <?= count($partners) ?> of <?= $totalFilteredPartners ?> partners</h4>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="sortable-header" data-sort="partner_name">
                                    Partner Name
                                    <?php if (($_GET['sort'] ?? '') === 'partner_name'): ?>
                                    <span class="sort-indicator">
                                        <?= ($_GET['order'] ?? '') === 'ASC' ? '↑' : '↓' ?>
                                    </span>
                                    <?php endif; ?>
                                </th>
                                <th>Partner Type</th>
                                <th class="sortable-header" data-sort="herd_code">
                                    Herd Code
                                    <?php if (($_GET['sort'] ?? '') === 'herd_code'): ?>
                                    <span class="sort-indicator">
                                        <?= ($_GET['order'] ?? '') === 'ASC' ? '↑' : '↓' ?>
                                    </span>
                                    <?php endif; ?>
                                </th>
                                <th>Contact</th>
                                <th>Location</th>
                                <th class="sortable-header" data-sort="is_active">
                                    Status
                                    <?php if (($_GET['sort'] ?? '') === 'is_active'): ?>
                                    <span class="sort-indicator">
                                        <?= ($_GET['order'] ?? '') === 'ASC' ? '↑' : '↓' ?>
                                    </span>
                                    <?php endif; ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="partnersTableBody">
                            <?php if (empty($partners)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No partners found. Add your first partner!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($partners as $partner): ?>
                                    <tr class="clickable-row" data-href="select.php?partner_id=<?= $partner['id'] ?>" style="cursor: pointer;">
                                        <td><?= htmlspecialchars($partner['partner_name']) ?></td>
                                        <td><?= htmlspecialchars($partner['coop_type']) ?></td>
                                        <td><?= htmlspecialchars($partner['herd_code']) ?></td>
                                        <td>
                                            <div><?= htmlspecialchars($partner['contact_person']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($partner['contact_number']) ?></small>
                                        </td>
                                        <td>
                                            <div><?= htmlspecialchars($partner['barangay']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($partner['municipality']) ?>, <?= htmlspecialchars($partner['province']) ?></small>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $partner['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                                <?= $partner['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>                            
                    </table>
                </div>

                <!-- Pagination Section -->
                <div class="pagination-container">
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <!-- Previous Button -->
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" 
                                   href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                                   aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>

                            <!-- Page Numbers -->
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" 
                                       href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- Next Button -->
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" 
                                   href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                                   aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>

                <!-- Pagination Summary -->
                <div class="text-center text-muted small">
                    Showing <?= ($offset + 1) ?> to <?= min($offset + $limit, $totalFilteredPartners) ?> of <?= $totalFilteredPartners ?> entries
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <script>
        $(document).ready(function() {
            // Initialize variables
            let currentSort = '<?= $_GET['sort'] ?? 'partner_name' ?>';
            let currentOrder = '<?= $_GET['order'] ?? 'ASC' ?>';
            let currentFilter = '<?= $_GET['filter'] ?? '' ?>';
            let currentSearch = '<?= $_GET['search'] ?? '' ?>';
            let activeFilters = currentFilter ? currentFilter.split(',') : [];
            
            // Initialize search terms display
            updateSearchTermDisplay();
            
            // Initialize clear button
            updateClearButton();
            
            // Initialize sort indicators
            initSortIndicators();
            
            // Initialize filter button states
            updateAllButtonState();
            
            // Sidebar toggle for mobile
            $('#sidebarToggle').click(function() {
                $('.sidebar').toggleClass('show');
            });
            
            // Handle window resize
            $(window).resize(function() {
                if ($(window).width() > 992) {
                    $('.sidebar').removeClass('show');
                }
            });
            
            // Search input handling
            $('#searchInput').on('input', function() {
                currentSearch = $(this).val().replace(/, /g, ',').trim();
                updateSearchTermDisplay();
                updateClearButton();
                fetchPartners();
            });
            
            // Clear search button
            $('#clearSearch').click(function() {
                $('#searchInput').val('');
                currentSearch = '';
                updateSearchTermDisplay();
                updateClearButton();
                fetchPartners();
            });
            
            // Filter buttons
            $('.filter-btn').click(function(e) {
                e.preventDefault();
                
                const filterType = $(this).data('filter');
                
                if (filterType === 'all') {
                    activeFilters = [];
                    $('.type-btn').removeClass('active');
                } else {
                    const index = activeFilters.indexOf(filterType);
                    if (index === -1) {
                        activeFilters.push(filterType);
                    } else {
                        activeFilters.splice(index, 1);
                    }
                    $(this).toggleClass('active');
                }
                
                updateAllButtonState();
                currentFilter = activeFilters.join(',');
                fetchPartners();
            });
            
            // Sortable headers
            $('.sortable-header').click(function() {
                const sortField = $(this).data('sort');
                
                if (currentSort === sortField) {
                    currentOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
                } else {
                    currentSort = sortField;
                    currentOrder = 'ASC';
                }
                
                fetchPartners();
            });
            
            // Clickable rows
            $(document).on('click', '.clickable-row', function(e) {
                // Don't navigate if clicking on a button or link within the row
                if ($(e.target).is('a, button, input, .btn') || $(e.target).closest('a, button, input, .btn').length) {
                    return;
                }
                
                const url = $(this).data('href');
                if (url) {
                    window.location.href = url;
                }
            });
            
            // Logout confirmation
            $('#logoutLink').click(function(e) {
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
                        window.location.href = $(this).attr('href');
                    }
                });
            });
            
            // Functions
            function fetchPartners() {
                const params = {
                    sort: currentSort,
                    order: currentOrder,
                    page: 1, // Always reset to first page when changing filters/sort
                    search: currentSearch,
                    filter: currentFilter
                };
                
                // Update URL
                updateURL(params);
                
                $.ajax({
                    url: 'partners_ajax.php',
                    method: 'GET',
                    data: params,
                    dataType: 'json',
                    success: function(data) {
                        if (data.error) {
                            console.error(data.error);
                            return;
                        }
                        
                        $('#partnersTableBody').html(data.html);
                        $('#resultsCount h4').text(`Showing ${data.count} of ${data.total} partners`);
                        
                        // Update pagination
                        $('.pagination-container').html(data.pagination);
                        
                        // Highlight search terms
                        if (currentSearch) {
                            highlightSearchTerms(currentSearch.split(',').map(t => t.trim()));
                        }
                        
                        // Update sort indicators
                        initSortIndicators();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                    }
                });
            }
            
            function updateURL(params) {
                const queryString = $.param(params);
                const newUrl = queryString ? `${window.location.pathname}?${queryString}` : window.location.pathname;
                window.history.replaceState(null, '', newUrl);
            }
            
            function updateSearchTermDisplay() {
                const termsDisplay = $('#searchTermsDisplay');
                termsDisplay.empty();
                
                if (currentSearch.includes(',')) {
                    const terms = currentSearch.split(',').map(t => t.trim()).filter(t => t);
                    const termsHtml = terms.map((term, i) => 
                        `<span class="search-term ${i > 0 ? 'narrow-term' : ''}">${term}</span>`).join(' ');
                    
                    termsDisplay.html(`
                        <div class="narrowing-indicator">
                            <small>Narrowing by:</small>
                            ${termsHtml}
                        </div>
                    `);
                }
            }
            
            function updateClearButton() {
                const clearBtn = $('#clearSearch');
                if (currentSearch) {
                    clearBtn.show();
                } else {
                    clearBtn.hide();
                }
            }
            
            function updateAllButtonState() {
                const allBtn = $('.all-btn');
                if (activeFilters.length === 0) {
                    allBtn.addClass('active');
                } else {
                    allBtn.removeClass('active');
                }
            }
            
            function initSortIndicators() {
                $('.sort-indicator').hide();
                $(`.sortable-header[data-sort="${currentSort}"] .sort-indicator`)
                    .show()
                    .text(currentOrder === 'ASC' ? '↑' : '↓');
            }
            
            function highlightSearchTerms(terms) {
                $('td').each(function() {
                    let cellHtml = $(this).html();
                    
                    terms.forEach(term => {
                        if (term.toLowerCase() === 'active' || term.toLowerCase() === 'inactive') {
                            return; // Skip status terms
                        }
                        
                        if (term) {
                            const regex = new RegExp(escapeRegExp(term), 'gi');
                            cellHtml = cellHtml.replace(regex, match => 
                                `<span class="highlight">${match}</span>`);
                        }
                    });
                    
                    $(this).html(cellHtml);
                });
            }
            
            function escapeRegExp(string) {
                return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }
        });
    </script>
</body>
</html>