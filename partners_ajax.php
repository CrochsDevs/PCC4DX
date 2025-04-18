<?php
include('db_config.php');
session_start();

$centerCode = $_SESSION['center_code'];
$sort = $_GET['sort'] ?? 'partner_name';
$order = $_GET['order'] ?? 'ASC';
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

$validColumns = ['partner_name', 'coop_type', 'herd_code', 'is_active'];
$sort = in_array($sort, $validColumns) ? $sort : 'partner_name';
$order = in_array(strtoupper($order), ['ASC', 'DESC']) ? strtoupper($order) : 'ASC';

$totalQuery = "SELECT COUNT(*) as total FROM partners WHERE center_code = :center_code";
$query = "SELECT * FROM partners WHERE center_code = :center_code";
$params = [':center_code' => $centerCode];

if (!empty($search)) {
    $search = trim($search);
    if (strtolower($search) === 'active') {
        $query .= " AND is_active = 1";
        $totalQuery .= " AND is_active = 1";
    } elseif (strtolower($search) === 'inactive') {
        $query .= " AND is_active = 0";
        $totalQuery .= " AND is_active = 0";
    } else {
        // Split by comma and clean each term
        $searchTerms = array_filter(array_map('trim', explode(',', $search)));
        
        if (!empty($searchTerms)) {
            $conditions = [];
            $statusConditions = [];
            
            foreach ($searchTerms as $index => $term) {
                $term = strtolower($term);
                if ($term === 'active') {
                    $statusConditions[] = "is_active = 1";
                } elseif ($term === 'inactive') {
                    $statusConditions[] = "is_active = 0";
                } else {
                    $param = ":search".$index;
                    $conditions[] = "(partner_name LIKE $param OR 
                                    herd_code LIKE $param OR 
                                    contact_person LIKE $param OR 
                                    contact_number LIKE $param OR 
                                    municipality LIKE $param OR 
                                    province LIKE $param OR 
                                    coop_type LIKE $param)";
                    $params[$param] = "%$term%";
                    $filterParams[$param] = "%$term%";
                }
            }
            
            // Combine all conditions with AND
            $allConditions = [];
            if (!empty($conditions)) {
                $allConditions[] = "(".implode(" AND ", $conditions).")";
            }
            if (!empty($statusConditions)) {
                $allConditions[] = "(".implode(" AND ", $statusConditions).")";
            }
            
            if (!empty($allConditions)) {
                $query .= " AND ".implode(" AND ", $allConditions);
                $totalQuery .= " AND ".implode(" AND ", $allConditions);
            }
        }
    }
}

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

$stmtTotal = $conn->prepare($totalQuery);
$stmtTotal->execute($params);
$totalCount = $stmtTotal->fetchColumn();

$query .= " ORDER BY $sort $order";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

$html = '';
foreach ($partners as $partner) {
    $html .= '<tr class="clickable-row" data-href="select.php?partner_id='.$partner['id'].'">';
    $html .= '<td>'.htmlspecialchars($partner['partner_name']).'</td>';
    $html .= '<td>'.htmlspecialchars($partner['coop_type']).'</td>';
    $html .= '<td>'.htmlspecialchars($partner['herd_code']).'</td>';
    $html .= '<td><div>'.htmlspecialchars($partner['contact_person']).'</div><small class="text-muted">'.htmlspecialchars($partner['contact_number']).'</small></td>';
    $html .= '<td><div>'.htmlspecialchars($partner['barangay']).'</div><small class="text-muted">'.htmlspecialchars($partner['municipality']).', '.htmlspecialchars($partner['province']).'</small></td>';
    $html .= '<td><form method="POST" class="d-inline-block toggle-status-form"><input type="hidden" name="partner_id" value="'.$partner['id'].'"><button type="submit" name="toggle_status" class="btn btn-sm status-toggle '.($partner['is_active'] ? 'btn-success' : 'btn-danger').'">'.($partner['is_active'] ? 'Active' : 'Inactive').'</button></form></td>';
    $html .= '<td><div class="action-buttons"><button class="btn btn-info btn-sm edit-btn" data-id="'.$partner['id'].'" data-coop="'.htmlspecialchars($partner['coop_type']).'" data-name="'.htmlspecialchars($partner['partner_name']).'" data-herd="'.htmlspecialchars($partner['herd_code']).'" data-person="'.htmlspecialchars($partner['contact_person']).'" data-number="'.htmlspecialchars($partner['contact_number']).'" data-barangay="'.htmlspecialchars($partner['barangay']).'" data-municipality="'.htmlspecialchars($partner['municipality']).'" data-province="'.htmlspecialchars($partner['province']).'" data-active="'.$partner['is_active'].'"><i class="fas fa-edit"></i></button><form method="POST" onsubmit="return confirm(\'Are you sure you want to delete this partner?\');" style="display:inline;"><input type="hidden" name="partner_id" value="'.$partner['id'].'"><button type="submit" name="delete" class="btn btn-danger btn-sm delete-btn"><i class="fas fa-trash"></i></button></form></div></td>';
    $html .= '</tr>';
}

header('Content-Type: application/json');
echo json_encode([
    'html' => $html ?: '<tr><td colspan="7" class="text-center">No partners found matching your criteria</td></tr>',
    'count' => count($partners),
    'total' => $totalCount
]);
?>