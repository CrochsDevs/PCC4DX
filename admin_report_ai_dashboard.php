<?php
session_start();
require 'auth_check.php';
include('db_config.php');

// Check if user is from HQ
if ($_SESSION['user']['center_code'] !== 'HQ') {
    header('Location: access_denied.php');
    exit;
}

class AIReportManager {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getReports($centerCode, $year = null, $month = null, $week = null, $date = null) {
        
        $query = "SELECT aiID, aiServices, date, remarks, center 
                FROM ai_services 
                WHERE center = :center";
        $params = [':center' => $centerCode];
        
        if ($date) {
            $query .= " AND date = :date";
            $params[':date'] = $date;
        } else {
            if ($year) {
                $query .= " AND YEAR(date) = :year";
                $params[':year'] = $year;
            }
            if ($month) {
                $query .= " AND MONTH(date) = :month";
                $params[':month'] = $month;
            }
            if ($week) {
                $query .= " AND WEEK(date, 3) = :week";
                $params[':week'] = $week;
            }
        }
        
        $query .= " ORDER BY date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate totals
        $total = 0;
        foreach ($reports as $row) {
            $total += $row['aiServices'];
        }

        $count = count($reports); 

        return [
            'reports' => $reports,
            'total' => $total,
            'count' => $count    
        ];
    }

    public function getAvailableYears($centerCode) {
        $query = "SELECT DISTINCT YEAR(date) as year 
                  FROM ai_services 
                  WHERE center = :center 
                  ORDER BY year DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':center' => $centerCode]);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    
    public function getAvailableWeeks($year, $month, $centerCode) {
        $query = "SELECT DISTINCT WEEK(date, 3) as week 
                  FROM ai_services 
                  WHERE center = :center 
                  AND YEAR(date) = :year 
                  AND MONTH(date) = :month
                  ORDER BY week";
                  
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':center' => $centerCode,
            ':year' => $year,
            ':month' => $month
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    
    public function getAllCenters() {
        $query = "SELECT center_code, center_name FROM centers WHERE center_code != 'HQ' ORDER BY center_name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateReport($aiID, $aiServices, $remarks) {
        $query = "UPDATE ai_services SET aiServices = :aiServices, remarks = :remarks WHERE aiID = :aiID";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':aiID' => $aiID,
            ':aiServices' => $aiServices,
            ':remarks' => $remarks
        ]);
    }

    public function deleteReport($aiID) {
        $query = "DELETE FROM ai_services WHERE aiID = :aiID";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':aiID' => $aiID]);
    }
}

$reportManager = new AIReportManager($conn);

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'get_years':
            if (isset($_GET['center'])) {
                echo json_encode($reportManager->getAvailableYears($_GET['center']));
            } else {
                echo json_encode([]);
            }
            break;
            
        case 'get_weeks':
            if (isset($_GET['year']) && isset($_GET['month']) && isset($_GET['center'])) {
                echo json_encode($reportManager->getAvailableWeeks($_GET['year'], $_GET['month'], $_GET['center']));
            } else {
                echo json_encode([]);
            }
            break;
            
        case 'get_reports':
            if (isset($_GET['center'])) {
                $year = isset($_GET['year']) ? $_GET['year'] : null;
                $month = isset($_GET['month']) ? $_GET['month'] : null;
                $week = isset($_GET['week']) ? $_GET['week'] : null;
                echo json_encode($reportManager->getReports($_GET['center'], $year, $month, $week));
            } else {
                echo json_encode(['error' => 'Center not specified']);
            }
            break;
            
        case 'get_centers':
            echo json_encode($reportManager->getAllCenters());
            break;

        case 'update_report':
            if (isset($_POST['aiID']) && isset($_POST['aiServices']) && isset($_POST['remarks'])) {
                $success = $reportManager->updateReport($_POST['aiID'], $_POST['aiServices'], $_POST['remarks']);
                echo json_encode(['success' => $success]);
            } else {
                echo json_encode(['error' => 'Missing parameters']);
            }
            break;

        case 'delete_report':
            if (isset($_POST['aiID'])) {
                $success = $reportManager->deleteReport($_POST['aiID']);
                echo json_encode(['success' => $success]);
            } else {
                echo json_encode(['error' => 'Missing report ID']);
            }
            break;
            
        default:
            echo json_encode(['error' => 'Invalid AJAX request']);
    }
    exit;
}

$allCenters = $reportManager->getAllCenters();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HQ AI Score Card Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
    <link rel="stylesheet" href="css/calf.css">
    <link rel="stylesheet" href="css/cd_report.css">
    <style>
        .week-white {
            background-color: #ffffff;
        }
        .week-grey {
            background-color: #f0f0f0;
        }
        .title {
            font-size: 1.875rem;
            font-weight: bold;
            color:rgb(0, 0, 0);
            padding-left: 1rem;
            padding-top: 2rem;
        }
        .subtitle {
            color: #4B5563;
            padding-left: 1rem;
        }
        .center-selector {
            margin: 20px 0;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 16px;
            width: 300px;
        }
        .center-filter{
            padding-left: 20px;
        }
        .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .no-data {
            padding: 20px;
            text-align: center;
            color: #666;
            font-style: italic;
         
        }
        .filter-container {
            margin-top: 20px;
            margin-left: 20px;
            margin-right: 20px;
        }
        .filter-options {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        .filter-btn {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f8f9fa;
            cursor: pointer;
        }
        .filter-btn.active {
            background: #3730a3;
            color: white;
            border-color: #3730a3;
        }
        .export-btn {
            padding: 8px 16px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .export-btn.disabled {
            background: #9ca3af;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #3b82f6;
        }

        .table-container {
            margin: 20px;       
            overflow-x: auto;   
            max-width: 100%;   
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;   
            table-layout: fixed; 
        }

        .report-table th, .report-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
             
        }
        .report-table th {
            background-color:#3a7fc5;
            color: white;
        }
        .total-row {
            font-weight: bold;
            background-color: #e6f7ff;
        }
        .action-btns {
            display: flex;
            gap: 5px;
        }
        .edit-btn {
            background-color: #ffc107;
            color: #000;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 100%;
            max-width: 500px;
            border-radius: 8px;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .modal-title {
            font-size: 1.25rem;
            font-weight: bold;
        }
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
        }
        .modal-body {
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            gap: 10px;
        }
        .btn {
            padding: 8px 16px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-secondary {
            background: #6b7280;
        }
        /* Add/edit CSS for inline editing */
        .edit-mode {
            width: 100%;
            padding: 4px;
            box-sizing: border-box;
        }

        .view-mode {
            display: block;
            padding: 4px;
        }

        .btn-success {
            background-color: #28a745 !important;
        }

        .btn-danger {
            background-color: #dc3545 !important;
        }

        .action-btns button {
            margin: 0 2px;
            padding: 3px 8px;
        }
    </style>
</head>

<div class="sidebar">
    <div class="user-profile" id="sidebar-profile">
        <div class="profile-picture">
            <?php if (!empty($_SESSION['user']['profile_image'])): ?>
                <img src="uploads/profile_images/<?= htmlspecialchars($_SESSION['user']['profile_image']) ?>" alt="Profile Picture" id="sidebar-profile-img">
            <?php else: ?>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user']['full_name']) ?>&background=0056b3&color=fff&size=128" alt="Profile Picture" id="sidebar-profile-img">
            <?php endif; ?>
        </div>
        <div class="profile-info">
            <h3 class="user-name" id="sidebar-profile-name"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></h3>
            <p class="user-email" id="sidebar-profile-email"><?= htmlspecialchars($_SESSION['user']['email']) ?></p>
        </div>                          
    </div>

    <ul>
        <li><a href="admin.php" class="nav-link">
        <i class="fa-solid fa-arrow-left"></i> Back to Admin</a></li>
        <li><a href="admin_ai_dashboard.php" class="nav-link" data-section="dashboard-section">
        <i class="fas fa-chart-line"></i> Dashboard</a></li>
        <li><a href="admin_centertarget_ai_dashboard.php"class="nav-link" data-section="announcement-section">
        <i class="fas fa-file-alt"></i> Center Target</a></li>
        <li><a href="admin_report_dashboard.php" class="nav-link active" data-section="quickfacts-section">
        <i class="fas fa-sitemap"></i> Reports</a></li>
    </ul>
</div>

<body class="bg-gray-50">

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Delete</h3>
                <span class="close">&times;</span>
            </div>
            <form id="deleteReportForm">
                <div class="modal-body">
                    <input type="hidden" id="deleteAiID" name="aiID">
                    <p>Are you sure you want to delete this AI report?</p>
                    <p><strong>Date:</strong> <span id="deleteDate"></span></p>
                    <p><strong>AI Services:</strong> <span id="deleteAiServices"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <header class="mb-10">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="title">Artificial Insemination Reports</h1>
                    <p class="subtitle">HQ Dashboard - View Center AI Reports</p>
                </div>
            </div>
        </header>

        <div class="container">
            <!-- Center Selection Dropdown -->
            <div class="center-filter">
                <select id="centerSelect" class="center-selector">
                    <option value="">Select Center</option>
                    <?php foreach ($allCenters as $center): ?>
                        <option value="<?= $center['center_code'] ?>">
                            <?= htmlspecialchars($center['center_name']) ?> (<?= htmlspecialchars($center['center_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-container">
                <div class="year-filter">
                    <div class="filter-header">
                        <div class="filter-title">Year</div>
                        <div class="export-btn-container">
                            <button id="exportToExcel" class="export-btn disabled" disabled>Export</button>
                        </div>
                    </div>
                    <div class="filter-options" id="yearFilter">
                        <!-- Years will be populated by JavaScript -->
                    </div>
                </div>

                <div class="month-filter">
                    <div class="filter-title">Month</div>
                    <div class="filter-options" id="monthFilter">
                        <button class="filter-btn" data-month="1">Jan</button>
                        <button class="filter-btn" data-month="2">Feb</button>
                        <button class="filter-btn" data-month="3">Mar</button>
                        <button class="filter-btn" data-month="4">Apr</button>
                        <button class="filter-btn" data-month="5">May</button>
                        <button class="filter-btn" data-month="6">Jun</button>
                        <button class="filter-btn" data-month="7">Jul</button>
                        <button class="filter-btn" data-month="8">Aug</button>
                        <button class="filter-btn" data-month="9">Sep</button>
                        <button class="filter-btn" data-month="10">Oct</button>
                        <button class="filter-btn" data-month="11">Nov</button>
                        <button class="filter-btn" data-month="12">Dec</button>
                    </div>
                </div>
                
                <div class="week-filter">
                    <div class="filter-title">Week</div>
                    <div class="filter-options" id="weekFilter">
                        <!-- Weeks will be populated when a month is selected -->
                    </div>
                </div>
            </div>
            
            <div class="table-container">
                <div id="loadingIndicator" class="loading" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i> Loading data...
                </div>
                <div id="reportResults">
                    <div class="no-data">Please select a center to view reports</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            let currentCenter = $('#centerSelect').val();
            let currentYear = null;
            let currentMonth = null;
            let currentWeek = null;
            
            const deleteModal = document.getElementById("deleteModal");
            const closeButtons = document.getElementsByClassName("close");

            // Close modals when clicking the X button or Cancel button
            for (let i = 0; i < closeButtons.length; i++) {
                closeButtons[i].onclick = function() {
                    deleteModal.style.display = "none";
                }
            }

            // Close modals when clicking outside the modal
            window.onclick = function(event) {
                if (event.target == deleteModal) {
                    deleteModal.style.display = "none";
                }
            }

            function updateExportButtonState() {
                if (!currentCenter) {
                    $('#exportToExcel').prop('disabled', true).addClass('disabled');
                } else {
                    $('#exportToExcel').prop('disabled', false).removeClass('disabled');
                }
            }
            
            // Load available years for the selected center
            function loadYears() {
                    if (!currentCenter) {
                    $('#yearFilter').empty();
                    $('#weekFilter').empty();
                    $('#reportResults').html('<div class="no-data">Please select a center to view reports</div>');
                    return;
                }
                
                $('#loadingIndicator').show();
                $.ajax({
                    url: window.location.href.split('?')[0] + '?ajax=get_years',
                    type: 'GET',
                    data: { center: currentCenter },
                    success: function(years) {
                        const yearFilter = $('#yearFilter');
                        yearFilter.empty();
                        
                        if (years.length > 0) {
                            years.forEach(year => {
                                yearFilter.append(
                                    `<button class="filter-btn" data-year="${year}">${year}</button>`
                                );
                            });
                            
                            // Set current year to the most recent one if not set
                            if (!currentYear && years.length > 0) {
                                currentYear = years[0];
                                $('[data-year="' + currentYear + '"]').addClass('active');
                            }
                            loadReports();
                        } else {
                            $('#reportResults').html('<div class="no-data">No report data available for selected center</div>');
                        }
                        $('#loadingIndicator').hide();
                    },
                    error: function() {
                        $('#loadingIndicator').hide();
                        $('#reportResults').html('<div class="no-data">Error loading data</div>');
                    }
                });
            }
            
            // Load reports based on current filters
            function loadReports() {
                if (!currentCenter) {
                    $('#reportResults').html('<div class="no-data">Please select a center to view reports</div>');
                    return;
                }
                
                $('#loadingIndicator').show();
                $('#reportResults').empty();
                
                $.ajax({
                    url: window.location.href.split('?')[0] + '?ajax=get_reports',
                    type: 'GET',
                    data: { 
                        center: currentCenter,
                        year: currentYear,
                        month: currentMonth,
                        week: currentWeek
                    },
                    success: function(data) {
                        $('#loadingIndicator').hide();
                                                
                        if (data.reports && data.reports.length > 0) {
                            let html = `
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Day</th>
                                            <th>AI Services</th>
                                            <th>Remarks</th>
                                            <th>Actions</th>
                                        </tr>
                                        <tr class="total-row">
                                            <td>Total</td>
                                            <td>Count: ${data.count}</td>
                                            <td>${data.total}</td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    </thead>
                                    <tbody>`;
  
                            let previousWeek = null;
                            let toggleColor = false;

                            data.reports.forEach(row => {
                                const dateObj = new Date(row.date);
                                const firstJan = new Date(dateObj.getFullYear(), 0, 1);
                                const pastDaysOfYear = (dateObj - firstJan) / 86400000;
                                const week = Math.ceil((pastDaysOfYear + firstJan.getDay() + 1) / 7);

                                if (week !== previousWeek) {
                                    toggleColor = !toggleColor;
                                    previousWeek = week;
                                }

                                const rowClass = toggleColor ? 'week-grey' : 'week-white';
                                const dayOfWeek = dateObj.toLocaleDateString('en-US', { weekday: 'long' });
                                const formattedDate = dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }).replace(',', '');

                                html += `
                                <tr class="${rowClass}">
                                    <td>${formattedDate}</td>
                                    <td>${dayOfWeek}</td>
                                    <td>
                                        <span class="view-mode">${row.aiServices}</span>
                                        <input type="number" class="edit-mode form-control" value="${row.aiServices}" style="display: none;">
                                    </td>
                                    <td>
                                        <span class="view-mode">${row.remarks ? row.remarks : ''}</span>
                                        <textarea class="edit-mode form-control" style="display: none; rows="2">${row.remarks ? row.remarks : ''}</textarea>
                                    </td>
                                    <td class="action-btns">
                                        <button class="edit-btn" data-id="${row.aiID}">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="delete-btn" data-id="${row.aiID}">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </td>
                                </tr>`;
                            });

                            html += `</tbody></table>`;
                            $('#reportResults').html(html);
                        } else {
                            $('#reportResults').html('<div class="no-data">No data found for the selected filters</div>');
                        }
                    },
                    error: function() {
                        $('#loadingIndicator').hide();
                        $('#reportResults').html('<div class="no-data">Error loading reports</div>');
                    }
                });
            }

            // Load available weeks for a month
            function loadWeeks(year, month) {
                if (!currentCenter || !year || !month) {
                    $('#weekFilter').empty();
                    return;
                }
                
                $.ajax({
                    url: window.location.href.split('?')[0] + '?ajax=get_weeks',
                    type: 'GET',
                    data: { 
                        center: currentCenter,
                        year: year,
                        month: month
                    },
                    success: function(weeks) {
                        const weekFilter = $('#weekFilter');
                        weekFilter.empty();
                        
                        if (weeks.length > 0) {
                            weeks.forEach(week => {
                                weekFilter.append(
                                    `<button class="filter-btn week-btn" data-week="${week}">Week ${week}</button>`
                                );
                            });
                        }
                    }
                });
            }
            
            // Initialize the report
            updateExportButtonState();
            
            // Center selection change
            $('#centerSelect').change(function() {
                currentCenter = $(this).val();
                currentYear = null;
                currentMonth = null;
                currentWeek = null;
                
                $('[data-year]').removeClass('active');
                $('[data-month]').removeClass('active');
                $('[data-week]').removeClass('active');
                $('#weekFilter').empty();
                
                updateExportButtonState();
                loadYears();
            });
            
            // Event handlers for filter buttons
            $(document).on('click', '[data-year]', function() {
                    currentYear = $(this).data('year');
                    currentMonth = null;
                    currentWeek = null;
                    
                    $('[data-year]').removeClass('active');
                    $(this).addClass('active');
                    $('[data-month]').removeClass('active');
                    $('#weekFilter').empty();
                    
                    loadReports();
            });
            
            $(document).on('click', '[data-month]', function() {
                currentMonth = $(this).data('month');
                currentWeek = null;
                
                $('[data-month]').removeClass('active');
                $(this).addClass('active');
                
                if (currentYear) {
                    loadWeeks(currentYear, currentMonth);
                }
                
                loadReports();
            });
            
            $(document).on('click', '[data-week]', function() {
                currentWeek = $(this).data('week');
                
                $('[data-week]').removeClass('active');
                $(this).addClass('active');
                
                loadReports();
            });

            // Handle delete button click
            $(document).on('click', '.delete-btn', function() {
                const aiID = $(this).data('id');
                const row = $(this).closest('tr');
                
                $('#deleteAiID').val(aiID);
                $('#deleteDate').text(row.find('td:eq(0)').text());
                $('#deleteAiServices').text(row.find('td:eq(2)').text());
                
                deleteModal.style.display = "block";
            });

            // Handle delete form submission
            $('#deleteReportForm').submit(function(e) {
                e.preventDefault();
                
                const formData = $(this).serialize();
                
                $.ajax({
                    url: window.location.href.split('?')[0] + '?ajax=delete_report',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            deleteModal.style.display = "none";
                            loadReports(); // Refresh the table
                        } else {
                            alert('Failed to delete report. Please try again.');
                        }
                    },
                    error: function() {
                        alert('Error deleting report. Please try again.');
                    }
                });
            });

            $(document).on('click', '.edit-btn', function() {
                const $btn = $(this);
                const $row = $btn.closest('tr');
                const aiID = $btn.data('id');
                
                // Store original values
                $row.data('original', {
                    services: $row.find('.view-mode:eq(0)').text(),
                    remarks: $row.find('.view-mode:eq(1)').text()
                });
                
                // Switch to edit mode
                $row.find('.view-mode').hide();
                $row.find('.edit-mode').show();
                
                // Change buttons
                $row.find('.action-btns').html(`
                    <button class="btn btn-success save-btn" data-id="${aiID}">
                        <i class="fas fa-check"></i>
                    </button>
                    <button class="btn btn-danger cancel-btn">
                        <i class="fas fa-times"></i>
                    </button>
                `);
            });

            $(document).on('click', '.save-btn', function() { 
                const $btn = $(this);
                const $row = $btn.closest('tr');
                const aiID = $btn.data('id');
                
                const newServices = $row.find('input.edit-mode').val();
                const newRemarks = $row.find('textarea.edit-mode').val();
                
                $.ajax({
                    url: '?ajax=update_report',
                    method: 'POST',
                    data: {
                        aiID: aiID,
                        aiServices: newServices,
                        remarks: newRemarks
                    },
                    success: function(response) {
                        if (response.success) {
                            $row.find('.view-mode:eq(0)').text(newServices);
                            $row.find('.view-mode:eq(1)').text(newRemarks);
                            exitEditMode($row);
                        } else {
                            alert('Error saving changes');
                        }
                    }
                });                
            });

            $(document).on('click', '.cancel-btn', function() {
                const $row = $(this).closest('tr');
                const original = $row.data('original');
                
                $row.find('input.edit-mode').val(original.services);
                $row.find('textarea.edit-mode').val(original.remarks);
                exitEditMode($row);
            });

            function exitEditMode($row) {
                $row.find('.view-mode').show();
                $row.find('.edit-mode').hide();
                $row.find('.action-btns').html(`
                    <button class="edit-btn" data-id="${$row.find('.save-btn').data('id')}">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="delete-btn" data-id="${$row.find('.save-btn').data('id')}">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                `);
            }            

            $('#exportToExcel').click(function() {
                if (!currentCenter) return;
                
                const today = new Date();
                const dateStr = today.toISOString().split('T')[0];
                const centerName = $('#centerSelect option:selected').text();
                
                let fileName = `AI_Services_${centerName.replace(/\s+/g, '_')}`;
                if (currentYear) fileName += `_${currentYear}`;
                if (currentMonth) fileName += `_Month${currentMonth}`;
                if (currentWeek) fileName += `_Week${currentWeek}`;
                fileName += `_${dateStr}.csv`;

                let csvContent = "Date,Day,AI Services,Remarks\n";

                // Add total row
                const totalRow = $('.report-table .total-row');
                if (totalRow.length) {
                    const totalCells = totalRow.find('td');
                    csvContent += `Total,,${totalCells.eq(2).text().trim()},\n`;
                }

                $('.report-table tbody tr').each(function() {
                    if (!$(this).hasClass('total-row')) {
                        const cells = $(this).find('td');
                        const row = [
                            cells.eq(0).text().trim(),  // Date
                            cells.eq(1).text().trim(),  // Day
                            cells.eq(2).text().trim(),  // AI Services
                            cells.eq(3).text().trim()   // Remarks
                        ];
                        csvContent += row.join(',') + '\n';
                    }
                });

                const encodedUri = encodeURI('data:text/csv;charset=utf-8,' + csvContent);
                const link = document.createElement('a');
                link.setAttribute('href', encodedUri);
                link.setAttribute('download', fileName);
                document.body.appendChild(link);

                link.click();
                document.body.removeChild(link);
            });
        });
    </script>
</body>
</html>