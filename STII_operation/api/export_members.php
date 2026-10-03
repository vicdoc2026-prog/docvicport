<?php
require_once '../config/conn.php';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=cooperative_members_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add column headers
fputcsv($output, array('ID', 'Name', 'Position', 'Membership Date', 'Status', 'Loan Check Number', 'Loan Date', 'Loan Batch', 'Loan Amount'));

// Fetch all members with loan data
$sql = "SELECT m.*, l.check_number, l.loan_date, l.batch, l.amount 
        FROM members m 
        LEFT JOIN loans l ON m.id = l.member_id 
        ORDER BY m.id ASC";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        fputcsv($output, array(
            $row['id'],
            $row['full_name'],
            ucwords(str_replace('-', ' ', $row['position'])),
            $row['date_of_membership'],
            ucfirst($row['status']),
            $row['check_number'] ?? 'N/A',
            $row['loan_date'] ?? 'N/A',
            $row['batch'] ?? 'N/A',
            $row['amount'] ?? '0.00'
        ));
    }
}

fclose($output);
$conn->close();
exit();
?>