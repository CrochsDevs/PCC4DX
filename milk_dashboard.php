<?php
session_start();
require 'auth_check.php';
require 'db_config.php';

if ($_SESSION['user']['center_type'] === 'Headquarters') {
    header('Location: access_denied.php');
    exit;
}

$centerCode = $_SESSION['user']['center_code'];

class MilkDashboard {
    private $conn;
    private $centerCode;

    public function __construct($conn, $centerCode) {
        $this->conn = $conn;
        $this->centerCode = $centerCode;
    }

    public function getProductionData($page = 1, $perPage = 10, $search = '', $month = null, $week = null) {
        $offset = ($page - 1) * $perPage;
        
        $query = "SELECT 
                    mp.*, 
                    p.partner_name,
                    WEEK(mp.entry_date, 3) as week_number,
                    DATE_FORMAT(mp.entry_date, '%b %d, %Y') as formatted_entry_date,
                    IFNULL(DATE_FORMAT(mp.end_date, '%b %d, %Y'), 'N/A') as formatted_end_date
                  FROM milk_production mp
                  JOIN partners p ON mp.partner_id = p.id
                  WHERE mp.center_code = :centerCode";
        
        $params = [':centerCode' => $this->centerCode];
        
        if (!empty($search)) {
            $query .= " AND (
                p.partner_name LIKE :search OR
                mp.status LIKE :search OR
                mp.quantity LIKE :search OR
                mp.volume LIKE :search OR
                mp.total LIKE :search OR
                DATE_FORMAT(mp.entry_date, '%b %d, %Y') LIKE :search OR
                DATE_FORMAT(mp.end_date, '%b %d, %Y') LIKE :search
            )";
            $params[':search'] = "%$search%";
        }
        
        if ($month) {
            $query .= " AND DATE_FORMAT(mp.entry_date, '%Y-%m') = :month";
            $params[':month'] = $month;
        }
        
        if ($week !== null) {
            $query .= " AND WEEK(mp.entry_date, 3) = :week";
            $params[':week'] = $week;
        }
        
        $query .= " ORDER BY mp.entry_date DESC
                  LIMIT :offset, :perPage";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalRecords($search = '', $month = null, $week = null) {
        $query = "SELECT COUNT(*) as total
                  FROM milk_production mp
                  JOIN partners p ON mp.partner_id = p.id
                  WHERE mp.center_code = :centerCode";
        
        $params = [':centerCode' => $this->centerCode];
        
        if (!empty($search)) {
            $query .= " AND (
                p.partner_name LIKE :search OR
                mp.status LIKE :search OR
                mp.quantity LIKE :search OR
                mp.volume LIKE :search OR
                mp.total LIKE :search OR
                DATE_FORMAT(mp.entry_date, '%b %d, %Y') LIKE :search OR
                DATE_FORMAT(mp.end_date, '%b %d, %Y') LIKE :search
            )";
            $params[':search'] = "%$search%";
        }
        
        if ($month) {
            $query .= " AND DATE_FORMAT(mp.entry_date, '%Y-%m') = :month";
            $params[':month'] = $month;
        }
        
        if ($week !== null) {
            $query .= " AND WEEK(mp.entry_date, 3) = :week";
            $params[':week'] = $week;
        }
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getWeeksInMonth($month) {
        $query = "SELECT DISTINCT WEEK(entry_date, 3) as week_number 
                  FROM milk_production 
                  WHERE center_code = :centerCode 
                  AND DATE_FORMAT(entry_date, '%Y-%m') = :month 
                  ORDER BY week_number";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':centerCode' => $this->centerCode,
            ':month' => $month
        ]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function getSummaryStats($month = null, $week = null) {
        $query = "SELECT 
                    SUM(quantity) as total_quantity,
                    SUM(volume) as total_volume,
                    SUM(total) as total_value,
                    COUNT(DISTINCT partner_id) as partner_count,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count
                  FROM milk_production
                  WHERE center_code = :centerCode";
        
        $params = [':centerCode' => $this->centerCode];
        
        if ($month) {
            $query .= " AND DATE_FORMAT(entry_date, '%Y-%m') = :month";
            $params[':month'] = $month;
        }
        
        if ($week !== null) {
            $query .= " AND WEEK(entry_date, 3) = :week";
            $params[':week'] = $week;
        }

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getMonthlyTrends() {
        $query = "SELECT 
                    DATE_FORMAT(entry_date, '%Y-%m') as month,
                    DATE_FORMAT(entry_date, '%b %Y') as month_display,
                    SUM(quantity) as quantity,
                    SUM(volume) as volume,
                    SUM(total) as value
                  FROM milk_production
                  WHERE center_code = :centerCode
                  GROUP BY DATE_FORMAT(entry_date, '%Y-%m')
                  ORDER BY month DESC
                  LIMIT 12";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':centerCode', $this->centerCode, PDO::PARAM_STR);
        $stmt->execute();
        return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getWeeklyTrends($month) {
        $query = "SELECT 
                    WEEK(entry_date, 3) as week_number,
                    CONCAT('Week ', WEEK(entry_date, 3)) as week_display,
                    SUM(quantity) as quantity,
                    SUM(volume) as volume,
                    SUM(total) as value
                  FROM milk_production
                  WHERE center_code = :centerCode
                  AND DATE_FORMAT(entry_date, '%Y-%m') = :month
                  GROUP BY WEEK(entry_date, 3)
                  ORDER BY week_number";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':centerCode', $this->centerCode, PDO::PARAM_STR);
        $stmt->bindParam(':month', $month, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPartnerDistribution($month = null, $week = null) {
        $query = "SELECT 
                    p.partner_name,
                    SUM(mp.quantity) as total_quantity,
                    SUM(mp.volume) as total_volume,
                    SUM(mp.total) as total_value
                  FROM milk_production mp
                  JOIN partners p ON mp.partner_id = p.id
                  WHERE mp.center_code = :centerCode";
        
        $params = [':centerCode' => $this->centerCode];
        
        if ($month) {
            $query .= " AND DATE_FORMAT(mp.entry_date, '%Y-%m') = :month";
            $params[':month'] = $month;
        }
        
        if ($week !== null) {
            $query .= " AND WEEK(mp.entry_date, 3) = :week";
            $params[':week'] = $week;
        }
        
        $query .= " GROUP BY p.partner_name
                  ORDER BY total_value DESC
                  LIMIT 5";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMonthlyTarget($month) {
        $query = "SELECT target_volume, target_value 
                  FROM mp_target
                  WHERE center_code = :centerCode 
                  AND target_month = :month";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':centerCode' => $this->centerCode,
            ':month' => $month
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getWeeklyTarget($month, $week) {
        $query = "SELECT target_volume, target_value 
                  FROM mp_target 
                  WHERE center_code = :centerCode 
                  AND target_month = :month
                  AND target_week = :week";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':centerCode' => $this->centerCode,
            ':month' => $month,
            ':week' => $week
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    

    public function exportData($format = 'csv', $month = null, $week = null) {
        $query = "SELECT 
                    p.partner_name,
                    mp.quantity,
                    mp.volume,
                    mp.total,
                    mp.status,
                    DATE_FORMAT(mp.entry_date, '%b %d, %Y') as entry_date,
                    IFNULL(DATE_FORMAT(mp.end_date, '%b %d, %Y'), 'N/A') as end_date
                  FROM milk_production mp
                  JOIN partners p ON mp.partner_id = p.id
                  WHERE mp.center_code = :centerCode";
        
        $params = [':centerCode' => $this->centerCode];
        
        if ($month) {
            $query .= " AND DATE_FORMAT(mp.entry_date, '%Y-%m') = :month";
            $params[':month'] = $month;
        }
        
        if ($week !== null) {
            $query .= " AND WEEK(mp.entry_date, 3) = :week";
            $params[':week'] = $week;
        }
        
        $query .= " ORDER BY mp.entry_date DESC";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($format === 'csv') {
            $this->exportToCSV($data, $month, $week);
        } elseif ($format === 'excel') {
            $this->exportToExcel($data, $month, $week);
        }
    }

    private function exportToCSV($data, $month, $week) {
        $filename = 'milk_production_' . ($week ? 'week_' . $week . '_' : '') . ($month ?: date('Y-m')) . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add headers
        fputcsv($output, array_keys($data[0]));
        
        // Add data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }

    private function exportToExcel($data, $month, $week) {
        $filename = 'milk_production_' . ($week ? 'week_' . $week . '_' : '') . ($month ?: date('Y-m')) . '.xlsx';
        
        require_once 'vendor/autoload.php';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Add headers
        $headers = array_keys($data[0]);
        $sheet->fromArray($headers, NULL, 'A1');
        
        // Add data
        $rowNum = 2;
        foreach ($data as $row) {
            $sheet->fromArray($row, NULL, 'A' . $rowNum);
            $rowNum++;
        }
        
        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Set headers and output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
}

// Get parameters
$selectedMonth = $_GET['month'] ?? date('Y-m');
$searchTerm = $_GET['search'] ?? '';
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$exportFormat = $_GET['export'] ?? '';

// Initialize dashboard
$dashboard = new MilkDashboard($conn, $centerCode);

// Handle export
if ($exportFormat && in_array($exportFormat, ['csv', 'excel'])) {
    $dashboard->exportData($exportFormat, $selectedMonth, $_GET['week'] ?? null);
}

// Get weeks in selected month
$weeksInMonth = $dashboard->getWeeksInMonth($selectedMonth);
$totalWeeks = count($weeksInMonth);
$selectedWeek = $_GET['week'] ?? ($weeksInMonth[0] ?? null);

// Get data
$productionData = $dashboard->getProductionData($currentPage, $perPage, $searchTerm, $selectedMonth, $selectedWeek);
$totalRecords = $dashboard->getTotalRecords($searchTerm, $selectedMonth, $selectedWeek);
$summaryStats = $dashboard->getSummaryStats($selectedMonth, $selectedWeek);
$monthlyTrends = $dashboard->getMonthlyTrends();
$weeklyTrends = $dashboard->getWeeklyTrends($selectedMonth);
$partnerDistribution = $dashboard->getPartnerDistribution($selectedMonth, $selectedWeek);

// Get targets
$monthlyTarget = $dashboard->getMonthlyTarget($selectedMonth);
$weeklyTarget = $dashboard->getWeeklyTarget($selectedMonth, $selectedWeek);

// Calculate accomplishment percentages
$volumePercentage = $monthlyTarget ? round(($summaryStats['total_volume'] / $monthlyTarget['target_volume']) * 100, 2) : 0;
$valuePercentage = $monthlyTarget ? round(($summaryStats['total_value'] / $monthlyTarget['target_value']) * 100, 2) : 0;

// Determine target status
$volumeStatus = $volumePercentage >= 100 ? 'success' : ($volumePercentage >= 80 ? 'warning' : 'danger');
$valueStatus = $valuePercentage >= 100 ? 'success' : ($valuePercentage >= 80 ? 'warning' : 'danger');

// Format data for display
$totalQuantity = number_format($summaryStats['total_quantity'] ?? 0, 2);
$totalVolume = number_format($summaryStats['total_volume'] ?? 0, 2) . ' L';
$totalValue = '₱' . number_format($summaryStats['total_value'] ?? 0, 2);
$partnerCount = $summaryStats['partner_count'] ?? 0;
$pendingCount = $summaryStats['pending_count'] ?? 0;

// Prepare chart data
$chartLabels = array_column($monthlyTrends, 'month_display');
$chartQuantities = array_column($monthlyTrends, 'quantity');
$chartVolumes = array_column($monthlyTrends, 'volume');
$chartValues = array_column($monthlyTrends, 'value');

$weeklyLabels = array_column($weeklyTrends, 'week_display');
$weeklyVolumes = array_column($weeklyTrends, 'volume');
$weeklyValues = array_column($weeklyTrends, 'value');

$partnerLabels = array_map(function($partner) {
    return strlen($partner['partner_name']) > 15 ? 
           substr($partner['partner_name'], 0, 12) . '...' : 
           $partner['partner_name'];
}, $partnerDistribution);
$partnerVolumes = array_column($partnerDistribution, 'total_volume');
$partnerValues = array_column($partnerDistribution, 'total_value');

// Calculate total pages
$totalPages = ceil($totalRecords / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Milk Production Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary: #2A5C82;
            --primary-light: #3A6C92;
            --secondary: #5CACEE;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            margin-left: 280px;
            padding-top: 20px;
            color: #495057;
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
            background-color: red;
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

        /* Header Styles */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border-left: 5px solid var(--primary);
        }

        .header-title h2 {
            color: var(--primary);
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .header-title p {
            color: #6c757d;
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Stat Cards */
        .stat-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-left: 4px solid;
            overflow: hidden;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card .card-body {
            padding: 1.5rem;
            position: relative;
        }

        .stat-card .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        .stat-card .stat-title {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .stat-card .stat-subtext {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .stat-card .progress {
            height: 6px;
            margin-top: 10px;
            border-radius: 3px;
        }

        .stat-card.primary {
            border-left-color: var(--primary);
        }

        .stat-card.primary .stat-icon {
            color: var(--primary);
        }

        .stat-card.success {
            border-left-color: var(--success);
        }

        .stat-card.success .stat-icon {
            color: var(--success);
        }

        .stat-card.info {
            border-left-color: var(--info);
        }

        .stat-card.info .stat-icon {
            color: var(--info);
        }

        .stat-card.warning {
            border-left-color: var(--warning);
        }

        .stat-card.warning .stat-icon {
            color: var(--warning);
        }

        .stat-card.danger {
            border-left-color: var(--danger);
        }

        .stat-card.danger .stat-icon {
            color: var(--danger);
        }

        /* Target Status Badge */
        .target-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
        }

        /* Chart Containers */
        .chart-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .chart-container:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .chart-container .card-title {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
        }

        .chart-container .card-title i {
            font-size: 1.25rem;
        }

        .chart-area {
            height: 300px;
            position: relative;
        }

        /* Week Navigation */
        .week-nav {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .week-nav .week-btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s;
            border: 1px solid #dee2e6;
            background-color: white;
            color: var(--dark);
            text-decoration: none;
        }

        .week-nav .week-btn:hover {
            background-color: #f1f1f1;
        }

        .week-nav .week-btn.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            body {
                margin-left: 0;
                padding-top: 0;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 1050;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0 !important;
            }
            
            .navbar-toggler {
                display: block;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animated-card {
            animation: fadeIn 0.5s ease-out forwards;
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <!-- Fixed Sidebar -->
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
                <p class="user-email" style="word-break: break-word; font-size: 0.9rem;">
                <?= htmlspecialchars($_SESSION['user']['email']) ?>
                </p>

            </div>
        </div>

        <nav>
            <ul>
                <li><a href="services.php" class="nav-link"><i class="fa-solid fa-arrow-left"></i> Back to quickfacts</a></li>
                <li><a href="milk_dashboard.php" class="nav-link active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="partners.php" class="nav-link"><i class="fas fa-users"></i> Partners</a></li>
                <li><a href="milk_report.php" class="nav-link"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </div>

    <div class="container-fluid py-4">
        <!-- Improved Header -->
        <div class="dashboard-header animated-card">
            <div class="header-title">
                <h2><i class="fas fa-chart-pie me-2"></i> Milk Production Dashboard</h2>
                <p>Center: <?= htmlspecialchars($centerCode) ?> | <?= date('F j, Y') ?></p>
            </div>
            <div class="header-actions">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="monthDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-calendar-alt me-1"></i> <?= date('M Y', strtotime($selectedMonth)) ?>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="monthDropdown">
                        <?php 
                        $months = $dashboard->getMonthlyTrends();
                        foreach (array_reverse($months) as $month): 
                            $monthValue = $month['month'];
                        ?>
                            <li>
                                <a class="dropdown-item <?= $monthValue == $selectedMonth ? 'active' : '' ?>" href="?month=<?= $monthValue ?>">
                                    <?= $month['month_display'] ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-download me-1"></i> Export
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                        <li><a class="dropdown-item" href="?month=<?= $selectedMonth ?>&export=csv<?= $selectedWeek ? '&week='.$selectedWeek : '' ?>"><i class="fas fa-file-csv me-2"></i> CSV</a></li>
                        <li><a class="dropdown-item" href="?month=<?= $selectedMonth ?>&export=excel<?= $selectedWeek ? '&week='.$selectedWeek : '' ?>"><i class="fas fa-file-excel me-2"></i> Excel</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Week Navigation -->
        <?php if ($weeksInMonth): ?>
        <div class="week-nav animated-card delay-1">
            <?php foreach ($weeksInMonth as $week): ?>
                <a href="?month=<?= $selectedMonth ?>&week=<?= $week ?>" 
                   class="week-btn <?= $selectedWeek == $week ? 'active' : '' ?>">
                    Week <?= $week ?>
                </a>
            <?php endforeach; ?>
          
        </div>
        <?php endif; ?>

        <!-- Summary Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card card primary animated-card delay-1">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-weight-hanging"></i>
                        </div>
                        <h6 class="stat-title">Total Produced</h6>
                        <h3 class="stat-value"><?= $totalQuantity ?> kg</h3>
                        <p class="stat-subtext"><?= $selectedWeek ? 'Week '.$selectedWeek : 'All Weeks' ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card card success animated-card delay-2">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-peso-sign"></i>
                        </div>
                        <h6 class="stat-title">Total Revenue</h6>
                        <h3 class="stat-value"><?= $totalValue ?></h3>
                        <p class="stat-subtext"><?= $selectedWeek ? 'Week '.$selectedWeek : 'All Weeks' ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card card info animated-card delay-3">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-wine-bottle"></i>
                        </div>
                        <h6 class="stat-title">Total Volume</h6>
                        <h3 class="stat-value"><?= $totalVolume ?></h3>
                        <p class="stat-subtext"><?= $selectedWeek ? 'Week '.$selectedWeek : 'All Weeks' ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card card <?= $volumeStatus ?> animated-card delay-4">
                    <div class="card-body">
                        <span class="target-badge bg-<?= $volumeStatus ?>">
                            <?= $volumePercentage ?>% of Target
                        </span>
                        <div class="stat-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h6 class="stat-title">Volume Target</h6>
                        <h3 class="stat-value"><?= $monthlyTarget ? number_format($monthlyTarget['target_volume'], 2).' L' : 'Not Set' ?></h3>
                        <div class="progress">
                            <div class="progress-bar bg-<?= $volumeStatus ?>" role="progressbar" 
                                 style="width: <?= min($volumePercentage, 100) ?>%" 
                                 aria-valuenow="<?= $volumePercentage ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="chart-container animated-card delay-1">
                    <h5 class="card-title"><i class="fas fa-chart-bar me-2"></i>Monthly Production Volume (Liters)</h5>
                    <div class="chart-area">
                        <canvas id="volumeChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="chart-container animated-card delay-2">
                    <h5 class="card-title"><i class="fas fa-chart-line me-2"></i>Monthly Revenue Value (₱)</h5>
                    <div class="chart-area">
                        <canvas id="valueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="chart-container animated-card delay-3">
                    <h5 class="card-title"><i class="fas fa-chart-pie me-2"></i>Partner Volume Distribution</h5>
                    <div class="chart-area">
                        <canvas id="partnerVolumeChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="chart-container animated-card delay-4">
                    <h5 class="card-title"><i class="fas fa-chart-pie me-2"></i>Partner Value Distribution</h5>
                    <div class="chart-area">
                        <canvas id="partnerValueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Weekly Charts Row -->
        <?php if ($weeklyTrends): ?>
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="chart-container animated-card">
                    <h5 class="card-title"><i class="fas fa-chart-area me-2"></i>Weekly Production Volume (Liters)</h5>
                    <div class="chart-area">
                        <canvas id="weeklyVolumeChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="chart-container animated-card">
                    <h5 class="card-title"><i class="fas fa-chart-line me-2"></i>Weekly Revenue Value (₱)</h5>
                    <div class="chart-area">
                        <canvas id="weeklyValueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Volume Chart
        const volumeCtx = document.getElementById('volumeChart').getContext('2d');
        new Chart(volumeCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    label: 'Volume (L)',
                    data: <?= json_encode($chartVolumes) ?>,
                    backgroundColor: 'rgba(23, 162, 184, 0.7)',
                    borderColor: 'rgba(23, 162, 184, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { 
                            display: true, 
                            text: 'Volume (Liters)',
                            font: {
                                weight: 'bold'
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month',
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ` ${context.dataset.label}: ${context.raw.toLocaleString()} L`;
                            }
                        }
                    }
                }
            }
        });

        // Value Chart
        const valueCtx = document.getElementById('valueChart').getContext('2d');
        new Chart(valueCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    label: 'Value (₱)',
                    data: <?= json_encode($chartValues) ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { 
                            display: true, 
                            text: 'Value (₱)',
                            font: {
                                weight: 'bold'
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month',
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ` ${context.dataset.label}: ₱${context.raw.toLocaleString()}`;
                            }
                        }
                    }
                }
            }
        });

        // Partner Volume Chart
        const partnerVolumeCtx = document.getElementById('partnerVolumeChart').getContext('2d');
        new Chart(partnerVolumeCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($partnerLabels) ?>,
                datasets: [{
                    data: <?= json_encode($partnerVolumes) ?>,
                    backgroundColor: [
                        'rgba(42, 92, 130, 0.7)',
                        'rgba(92, 172, 238, 0.7)',
                        'rgba(23, 162, 184, 0.7)',
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ` ${context.label}: ${context.raw.toLocaleString()} L`;
                            }
                        }
                    },
                    datalabels: {
                        display: false
                    }
                }
            }
        });

        // Partner Value Chart
        const partnerValueCtx = document.getElementById('partnerValueChart').getContext('2d');
        new Chart(partnerValueCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode($partnerLabels) ?>,
                datasets: [{
                    data: <?= json_encode($partnerValues) ?>,
                    backgroundColor: [
                        'rgba(42, 92, 130, 0.7)',
                        'rgba(92, 172, 238, 0.7)',
                        'rgba(23, 162, 184, 0.7)',
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ` ${context.label}: ₱${context.raw.toLocaleString()}`;
                            }
                        }
                    },
                    datalabels: {
                        display: false
                    }
                }
            }
        });

        // Weekly Volume Chart
        <?php if ($weeklyTrends): ?>
        const weeklyVolumeCtx = document.getElementById('weeklyVolumeChart').getContext('2d');
        new Chart(weeklyVolumeCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($weeklyLabels) ?>,
                datasets: [{
                    label: 'Volume (L)',
                    data: <?= json_encode($weeklyVolumes) ?>,
                    backgroundColor: 'rgba(92, 172, 238, 0.7)',
                    borderColor: 'rgba(92, 172, 238, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { 
                            display: true, 
                            text: 'Volume (Liters)',
                            font: {
                                weight: 'bold'
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Week',
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ` ${context.dataset.label}: ${context.raw.toLocaleString()} L`;
                            }
                        }
                    }
                }
            }
        });

        // Weekly Value Chart
        const weeklyValueCtx = document.getElementById('weeklyValueChart').getContext('2d');
        new Chart(weeklyValueCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($weeklyLabels) ?>,
                datasets: [{
                    label: 'Value (₱)',
                    data: <?= json_encode($weeklyValues) ?>,
                    backgroundColor: 'rgba(255, 193, 7, 0.2)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { 
                            display: true, 
                            text: 'Value (₱)',
                            font: {
                                weight: 'bold'
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Week',
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ` ${context.dataset.label}: ₱${context.raw.toLocaleString()}`;
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Month selector change handler
        $('#monthSelect').change(function() {
            const month = $(this).val();
            window.location.href = `?month=${month}&page=1&search=<?= urlencode($searchTerm) ?>`;
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>