<?php
include('db_config.php');
session_start();

$centerCode = $_SESSION['center_code']; 

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $coop_type = trim($_POST['coop_type']);
        $partner_name = trim($_POST['partner_name']);
        $herd_code = trim($_POST['herd_code']);
        $contact_person = trim($_POST['contact_person']);
        $contact_number = trim($_POST['contact_number']);
        $barangay = trim($_POST['barangay']);
        $municipality = trim($_POST['municipality']);
        $province = trim($_POST['province']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        try {
            $query = "INSERT INTO partners (partner_name, herd_code, contact_person, contact_number, barangay, municipality, province, is_active, center_code, coop_type) 
                      VALUES (:partner_name, :herd_code, :contact_person, :contact_number, :barangay, :municipality, :province, :is_active, :center_code, :coop_type)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':partner_name', $partner_name);
            $stmt->bindParam(':herd_code', $herd_code);
            $stmt->bindParam(':contact_person', $contact_person);
            $stmt->bindParam(':contact_number', $contact_number);
            $stmt->bindParam(':barangay', $barangay);
            $stmt->bindParam(':municipality', $municipality);
            $stmt->bindParam(':province', $province);
            $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
            $stmt->bindParam(':center_code', $centerCode);
            $stmt->bindParam(':coop_type', $coop_type);
            $stmt->execute();
            
            $_SESSION['message'] = "Partner added successfully!";
            $_SESSION['message_type'] = "success";
            header("Location: ".$_SERVER['PHP_SELF']); 
            exit;
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error adding partner: " . $e->getMessage();
            $_SESSION['message_type'] = "danger";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        }
    } elseif (isset($_POST['edit'])) {
        $partner_id = $_POST['partner_id'];
        $coop_type = trim($_POST['coop_type']);
        $partner_name = trim($_POST['partner_name']);
        $herd_code = trim($_POST['herd_code']);
        $contact_person = trim($_POST['contact_person']);
        $contact_number = trim($_POST['contact_number']);
        $barangay = trim($_POST['barangay']);
        $municipality = trim($_POST['municipality']);
        $province = trim($_POST['province']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

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
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':partner_name', $partner_name);
            $stmt->bindParam(':herd_code', $herd_code);
            $stmt->bindParam(':contact_person', $contact_person);
            $stmt->bindParam(':contact_number', $contact_number);
            $stmt->bindParam(':barangay', $barangay);
            $stmt->bindParam(':municipality', $municipality);
            $stmt->bindParam(':province', $province);
            $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
            $stmt->bindParam(':coop_type', $coop_type);
            $stmt->bindParam(':partner_id', $partner_id);
            $stmt->bindParam(':center_code', $centerCode);
            $stmt->execute();
            
            $_SESSION['message'] = "Partner updated successfully!";
            $_SESSION['message_type'] = "success";
            header("Location: ".$_SERVER['PHP_SELF']); 
            exit;
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error updating partner: " . $e->getMessage();
            $_SESSION['message_type'] = "danger";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        }
    } elseif (isset($_POST['delete'])) {
        $partner_id = $_POST['partner_id'];
        try {
            $query = "DELETE FROM partners WHERE id = :partner_id AND center_code = :center_code";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':partner_id', $partner_id);
            $stmt->bindParam(':center_code', $centerCode);
            $stmt->execute();
            
            $_SESSION['message'] = "Partner deleted successfully!";
            $_SESSION['message_type'] = "success";
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error deleting partner: " . $e->getMessage();
            $_SESSION['message_type'] = "danger";
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    } elseif (isset($_POST['toggle_status'])) {
        $partner_id = $_POST['partner_id'];
        try {
            $query = "UPDATE partners SET is_active = NOT is_active WHERE id = :partner_id AND center_code = :center_code";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':partner_id', $partner_id);
            $stmt->bindParam(':center_code', $centerCode);
            $stmt->execute();
            
            $_SESSION['message'] = "Partner status updated!";
            $_SESSION['message_type'] = "success";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error updating status: " . $e->getMessage();
            $_SESSION['message_type'] = "danger";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        }
    } elseif (isset($_POST['export'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="partners_export_'.date('Y-m-d').'.csv"');
        $output = fopen("php://output", "w");
        fputcsv($output, ['ID', 'Partner Name', 'Partner Type', 'Herd Code', 'Contact Person', 'Contact Number', 'Barangay', 'Municipality', 'Province', 'Status']);
        foreach ($partners as $row) {
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
}

// Get sorting/filtering parameters
$sort = $_GET['sort'] ?? 'partner_name';
$order = $_GET['order'] ?? 'ASC';
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

// Validate sort parameters
$validColumns = ['partner_name', 'coop_type', 'herd_code', 'is_active'];
$sort = in_array($sort, $validColumns) ? $sort : 'partner_name';
$order = in_array(strtoupper($order), ['ASC', 'DESC']) ? strtoupper($order) : 'ASC';

// Build query
$query = "SELECT * FROM partners WHERE center_code = :center_code";
$params = [':center_code' => $centerCode];

if (!empty($search)) {
    $query .= " AND (partner_name LIKE :search OR herd_code LIKE :search OR contact_person LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($filter)) {
    $query .= " AND coop_type = :filter";
    $params[':filter'] = $filter;
}

$query .= " ORDER BY $sort $order";

try {
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['message'] = "Error fetching partners: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    $partners = [];
}
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
    <link rel="stylesheet" href="css/center.css">
    <link rel="stylesheet" href="css/partners.css">
</head>
<body>
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
                <h3 class="user-name"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></h3>
                <p class="user-email"><?= htmlspecialchars($_SESSION['user']['email']) ?></p>
            </div>
        </div>

        <ul>
            <li><a href="center_dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="services.php" class="nav-link"><i class="fas fa-concierge-bell"></i> 4DX Report</a></li>
            <li><a href="partners.php" class="nav-link active"><i class="fas fa-users"></i> Partners</a></li>
            <li><a href="settings.php" class="nav-link"><i class="fas fa-cogs"></i> Settings</a></li>
            <li><a href="logout.php" class="logout-btn" onclick="confirmLogout(event)"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
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

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] === 'success' ? 'success' : 'danger' ?>">
                <i class="fas fa-<?= $_SESSION['message_type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <span><?= $_SESSION['message'] ?></span>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <!-- Partners Card -->
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
                <!-- Search and Filter Bar -->
                <div class="search-filter-bar">
                    <form method="GET" class="d-flex gap-2" style="flex-grow: 1;">
                        <div class="input-group" style="max-width: 400px;">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search partners..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="?" class="btn btn-outline-danger">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="filter-buttons">
                        <span style="font-weight: 500;">Filter by type:</span>
                        <a href="?<?= http_build_query(['filter' => '', 'search' => $search, 'sort' => $sort, 'order' => $order]) ?>" 
                           class="filter-btn <?= empty($filter) ? 'active' : '' ?>">All</a>
                        <?php 
                        $types = ['Cooperations', 'Associations', 'LGU', 'SCU', 'Family_Module', 'Corporation'];
                        foreach ($types as $type): ?>
                            <a href="?<?= http_build_query(['filter' => $type, 'search' => $search, 'sort' => $sort, 'order' => $order]) ?>" 
                               class="filter-btn <?= $filter === $type ? 'active' : '' ?>">
                                <?= str_replace('_', ' ', $type) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Partners Table -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="sortable-header" data-sort="partner_name">
                                    Partner Name
                                    <?php if ($sort === 'partner_name'): ?>
                                    <span class="sort-indicator">
                                        <?= $order === 'ASC' ? '↑' : '↓' ?>
                                    </span>
                                    <?php endif; ?>
                                </th>
                                <th>Partner Type</th>
                                <th class="sortable-header" data-sort="herd_code">
                                    Herd Code
                                    <?php if ($sort === 'herd_code'): ?>
                                    <span class="sort-indicator">
                                        <?= $order === 'ASC' ? '↑' : '↓' ?>
                                    </span>
                                    <?php endif; ?>
                                </th>
                                <th>Contact</th>
                                <th>Location</th>
                                <th class="sortable-header" data-sort="is_active">
                                    Status
                                    <?php if ($sort === 'is_active'): ?>
                                    <span class="sort-indicator">
                                        <?= $order === 'ASC' ? '↑' : '↓' ?>
                                    </span>
                                    <?php endif; ?>
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
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
                                            <form method="POST" class="d-inline-block" onclick="event.stopPropagation()">
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
        </div>
    </div>

    <!-- Create Partner Modal -->
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
                            <option value="Cooperations">Cooperations</option>
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

    <!-- Edit Partner Modal -->
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
                            <option value="Cooperations">Cooperations</option>
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
        // DOM Elements
        const createBtn = document.getElementById('createBtn');
        const createModal = document.getElementById('createModal');
        const editModal = document.getElementById('editModal');
        const modalCloses = document.querySelectorAll('.modal-close');
        const editBtns = document.querySelectorAll('.edit-btn');

        // Show Create Modal
        createBtn.addEventListener('click', () => {
            createModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        });

        // Show Edit Modal
        editBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent row click when clicking edit button
                
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

        // Close Modals
        modalCloses.forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.classList.remove('show');
                });
                document.body.style.overflow = 'auto';
            });
        });

        // Close Modals when clicking cancel button
        document.querySelectorAll('.modal-cancel').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.classList.remove('show');
                });
                document.body.style.overflow = 'auto';
            });
        });     
           
        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        });

        // Sorting functionality
        document.querySelectorAll('.sortable-header').forEach(header => {
            header.addEventListener('click', () => {
                const sortField = header.dataset.sort;
                const currentOrder = '<?= $order ?>';
                const currentSort = '<?= $sort ?>';
                let newOrder = 'ASC';

                if (sortField === currentSort) {
                    newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
                }

                const url = new URL(window.location.href);
                url.searchParams.set('sort', sortField);
                url.searchParams.set('order', newOrder);
                // Preserve search and filter
                if ('<?= $search ?>') url.searchParams.set('search', '<?= $search ?>');
                if ('<?= $filter ?>') url.searchParams.set('filter', '<?= $filter ?>');
                window.location.href = url.toString();
            });
        });

        // Handle row clicking
        document.addEventListener("DOMContentLoaded", function() {
            const rows = document.querySelectorAll(".clickable-row");

            rows.forEach(row => {
                row.addEventListener("click", function(e) {
                    // Avoid clicking if action buttons are clicked
                    if (e.target.closest(".action-buttons") || e.target.closest('form')) return;

                    const url = this.getAttribute("data-href");
                    if (url) {
                        window.location.href = url;
                    }
                });
            });
        });

        // Responsive sidebar toggle (for mobile)
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
        
        // Show/hide sidebar toggle based on screen size
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

        // Confirm logout
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
    </script>
</body>
</html>