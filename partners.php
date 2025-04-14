<?php
include('db_config.php');
session_start();

if (!isset($_SESSION['center_id'])) {
    $_SESSION['center_id'] = 1; // TEMP fallback
}
$center_id = $_SESSION['center_id'];

// Fetch partners
try {
    $query = "SELECT * FROM partners WHERE center_id = :center_id ORDER BY partner_name ASC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':center_id', $center_id);
    $stmt->execute();
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Fetch Error: " . $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $partner_name = trim($_POST['partner_name']);
        $herd_code = trim($_POST['herd_code']);
        $contact_person = trim($_POST['contact_person']);
        $contact_number = trim($_POST['contact_number']);
        $barangay = trim($_POST['barangay']);
        $municipality = trim($_POST['municipality']);
        $province = trim($_POST['province']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        try {
            $query = "INSERT INTO partners (partner_name, herd_code, contact_person, contact_number, barangay, municipality, province, is_active, center_id) 
                      VALUES (:partner_name, :herd_code, :contact_person, :contact_number, :barangay, :municipality, :province, :is_active, :center_id)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':partner_name', $partner_name);
            $stmt->bindParam(':herd_code', $herd_code);
            $stmt->bindParam(':contact_person', $contact_person);
            $stmt->bindParam(':contact_number', $contact_number);
            $stmt->bindParam(':barangay', $barangay);
            $stmt->bindParam(':municipality', $municipality);
            $stmt->bindParam(':province', $province);
            $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
            $stmt->bindParam(':center_id', $center_id);
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
                      is_active = :is_active 
                      WHERE id = :partner_id AND center_id = :center_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':partner_name', $partner_name);
            $stmt->bindParam(':herd_code', $herd_code);
            $stmt->bindParam(':contact_person', $contact_person);
            $stmt->bindParam(':contact_number', $contact_number);
            $stmt->bindParam(':barangay', $barangay);
            $stmt->bindParam(':municipality', $municipality);
            $stmt->bindParam(':province', $province);
            $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
            $stmt->bindParam(':partner_id', $partner_id);
            $stmt->bindParam(':center_id', $center_id);
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
            $query = "DELETE FROM partners WHERE id = :partner_id AND center_id = :center_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':partner_id', $partner_id);
            $stmt->bindParam(':center_id', $center_id);
            $stmt->execute();
            
            $_SESSION['message'] = "Partner deleted successfully!";
            $_SESSION['message_type'] = "success";
            header("Location: ".$_SERVER['PHP_SELF']); 
            exit;
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error deleting partner: " . $e->getMessage();
            $_SESSION['message_type'] = "danger";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        }
    } elseif (isset($_POST['toggle_status'])) {
        $partner_id = $_POST['partner_id'];
        try {
            // Get current status
            $query = "SELECT is_active FROM partners WHERE id = :partner_id AND center_id = :center_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':partner_id', $partner_id);
            $stmt->bindParam(':center_id', $center_id);
            $stmt->execute();
            $current_status = $stmt->fetchColumn();
            
            // Toggle status
            $new_status = $current_status ? 0 : 1;
            
            $query = "UPDATE partners SET is_active = :is_active WHERE id = :partner_id AND center_id = :center_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':is_active', $new_status, PDO::PARAM_INT);
            $stmt->bindParam(':partner_id', $partner_id);
            $stmt->bindParam(':center_id', $center_id);
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
        fputcsv($output, ['ID', 'Partner Name', 'Herd Code', 'Contact Person', 'Contact Number', 'Barangay', 'Municipality', 'Province', 'Status']);
        foreach ($partners as $row) {
            fputcsv($output, [
                $row['id'],
                $row['partner_name'],
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cooperative Partners Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --primary-light: #ebf0ff;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --white: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius-sm: 0.25rem;
            --radius: 0.5rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --radius-xl: 1.5rem;
            --transition: all 0.2s ease-in-out;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
        }

        .container {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 2rem 1.5rem;
            box-shadow: var(--shadow-md);
            position: relative;
            z-index: 10;
        }

        .sidebar-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 1rem;
        }

        .sidebar-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .sidebar-subtitle {
            font-size: 0.875rem;
            opacity: 0.8;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-item {
            margin-bottom: 0.75rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-radius: var(--radius);
            transition: var(--transition);
            font-weight: 500;
        }

        .sidebar-link i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }

        .sidebar-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
        }

        .sidebar-link.active {
            background: var(--white);
            color: var(--primary);
            font-weight: 600;
            box-shadow: var(--shadow-sm);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem;
            margin-top: 2rem;
            background-color: var(--danger);
            color: white;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .logout-btn:hover {
            background-color: #d31666;
            transform: translateY(-2px);
        }

        /* Main Content Styles */
        .main-content {
            padding: 2rem;
            overflow-y: auto;
        }

        /* Header Styles */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .page-title h1 {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--dark);
        }

        .page-title p {
            color: var(--gray);
            font-size: 0.875rem;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Notification Styles */
        .notification {
            position: relative;
        }

        .notification-btn {
            background: none;
            border: none;
            color: var(--dark);
            font-size: 1.25rem;
            cursor: pointer;
            position: relative;
            padding: 0.5rem;
            border-radius: 50%;
            transition: var(--transition);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-btn:hover {
            background: var(--gray-light);
        }

        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }

        /* Card Styles */
        .card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border-radius: var(--radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            text-decoration: none;
        }

        .btn i {
            font-size: 1rem;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #3ab7db;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #d31666;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-warning {
            background-color: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background-color: #e68a19;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-info {
            background-color: var(--info);
            color: white;
        }

        .btn-info:hover {
            background-color: #3a84d4;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-light {
            background-color: var(--gray-light);
            color: var(--dark);
        }

        .btn-light:hover {
            background-color: #d9dde2;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .table th {
            background-color: var(--primary-light);
            color: var(--primary-dark);
            font-weight: 600;
            padding: 1rem;
            text-align: left;
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-light);
            vertical-align: middle;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover td {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: var(--radius);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-active {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success);
        }

        .status-inactive {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--danger);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .modal.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-dialog {
            background-color: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            transform: translateY(-50px);
            transition: var(--transition);
        }

        .modal.show .modal-dialog {
            transform: translateY(0);
        }

        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
            transition: var(--transition);
        }

        .modal-close:hover {
            color: var(--danger);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid var(--gray-light);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-light);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-check-input {
            width: 1.25rem;
            height: 1.25rem;
        }

        .form-check-label {
            font-weight: 500;
            color: var(--dark);
        }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert i {
            font-size: 1.25rem;
        }

        .alert-success {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: fixed;
                top: 0;
                left: -280px;
                width: 280px;
                height: 100vh;
                transition: var(--transition);
                z-index: 1000;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <?php if (!empty($_SESSION['user']['profile_image'])): ?>
                    <img src="uploads/profile_images/<?= htmlspecialchars($_SESSION['user']['profile_image']) ?>" alt="Profile" class="profile-img">
                <?php else: ?>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user']['full_name']) ?>&background=4361ee&color=fff&size=128" alt="Profile" class="profile-img">
                <?php endif; ?>
                <h3 class="sidebar-title"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></h3>
                <p class="sidebar-subtitle"><?= htmlspecialchars($_SESSION['user']['center_name']) ?></p>
            </div>
            
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="center_dashboard.php" class="sidebar-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="reports.php" class="sidebar-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>4DX Reports</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="partners.php" class="sidebar-link active">
                        <i class="fas fa-users"></i>
                        <span>Partners</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="settings.php" class="sidebar-link">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
            
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="page-title">
                    <h1>Partners Management</h1>
                    <p>Manage your cooperative partners and their information</p>
                </div>
                
                <div class="header-actions">
                    <div class="notification">
                        <button class="notification-btn">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge">3</span>
                        </button>
                    </div>
                </div>
            </header>

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
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Partner Name</th>
                                    <th>Herd Code</th>
                                    <th>Contact</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($partners)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No partners found. Add your first partner!</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($partners as $partner): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($partner['partner_name']) ?></td>
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
                                                <span class="status-badge status-<?= $partner['is_active'] ? 'active' : 'inactive' ?>">
                                                    <?= $partner['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-info btn-sm edit-btn" 
                                                            data-id="<?= $partner['id'] ?>"
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
                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this partner?');">
                                                        <input type="hidden" name="partner_id" value="<?= $partner['id'] ?>">
                                                        <button type="submit" name="delete" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST">
                                                        <input type="hidden" name="partner_id" value="<?= $partner['id'] ?>">
                                                        <button type="submit" name="toggle_status" class="btn btn-<?= $partner['is_active'] ? 'warning' : 'success' ?> btn-sm">
                                                            <i class="fas fa-<?= $partner['is_active'] ? 'ban' : 'check' ?>"></i>
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
        </main>
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
                    <button type="button" class="btn btn-light modal-close">Cancel</button>
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
                    <button type="button" class="btn btn-light modal-close">Cancel</button>
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
            btn.addEventListener('click', () => {
                document.getElementById('edit_id').value = btn.dataset.id;
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

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
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
    </script>
</body>
</html>