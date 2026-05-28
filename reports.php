<?php
require 'auth.php';
require 'db.php';

/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/
$created_by = $_GET['created_by'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where = " WHERE 1=1 ";
$params = [];

/*
|--------------------------------------------------------------------------
| FILTER BY USER
|--------------------------------------------------------------------------
*/
if($created_by != ''){

    $where .= " AND sl.created_by = ? ";
    $params[] = $created_by;
}

/*
|--------------------------------------------------------------------------
| FILTER BY DATE
|--------------------------------------------------------------------------
*/
if($date_from != ''){

    $where .= " AND DATE(sl.created_at) >= ? ";
    $params[] = $date_from;
}

if($date_to != ''){

    $where .= " AND DATE(sl.created_at) <= ? ";
    $params[] = $date_to;
}

/*
|--------------------------------------------------------------------------
| STOCK LOGS REPORT
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
SELECT
sl.*,
p.name as product_name
FROM stock_logs sl
JOIN products p ON p.id = sl.product_id
$where
ORDER BY sl.created_at DESC
");

$stmt->execute($params);

$logs = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| INVENTORY SUMMARY
|--------------------------------------------------------------------------
*/
$inventory = $pdo->query("
SELECT
name,
stock,
price,
(stock * price) as inventory_value
FROM products
ORDER BY name ASC
")->fetchAll();

/*
|--------------------------------------------------------------------------
| TOTAL SALES
|--------------------------------------------------------------------------
*/
$sales = $pdo->query("
SELECT IFNULL(SUM(total),0) as total
FROM transactions
")->fetch();

/*
|--------------------------------------------------------------------------
| TOTAL SOLD ITEMS
|--------------------------------------------------------------------------
*/
$totalSold = $pdo->query("
SELECT IFNULL(SUM(quantity),0) as total
FROM transaction_items
")->fetch();

/*
|--------------------------------------------------------------------------
| USERS
|--------------------------------------------------------------------------
*/
$users = $pdo->query("
SELECT DISTINCT created_by
FROM stock_logs
ORDER BY created_by ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Reports</title>


<link rel="stylesheet" href="tailwind.min.css">

</head>

<body class="bg-gray-100">

<div class="flex">

    <!-- SIDEBAR -->
    <div class="w-64 bg-gray-900 text-white min-h-screen p-5">

        <h1 class="text-3xl font-bold mb-10">
            POS SYSTEM
        </h1>

        <div class="space-y-3">

            <a href="dashboard.php"
               class="block hover:bg-gray-700 p-3 rounded">
               Dashboard
            </a>

            <a href="stocks.php"
               class="block hover:bg-gray-700 p-3 rounded">
               Stocks
            </a>

            <a href="reports.php"
               class="block bg-green-600 p-3 rounded">
               Reports
            </a>

            <a href="logout.php"
               class="block hover:bg-red-700 p-3 rounded">
               Logout
            </a>

        </div>

    </div>

    <!-- CONTENT -->
    <div class="flex-1 p-6">

        <h2 class="text-3xl font-bold mb-6">
            Detailed Reports
        </h2>

        <!-- FILTERS -->
        <div class="bg-white p-5 rounded-2xl shadow mb-6">

            <form method="GET"
                  class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <!-- USER -->
                <select name="created_by"
                        class="border p-3 rounded">

                    <option value="">All Users</option>

                    <?php foreach($users as $u): ?>

                        <option value="<?= $u['created_by'] ?>"
                            <?= $created_by == $u['created_by'] ? 'selected' : '' ?>>

                            <?= $u['created_by'] ?>

                        </option>

                    <?php endforeach; ?>

                </select>

                <!-- DATE FROM -->
                <input
                    type="date"
                    name="date_from"
                    value="<?= $date_from ?>"
                    class="border p-3 rounded"
                >

                <!-- DATE TO -->
                <input
                    type="date"
                    name="date_to"
                    value="<?= $date_to ?>"
                    class="border p-3 rounded"
                >

                <button
                    class="bg-green-600 text-white rounded p-3">
                    Filter Report
                </button>

            </form>

        </div>

        <!-- SUMMARY -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">

            <div class="bg-white p-5 rounded-2xl shadow">

                <h3 class="text-gray-500">
                    Total Sales
                </h3>

                <p class="text-3xl font-bold text-green-600 mt-2">
                    ₱<?= number_format($sales['total'],2) ?>
                </p>

            </div>

            <div class="bg-white p-5 rounded-2xl shadow">

                <h3 class="text-gray-500">
                    Total Sold Items
                </h3>

                <p class="text-3xl font-bold text-blue-600 mt-2">
                    <?= number_format($totalSold['total']) ?>
                </p>

            </div>

            <div class="bg-white p-5 rounded-2xl shadow">

                <h3 class="text-gray-500">
                    Remaining Inventory
                </h3>

                <p class="text-3xl font-bold text-purple-600 mt-2">
                    <?= count($inventory) ?>
                </p>

            </div>

        </div>

        <!-- STOCK LOGS -->
        <div class="bg-white p-6 rounded-2xl shadow mb-8 overflow-auto">

            <h3 class="text-2xl font-bold mb-5">
                Stock Transactions
            </h3>

            <table class="w-full">

                <tr class="border-b bg-gray-100">

                    <th class="text-left p-3">Date</th>
                    <th class="text-left p-3">Product</th>
                    <th class="text-left p-3">Type</th>
                    <th class="text-left p-3">Qty</th>
                    <th class="text-left p-3">User</th>
                    <th class="text-left p-3">Note</th>

                </tr>

                <?php foreach($logs as $l): ?>

                <tr class="border-b">

                    <td class="p-3">
                        <?= $l['created_at'] ?>
                    </td>

                    <td class="p-3">
                        <?= $l['product_name'] ?>
                    </td>

                    <td class="p-3">

                        <?php if($l['type'] == 'IN'): ?>

                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded">
                                STOCK IN
                            </span>

                        <?php elseif($l['type'] == 'OUT'): ?>

                            <span class="bg-red-100 text-red-700 px-3 py-1 rounded">
                                STOCK OUT
                            </span>

                        <?php else: ?>

                            <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded">
                                DAMAGE
                            </span>

                        <?php endif; ?>

                    </td>

                    <td class="p-3">
                        <?= $l['quantity'] ?>
                    </td>

                    <td class="p-3">
                        <?= $l['created_by'] ?>
                    </td>

                    <td class="p-3">
                        <?= $l['note'] ?>
                    </td>

                </tr>

                <?php endforeach; ?>

            </table>

        </div>

        <!-- INVENTORY -->
        <div class="bg-white p-6 rounded-2xl shadow overflow-auto">

            <h3 class="text-2xl font-bold mb-5">
                Inventory Physical Count
            </h3>

            <table class="w-full">

                <tr class="border-b bg-gray-100">

                    <th class="text-left p-3">Product</th>
                    <th class="text-left p-3">Price</th>
                    <th class="text-left p-3">Stocks</th>
                    <th class="text-left p-3">Inventory Value</th>

                </tr>

                <?php foreach($inventory as $i): ?>

                <tr class="border-b">

                    <td class="p-3">
                        <?= $i['name'] ?>
                    </td>

                    <td class="p-3">
                        ₱<?= number_format($i['price'],2) ?>
                    </td>

                    <td class="p-3">
                        <?= $i['stock'] ?>
                    </td>

                    <td class="p-3 font-bold text-green-600">
                        ₱<?= number_format($i['inventory_value'],2) ?>
                    </td>

                </tr>

                <?php endforeach; ?>

            </table>

        </div>

    </div>

</div>

</body>
</html>