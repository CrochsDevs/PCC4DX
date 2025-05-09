<?php
include('db_config.php');
session_start();

$centerCode = $_SESSION['center_code'];
$sort = $_GET['sort'] ?? 'partner_name';
$order = $_GET['order'] ?? 'ASC';
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$validColumns = ['partner_name', 'coop_type', 'herd_code', 'is_active'];
$sort = in_array($sort, $validColumns) ? $sort : 'partner_name';
$order = in_array(strtoupper($order), ['ASC', 'DESC']) ? strtoupper($order) : 'ASC';

// Base queries
$totalQuery = "SELECT COUNT(*) as total FROM partners WHERE center_code = :center_code";
$query = "SELECT * FROM partners WHERE center_code = :center_code";
$params = [':center_code' => $centerCode];

// Search handling
if (!empty($search)) {
    $search = trim($search);
    $searchTerms = array_filter(array_map('trim', explode(',', $search)));
    
    if (!empty($searchTerms)) {
        $conditions = [];
        $statusConditions = [];
        
        foreach ($searchTerms as $index => $term) {
            $termLower = strtolower($term);
            if ($termLower === 'active') {
                $statusConditions[] = "is_active = 1";
            } elseif ($termLower === 'inactive') {
                $statusConditions[] = "is_active = 0";
            } else {
                $param = ":search".$index;
                $conditions[] = "(LOWER(partner_name) LIKE LOWER($param) OR 
                                LOWER(herd_code) LIKE LOWER($param) OR 
                                LOWER(contact_person) LIKE LOWER($param) OR 
                                LOWER(contact_number) LIKE LOWER($param) OR 
                                LOWER(municipality) LIKE LOWER($param) OR 
                                LOWER(province) LIKE LOWER($param) OR 
                                LOWER(coop_type) LIKE LOWER($param))";
                $params[$param] = "%$term%";
            }
        }
        
        // Combine all conditions
        $allConditions = [];
        if (!empty($conditions)) {
            $allConditions[] = "(".implode(" OR ", $conditions).")";
        }
        if (!empty($statusConditions)) {
            $allConditions[] = "(".implode(" OR ", $statusConditions).")";
        }
        
        if (!empty($allConditions)) {
            $query .= " AND (".implode(" OR ", $allConditions).")";
            $totalQuery .= " AND (".implode(" OR ", $allConditions).")";
        }
    }
}

// Filter handling
if (!empty($filter)) {
    $filterTypes = explode(',', $filter);
    $placeholders = implode(',', array_map(function($i) { 
        return ":filter$i"; 
    }, array_keys($filterTypes)));
    
    $query .= " AND coop_type IN ($placeholders)";
    $totalQuery .= " AND coop_type IN ($placeholders)";
    
    foreach ($filterTypes as $i => $type) {
        $params[":filter$i"] = $type;
    }
}

// Get total count
$stmtTotal = $conn->prepare($totalQuery);
$stmtTotal->execute($params);
$totalCount = $stmtTotal->fetchColumn();

// Add sorting and pagination
$query .= " ORDER BY is_active DESC, $sort $order LIMIT :limit OFFSET :offset";
$params[':limit'] = $limit;
$params[':offset'] = $offset;

// Fetch partners
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate HTML for partners
$html = '';
foreach ($partners as $partner) {
    $html .= '<tr class="clickable-row" data-href="select.php?partner_id='.$partner['id'].'">';
    $html .= '<td>'.htmlspecialchars($partner['partner_name']).'</td>';
    $html .= '<td>'.htmlspecialchars($partner['coop_type']).'</td>';
    $html .= '<td>'.htmlspecialchars($partner['herd_code']).'</td>';
    $html .= '<td><div>'.htmlspecialchars($partner['contact_person']).'</div><small class="text-muted">'.htmlspecialchars($partner['contact_number']).'</small></td>';
    $html .= '<td><div>'.htmlspecialchars($partner['barangay']).'</div><small class="text-muted">'.htmlspecialchars($partner['municipality']).', '.htmlspecialchars($partner['province']).'</small></td>';
    $html .= '<td><span class="status-badge '.($partner['is_active'] ? 'status-active' : 'status-inactive').'">'.($partner['is_active'] ? 'Active' : 'Inactive').'</span></td>';
    $html .= '</tr>';
}

// Generate pagination
$totalPages = ceil($totalCount / $limit);
$pagination = '';
if ($totalPages > 1) {
    $pagination = '<nav aria-label="Page navigation"><ul class="pagination">';
    
    // Previous button
    $pagination .= '<li class="page-item '.($page <= 1 ? 'disabled' : '').'">';
    $pagination .= '<a class="page-link" href="?'.http_build_query(array_merge($_GET, ['page' => $page - 1])).'" aria-label="Previous">';
    $pagination .= '<span aria-hidden="true">&laquo;</span></a></li>';
    
    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        $pagination .= '<li class="page-item '.($i == $page ? 'active' : '').'">';
        $pagination .= '<a class="page-link" href="?'.http_build_query(array_merge($_GET, ['page' => $i])).'">'.$i.'</a></li>';
    }
    
    // Next button
    $pagination .= '<li class="page-item '.($page >= $totalPages ? 'disabled' : '').'">';
    $pagination .= '<a class="page-link" href="?'.http_build_query(array_merge($_GET, ['page' => $page + 1])).'" aria-label="Next">';
    $pagination .= '<span aria-hidden="true">&raquo;</span></a></li>';
    
    $pagination .= '</ul></nav>';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'html' => $html ?: '<tr><td colspan="6" class="text-center">No partners found matching your criteria</td></tr>',
    'count' => count($partners),
    'total' => $totalCount,
    'pagination' => $pagination,
    'showing_from' => $offset + 1,
    'showing_to' => min($offset + $limit, $totalCount)
]);