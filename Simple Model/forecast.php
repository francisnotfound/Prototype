<?php
// Example mock data (you can replace with DB query later)
$months = [
    ['month' => '2024-11', 'tithes_amount' => 10000, 'offerings_amount' => 5000],
    ['month' => '2024-12', 'tithes_amount' => 12000, 'offerings_amount' => 6000],
    ['month' => '2025-01', 'tithes_amount' => 13000, 'offerings_amount' => 7000],
    ['month' => '2025-02', 'tithes_amount' => 12500, 'offerings_amount' => 6500],
    ['month' => '2025-03', 'tithes_amount' => 14000, 'offerings_amount' => 7500],
    ['month' => '2025-04', 'tithes_amount' => 13500, 'offerings_amount' => 8000],
];

$data = [];
foreach ($months as $row) {
    $date = $row['month'] . '-01';
    $amount = floatval($row['tithes_amount']) + floatval($row['offerings_amount']);
    $data[] = ['ds' => $date, 'y' => $amount];
}

file_put_contents('prophet_input.json', json_encode($data));

// Run Python script
exec("python3 forecast.py");

// Redirect back to main page
header("Location: index.php");
exit;
?>
