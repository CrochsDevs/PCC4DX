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
</style>

</head>
<body>
      <!-- Sidebar -->
    <div class="sidebar">
       <!-- User Profile Section -->
        <div class="user-profile">
            <div class="profile-picture">
                <?php if (!empty($_SESSION['user']['profile_image'])): ?>
                    <!-- Display the uploaded profile image -->
                    <img src="uploads/profile_images/<?= htmlspecialchars($_SESSION['user']['profile_image']) ?>" alt="Profile Picture">
                <?php else: ?>
                    <!-- Fallback to the generated avatar -->
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
                    <button id="createBtn" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Partner
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
                                placeholder="Type search terms separated by commas..." 
                                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            <div class="input-group-append">
                                <button id="clearSearch" class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Add commas to narrow your search (e.g., "coop, active")</small>
                    </form>

                    <div id="searchTermsDisplay" class="search-terms-display mt-2"></div>

                    <div class="filter-buttons">
                        <span style="font-weight: 500;">Filter by type:</span>
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="partnersTableBody">
                            <?php if (empty($partners)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No partners found. Add your first partner!</td>
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
                                                <form class="toggle-status-form" method="POST">
                                                    <input type="hidden" name="partner_id" value="<?= $partner['id'] ?>">
                                                    <button type="submit" name="toggle_status" class="btn btn-sm status-toggle <?= $partner['is_active'] ? 'btn-success' : 'btn-danger' ?>">
                                                        <?= $partner['is_active'] ? 'Active' : 'Inactive' ?>
                                                    </button>
                                                </form>
                                            </td>
                                            <td>
                                                <div class="action-buttons" onclick="event.stopPropagation()">
                                                    <button class="btn btn-info btn-sm edit-btn"
                                                            data-id="<?= $partner['id'] ?>"
                                                            data-coop="<?= htmlspecialchars($partner['coop_type']) ?>"
                                                            data-name="<?= htmlspecialchars($partner['partner_name']) ?>"
                                                            data-herd="<?= htmlspecialchars($partner['herd_code']) ?>"
                                                            data-person="<?= htmlspecialchars($partner['contact_person']) ?>"
                                                            data-number="<?= htmlspecialchars($partner['contact_number']) ?>"
                                                            data-barangay="<?= htmlspecialchars($partner['barangay']) ?>"
                                                            data-municipality="<?= htmlspecialchars($partner['municipality']) ?>"
                                                            data-province="<?= htmlspecialchars($partner['province']) ?>"
                                                            data-active="<?= $partner['is_active'] ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this partner?');" style="display:inline;">
                                                        <input type="hidden" name="partner_id" value="<?= $partner['id'] ?>">
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

    <div id="createModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">Add New Partner</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="coop_type" class="drop-down">Partner Type</label>
                        <select id="coop_type" name="coop_type" class="form-control" required>
                            <option value="">-- Select Partner Type --</option>
                            <option value="Cooperatives">Cooperatives</option>
                            <option value="Associations">Associations</option>
                            <option value="LGU">LGU</option>
                            <option value="SCU">SCU</option>
                            <option value="Family_Module">Family Module</option>
                            <option value="Corporation">Corporation</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="partner_name" class="form-label">Partner Name</label>
                        <input type="text" id="partner_name" name="partner_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="herd_code" class="form-label">Herd Code</label>
                        <input type="text" id="herd_code" name="herd_code" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_person" class="form-label">Contact Person</label>
                        <input type="text" id="contact_person" name="contact_person" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_number" class="form-label">Contact Number</label>
                        <input type="text" id="contact_number" name="contact_number" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="barangay" class="form-label">Barangay</label>
                        <input type="text" id="barangay" name="barangay" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="municipality" class="form-label">Municipality</label>
                        <input type="text" id="municipality" name="municipality" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="province" class="form-label">Province</label>
                        <input type="text" id="province" name="province" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="is_active" name="is_active" class="form-check-input" checked>
                            <label for="is_active" class="form-check-label">Active Partner</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger modal-cancel">Cancel</button>
                    <button type="submit" name="add" class="btn btn-primary">Save Partner</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">Edit Partner</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" id="edit_id" name="partner_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_coop_type" class="drop-down">Partner Type</label>
                        <select id="edit_coop_type" name="coop_type" class="form-control" required>
                            <option value="">-- Select Partner Type --</option>
                            <option value="Cooperatives">Cooperatives</option>
                            <option value="Associations">Associations</option>
                            <option value="LGU">LGU</option>
                            <option value="SCU">SCU</option>
                            <option value="Family_Module">Family Module</option>
                            <option value="Corporation">Corporation</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_name" class="form-label">Partner Name</label>
                        <input type="text" id="edit_name" name="partner_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_herd" class="form-label">Herd Code</label>
                        <input type="text" id="edit_herd" name="herd_code" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_person" class="form-label">Contact Person</label>
                        <input type="text" id="edit_person" name="contact_person" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_number" class="form-label">Contact Number</label>
                        <input type="text" id="edit_number" name="contact_number" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_barangay" class="form-label">Barangay</label>
                        <input type="text" id="edit_barangay" name="barangay" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_municipality" class="form-label">Municipality</label>
                        <input type="text" id="edit_municipality" name="municipality" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_province" class="form-label">Province</label>
                        <input type="text" id="edit_province" name="province" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="edit_active" name="is_active" class="form-check-input">
                            <label for="edit_active" class="form-check-label">Active Partner</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger modal-cancel">Cancel</button>
                    <button type="submit" name="edit" class="btn btn-primary">Update Partner</button>
                </div>
            </form>
        </div>
    </div>

<script>
        const createBtn = document.getElementById('createBtn');
        const createModal = document.getElementById('createModal');
        const editModal = document.getElementById('editModal');
        const modalCloses = document.querySelectorAll('.modal-close');
        const editBtns = document.querySelectorAll('.edit-btn');
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');
        const filterBtns = document.querySelectorAll('.filter-btn');
        const sortableHeaders = document.querySelectorAll('.sortable-header');
        const partnersTableBody = document.getElementById('partnersTableBody');
        const toggleStatusForms = document.querySelectorAll('.toggle-status-form');

        let currentSort = '<?= $_GET['sort'] ?? 'partner_name' ?>';
        let currentOrder = '<?= $_GET['order'] ?? 'ASC' ?>';
        let currentFilter = '<?= $_GET['filter'] ?? '' ?>';
        let currentSearch = '<?= $_GET['search'] ?? '' ?>';
        let activeFilters = [];

        if (currentFilter && currentFilter !== 'all') {
            activeFilters = currentFilter.split(',');
        }

        createBtn.addEventListener('click', () => {
            createModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        });

        editBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_coop_type').value = btn.dataset.coop;
                document.getElementById('edit_name').value = btn.dataset.name;
                document.getElementById('edit_herd').value = btn.dataset.herd;
                document.getElementById('edit_person').value = btn.dataset.person;
                document.getElementById('edit_number').value = btn.dataset.number;
                document.getElementById('edit_barangay').value = btn.dataset.barangay;
                document.getElementById('edit_municipality').value = btn.dataset.municipality;
                document.getElementById('edit_province').value = btn.dataset.province;
                document.getElementById('edit_active').checked = btn.dataset.active === '1';
                
                editModal.classList.add('show');
                document.body.style.overflow = 'hidden';
            });
        });

let searchTimeout;

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    
    // Auto-format the input (add space after commas)
    const cursorPos = this.selectionStart;
    this.value = this.value.replace(/,(\S)/g, ', $1');
    this.setSelectionRange(cursorPos, cursorPos);
    
    searchTimeout = setTimeout(() => {
        currentSearch = this.value.replace(/, /g, ',').trim(); // Remove spaces after commas for processing
        fetchPartners();
        
        // Debug output
        console.log("Searching for:", currentSearch);
    }, 500);
});

function updateSearchTermDisplay() {
    const termsContainer = document.getElementById('searchTermsDisplay');
    
    if (!termsContainer) {
        const newContainer = document.createElement('div');
        newContainer.id = 'searchTermsDisplay';
        newContainer.className = 'search-terms-display mt-2';
        searchInput.parentNode.parentNode.appendChild(newContainer);
    }
    
    if (currentSearch.includes(',')) {
        const terms = currentSearch.split(',').map(t => t.trim()).filter(t => t);
        const html = terms.map((term, i) => 
            `<span class="search-term ${i > 0 ? 'narrow-term' : ''}">${term}</span>`).join(' ');
        
        termsContainer.innerHTML = `
            <div class="narrowing-indicator">
                <small>Narrowing by:</small>
                ${html}
                <small class="results-count"></small>
            </div>
        `;
    } else {
        termsContainer.innerHTML = '';
    }
}

// Update the fetchPartners function to maintain search terms in input
function fetchPartners() {
    if (window.fetchPartnersXHR) {
        window.fetchPartnersXHR.abort();
    }
    
    const params = new URLSearchParams();
    if (currentSort) params.append('sort', currentSort);
    if (currentOrder) params.append('order', currentOrder);
    if (currentSearch) params.append('search', currentSearch);
    if (currentFilter) params.append('filter', currentFilter);
    
    window.fetchPartnersXHR = new AbortController();
    const signal = window.fetchPartnersXHR.signal;
    
    fetch(`partners_ajax.php?${params.toString()}`, { signal })
        .then(response => {
            if (response.ok) return response.json();
            throw new Error('Network response was not ok.');
        })
        .then(data => {
        if (data.error) {
            console.error(data.error);
            return;
        }
        
        partnersTableBody.innerHTML = data.html;
        document.getElementById('resultsCount').textContent = 
            `Showing ${data.count} of ${data.total} partners`;
            
        // Update the narrowing indicator
        const countDisplay = document.querySelector('.results-count');
        if (countDisplay) {
            countDisplay.textContent = `(${data.count} matching results)`;
        }
        
        attachRowClickHandlers();
        attachEditButtonHandlers();
        attachStatusToggleHandlers();
        })
        .catch(error => {
            if (error.name !== 'AbortError') console.error('Error:', error);
        });
}

function updateClearButton() {
    const inputGroup = searchInput.parentElement;
    const clearBtn = document.getElementById('clearSearch');
    
    if (currentSearch && !clearBtn) {
        const newClearBtn = document.createElement('button');
        newClearBtn.id = 'clearSearch';
        newClearBtn.className = 'btn btn-outline-danger';
        newClearBtn.innerHTML = '<i class="fas fa-times"></i>';
        newClearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            searchInput.value = '';
            currentSearch = '';
            fetchPartners();
            updateClearButton();
            
            // Update URL
            const params = new URLSearchParams(window.location.search);
            params.delete('search');
            history.replaceState(null, '', '?' + params.toString());
        });
        inputGroup.appendChild(newClearBtn);
    } else if (!currentSearch && clearBtn) {
        clearBtn.parentElement.removeChild(clearBtn);
    }
}

function attachEventHandlers() {
    attachRowClickHandlers();
    attachEditButtonHandlers();
    attachStatusToggleHandlers();
    
    // Highlight search terms in results
    if (currentSearch) {
        const searchTerms = currentSearch.split(',').map(term => term.trim());
        highlightSearchTerms(searchTerms);
    }
}

function highlightSearchTerms(terms) {
    const rows = partnersTableBody.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td:not(:last-child)');
        let rowContainsTerm = false;
        
        cells.forEach(cell => {
            let cellHtml = cell.innerHTML;
            let cellContainsTerm = false;
            
            terms.forEach(term => {
                if (term.toLowerCase() === 'active' || term.toLowerCase() === 'inactive') {
                    return; // Skip status terms
                }
                
                const regex = new RegExp(escapeRegExp(term), 'gi');
                cellHtml = cellHtml.replace(regex, match => 
                    `<span class="highlight">${match}</span>`);
                
                if (cell.textContent.toLowerCase().includes(term.toLowerCase())) {
                    cellContainsTerm = true;
                }
            });
            
            if (cellContainsTerm) {
                rowContainsTerm = true;
                cell.innerHTML = cellHtml;
            }
        });
        
        if (rowContainsTerm) {
            row.classList.add('search-match');
        } else {
            row.classList.remove('search-match');
        }
    });
}

function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

        document.querySelectorAll('.type-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const filterType = this.dataset.filter;
                
                const index = activeFilters.indexOf(filterType);
                if (index === -1) {
                    activeFilters.push(filterType);
                    this.classList.add('active');
                } else {
                    activeFilters.splice(index, 1);
                    this.classList.remove('active');
                }
                
                updateAllButtonState();
                
                currentFilter = activeFilters.join(',');
                fetchPartners();
            });
        });

        document.querySelector('.all-btn').addEventListener('click', function(e) {
            e.preventDefault();
            activeFilters = [];
            currentFilter = '';
            
            document.querySelectorAll('.type-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            fetchPartners();
        });

        function updateAllButtonState() {
            const allBtn = document.querySelector('.all-btn');
            if (activeFilters.length === 0) {
                allBtn.classList.add('active');
            } else {
                allBtn.classList.remove('active');
            }
        }

        sortableHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const sortField = this.dataset.sort;
                
                if (currentSort === sortField) {
                    currentOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
                } else {
                    currentSort = sortField;
                    currentOrder = 'ASC';
                }
                
                fetchPartners();
            });
        });

        toggleStatusForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('toggle_status', '1');
                
                fetch(window.location.href.split('?')[0], {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.ok) {
                        return response.text();
                    }
                    throw new Error('Network response was not ok.');
                })
                .then(() => {
                    fetchPartners();
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });

        function fetchPartners() {
            const params = new URLSearchParams();
            if (currentSort) params.append('sort', currentSort);
            if (currentOrder) params.append('order', currentOrder);
            if (currentSearch) params.append('search', currentSearch);
            if (currentFilter) params.append('filter', currentFilter);
            
            fetch(`partners_ajax.php?${params.toString()}`)
                .then(response => {
                    if (response.ok) {
                        return response.json();
                    }
                    throw new Error('Network response was not ok.');
                })
                .then(data => {
                    partnersTableBody.innerHTML = data.html;
                    document.getElementById('resultsCount').textContent = 
                        `Showing ${data.count} of ${data.total} partners`;
                        
                    attachRowClickHandlers();
                    attachEditButtonHandlers();
                    attachStatusToggleHandlers();
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function attachRowClickHandlers() {
            document.querySelectorAll(".clickable-row").forEach(row => {
                row.addEventListener("click", function(e) {
                    if (e.target.closest(".action-buttons") || e.target.closest('form')) return;
                    const url = this.getAttribute("data-href");
                    if (url) window.location.href = url;
                });
            });
        }

        function attachEditButtonHandlers() {
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    document.getElementById('edit_id').value = btn.dataset.id;
                    document.getElementById('edit_coop_type').value = btn.dataset.coop;
                    document.getElementById('edit_name').value = btn.dataset.name;
                    document.getElementById('edit_herd').value = btn.dataset.herd;
                    document.getElementById('edit_person').value = btn.dataset.person;
                    document.getElementById('edit_number').value = btn.dataset.number;
                    document.getElementById('edit_barangay').value = btn.dataset.barangay;
                    document.getElementById('edit_municipality').value = btn.dataset.municipality;
                    document.getElementById('edit_province').value = btn.dataset.province;
                    document.getElementById('edit_active').checked = btn.dataset.active === '1';
                    editModal.classList.add('show');
                    document.body.style.overflow = 'hidden';
                });
            });
        }

        function attachStatusToggleHandlers() {
            document.querySelectorAll('.toggle-status-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    formData.append('toggle_status', '1');
                    
                    fetch(window.location.href.split('?')[0], {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (response.ok) {
                            return response.text();
                        }
                        throw new Error('Network response was not ok.');
                    })
                    .then(() => {
                        fetchPartners();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                });
            });
        }

        document.addEventListener("DOMContentLoaded", function() {
            attachRowClickHandlers();
        });

// Add this to your JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Delegate event for dynamically loaded content
    document.body.addEventListener('submit', function(e) {
        if (e.target.classList.contains('toggle-status-form')) {
            e.preventDefault();
            togglePartnerStatus(e.target);
        }
    });
});
 
function togglePartnerStatus(form) {
    const formData = new FormData(form);
    const button = form.querySelector('button');
    
    // Add loading state
    const originalText = button.textContent;
    button.disabled = true;

    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest' // Helps identify AJAX requests
        }
    })
    .then(response => {
        // First check if the response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                throw new Error(`Expected JSON, got: ${text.substring(0, 100)}...`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Update button based on new status
        if (data.new_status == 1) {
            button.classList.remove('btn-danger');
            button.classList.add('btn-success');
            button.textContent = 'Active';
        } else {
            button.classList.remove('btn-success');
            button.classList.add('btn-danger');
            button.textContent = 'Inactive';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Show error message near the button
        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-danger small mt-1';
        errorDiv.textContent = error.message;
        form.appendChild(errorDiv);
        setTimeout(() => errorDiv.remove(), 5000);
        
        // Reset button
        button.textContent = originalText;
    })
    .finally(() => {
        button.disabled = false;
    });
}

            // Update URL without reload
            function updateURL() {
                const params = new URLSearchParams();
                if (currentSort !== 'partner_name') params.append('sort', currentSort);
                if (currentOrder !== 'ASC') params.append('order', currentOrder);
                if (currentSearch) params.append('search', currentSearch);
                if (currentFilter) params.append('filter', currentFilter);
                
                const newUrl = params.toString() ? `${window.location.pathname}?${params.toString()}` : window.location.pathname;
                window.history.replaceState(null, '', newUrl);
            }
            
            
            function escapeRegExp(string) {
                return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }
            
            // Update search term display
            function updateSearchTermDisplay() {
                const termsDisplay = $('#searchTermsDisplay');
                
                if (currentSearch.includes(',')) {
                    const terms = currentSearch.split(',').map(t => t.trim()).filter(t => t);
                    const html = terms.map((term, i) => 
                        `<span class="search-term ${i > 0 ? 'narrow-term' : ''}">${term}</span>`).join(' ');
                    
                    termsDisplay.html(`
                        <div class="narrowing-indicator">
                            <small>Narrowing by:</small>
                            ${html}
                        </div>
                    `);
                } else {
                    termsDisplay.empty();
                }
            }
            
            // Show alert function
            function showAlert(title, text, icon) {
                Swal.fire({
                    title: title,
                    text: text,
                    icon: icon,
                    confirmButtonColor: '#3085d6',
                });
            }
            
            // Initialize sort indicators
            function initSortIndicators() {
                $('.sort-indicator').hide();
                const header = $(`.sortable-header[data-sort="${currentSort}"]`);
                if (header.length) {
                    const indicator = header.find('.sort-indicator');
                    indicator.show().text(currentOrder === 'ASC' ? '↑' : '↓');
                }
            }
            
            initSortIndicators();

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

        function confirmLogout(event) {
            event.preventDefault();
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
                    window.location.href = event.target.href;
                }
            });
        }

// navigation.js
document.addEventListener('DOMContentLoaded', () => {
    const currentPage = window.location.pathname.split('/').pop(); // Get current filename (e.g., "partners.php")

    // Highlight active nav-link based on current page or section
    document.querySelectorAll('nav .nav-link, nav a').forEach(link => {
        const href = link.getAttribute('href');
        
        // Highlight if href matches the current page
        if (href && currentPage === href) {
            link.classList.add('active');
        }

        // SPA-style section toggle for links with data-section (if ever needed again)
        if (link.dataset.section) {
            link.addEventListener('click', (e) => {
                e.preventDefault();

                document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
                document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));

                link.classList.add('active');
                const targetSection = document.getElementById(link.dataset.section);
                if (targetSection) {
                    targetSection.classList.add('active');
                }
            });
        }
    });
});

    </script>
</body>
</html>