<?php
session_start();
require 'auth_check.php';
include('db_config.php');

// Prevent headquarters users from accessing center dashboard
if ($_SESSION['user']['center_type'] === 'Headquarters') {
    header('Location: access_denied.php');
    exit;
}

class ReportManager {
    private $db;
    private $centerCode;
    
    public function __construct($db, $centerCode) {
        $this->db = $db;
        $this->centerCode = $centerCode;
    }
    
    public function getReports($year = null, $month = null, $week = null, $date = null) {
        $query = "SELECT *, 
                remarks, 
                (ai + bep + ih + private) as total 
                FROM calf_drop 
                WHERE center = :center";

        $params = [':center' => $this->centerCode];
        
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
            $totals['ai'] += $row['ai'];
            $totals['bep'] += $row['bep'];
            $totals['ih'] += $row['ih'];
            $totals['private'] += $row['private'];
            $totals['total'] += $row['total'];
        }

        $count = count($reports);

        return ['reports' => $reports, 'totals' => $totals, 'count' => $count];
    }

    public function getAvailableYears() {
        $query = "SELECT DISTINCT YEAR(date) as year 
                  FROM calf_drop 
                  WHERE center = :center 
                  ORDER BY year DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':center' => $this->centerCode]);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    
    public function getAvailableWeeks($year, $month) {
        $query = "SELECT DISTINCT WEEK(date, 3) as week 
                  FROM calf_drop 
                  WHERE center = :center 
                  AND YEAR(date) = :year 
                  AND MONTH(date) = :month
                  ORDER BY week";
                  
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':center' => $this->centerCode,
            ':year' => $year,
            ':month' => $month
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}

$centerCode = $_SESSION['center_code'];
$reportManager = new ReportManager($conn, $centerCode);

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'get_years':
            echo json_encode($reportManager->getAvailableYears());
            break;
            
        case 'get_weeks':
            if (isset($_GET['year']) && isset($_GET['month'])) {
                echo json_encode($reportManager->getAvailableWeeks($_GET['year'], $_GET['month']));
            } else {
                echo json_encode([]);
            }
            break;
            
        case 'get_reports':
            $year = isset($_GET['year']) ? $_GET['year'] : null;
            $month = isset($_GET['month']) ? $_GET['month'] : null;
            $week = isset($_GET['week']) ? $_GET['week'] : null;
            echo json_encode($reportManager->getReports($year, $month, $week));
            break;
            
        default:
            echo json_encode(['error' => 'Invalid AJAX request']);
    }
    exit;
}

$centerCode = $_SESSION['center_code'];
$year = isset($_GET['year']) ? $_GET['year'] : null;
$month = isset($_GET['month']) ? $_GET['month'] : null;
$week = isset($_GET['week']) ? $_GET['week'] : null;

$reportManager = new ReportManager($conn, $centerCode);
$reports = $reportManager->getReports($year, $month, $week);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($_SESSION['user']['center_name']) ?> Reports</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
    <link rel="stylesheet" href="css/calf.css">
    <link rel="stylesheet" href="css/cd_report.css">
    <style>
    /* Week-based alternating colors */
        .week-white {
            background-color: #ffffff;
        }

        .week-grey {
            background-color: #f0f0f0;
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

        <nav>
            <ul>
                <li><a href="services.php" class="nav-link"><i class="fa-solid fa-arrow-left"></i> Back to quickfacts</a></li>
                <li><a href="cd_dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="calf_drop.php" class="nav-link"><i class="fas fa-plus-circle"></i> Calf Drop</a></li>
                <li><a href="cd_report.php" class="nav-link active"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h1>Calf Drop Reports</h1>
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
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <!-- Reports Section -->
        <div class="container">
            <div class="filter-container">

            <div class="year-filter">
                <div class="filter-header">
                    <div class="filter-title">Year</div>
                    <div class="export-btn-container">
                        <button id="exportToExcel" class="export-btn">Export</button>
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
                    <!-- Report data will be loaded here via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            const centerCode = "<?= $_SESSION['center_code'] ?>";
            let currentYear = null;
            let currentMonth = null;
            let currentWeek = null;

            function formatDate(dateString) {
                const options = { year: 'numeric', month: 'short', day: 'numeric' };
                const dateObj = new Date(dateString);
                return dateObj.toLocaleDateString('en-US', options);
            }
            
            // Load available years
            function loadYears() {
                $.ajax({
                    url: 'cd_report.php?ajax=get_years',
                    type: 'GET',
                    data: { center: centerCode },
                    success: function(years) {
                        const yearFilter = $('#yearFilter');
                        yearFilter.empty();
                        
                        if (years.length > 0) {
                            years.forEach(year => {
                                yearFilter.append(
                                    `<button class="filter-btn" data-year="${year}">${year}</button>`
                                );
                            });
                            
                            // Set current year to the most recent one
                            currentYear = years[0];
                            $('[data-year="' + currentYear + '"]').addClass('active');
                            loadReports();
                        } else {
                            $('#reportResults').html('<div class="no-data">No report data available</div>');
                        }
                    }
                });
            }
            
            // Load reports based on current filters
            function loadReports() {
                $('#loadingIndicator').show();
                $('#reportResults').empty();
                
                $.ajax({
                    url: 'cd_report.php?ajax=get_reports',
                    type: 'GET',
                    data: { 
                        center: centerCode,
                        year: currentYear,
                        month: currentMonth,
                        week: currentWeek
                    },
                    success: function(data) {
                        $('#loadingIndicator').hide();
                        
                        if (data.reports.length > 0) {
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
                                </tr>
                                <tr class="total-row" style="background-color:rgb(125, 139, 139);">
                                    <td style="color: black; font-weight: bold;"><strong>Total</strong></td>
                                    <td style="color: black; font-weight: bold;"><strong>Count: ${data.count}</strong></td>
                                    <td style="color: black; font-weight: bold;"><strong>${data.totals.ai}</strong></td>
                                    <td style="color: black; font-weight: bold;"><strong>${data.totals.bep}</strong></td>
                                    <td style="color: black; font-weight: bold;"><strong>${data.totals.ih}</strong></td>
                                    <td style="color: black; font-weight: bold;"><strong>${data.totals.private}</strong></td>
                                    <td style="color: black; font-weight: bold;"></td>
                                    <td style="color: black; font-weight: bold;"><strong>${data.totals.total}</strong></td>
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

                                        html += `
                                        <tr class="${rowClass}">
                                            <td>${formatDate(row.date)}</td>
                                            <td>${dayOfWeek}</td>
                                            <td>${row.ai}</td>
                                            <td>${row.bep}</td>
                                            <td>${row.ih}</td>
                                            <td>${row.private}</td>
                                            <td>${row.remarks ? row.remarks : ''}</td>
                                            <td>${row.total}</td>
                                        </tr>`;
                                    });

                            html += `</tbody></table>`;
                            $('#reportResults').html(html);
                        } else {
                            $('#reportResults').html('<div class="no-data">No data found for the selected filters</div>');
                        }
                    }
                });
            }

            // Load available weeks for a month
            function loadWeeks(year, month) {
                $.ajax({
                    url: 'cd_report.php?ajax=get_weeks',
                    type: 'GET',
                    data: { 
                        center: centerCode,
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
            loadYears();
            
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
                if (!currentYear) {
                    alert("Please select a year to export the report.");
                    return;
                }
                if (!currentMonth) {
                    alert("Please select a month to export the report.");
                    return;
                }

                // Use current selected week or empty string if none
                const weekNumber = currentWeek || '';

                // Header for CSV
                let csvContent = "Date,Day,AI,BEP,IH,Private,Remarks,Total\n";

                // Get totals row cells, note: your total row's TD structure:
                // 0: Total label, 1: empty, 2: AI total, 3: BEP total, 4: IH total, 5: Private total, 6: empty, 7: Total total
                const totalsRow = $('.report-table .total-row');
                if (totalsRow.length) {
                    const totalsCells = totalsRow.find('td');
                    const totals = [
                        `Total ${centerCode}`,          // Date column with center + Total label
                        "",                            // Day empty
                        totalsCells.eq(2).text().trim(), // AI total (index 2)
                        totalsCells.eq(3).text().trim(), // BEP total (index 3)
                        totalsCells.eq(4).text().trim(), // IH total (index 4)
                        totalsCells.eq(5).text().trim(), // Private total (index 5)
                        "",                            // Remarks empty for totals row
                        totalsCells.eq(7).text().trim()  // Total total (index 7)
                    ];
                    csvContent += totals.join(',') + '\n';
                }

                // Iterate over each report row (skip total row)
                $('.report-table tbody tr').not('.total-row').each(function() {
                    const cells = $(this).find('td');
                    const rawDate = cells.eq(0).text().trim();

                    // Parse date string from table, assuming format like "May 10, 2025"
                    const dateObj = new Date(rawDate);
                    // Format date as yyyy-mm-dd for CSV
                    const formattedDate = dateObj.toISOString().split('T')[0];

                    // Get day of week from date
                    const dayOfWeek = dateObj.toLocaleDateString('en-US', { weekday: 'long' });

                    const row = [
                        formattedDate,                 // Date in yyyy-mm-dd
                        dayOfWeek,                    // Day
                        cells.eq(2).text().trim(),    // AI
                        cells.eq(3).text().trim(),    // BEP
                        cells.eq(4).text().trim(),    // IH
                        cells.eq(5).text().trim(),    // Private
                        '"' + cells.eq(6).text().trim().replace(/"/g, '""') + '"',  // Remarks (quoted to handle commas)
                        cells.eq(7).text().trim()     // Total
                    ];
                    csvContent += row.join(',') + '\n';
                });

                // Prepare file name
                const today = new Date();
                const dateStr = today.toISOString().split('T')[0];
                const fileName = `CalfDrop_${centerCode}${weekNumber ? '_Week' + weekNumber : ''}_${dateStr}.csv`;

                // Create and trigger download
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