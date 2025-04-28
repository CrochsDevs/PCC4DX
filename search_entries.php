<?php
require 'db_config.php';
session_start();

$search = $_POST['search'] ?? '';
$month = $_POST['month'] ?? '';
$centerCode = $_POST['centerCode'] ?? '';

$query = "SELECT 
            mp.*, 
            p.partner_name,
            DATE_FORMAT(mp.entry_date, '%b %d, %Y') as formatted_entry_date,
            IFNULL(DATE_FORMAT(mp.end_date, '%b %d, %Y'), 'N/A') as formatted_end_date
          FROM milk_production mp
          JOIN partners p ON mp.partner_id = p.id
          WHERE mp.center_code = :centerCode
          AND DATE_FORMAT(mp.entry_date, '%Y-%m') = :month
          AND (
              p.partner_name LIKE :search OR
              mp.status LIKE :search OR
              mp.quantity LIKE :search OR
              mp.volume LIKE :search OR
              mp.total LIKE :search OR
              DATE_FORMAT(mp.entry_date, '%b %d, %Y') LIKE :search OR
              DATE_FORMAT(mp.end_date, '%b %d, %Y') LIKE :search
          )
          ORDER BY mp.entry_date DESC";

$stmt = $conn->prepare($query);
$stmt->execute([
    ':centerCode' => $centerCode,
    ':month' => $month,
    ':search' => "%$search%"
]);

while ($entry = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>
            <td>{$entry['formatted_entry_date']}</td>
            <td>{$entry['formatted_end_date']}</td>
            <td>".number_format($entry['quantity'], 2)."</td>
            <td>".number_format($entry['volume'], 2)."</td>
            <td>₱".number_format($entry['total'], 2)."</td>
            <td>".htmlspecialchars($entry['partner_name'])."</td>
            <td><span class='badge ".($entry['status'] === 'Pending' ? 'badge-pending' : 'badge-completed')."'>
                ".htmlspecialchars($entry['status'])."
                </span></td>
          </tr>";
}
?>