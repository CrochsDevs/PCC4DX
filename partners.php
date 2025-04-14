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
            --primary: #0056b3;
            --primary-light: #3a7fc5;
            --primary-lighter: #e6f0fa;
            --secondary: #ffc107;
            --secondary-light: #ffd54f;
            --secondary-lighter: #fff8e6;
            --dark: #2d3748;
            --dark-light: #4a5568;
            --light: #f8f9fa;
            --danger: #e53e3e;
            --danger-light: #feb2b2;
            --danger-lighter: #fde8e8;
            --success: #38a169;
            --success-light: #9ae6b4;
            --success-lighter: #f0fff4;
            --info: #3182ce;
            --warning: #dd6b20;
            --gray: #718096;
            --gray-light: #e2e8f0;
            --gray-lighter: #f7fafc;
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
            background-color: #f7fafc;
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
            color: var(--dark);
        }
        
        /* Sidebar Styles */
        .sidebar {
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 2rem 1.5rem;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 10;
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
            background: var(--secondary);
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background-color: red;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background-color: #c53030;
            transform: translateY(-2px);
        }
        
        /* Main Content Styles */
        .main-content {
            padding: 2.5rem;
            overflow-y: auto;
        }
        
        /* Header Styles */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-light);
            position: relative;
        }
        
        .header-left, .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .notification-container {
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
            transition: all 0.3s;
        }
        
        .notification-btn:hover {
            background: var(--gray-light);
            transform: translateY(-2px);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
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
        
        .notification-dropdown {
            position: absolute;
            right: 0;
            top: 100%;
            width: 350px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            z-index: 100;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s;
        }
        
        .notification-container:hover .notification-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .notification-header {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-header h4 {
            margin: 0;
            font-size: 1rem;
            color: var(--dark);
        }
        
        .mark-all-read {
            color: var(--primary);
            font-size: 0.85rem;
            cursor: pointer;
        }
        
        .notification-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notification-item {
            display: flex;
            padding: 1rem;
            gap: 1rem;
            text-decoration: none;
            color: var(--dark);
            border-bottom: 1px solid var(--gray-light);
            transition: all 0.2s;
        }
        
        .notification-item:hover {
            background: rgba(0, 86, 179, 0.05);
        }
        
        .notification-item.unread {
            background: rgba(0, 86, 179, 0.03);
        }
        
        .notification-icon {
            font-size: 1.25rem;
            color: var(--primary);
        }
        
        .notification-icon .text-success {
            color: var(--success);
        }
        
        .notification-icon .text-danger {
            color: var(--danger);
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-content p {
            margin: 0 0 0.25rem 0;
            font-size: 0.9rem;
        }
        
        .notification-content small {
            color: var(--gray);
            font-size: 0.8rem;
        }
        
        .notification-footer {
            padding: 0.75rem 1rem;
            text-align: center;
            border-top: 1px solid var(--gray-light);
        }
        
        .notification-footer a {
            color: var(--primary);
            font-size: 0.85rem;
            text-decoration: none;
            font-weight: 500;
        }

         /* User Profile Styles */
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
        
        .profile-info {
            color: white;
        }
        
        .user-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .user-email {
            font-size: 0.85rem;
            opacity: 0.9;
            word-break: break-word;
        }
        
        /* Content Sections */
        .content-section {
            display: none;
        }
        
        .content-section.active {
            display: block;
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

        <ul>
            <li><a href="center_dashboard.php" class="nav-link active" data-section="dashboard-section"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="#" class="nav-link" data-section="services-section"><i class="fas fa-concierge-bell"></i> 4DX Report</a></li>
            <li><a href="Partners.php"><i class="fas fa-users"></i> Partners</a></li>
            <li><a href="#" class="nav-link" data-section="settings-section"><i class="fas fa-cogs"></i> Settings</a></li>
            <li><a href="logout.php" class="logout-btn" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
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