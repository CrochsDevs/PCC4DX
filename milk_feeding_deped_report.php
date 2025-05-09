<?php
session_start();
require_once 'db_config.php'; // Contains database connection details

class MilkFeeding {
    private $conn;
    private $centerCode;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->centerCode = $_SESSION['center_code'] ?? '';
    }

    public function create($data) {
        $stmt = $this->conn->prepare("INSERT INTO school_feeding_program 
            (fiscal_year, school_division, status, remarks, center_code, is_archived) 
            VALUES (:fiscal_year, :school_division, :status, :remarks, :center_code, 0)");
        
        return $stmt->execute([
            ':center_code' => $this->centerCode,
            ':fiscal_year' => $data['fiscal_year'],
            ':region' => $data['region'],
            ':school_division' => $data['school_division'],
            ':status' => $data['status'],
            ':remarks' => $data['remarks'],
        ]);
        
    }
    
    public function read($includeArchived = false, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM school_feeding_program WHERE center_code = :center_code";
        $countSql = "SELECT COUNT(*) as total FROM school_feeding_program WHERE center_code = :center_code";
        
        if (!$includeArchived) {
            $sql .= " AND is_archived = 0";
            $countSql .= " AND is_archived = 0";
        }
        
        $sql .= " LIMIT :offset, :perPage";
        
        // Get total count
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute([':center_code' => $this->centerCode]);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated results
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':center_code', $this->centerCode);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    public function update($id, $data) {
        $stmt = $this->conn->prepare("UPDATE school_feeding_program SET 
            fiscal_year = :fiscal_year, 
            school_division = :school_division, 
            status = :status, 
            remarks = :remarks 
            WHERE id = :id");
    
        return $stmt->execute([
            ':fiscal_year' => $data['fiscal_year'],
            ':school_division' => $data['school_division'],
            ':status' => $data['status'],
            ':remarks' => $data['remarks'],
            ':id' => $id
        ]);
    }
    
    public function archive($id) {
        $stmt = $this->conn->prepare("UPDATE school_feeding_program SET is_archived = 1 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    public function restore($id) {
        $stmt = $this->conn->prepare("UPDATE school_feeding_program SET is_archived = 0 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    public function getStatusCounts() {
        $stmt = $this->conn->prepare("SELECT status, COUNT(*) as count FROM school_feeding_program WHERE is_archived = 0 GROUP BY status");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $counts = [
            'Completed' => 0,
            'On-going Milk Deliveries' => 0,
            'Partially Completed' => 0,
            'Not Yet Started' => 0
        ];
        
        foreach ($results as $row) {
            $counts[$row['status']] = $row['count'];
        }
        
        return $counts;
    }
    
    public function getSchoolDivisions() {
        $stmt = $this->conn->prepare("SELECT DISTINCT school_division FROM school_feeding_program ORDER BY school_division");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}

// Initialize MilkFeeding system
$milkFeeding = new MilkFeeding($conn);

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_GET['ajax'] === 'archive' && isset($_GET['id'])) {
            $success = $milkFeeding->archive($_GET['id']);
            echo json_encode(['success' => $success]);
            exit;
        }
        
        if ($_GET['ajax'] === 'restore' && isset($_GET['id'])) {
            $success = $milkFeeding->restore($_GET['id']);
            echo json_encode(['success' => $success]);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Handle form submissions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['confirm']) && $_POST['confirm'] === 'true') {
            $data = [
                'fiscal_year' => htmlspecialchars($_POST['fiscal_year']),
                'school_division' => htmlspecialchars($_POST['school_division']),
                'status' => htmlspecialchars($_POST['status']),
                'remarks' => htmlspecialchars($_POST['remarks'])
            ];

            if (!empty($_POST['id'])) {
                $milkFeeding->update($_POST['id'], $data);
                $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>Entry updated successfully!
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
            } else {
                $milkFeeding->create($data);
                $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>New entry added successfully!
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
            }
        }
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Error: '.$e->getMessage().'
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
    }
}

// Get paginated entries and status counts
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$entries = $milkFeeding->read(false, $currentPage, $perPage);
$statusCounts = $milkFeeding->getStatusCounts();
$schoolDivisions = $milkFeeding->getSchoolDivisions();

$showArchived = isset($_GET['show_archived']) && $_GET['show_archived'] === 'true';
if ($showArchived) {
    $archivedPage = isset($_GET['archived_page']) ? max(1, intval($_GET['archived_page'])) : 1;
    $archivedEntries = $milkFeeding->read(true, $archivedPage, $perPage);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Milk Feeding Program Monitoring System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --pcc-blue: #0056b3;
            --pcc-dark-blue: #003366;
            --pcc-light-blue: #e6f0ff;
            --pcc-orange: #ff6b00;
            --pcc-light-orange: #fff3e6;
            --pcc-green: #28a745;
            --pcc-light-green: #e6f7eb;
            --pcc-red: #dc3545;
            --pcc-light-red: #f8d7da;
            --pcc-purple: #6f42c1;
            --pcc-light-purple: #f3e8ff;
            --secondary: #6c757d;
            --light: #f8f9fa;
            --dark: #343a40;
            --border-radius: 0.375rem;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            margin-left: 280px;
            color: #495057;
            transition: margin-left 0.3s;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 280px;
            background: linear-gradient(180deg, #0056b3 0%, #3a7fc5 100%);
            color: white;
            padding: 2rem 1.5rem;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            font-weight: 600;
            font-size: 1.5rem;
            letter-spacing: 0.5px;
        }

        .sidebar ul {
            list-style: none;
            padding-left: 0;
        }

        .sidebar li {
            margin-bottom: 1.25rem;
            transition: transform 0.2s;
        }

        .sidebar li:hover {
            transform: translateX(5px);
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 0.9rem 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar a i {
            margin-right: 1rem;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .sidebar a.active {
            background: #ffc107;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .user-profile {
            text-align: center;
            padding: 1.5rem 1rem;
            margin-bottom: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .profile-picture {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--secondary);
        }
       
        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background-color: var(--pcc-red);
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            margin-top: 2rem;
        }

        .logout-btn:hover {
            background-color: #c53030;
            transform: translateY(-2px);
        }   

        /* Main Content */
        .main-content {
            padding: 2rem;
            transition: margin-left 0.3s;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            border-top: 4px solid transparent;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card.completed {
            border-top-color: var(--pcc-green);
        }

        .stat-card.ongoing {
            border-top-color: var(--info);
        }

        .stat-card.partially {
            border-top-color: var(--pcc-purple);
        }

        .stat-card.not-started {
            border-top-color: var(--secondary);
        }

        .stat-card .stat-title {
            font-size: 0.9rem;
            color: var(--secondary);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .stat-card .stat-icon {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-card.completed .stat-icon {
            color: var(--pcc-green);
        }

        .stat-card.ongoing .stat-icon {
            color: var(--info);
        }

        .stat-card.partially .stat-icon {
            color: var(--pcc-purple);
        }

        .stat-card.not-started .stat-icon {
            color: var(--secondary);
        }

        /* Content Header */
        .content-header {
            background-color: white;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid var(--pcc-orange);
        }

        .content-title h2 {
            color: var(--pcc-dark-blue);
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .content-title p {
            color: var(--secondary);
            margin-bottom: 0;
            font-size: 0.95rem;
        }

        /* Data Card */
        .data-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            border: none;
            overflow: hidden;
        }

        .data-card-header {
            background-color: var(--pcc-light-blue);
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            color: var(--pcc-dark-blue);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .data-card-header h3 {
            font-weight: 600;
            margin-bottom: 0;
            font-size: 1.25rem;
        }

        .data-card-body {
            padding: 1.5rem;
        }

        /* Table Styles */
        .table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead th {
            background-color: var(--pcc-light-blue);
            color: var(--pcc-dark-blue);
            font-weight: 600;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 1.25rem;
            position: sticky;
            top: 0;
        }

        .table tbody td {
            padding: 1rem 1.25rem;
            vertical-align: middle;
            border-top: 1px solid #f1f1f1;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 86, 179, 0.03);
        }

        .archived-row {
            background-color: rgba(220, 53, 69, 0.05) !important;
        }

        /* Status Badges */
        .status-badge {
            padding: 0.5rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge i {
            font-size: 0.75rem;
        }

        .status-completed {
            background-color: var(--pcc-light-green);
            color: var(--pcc-green);
        }

        .status-ongoingmilkdeliveries {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info);
        }

        .status-partiallycompleted {
            background-color: var(--pcc-light-purple);
            color: var(--pcc-purple);
        }

        .status-notyetstarted {
            background-color: rgba(108, 117, 125, 0.1);
            color: var(--secondary);
        }

        /* Buttons */
        .btn {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.35rem 0.75rem;
            font-size: 0.85rem;
        }

        .btn-primary {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .btn-primary:hover {
            background-color: #004494;
            border-color: #004494;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-outline-primary {
            color:  #004494;
            border-color:  #004494;
        }

        .btn-outline-primary:hover {
            background-color: var(--pcc-light-orange);
            color: var(--pcc-orange);
            border-color: var(--pcc-orange);
        }

        .btn-outline-danger {
            color: var(--danger);
            border-color: var(--danger);
        }

        .btn-outline-danger:hover {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border-color: var(--danger);
        }

        .btn-outline-secondary {
            color: var(--secondary);
            border-color: var(--secondary);
        }

        .btn-outline-secondary:hover {
            background-color: rgba(108, 117, 125, 0.1);
            color: var(--secondary);
            border-color: var(--secondary);
        }

        /* Modal */
        .modal-header {
            background-color: var(--pcc-dark-blue);
            color: white;
            border-bottom: none;
            padding: 1.5rem;
        }

        .modal-title {
            font-weight: 600;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid #e9ecef;
            padding: 1.25rem 1.5rem;
        }

        /* Form Elements */
        .form-control, .form-select {
            border-radius: var(--border-radius);
            border: 1px solid #ced4da;
            padding: 0.625rem 0.875rem;
            transition: all 0.2s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--pcc-orange);
            box-shadow: 0 0 0 0.25rem rgba(255, 107, 0, 0.25);
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        /* Alerts */
        .alert {
            border-radius: var(--border-radius);
            padding: 1rem 1.25rem;
            border: none;
        }

        .alert-dismissible .btn-close {
            padding: 1rem;
        }

        /* Badges */
        .badge {
            font-weight: 500;
            padding: 0.5em 0.75em;
        }

        /* Tabs */
        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
        }

        .nav-tabs .nav-link {
            color: var(--secondary);
            font-weight: 500;
            border: none;
            padding: 0.75rem 1.25rem;
            margin-right: 0.5rem;
        }

        .nav-tabs .nav-link.active {
            color: #0056b3;
            border-bottom: 3px solid #0056b3;
            background-color: transparent;
        }

        .nav-tabs .nav-link:hover {
            border-color: transparent;
            color: var(--pcc-dark-blue);
        }

        /* Pagination */
        .pagination {
            justify-content: center;
            margin-top: 1.5rem;
        }

        .page-item.active .page-link {
            background-color: var(--pcc-blue);
            border-color: var(--pcc-blue);
        }

        .page-link {
            color: var(--pcc-blue);
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            body {
                margin-left: 0;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                padding: 1.5rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Loading Spinner */
        .spinner-container {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>
<body>
    <!-- Loading Spinner -->
    <div class="spinner-container" id="loadingSpinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="user-profile">
                    <div class="profile-picture">
                        <?php if (!empty($_SESSION['user']['profile_image'])): ?>
                            <img src="uploads/profile_images/<?= htmlspecialchars($_SESSION['user']['profile_image']) ?>" alt="Profile Picture">
                        <?php else: ?>
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user']['full_name']) ?>&background=0056b3&color=fff&size=128" alt="Profile Picture">
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h5 class="user-name"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></h5>
                        <p class="user-email"><?= htmlspecialchars($_SESSION['user']['email']) ?></p>
                    </div>
                </div>

                <nav>
                    <ul>
                        <li><a href="services.php" class="nav-link"><i class="fas fa-arrow-left"></i> Back to Quickfacts</a></li>
                        <li><a href="milkfeeding_dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                        <li><a href="milk_feeding_dswd_report.php" class="nav-link"><i class="fas fa-handshake"></i> DSWD Program Report</a></li>
                        <li><a href="milk_feeding_deped_report.php" class="nav-link active"><i class="fas fa-file-alt"></i> DepEd Program Report</a></li>
                        <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Content Header -->
                <div class="content-header fade-in">
                    <div class="content-title">
                        <h2>DepEd-School-based Feeding Program</h2>
                        <p>Track and manage milk feeding program implementations</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#entryModal">
                        <i class="bi bi-plus-lg"></i> Add New Entry
                    </button>
                </div>

                <?= $message ?>

                <!-- Stats Cards -->
                <div class="stats-container fade-in">
                    <div class="stat-card completed">
                        <div class="stat-icon">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div class="stat-title">Completed</div>
                        <div class="stat-value"><?= $statusCounts['Completed'] ?></div>
                        <div class="stat-desc">Programs successfully completed</div>
                    </div>
                    
                    <div class="stat-card ongoing">
                        <div class="stat-icon">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                        <div class="stat-title">On-going Milk Deliveries</div>
                        <div class="stat-value"><?= $statusCounts['On-going Milk Deliveries'] ?></div>
                        <div class="stat-desc">Programs currently in progress</div>
                    </div>
                
                    <div class="stat-card not-started">
                        <div class="stat-icon">
                            <i class="bi bi-clock"></i>
                        </div>
                        <div class="stat-title">Not Yet Started</div>
                        <div class="stat-value"><?= $statusCounts['Not Yet Started'] ?></div>
                        <div class="stat-desc">Programs scheduled to start</div>
                    </div>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-3 fade-in" id="entriesTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab">
                            <i class="bi bi-list-check me-1"></i> Active Entries
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="archived-tab" data-bs-toggle="tab" data-bs-target="#archived" type="button" role="tab">
                            <i class="bi bi-archive me-1"></i> Archived Entries
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="entriesTabContent">
                    <!-- Active Entries Tab -->
                    <div class="tab-pane fade show active" id="active" role="tabpanel">
                        <div class="data-card fade-in">
                            <div class="data-card-header">
                                <h3><i class="bi bi-table me-2"></i>Active Program Entries</h3>
                                <span class="badge bg-primary"><?= $entries['total'] ?> records</span>
                            </div>
                            <div class="data-card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Fiscal Year</th>
                                                <th>School Division</th>
                                                <th>Status</th>
                                                <th>Remarks</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($entries['data'] as $entry): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($entry['fiscal_year']) ?></td>
                                                <td><?= htmlspecialchars($entry['school_division']) ?></td>
                                                <td>
                                                    <?php 
                                                        $statusClass = 'status-' . strtolower(str_replace([' ', 'Yet'], ['', ''], $entry['status'])); 
                                                        $statusIcon = '';
                                                        if ($entry['status'] === 'Completed') {
                                                            $statusIcon = '<i class="bi bi-check-circle-fill"></i>';
                                                        } elseif ($entry['status'] === 'On-going Milk Deliveries') {
                                                            $statusIcon = '<i class="bi bi-arrow-repeat"></i>';
                                                        } elseif ($entry['status'] === 'Partially Completed') {
                                                            $statusIcon = '<i class="bi bi-check2-all"></i>';
                                                        } else {
                                                            $statusIcon = '<i class="bi bi-clock"></i>';
                                                        }
                                                    ?>
                                                    <span class="status-badge <?= $statusClass ?>">
                                                        <?= $statusIcon ?>
                                                        <?= htmlspecialchars($entry['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= $entry['remarks'] ? htmlspecialchars($entry['remarks']) : '<span class="text-muted">N/A</span>' ?></td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-sm btn-outline-primary edit-btn"
                                                            data-id="<?= $entry['id'] ?>"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#entryModal">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-secondary archive-btn"
                                                            data-id="<?= $entry['id'] ?>">
                                                            <i class="bi bi-archive"></i> Archive
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($entries['data'])): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    <i class="bi bi-database-fill-exclamation me-2"></i>No active program entries found
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                    
                                    <!-- Pagination -->
                                    <?php if ($entries['totalPages'] > 1): ?>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination">
                                            <li class="page-item <?= $entries['page'] == 1 ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=<?= $entries['page'] - 1 ?>" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                            <?php for ($i = 1; $i <= $entries['totalPages']; $i++): ?>
                                                <li class="page-item <?= $i == $entries['page'] ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            <li class="page-item <?= $entries['page'] == $entries['totalPages'] ? 'disabled' : '' ?>">
                                                <a class="page-link" href="?page=<?= $entries['page'] + 1 ?>" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Archived Entries Tab -->
                    <div class="tab-pane fade" id="archived" role="tabpanel">
                        <div class="data-card fade-in">
                            <div class="data-card-header">
                                <h3><i class="bi bi-archive me-2"></i>Archived Program Entries</h3>
                                <a href="?show_archived=true" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                </a>
                            </div>
                            <div class="data-card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Fiscal Year</th>
                                                <th>School Division</th>
                                                <th>Status</th>
                                                <th>Remarks</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($showArchived && !empty($archivedEntries['data'])): ?>
                                                <?php foreach ($archivedEntries['data'] as $entry): ?>
                                                    <?php if ($entry['is_archived'] == 1): ?>
                                                    <tr class="archived-row">
                                                        <td><?= htmlspecialchars($entry['fiscal_year']) ?></td>
                                                        <td><?= htmlspecialchars($entry['school_division']) ?></td>
                                                        <td>
                                                            <?php 
                                                                $statusClass = 'status-' . strtolower(str_replace([' ', 'Yet'], ['', ''], $entry['status'])); 
                                                                $statusIcon = '';
                                                                if ($entry['status'] === 'Completed') {
                                                                    $statusIcon = '<i class="bi bi-check-circle-fill"></i>';
                                                                } elseif ($entry['status'] === 'On-going Milk Deliveries') {
                                                                    $statusIcon = '<i class="bi bi-arrow-repeat"></i>';
                                                                } elseif ($entry['status'] === 'Partially Completed') {
                                                                    $statusIcon = '<i class="bi bi-check2-all"></i>';
                                                                } else {
                                                                    $statusIcon = '<i class="bi bi-clock"></i>';
                                                                }
                                                            ?>
                                                            <span class="status-badge <?= $statusClass ?>">
                                                                <?= $statusIcon ?>
                                                                <?= htmlspecialchars($entry['status']) ?>
                                                            </span>
                                                        </td>
                                                        <td><?= $entry['remarks'] ? htmlspecialchars($entry['remarks']) : '<span class="text-muted">N/A</span>' ?></td>
                                                        <td>
                                                            <div class="d-flex gap-2">
                                                                <button class="btn btn-sm btn-outline-primary restore-btn"
                                                                    data-id="<?= $entry['id'] ?>">
                                                                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                
                                                <!-- Pagination for Archived Entries -->
                                                <?php if ($archivedEntries['totalPages'] > 1): ?>
                                                <tr>
                                                    <td colspan="5">
                                                        <nav aria-label="Page navigation">
                                                            <ul class="pagination">
                                                                <li class="page-item <?= $archivedEntries['page'] == 1 ? 'disabled' : '' ?>">
                                                                    <a class="page-link" href="?show_archived=true&archived_page=<?= $archivedEntries['page'] - 1 ?>" aria-label="Previous">
                                                                        <span aria-hidden="true">&laquo;</span>
                                                                    </a>
                                                                </li>
                                                                <?php for ($i = 1; $i <= $archivedEntries['totalPages']; $i++): ?>
                                                                    <li class="page-item <?= $i == $archivedEntries['page'] ? 'active' : '' ?>">
                                                                        <a class="page-link" href="?show_archived=true&archived_page=<?= $i ?>"><?= $i ?></a>
                                                                    </li>
                                                                <?php endfor; ?>
                                                                <li class="page-item <?= $archivedEntries['page'] == $archivedEntries['totalPages'] ? 'disabled' : '' ?>">
                                                                    <a class="page-link" href="?show_archived=true&archived_page=<?= $archivedEntries['page'] + 1 ?>" aria-label="Next">
                                                                        <span aria-hidden="true">&raquo;</span>
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </nav>
                                                    </td>
                                                </tr>
                                                <?php endif; ?>
                                                
                                            <?php elseif ($showArchived && empty($archivedEntries['data'])): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">
                                                        <i class="bi bi-database-fill-exclamation me-2"></i>No archived program entries found
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">
                                                        <i class="bi bi-info-circle me-2"></i>Click the "Refresh" button to view archived entries
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CRUD Modal -->
    <div class="modal fade" id="entryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="entryForm">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-file-earmark-text me-2"></i>Program Entry Form</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="entryId">
                        <input type="hidden" name="confirm" id="confirmSubmit" value="false">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Fiscal Year <span class="text-danger">*</span></label>
                                <input type="number" name="fiscal_year" id="fiscalYear" class="form-control" 
                                    min="2000" max="2050" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">School Division <span class="text-danger">*</span></label>
                                <select name="school_division" id="schoolDivision" class="form-select" required>
                                    <option value="">Select School Division</option>
                                    <?php foreach ($schoolDivisions as $division): ?>
                                        <option value="<?= htmlspecialchars($division) ?>"><?= htmlspecialchars($division) ?></option>
                                    <?php endforeach; ?>
                                    <option value="Other">Other (Please specify)</option>
                                </select>
                                <input type="text" name="custom_school_division" id="customSchoolDivision" 
                                    class="form-control mt-2" style="display: none;" 
                                    placeholder="Enter school division name">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select" required>
                                    <option value="Completed">Completed</option>
                                    <option value="On-going Milk Deliveries">On-going Milk Deliveries</option>
                                    <option value="Not Yet Started">Not Yet Started</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" id="remarks" class="form-control" rows="3" 
                                    placeholder="Additional notes or comments..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-1"></i> Cancel
                        </button>
                        <button type="button" class="btn btn-primary" id="submitBtn">
                            <i class="bi bi-save me-1"></i> Save Entry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Confirm Submission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to submit this entry?</p>
                    <div class="alert alert-info mt-3">
                        <h6 class="fw-bold">Entry Preview:</h6>
                        <div><strong>Fiscal Year:</strong> <span id="previewFiscalYear"></span></div>
                        <div><strong>School Division:</strong> <span id="previewSchoolDivision"></span></div>
                        <div><strong>Status:</strong> <span id="previewStatus"></span></div>
                        <div><strong>Remarks:</strong> <span id="previewRemarks"></span></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmSubmitBtn">
                        <i class="bi bi-check-lg me-1"></i> Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Handle edit button clicks
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const row = btn.closest('tr');
                const cells = row.children;
                
                document.getElementById('entryId').value = btn.dataset.id;
                document.getElementById('fiscalYear').value = cells[0].textContent;
                
                // Set school division
                const schoolDivision = cells[1].textContent;
                const schoolDivisionSelect = document.getElementById('schoolDivision');
                const options = Array.from(schoolDivisionSelect.options);
                const existingOption = options.find(opt => opt.text === schoolDivision);
                
                if (existingOption) {
                    schoolDivisionSelect.value = schoolDivision;
                    document.getElementById('customSchoolDivision').style.display = 'none';
                } else {
                    schoolDivisionSelect.value = 'Other';
                    document.getElementById('customSchoolDivision').style.display = 'block';
                    document.getElementById('customSchoolDivision').value = schoolDivision;
                }
                
                // Extract status text (removing icon if present)
                const statusText = cells[2].querySelector('.status-badge').textContent.trim().replace(/\s+/g, ' ');
                document.getElementById('status').value = statusText;
                
                // Handle remarks (check if it's "N/A" span or regular text)
                const remarksCell = cells[3];
                const remarksText = remarksCell.querySelector('span.text-muted') ? '' : remarksCell.textContent.trim();
                document.getElementById('remarks').value = remarksText;
            });
        });

        // Clear form when adding new entry
        document.getElementById('entryModal').addEventListener('show.bs.modal', event => {
            if (!event.relatedTarget) {
                document.getElementById('entryId').value = '';
                document.getElementById('entryForm').reset();
                document.getElementById('confirmSubmit').value = 'false';
                document.getElementById('customSchoolDivision').style.display = 'none';
            }
        });

        // Handle form submission with confirmation
        document.getElementById('submitBtn').addEventListener('click', function() {
            // Update preview
            document.getElementById('previewFiscalYear').textContent = document.getElementById('fiscalYear').value;
            
            // Handle school division preview
            const schoolDivisionSelect = document.getElementById('schoolDivision');
            let schoolDivision = schoolDivisionSelect.value;
            if (schoolDivision === 'Other') {
                schoolDivision = document.getElementById('customSchoolDivision').value;
            }
            document.getElementById('previewSchoolDivision').textContent = schoolDivision;
            
            document.getElementById('previewStatus').textContent = document.getElementById('status').value;
            document.getElementById('previewRemarks').textContent = document.getElementById('remarks').value || 'N/A';
            
            // Show confirmation modal
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
            confirmModal.show();
        });

        // Final confirmation
        document.getElementById('confirmSubmitBtn').addEventListener('click', function() {
            document.getElementById('confirmSubmit').value = 'true';
            document.getElementById('entryForm').submit();
        });

        // Toggle custom school division input
        document.getElementById('schoolDivision').addEventListener('change', function() {
            const customDivisionInput = document.getElementById('customSchoolDivision');
            customDivisionInput.style.display = this.value === 'Other' ? 'block' : 'none';
            if (this.value !== 'Other') {
                customDivisionInput.value = '';
            }
        });

        // Handle archive button clicks with AJAX
        document.querySelectorAll('.archive-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const entryId = this.dataset.id;
                
                Swal.fire({
                    title: 'Archive Entry',
                    text: 'Are you sure you want to archive this entry?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, archive it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        showLoading();
                        
                        fetch(`?ajax=archive&id=${entryId}`)
                            .then(response => response.json())
                            .then(data => {
                                hideLoading();
                                
                                if (data.success) {
                                    Swal.fire(
                                        'Archived!',
                                        'The entry has been archived.',
                                        'success'
                                    ).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire(
                                        'Error!',
                                        'Failed to archive the entry.',
                                        'error'
                                    );
                                }
                            })
                            .catch(error => {
                                hideLoading();
                                Swal.fire(
                                    'Error!',
                                    'An error occurred while archiving the entry.',
                                    'error'
                                );
                            });
                    }
                });
            });
        });

        // Handle restore button clicks with AJAX
        document.querySelectorAll('.restore-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const entryId = this.dataset.id;
                
                Swal.fire({
                    title: 'Restore Entry',
                    text: 'Are you sure you want to restore this entry?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, restore it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        showLoading();
                        
                        fetch(`?ajax=restore&id=${entryId}`)
                            .then(response => response.json())
                            .then(data => {
                                hideLoading();
                                
                                if (data.success) {
                                    Swal.fire(
                                        'Restored!',
                                        'The entry has been restored.',
                                        'success'
                                    ).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire(
                                        'Error!',
                                        'Failed to restore the entry.',
                                        'error'
                                    );
                                }
                            })
                            .catch(error => {
                                hideLoading();
                                Swal.fire(
                                    'Error!',
                                    'An error occurred while restoring the entry.',
                                    'error'
                                );
                            });
                    }
                });
            });
        });

        // Loading spinner functions
        function showLoading() {
            document.getElementById('loadingSpinner').style.display = 'flex';
        }
        
        function hideLoading() {
            document.getElementById('loadingSpinner').style.display = 'none';
        }
    </script>
</body>
</html>