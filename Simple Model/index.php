<!DOCTYPE html>
<html>
<head>
    <title>Church Income Forecast</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<h2>Church Tithes & Offerings Forecast</h2>
<form action="forecast.php" method="post">
    <button type="submit">Generate Forecast</button>
</form>

<?php
if (file_exists('forecast_output.json')) {
    $json = file_get_contents('forecast_output.json');
    $forecast = json_decode($json, true);
    $labels = [];
    $values = [];

    foreach ($forecast as $row) {
        $labels[] = $row['ds'];
        $values[] = round($row['yhat'], 2);
    }

    echo "<canvas id='forecastChart' width='600' height='400'></canvas>
        <script>
            const ctx = document.getElementById('forecastChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: " . json_encode($labels) . ",
                    datasets: [{
                        label: 'Predicted Income (₱)',
                        data: " . json_encode($values) . ",
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        tension: 0.4
                    }]
                },
                options: {
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        </script>";
}
?>
</body>
</html>
