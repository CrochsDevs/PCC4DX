<?php
session_start();
require 'auth_check.php';
include('db_config.php');

// Check if user is from HQ
if ($_SESSION['user']['center_code'] !== 'HQ') {
    header('Location: access_denied.php');
    exit;
}

class CalfDropReportManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getReports($centerCode, $year = null, $month = null, $week = null, $date = null) {
        $query = "SELECT cdID, ai, bep, ih, private, remarks, date, center
                FROM pcc_auth_system.calf_drop
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
        $totals = ['ai' => 0, 'bep' => 0, 'ih' => 0, 'private' => 0, 'total' => 0];
        foreach ($reports as $row) {
            $totals['ai'] += (int)$row['ai'];
            $totals['bep'] += (int)$row['bep'];
            $totals['ih'] += (int)$row['ih'];
            $totals['private'] += (int)$row['private'];
            $totals['total'] += (int)$row['ai'] + (int)$row['bep'] + (int)$row['ih'] + (int)$row['private'];
        }

        $count = count($reports);

        return [
            'reports' => $reports,
            'totals' => $totals,
            'count' => $count
        ];
    }

    public function getAvailableYears($centerCode) {
        $query = "SELECT DISTINCT YEAR(date) as year
                  FROM pcc_auth_system.calf_drop
                  WHERE center = :center
                  ORDER BY year DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':center' => $centerCode]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function getAvailableWeeks($year, $month, $centerCode) {
        $query = "SELECT DISTINCT WEEK(date, 3) as week
                  FROM pcc_auth_system.calf_drop
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

    public function updateReport($cdID, $ai, $bep, $ih, $private, $remarks) {
        $query = "UPDATE calf_drop 
                SET ai = :ai, bep = :bep, ih = :ih, private = :private, remarks = :remarks 
                WHERE cdID = :cdID";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':cdID' => $cdID,
            ':ai' => $ai,
            ':bep' => $bep,
            ':ih' => $ih,
            ':private' => $private,
            ':remarks' => $remarks
        ]);
    }

    public function deleteReport($cdID) {
        $query = "DELETE FROM calf_drop WHERE cdID = :cdID";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':cdID' => $cdID]);
    }

}

$reportManager = new CalfDropReportManager($conn);

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
            if (isset($_POST['cdID']) && isset($_POST['ai']) && isset($_POST['bep']) && isset($_POST['ih']) && isset($_POST['private']) && isset($_POST['remarks'])) {
                $success = $reportManager->updateReport(
                    $_POST['cdID'],
                    $_POST['ai'],
                    $_POST['bep'],
                    $_POST['ih'],
                    $_POST['private'],
                    $_POST['remarks']
                );
                echo json_encode(['success' => $success]);
            } else {
                echo json_encode(['error' => 'Missing parameters']);
            }
            break;


        case 'delete_report':
            if (isset($_POST['cdID'])) {
                $success = $reportManager->deleteReport($_POST['cdID']);
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
    <title>HQ Calf Drop Report</title>
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
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
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
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-left: 20px;
            margin-right: 40px;
            
        }
        .report-table th, .report-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
             
        }
        .report-table th {
            background-color:#3a7fc5;
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

        <nav>
            <ul>
                <li><a href="admin.php#quickfacts-section" class="nav-link">
                    <i class="fa-solid fa-arrow-left"></i> Back to Admin</a></li>

                <li><a href="admin_cd_dashboard.php" class="nav-link " data-section="dashboard-section">
                    <i class="fas fa-chart-line"></i> Dashboard</a></li>

                <li><a href="admin_centertarget_calf_dashboard.php" class="nav-link" data-section="announcement-section">
                    <i class="fas fa-file-alt"></i> Center Target</a></li>
                
                <li><a href="admin_report_calf_dashboard.php" class="nav-link active" data-section="quickfacts-section">
                    <i class="fas fa-sitemap"></i> Reports</a></li>
            </ul>
        </nav>
</div>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <header class="mb-10">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="title">Calf Drop Reports</h1>
                    <p class="subtitle">HQ Dashboard - View Center CD Reports</p>
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

        <!-- Edit Report Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Edit Calf Drop Report</h3>
                    <span class="close">&times;</span>
                </div>
                <form id="editReportForm">
                    <div class="modal-body">
                        <input type="hidden" id="editCdID" name="cdID">
                        <div class="form-group">
                            <label for="editDate">Date</label>
                            <input type="text" id="editDate" class="form-control" disabled>
                        </div>
                        <div class="form-group">
                            <label for="editAI">AI</label>
                            <input type="number" id="editAI" name="ai" class="form-control" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="editBEP">BEP</label>
                            <input type="number" id="editBEP" name="bep" class="form-control" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="editIH">IH</label>
                            <input type="number" id="editIH" name="ih" class="form-control" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="editPrivate">Private</label>
                            <input type="number" id="editPrivate" name="private" class="form-control" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="editRemarks">Remarks</label>
                            <textarea id="editRemarks" name="remarks" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close">Cancel</button>
                        <button type="submit" class="btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Confirm Delete</h3>
                    <span class="close">&times;</span>
                </div>
                <form id="deleteReportForm">
                    <div class="modal-body">
                        <input type="hidden" id="deleteCdID" name="cdID">
                        <p>Are you sure you want to delete this calf drop report?</p>
                        <p><strong>Date:</strong> <span id="deleteDate"></span></p>
                        <p><strong>AI:</strong> <span id="deleteAI"></span></p>
                        <p><strong>BEP:</strong> <span id="deleteBEP"></span></p>
                        <p><strong>IH:</strong> <span id="deleteIH"></span></p>
                        <p><strong>Private:</strong> <span id="deletePrivate"></span></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        $(document).ready(function() {
            let currentCenter = $('#centerSelect').val();
            let currentYear = null;
            let currentMonth = null;
            let currentWeek = null;
            
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
                                        <th>AI</th>
                                        <th>BEP</th>
                                        <th>IH</th>
                                        <th>Private</th>
                                        <th>Remarks</th>
                                        <th>Total</th>
                                        <th>Actions</th>
                                    </tr>
                                    <tr class="total-row">
                                        <td>Total</td>
                                        <td>Count: ${data.count}</td>
                                        <td>${data.totals.ai}</td>
                                        <td>${data.totals.bep}</td>
                                        <td>${data.totals.ih}</td>
                                        <td>${data.totals.private}</td>
                                        <td></td>
                                        <td>${data.totals.total}</td>
                                        <td></td>
                                    </tr>
                                </thead>
                                <tbody>`;

                        let previousWeek = null;
                        let toggleColor = false;

                        data.reports.forEach(row => {
                            const dateObj = new Date(row.date);
                            
                            const formattedDate = dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }).replace(',', '');
                            const firstJan = new Date(dateObj.getFullYear(), 0, 1);
                            const pastDaysOfYear = (dateObj - firstJan) / 86400000;
                            const week = Math.ceil((pastDaysOfYear + firstJan.getDay() + 1) / 7);

                            if (week !== previousWeek) {
                                toggleColor = !toggleColor;
                                previousWeek = week;
                            }

                            const rowClass = toggleColor ? 'week-grey' : 'week-white';
                            const dayOfWeek = dateObj.toLocaleDateString('en-US', { weekday: 'long' });
                            const total = Number(row.ai) + Number(row.bep) + Number(row.ih) + Number(row.private);

                        // Inside loadReports success function when building the table
                        html += `
                        <tr class="${rowClass}">
                            <td>${formattedDate}</td>
                            <td>${dayOfWeek}</td>
                            <td>${row.ai}</td>
                            <td>${row.bep}</td>
                            <td>${row.ih}</td>
                            <td>${row.private}</td>
                            <td>${row.remarks ? row.remarks : ''}</td>
                            <td>${total}</td>
                            <td class="action-btns">
                                <button class="edit-btn" data-id="${row.cdID}">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="delete-btn" data-id="${row.cdID}">
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
            
            // Handle edit button click
            $(document).on('click', '.edit-btn', function() {
                const cdID = $(this).data('id');
                const row = $(this).closest('tr');
                
                $('#editCdID').val(cdID);
                $('#editDate').val(row.find('td:eq(0)').text());
                $('#editAI').val(row.find('td:eq(2)').text());
                $('#editBEP').val(row.find('td:eq(3)').text());
                $('#editIH').val(row.find('td:eq(4)').text());
                $('#editPrivate').val(row.find('td:eq(5)').text());
                $('#editRemarks').val(row.find('td:eq(6)').text().trim());
                
                editModal.style.display = "block";
            });

            // Handle delete button click
            $(document).on('click', '.delete-btn', function() {
                const cdID = $(this).data('id');
                const row = $(this).closest('tr');
                
                $('#deleteCdID').val(cdID);
                $('#deleteDate').text(row.find('td:eq(0)').text());
                $('#deleteAI').text(row.find('td:eq(2)').text());
                $('#deleteBEP').text(row.find('td:eq(3)').text());
                $('#deleteIH').text(row.find('td:eq(4)').text());
                $('#deletePrivate').text(row.find('td:eq(5)').text());
                
                deleteModal.style.display = "block";
            });

            // Edit form submission
            $('#editReportForm').submit(function(e) {
                e.preventDefault();
                const formData = $(this).serialize();
                
                $.ajax({
                    url: window.location.href.split('?')[0] + '?ajax=update_report',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#editModal').hide();
                            loadReports();
                        } else {
                            alert('Failed to update report: ' + (response.error || 'Unknown error'));
                        }
                    },
                    error: function(xhr) {
                        alert('Error updating report: ' + xhr.statusText);
                    }
                });
            });

            // Delete form submission
            $('#deleteReportForm').submit(function(e) {
                e.preventDefault();
                const formData = $(this).serialize();
                
                $.ajax({
                    url: window.location.href.split('?')[0] + '?ajax=delete_report',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#deleteModal').hide();
                            loadReports();
                        } else {
                            alert('Failed to delete report: ' + (response.error || 'Unknown error'));
                        }
                    },
                    error: function(xhr) {
                        alert('Error deleting report: ' + xhr.statusText);
                    }
                });
            });

            // Close modals when clicking close buttons
            $('.modal .close, .modal .btn-secondary').click(function() {
                $(this).closest('.modal').hide();
            });

            // Close modal when clicking outside
            window.onclick = function(event) {
                if (event.target.className === 'modal') {
                    event.target.style.display = 'none';
                }
            }

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

                // CSV header with all columns
                let csvContent = "Date,Day,AI,BEP,IH,Private,Remarks,Total\n";

                // Add total row immediately below header (if exists)
                const totalRow = $('.report-table .total-row');
                if (totalRow.length) {
                    const totalCells = totalRow.find('td');
                    const totalLine = [
                        'Total',       // Label for first column
                        '',            // Empty Day
                        totalCells.eq(2).text().trim(),  // AI total
                        totalCells.eq(3).text().trim(),  // BEP total
                        totalCells.eq(4).text().trim(),  // IH total
                        totalCells.eq(5).text().trim(),  // Private total
                        totalCells.eq(6).text().trim(),  // Remarks
                        totalCells.eq(7).text().trim()   // Total total
                    ];
                    csvContent += totalLine.join(',') + '\n';
                }

                // Add all data rows except total-row
                $('.report-table tbody tr').each(function() {
                    if (!$(this).hasClass('total-row')) {
                        const cells = $(this).find('td');
                        const row = [
                            cells.eq(0).text().trim(),  // Date
                            cells.eq(1).text().trim(),  // Day
                            cells.eq(2).text().trim(),  // AI
                            cells.eq(3).text().trim(),  // BEP
                            cells.eq(4).text().trim(),  // IH
                            cells.eq(5).text().trim(),  // Private
                            '"' + cells.eq(6).text().trim().replace(/"/g, '""') + '"', // Remarks with quotes for CSV safety
                            cells.eq(7).text().trim()   // Total
                        ];
                        csvContent += row.join(',') + '\n';
                    }
                });

                // Encode and download CSV
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