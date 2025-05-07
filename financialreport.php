<?php
// financialreport.php
session_start();
require_once 'config.php';
require_once 'user_functions.php';

// Get user profile from database
$user_profile = getUserProfile($conn, $_SESSION["user"]);

// Check if user is logged in and is admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["user"] !== "admin") {
    header("Location: login.php");
    exit;
}

// Site configuration
$church_name = "Church of Christ-Disciples";
$current_page = basename($_SERVER['PHP_SELF']);

// Denominations for bills and coins
$denominations = [
    'bills' => [1000, 500, 200, 100, 50, 20],
    'coins' => [20, 10, 5, 1, 0.5, 0.25]
];

// Specified gift categories
$specified_gifts = [
    'Provident Fund',
    'Building Fund',
    'Building and Equipment',
    'Others (e.g., Wedding, etc.)',
    'Depreciation'
];

// Initialize financial data if not set
if (!isset($_SESSION['financial_data'])) {
    $_SESSION['financial_data'] = [
        'tithes' => [],
        'offerings' => [],
        'bank_gifts' => [],
        'specified_gifts' => []
    ];
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = "";
    $messageType = "success";

    if (isset($_POST["add_tithes"])) {
        $tithes_entry = [
            "id" => count($_SESSION['financial_data']['tithes']) + 1,
            "date" => htmlspecialchars(trim($_POST["date"])),
            "member_name" => htmlspecialchars(trim($_POST["member_name"])),
            "denominations" => []
        ];
        foreach ($denominations['bills'] as $bill) {
            $tithes_entry['denominations']["bill_$bill"] = intval($_POST["bill_$bill"] ?? 0);
        }
        foreach ($denominations['coins'] as $coin) {
            $tithes_entry['denominations']["coin_$coin"] = floatval($_POST["coin_$coin"] ?? 0);
        }
        $total = calculate_total($tithes_entry['denominations'], $denominations);
        $tithes_entry['total'] = $total;
        $_SESSION['financial_data']['tithes'][] = $tithes_entry;
        $message = "Tithes record for {$tithes_entry['member_name']} added successfully!";
    } elseif (isset($_POST["add_offering"])) {
        $offering_entry = [
            "id" => count($_SESSION['financial_data']['offerings']) + 1,
            "date" => htmlspecialchars(trim($_POST["date"])),
            "member_name" => htmlspecialchars(trim($_POST["member_name"])),
            "denominations" => []
        ];
        foreach ($denominations['bills'] as $bill) {
            $offering_entry['denominations']["bill_$bill"] = intval($_POST["bill_$bill"] ?? 0);
        }
        foreach ($denominations['coins'] as $coin) {
            $offering_entry['denominations']["coin_$coin"] = floatval($_POST["coin_$coin"] ?? 0);
        }
        $total = calculate_total($offering_entry['denominations'], $denominations);
        $offering_entry['total'] = $total;
        $_SESSION['financial_data']['offerings'][] = $offering_entry;
        $message = "Offering record for {$offering_entry['member_name']} added successfully!";
    } elseif (isset($_POST["add_bank_gift"])) {
        $bank_entry = [
            "id" => count($_SESSION['financial_data']['bank_gifts']) + 1,
            "date_deposited" => htmlspecialchars(trim($_POST["date_deposited"])),
            "date_updated" => htmlspecialchars(trim($_POST["date_updated"])),
            "amount" => floatval($_POST["amount"])
        ];
        $_SESSION['financial_data']['bank_gifts'][] = $bank_entry;
        $message = "Bank gift record added successfully!";
    } elseif (isset($_POST["add_specified_gift"])) {
        $specified_entry = [
            "id" => count($_SESSION['financial_data']['specified_gifts']) + 1,
            "date" => htmlspecialchars(trim($_POST["date"])),
            "category" => htmlspecialchars(trim($_POST["category"])),
            "denominations" => []
        ];
        foreach ($denominations['bills'] as $bill) {
            $specified_entry['denominations']["bill_$bill"] = intval($_POST["bill_$bill"] ?? 0);
        }
        foreach ($denominations['coins'] as $coin) {
            $specified_entry['denominations']["coin_$coin"] = floatval($_POST["coin_$coin"] ?? 0);
        }
        $total = calculate_total($specified_entry['denominations'], $denominations);
        $specified_entry['total'] = $total;
        $_SESSION['financial_data']['specified_gifts'][] = $specified_entry;
        $message = "Specified gift record added successfully!";
    }
}

// Function to calculate total amount from denominations
function calculate_total($denominations, $denomination_list) {
    $total = 0;
    foreach ($denomination_list['bills'] as $bill) {
        $total += $denominations["bill_$bill"] * $bill;
    }
    foreach ($denomination_list['coins'] as $coin) {
        $total += $denominations["coin_$coin"] * $coin;
    }
    return $total;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports - <?php echo $church_name; ?></title>
    <link rel="icon" type="image/png" href="logo/cocd_icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3a3a3a;
            --accent-color: rgb(0, 139, 30);
            --light-gray: #d0d0d0;
            --white: #ffffff;
            --sidebar-width: 250px;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --info-color: #2196f3;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: var(--primary-color);
            line-height: 1.6;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary-color);
            color: var(--white);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header img {
            height: 60px;
            margin-bottom: 10px;
        }

        .sidebar-header h3 {
            font-size: 18px;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu ul {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--white);
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar-menu a.active {
            background-color: var(--accent-color);
        }

        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .content-area {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--white);
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .top-bar h2 {
            font-size: 24px;
        }

        .user-profile {
            display: flex;
            align-items: center;
        }

        .user-profile .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--accent-color);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
            overflow: hidden;
        }

        .user-profile .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-info {
            margin-right: 15px;
        }

        .user-info h4 {
            font-size: 14px;
            margin: 0;
        }

        .user-info p {
            font-size: 12px;
            margin: 0;
            color: #666;
        }

        .logout-btn {
            background-color: #f0f0f0;
            color: var(--primary-color);
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #e0e0e0;
        }

        .financial-content {
            margin-top: 20px;
        }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--accent-color);
            color: var(--white);
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: rgb(0, 112, 9);
        }

        .btn i {
            margin-right: 5px;
        }

        .financial-form {
            background-color: var(--white);
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: none;
        }

        .financial-form.active {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .denomination-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }

        .table-responsive {
            background-color: var(--white);
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eeeeee;
        }

        th {
            background-color: #f5f5f5;
            font-weight: 600;
        }

        tbody tr:hover {
            background-color: #f9f9f9;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }

        .alert i {
            margin-right: 10px;
            font-size: 20px;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }
            .sidebar-header h3, .sidebar-menu span {
                display: none;
            }
            .content-area {
                margin-left: 70px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .sidebar-menu {
                display: flex;
                padding: 0;
                overflow-x: auto;
            }
            .sidebar-menu ul {
                display: flex;
                width: 100%;
            }
            .sidebar-menu li {
                margin-bottom: 0;
                flex: 1;
            }
            .sidebar-menu a {
                padding: 10px;
                justify-content: center;
            }
            .sidebar-menu i {
                margin-right: 0;
            }
            .content-area {
                margin-left: 0;
            }
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            .user-profile {
                margin-top: 10px;
            }
            .action-bar {
                flex-direction: column;
                gap: 10px;
            }
            .denomination-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="logo/cocd_icon.png" alt="Church Logo">
            <h3><?php echo $church_name; ?></h3>
        </div>
        <div class="sidebar-menu">
            <ul>
                <li><a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="events.php" class="<?php echo $current_page == 'events.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> <span>Events</span></a></li>
                <li><a href="messages.php" class="<?php echo $current_page == 'messages.php' ? 'active' : ''; ?>"><i class="fas fa-video"></i> <span>Messages</span></a></li>
                <li><a href="member_records.php" class="<?php echo $current_page == 'member_records.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> <span>Member Records</span></a></li>
                <li><a href="prayers.php" class="<?php echo $current_page == 'prayers.php' ? 'active' : ''; ?>"><i class="fas fa-hands-praying"></i> <span>Prayer Requests</span></a></li>
                <li><a href="financialreport.php" class="<?php echo $current_page == 'financialreport.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> <span>Financial Reports</span></a></li>
                <li><a href="settings.php" class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
            </ul>
        </div>
    </aside>

    <main class="content-area">
        <div class="top-bar">
            <h2>Financial Reports</h2>
            <div class="user-profile">
                <div class="avatar">
                    <?php if (!empty($user_profile['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($user_profile['profile_picture']); ?>" alt="Profile Picture">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user_profile['username'] ?? 'U', 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($user_profile['username'] ?? 'Unknown User'); ?></h4>
                    <p><?php echo htmlspecialchars($user_profile['role'] ?? 'User'); ?></p>
                </div>
                <form action="logout.php" method="post">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </div>
        </div>

        <div class="financial-content">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-info-circle"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="action-bar">
                <button class="btn" id="add-tithes-btn"><i class="fas fa-plus"></i> Add Tithes</button>
                <button class="btn" id="add-offering-btn"><i class="fas fa-plus"></i> Add Offering</button>
                <button class="btn" id="add-bank-gift-btn"><i class="fas fa-plus"></i> Add Bank Gift</button>
                <button class="btn" id="add-specified-gift-btn"><i class="fas fa-plus"></i> Add Specified Gift</button>
            </div>

            <!-- Tithes Form -->
            <div class="financial-form" id="tithes-form">
                <h3>Add Weekly Tithes</h3>
                <form action="" method="post">
                    <div class="form-group">
                        <label for="tithes-date">Date</label>
                        <input type="date" id="tithes-date" name="date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="member-name-tithes">Member Name</label>
                        <input type="text" id="member-name-tithes" name="member_name" class="form-control" required>
                    </div>
                    <h4>Bills</h4>
                    <div class="denomination-grid">
                        <?php foreach ($denominations['bills'] as $bill): ?>
                            <div class="form-group">
                                <label for="bill_<?php echo $bill; ?>">₱<?php echo $bill; ?></label>
                                <input type="number" id="bill_<?php echo $bill; ?>" name="bill_<?php echo $bill; ?>" class="form-control" min="0" value="0">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <h4>Coins</h4>
                    <div class="denomination-grid">
                        <?php foreach ($denominations['coins'] as $coin): ?>
                            <div class="form-group">
                                <label for="coin_<?php echo $coin; ?>">₱<?php echo $coin; ?></label>
                                <input type="number" id="coin_<?php echo $coin; ?>" name="coin_<?php echo $coin; ?>" class="form-control" min="0" step="0.01" value="0">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn" name="add_tithes"><i class="fas fa-save"></i> Save</button>
                    <button type="button" class="btn cancel-btn" style="background-color: #f0f0f0; color: var(--primary-color); margin-left: 10px;">Cancel</button>
                </form>
            </div>

            <!-- Offering Form -->
            <div class="financial-form" id="offering-form">
                <h3>Add Weekly Offering</h3>
                <form action="" method="post">
                    <div class="form-group">
                        <label for="offering-date">Date</label>
                        <input type="date" id="offering-date" name="date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="member-name-offering">Member Name</label>
                        <input type="text" id="member-name-offering" name="member_name" class="form-control" required>
                    </div>
                    <h4>Bills</h4>
                    <div class="denomination-grid">
                        <?php foreach ($denominations['bills'] as $bill): ?>
                            <div class="form-group">
                                <label for="bill_<?php echo $bill; ?>">₱<?php echo $bill; ?></label>
                                <input type="number" id="bill_<?php echo $bill; ?>" name="bill_<?php echo $bill; ?>" class="form-control" min="0" value="0">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <h4>Coins</h4>
                    <div class="denomination-grid">
                        <?php foreach ($denominations['coins'] as $coin): ?>
                            <div class="form-group">
                                <label for="coin_<?php echo $coin; ?>">₱<?php echo $coin; ?></label>
                                <input type="number" id="coin_<?php echo $coin; ?>" name="coin_<?php echo $coin; ?>" class="form-control" min="0" step="0.01" value="0">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn" name="add_offering"><i class="fas fa-save"></i> Save</button>
                    <button type="button" class="btn cancel-btn" style="background-color: #f0f0f0; color: var(--primary-color); margin-left: 10px;">Cancel</button>
                </form>
            </div>

            <!-- Bank Gift Form -->
            <div class="financial-form" id="bank-gift-form">
                <h3>Add Gift Received Through Bank</h3>
                <form action="" method="post">
                    <div class="form-group">
                        <label for="date_deposited">Date Deposited</label>
                        <input type="date" id="date_deposited" name="date_deposited" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="date_updated">Date Updated to COCD Passbook</label>
                        <input type="date" id="date_updated" name="date_updated" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="amount">Amount (₱)</label>
                        <input type="number" id="amount" name="amount" class="form-control" step="0.01" min="0" required>
                    </div>
                    <button type="submit" class="btn" name="add_bank_gift"><i class="fas fa-save"></i> Save</button>
                    <button type="button" class="btn cancel-btn" style="background-color: #f0f0f0; color: var(--primary-color); margin-left: 10px;">Cancel</button>
                </form>
            </div>

            <!-- Specified Gift Form -->
            <div class="financial-form" id="specified-gift-form">
                <h3>Add Specified Gift</h3>
                <form action="" method="post">
                    <div class="form-group">
                        <label for="specified-date">Date</label>
                        <input type="date" id="specified-date" name="date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" class="form-control" required>
                            <?php foreach ($specified_gifts as $category): ?>
                                <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <h4>Bills</h4>
                    <div class="denomination-grid">
                        <?php foreach ($denominations['bills'] as $bill): ?>
                            <div class="form-group">
                                <label for="bill_<?php echo $bill; ?>">₱<?php echo $bill; ?></label>
                                <input type="number" id="bill_<?php echo $bill; ?>" name="bill_<?php echo $bill; ?>" class="form-control" min="0" value="0">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <h4>Coins</h4>
                    <div class="denomination-grid">
                        <?php foreach ($denominations['coins'] as $coin): ?>
                            <div class="form-group">
                                <label for="coin_<?php echo $coin; ?>">₱<?php echo $coin; ?></label>
                                <input type="number" id="coin_<?php echo $coin; ?>" name="coin_<?php echo $coin; ?>" class="form-control" min="0" step="0.01" value="0">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn" name="add_specified_gift"><i class="fas fa-save"></i> Save</button>
                    <button type="button" class="btn cancel-btn" style="background-color: #f0f0f0; color: var(--primary-color); margin-left: 10px;">Cancel</button>
                </form>
            </div>

            <!-- Financial Summary -->
            <div class="table-responsive">
                <h3>Financial Summary</h3>
                <table>
                    <thead>
                    <tr>
                        <th>Category</th>
                        <th>Total (₱)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Tithes</td>
                        <td><?php echo number_format(array_sum(array_column($_SESSION['financial_data']['tithes'], 'total')), 2); ?></td>
                    </tr>
                    <tr>
                        <td>Offerings</td>
                        <td><?php echo number_format(array_sum(array_column($_SESSION['financial_data']['offerings'], 'total')), 2); ?></td>
                    </tr>
                    <tr>
                        <td>Bank Gifts</td>
                        <td><?php echo number_format(array_sum(array_column($_SESSION['financial_data']['bank_gifts'], 'amount')), 2); ?></td>
                    </tr>
                    <tr>
                        <td>Specified Gifts</td>
                        <td><?php echo number_format(array_sum(array_column($_SESSION['financial_data']['specified_gifts'], 'total')), 2); ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Detailed Records -->
            <div class="table-responsive">
                <h3>Tithes Records</h3>
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Member Name</th>
                        <th>Total (₱)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($_SESSION['financial_data']['tithes'] as $entry): ?>
                        <tr>
                            <td><?php echo $entry['id']; ?></td>
                            <td><?php echo $entry['date']; ?></td>
                            <td><?php echo $entry['member_name']; ?></td>
                            <td><?php echo number_format($entry['total'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-responsive">
                <h3>Offering Records</h3>
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Member Name</th>
                        <th>Total (₱)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($_SESSION['financial_data']['offerings'] as $entry): ?>
                        <tr>
                            <td><?php echo $entry['id']; ?></td>
                            <td><?php echo $entry['date']; ?></td>
                            <td><?php echo $entry['member_name']; ?></td>
                            <td><?php echo number_format($entry['total'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-responsive">
                <h3>Bank Gifts Records</h3>
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date Deposited</th>
                        <th>Date Updated</th>
                        <th>Amount (₱)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($_SESSION['financial_data']['bank_gifts'] as $entry): ?>
                        <tr>
                            <td><?php echo $entry['id']; ?></td>
                            <td><?php echo $entry['date_deposited']; ?></td>
                            <td><?php echo $entry['date_updated']; ?></td>
                            <td><?php echo number_format($entry['amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-responsive">
                <h3>Specified Gifts Records</h3>
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Total (₱)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($_SESSION['financial_data']['specified_gifts'] as $entry): ?>
                        <tr>
                            <td><?php echo $entry['id']; ?></td>
                            <td><?php echo $entry['date']; ?></td>
                            <td><?php echo $entry['category']; ?></td>
                            <td><?php echo number_format($entry['total'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const buttons = {
            'add-tithes-btn': 'tithes-form',
            'add-offering-btn': 'offering-form',
            'add-bank-gift-btn': 'bank-gift-form',
            'add-specified-gift-btn': 'specified-gift-form'
        };

        Object.keys(buttons).forEach(btnId => {
            const btn = document.getElementById(btnId);
            const form = document.getElementById(buttons[btnId]);
            btn.addEventListener('click', () => {
                // Hide all forms
                Object.values(buttons).forEach(formId => {
                    document.getElementById(formId).classList.remove('active');
                });
                // Show clicked form
                form.classList.add('active');
            });
        });

        // Handle cancel buttons
        document.querySelectorAll('.cancel-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.closest('.financial-form').classList.remove('active');
            });
        });
    });
</script>
</body>
</html>