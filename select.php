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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/center.css">
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
            <li><a href="center_dashboard.php" class="nav-link" data-section="dashboard-section"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="services.php" class="nav-link" data-section="services-section"><i class="fas fa-concierge-bell"></i> 4DX Report</a></li>
            <li><a href="partners.php"  class="nav-link active" ><i class="fas fa-users"></i> Partners</a></li>
            <li><a href="settings.php" class="nav-link" data-section="settings-section"><i class="fas fa-cogs"></i> Settings</a></li>
            <li><a href="logout.php" class="logout-btn" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                    <h1>Partner</h1>
            </div>
            
            

    <script>
    </script>
</body>
</html>