<?php
// Include your config file with getDBConnection() and other constants
include 'config.php'; // Make sure this is the correct path

header('Content-Type: application/json');

// Get the requested period, default to 'month'
$period = $_GET['period'] ?? 'month'; 
$pdo = getDBConnection();

$date_format_sql = '';
$date_sub_interval = '';
$data_limit = 0; // Number of periods to show

switch ($period) {
    case 'day':
        // Show the last 30 days
        $date_format_sql = '%Y-%m-%d';
        $date_sub_interval = 'INTERVAL 30 DAY';
        $php_date_format = 'M d';
        $data_limit = 30;
        break;
    case 'week':
        // Show the last 12 weeks
        $date_format_sql = '%Y-%v'; // Year and week number
        $date_sub_interval = 'INTERVAL 12 WEEK';
        $php_date_format = 'Y-W\k W'; 
        $data_limit = 12;
        break;
    case 'month':
    default:
        // Show the last 12 months
        $date_format_sql = '%Y-%m';
        $date_sub_interval = 'INTERVAL 12 MONTH';
        $php_date_format = 'M Y';
        $data_limit = 12;
        break;
}

$sql = "
    SELECT
        DATE_FORMAT(submission_date, '{$date_format_sql}') AS inquiry_period,
        COUNT(inquiry_id) AS inquiry_count
    FROM inquiries
    WHERE submission_date >= DATE_SUB(NOW(), {$date_sub_interval})
    GROUP BY inquiry_period
    ORDER BY inquiry_period ASC
";

$stmt = $pdo->query($sql);
$fetchedData = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $fetchedData[$row['inquiry_period']] = (int)$row['inquiry_count'];
}

// --- Dynamic Date Generation for Complete Data Set ---
$chartLabels = [];
$chartData = [];

for ($i = $data_limit - 1; $i >= 0; $i--) {
    $date_string = "-$i {$period}s";
    
    // PHP date calculation needs to match the SQL grouping logic
    if ($period === 'day') {
        $targetTimestamp = strtotime($date_string);
        $targetPeriodKey = date('Y-m-d', $targetTimestamp);
    } elseif ($period === 'week') {
        // Week number calculation can be tricky, using MySQL's Y-v format for key
        $targetTimestamp = strtotime($date_string);
        $targetPeriodKey = date('Y-W', $targetTimestamp);
        $php_date_format = 'M d, Y'; // Use a standard date for the label
    } else { // month
        $targetTimestamp = strtotime($date_string);
        $targetPeriodKey = date('Y-m', $targetTimestamp);
    }
    
    // Fallback display format for the label
    $displayLabel = date($php_date_format, $targetTimestamp);
    if($period === 'week') {
         // Custom week label for clarity (e.g., 'Week 42')
         $displayLabel = 'Wk ' . date('W', $targetTimestamp);
    }

    $chartLabels[] = $displayLabel;
    
    // Look up the count using the calculated period key
    $count = $fetchedData[$targetPeriodKey] ?? 0;
    $chartData[] = $count;
}

// Output the data as JSON
echo json_encode([
    'labels' => $chartLabels,
    'data' => $chartData
]);
?>