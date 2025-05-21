<?php
session_start();
require_once 'db_config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

class MilkFeeding {
    private $conn;
    private $centerCode;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->centerCode = $_SESSION['center_code'] ?? die("Error: Center code not set in session!");
    }
    
    public function read($includeArchived = false, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM school_feeding_program WHERE center_code = :center_code";
        $countSql = "SELECT COUNT(*) as total FROM school_feeding_program WHERE center_code = :center_code";
        
        if (!$includeArchived) {
            $sql .= " AND is_archived = 0";
            $countSql .= " AND is_archived = 0";
        } else {
            $sql .= " AND is_archived = 1";
            $countSql .= " AND is_archived = 1";
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
    
    public function archive($id) {
        $stmt = $this->conn->prepare("UPDATE school_feeding_program SET is_archived = 1 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    public function restore($id) {
        $stmt = $this->conn->prepare("UPDATE school_feeding_program SET is_archived = 0 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    public function getStatusCounts() {
        $stmt = $this->conn->prepare("SELECT status, COUNT(*) as count 
            FROM school_feeding_program 
            WHERE is_archived = 0 AND center_code = :center_code 
            GROUP BY status");
        $stmt->execute([':center_code' => $this->centerCode]);
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
}

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

// Get data
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$archivedPage = isset($_GET['archived_page']) ? max(1, intval($_GET['archived_page'])) : 1;
$perPage = 10;

$activeEntries = $milkFeeding->read(false, $currentPage, $perPage);
$archivedEntries = $milkFeeding->read(true, $archivedPage, $perPage);
$statusCounts = $milkFeeding->getStatusCounts();
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


        .main-content {
            padding: 2rem;
            transition: margin-left 0.3s;
        }

        .table-container {
            max-height: 500px;
            overflow: auto;
            margin-bottom: 1rem;
            border: 1px solid #dee2e6;
            border-radius: var(--border-radius);
            background-color: #fff;
        }

        .table {
            margin-bottom: 0;
            border-collapse: collapse;
            min-width: 1200px;
            table-layout: fixed;
        }

        .table thead th {
            background-color: var(--pcc-light-blue);
            color: var(--pcc-dark-blue);
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            padding: 1rem 1.25rem;
            position: sticky;
            top: 0;
            z-index: 2;
            vertical-align: middle;
        }

        .table tbody td {
            padding: 1rem 1.25rem;
            vertical-align: middle;
            border-top: 1px solid #f1f1f1;
            line-height: 1.4;
            max-width: 200px;
            word-wrap: break-word;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            max-height: 3.6em;
        }

        .archived-row {
            background-color: rgba(220, 53, 69, 0.05) !important;
        }

        .status-badge {
            padding: 0.5rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
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

        .nav-tabs .nav-link.active {
            color: #0056b3;
            border-bottom: 3px solid #0056b3;
            background-color: transparent;
        }

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
    </style>
</head>
<body>
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
                        <li><a href="service.php" class="nav-link "><i class="fa-solid fa-arrow-left"></i>Back to quickfacts</a></li>
                        <li><a href="milkfeeding_dashboard.php" class="nav-link "><i class="fas fa-chart-line"></i>DSWD Program Report</a></li>
                        <li><a href="milk_feeding_dswd_report.php" class="nav-link "><i class="fas fa-file-alt"></i>DSWD Program Report</a></li>
                        <li><a href="milk_feeding_deped_report.php" class="nav-link active"><i class="fas fa-file-alt"></i> DepEd Program Report</a></li>
                        <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Content Header -->
                <div class="content-header">
                    <div class="content-title">
                        <h2>DepEd-School-based Feeding Program</h2>
                        <p>Track and manage milk feeding program implementations</p>
                    </div>
                </div>

                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs mb-3" id="mainTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab" aria-controls="active" aria-selected="true">
                            <i class="bi bi-list-check me-2"></i>Active Entries (<?= $activeEntries['total'] ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="archived-tab" data-bs-toggle="tab" data-bs-target="#archived" type="button" role="tab" aria-controls="archived" aria-selected="false">
                            <i class="bi bi-archive me-2"></i>Archived Entries (<?= $archivedEntries['total'] ?>)
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                       <!-- Active Entries Tab -->
                        <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
                            <div class="table-container">
                                <table class="table table-hover">
                                    <thead class="bg-primary text-white">
                                        <tr>
                                        <th>Fiscal Year</th>
                                        <th>Region</th>
                                        <th>Province</th>
                                        <th>SDO</th>
                                        <th>Beneficiaries</th>
                                        <th>Milk Type</th>
                                        <th>Milk Packs</th>
                                        <th>Price/Pack</th>
                                        <th>Supplier</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                        <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activeEntries['data'] as $entry): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($entry['fiscal_year']) ?></td>
                                            <td><?= htmlspecialchars($entry['region']) ?></td>
                                            <td><?= htmlspecialchars($entry['province']) ?></td>
                                            <td><?= htmlspecialchars($entry['sdo']) ?></td>
                                            <td><?= number_format($entry['beneficiaries']) ?></td>
                                            <td><?= htmlspecialchars($entry['milk_type']) ?></td>
                                            <td><?= number_format($entry['raw_milk_liters']) ?></td>
                                            <td><?= number_format($entry['milk_packs']) ?></td>
                                            <td>₱<?= number_format($entry['price_per_pack'], 2) ?></td>
                                            <td><?= htmlspecialchars($entry['supplier']) ?></td>
                                            <td>₱<?= number_format($entry['gross_income'], 2) ?></td>
                                            <td><?= date('M d, Y', strtotime($entry['delivery_date'])) ?></td>
                                            <td>₱<?= number_format($entry['status'], 2) ?></td>
                                            <td>₱<?= number_format($entry['remarks'], 2) ?></td>
                                            <td>
                                                <span class="status-badge status-<?= strtolower(str_replace(' ', '', $entry['status'])) ?>">
                                                    <?= htmlspecialchars($entry['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= $entry['remarks'] ?: 'N/A' ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary archive-btn"
                                                    data-id="<?= $entry['id'] ?>">
                                                    <i class="bi bi-archive"></i> Archive
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
        
                        
                        <!-- Pagination -->
                        <?php if ($activeEntries['totalPages'] > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <li class="page-item <?= $activeEntries['page'] == 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $activeEntries['page'] - 1 ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $activeEntries['totalPages']; $i++): ?>
                                    <li class="page-item <?= $i == $activeEntries['page'] ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $activeEntries['page'] == $activeEntries['totalPages'] ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $activeEntries['page'] + 1 ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>

                    <!-- Archived Entries Tab -->
                    <div class="tab-pane fade" id="archived" role="tabpanel" aria-labelledby="archived-tab">
                        <div class="table-container">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                       <th>Fiscal Year</th>
                                        <th>Region</th>
                                        <th>Province</th>
                                        <th>SDO</th>
                                        <th>Beneficiaries</th>
                                        <th>Milk Type</th>
                                        <th>Milk Packs</th>
                                        <th>Price/Pack</th>
                                        <th>Supplier</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($archivedEntries['data'])): ?>
                                        <?php foreach ($archivedEntries['data'] as $entry): ?>
                                        <tr class="archived-row">
                                            <td><?= htmlspecialchars($entry['fiscal_year']) ?></td>
                                            <td><?= htmlspecialchars($entry['region']) ?></td>
                                            <td><?= htmlspecialchars($entry['province']) ?></td>
                                            <td><?= htmlspecialchars($entry['sdo']) ?></td>
                                            <td><?= htmlspecialchars($entry['beneficiaries']) ?></td>
                                            <td><?= htmlspecialchars($entry['milk_type']) ?></td>
                                            <td><?= htmlspecialchars($entry['milk_packs']) ?></td>
                                            <td><?= htmlspecialchars($entry['price_per_liter']) ?></td>
                                            <td><?= htmlspecialchars($entry['supplier']) ?></td>
                                            <td><?= htmlspecialchars($entry['status']) ?></td>
                                            <td><?= htmlspecialchars($entry['Remarks']) ?></td>

                                            <td>
                                                <?php 
                                                $statusClass = 'status-' . strtolower(str_replace([' ', 'Yet'], ['', ''], $entry['status'])); 
                                                $statusIcon = match($entry['status']) {
                                                    'Completed' => '<i class="bi bi-check-circle-fill"></i>',
                                                    'On-going Milk Deliveries' => '<i class="bi bi-arrow-repeat"></i>',
                                                    'Partially Completed' => '<i class="bi bi-check2-all"></i>',
                                                    default => '<i class="bi bi-clock"></i>'
                                                };
                                                ?>
                                                <span class="status-badge <?= $statusClass ?>">
                                                    <?= $statusIcon ?>
                                                    <?= htmlspecialchars($entry['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary restore-btn" data-id="<?= $entry['id'] ?>">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="bi bi-database-fill-exclamation me-2"></i>No archived entries found
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($archivedEntries['totalPages'] > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <li class="page-item <?= $archivedEntries['page'] == 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?archived_page=<?= $archivedEntries['page'] - 1 ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $archivedEntries['totalPages']; $i++): ?>
                                    <li class="page-item <?= $i == $archivedEntries['page'] ? 'active' : '' ?>">
                                        <a class="page-link" href="?archived_page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $archivedEntries['page'] == $archivedEntries['totalPages'] ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?archived_page=<?= $archivedEntries['page'] + 1 ?>" aria-label="Next">
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.querySelectorAll('.archive-btn, .restore-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.classList.contains('archive-btn') ? 'archive' : 'restore';
                const entryId = this.dataset.id;
                
                Swal.fire({
                    title: `${action.charAt(0).toUpperCase() + action.slice(1)} Entry`,
                    text: `Are you sure you want to ${action} this entry?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: `Yes, ${action} it!`
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`?ajax=${action}&id=${entryId}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire(
                                        `${action.charAt(0).toUpperCase() + action.slice(1)}!`,
                                        `Entry has been ${action}d.`,
                                        'success'
                                    ).then(() => window.location.reload());
                                } else {
                                    Swal.fire('Error!', 'Operation failed', 'error');
                                }
                            })
                            .catch(error => Swal.fire('Error!', 'Operation failed', 'error'));
                    }
                });
            });
        });
    </script>
</body>
</html>