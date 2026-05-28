<?php
require 'auth.php';
require 'db.php';

/*
|--------------------------------------------------------------------------
| DEFAULT SYSTEM DATE
|--------------------------------------------------------------------------
|
| Automatically use today's date
|
|--------------------------------------------------------------------------
*/
$today = date('Y-m-d');

/*
|--------------------------------------------------------------------------
| FILTER VALUES
|--------------------------------------------------------------------------
*/
$created_by = $_GET['created_by'] ?? '';
$type       = $_GET['type'] ?? '';
$period     = $_GET['period'] ?? '';

$date_from  = $_GET['date_from'] ?? $today;
$date_to    = $_GET['date_to'] ?? $today;

/*
|--------------------------------------------------------------------------
| REPORT WHERE CONDITIONS
|--------------------------------------------------------------------------
*/

$where = " WHERE 1=1 ";
$params = [];

/*
|--------------------------------------------------------------------------
| FILTER USER
|--------------------------------------------------------------------------
*/
if($created_by != ''){

    $where .= " AND sl.created_by = ? ";
    $params[] = $created_by;
}

/*
|--------------------------------------------------------------------------
| FILTER TYPE
|--------------------------------------------------------------------------
*/
if($type != ''){

    $where .= " AND sl.type = ? ";
    $params[] = $type;
}

/*
|--------------------------------------------------------------------------
| FILTER DATE RANGE
|--------------------------------------------------------------------------
|
| VERY IMPORTANT:
| Uses stock_logs date ONLY
|
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
| FILTER PERIOD
|--------------------------------------------------------------------------
*/
if($period == 'daily'){

    $where .= "
    AND DATE(sl.created_at)=CURDATE()
    ";

}elseif($period == 'weekly'){

    $where .= "
    AND YEARWEEK(sl.created_at,1)=YEARWEEK(CURDATE(),1)
    ";

}elseif($period == 'monthly'){

    $where .= "
    AND MONTH(sl.created_at)=MONTH(CURRENT_DATE())
    AND YEAR(sl.created_at)=YEAR(CURRENT_DATE())
    ";
}

/*
|--------------------------------------------------------------------------
| SALES DATE FILTER
|--------------------------------------------------------------------------
|
| THIS FIXES YOUR MAIN ISSUE
|
| Before:
| Sales query ignored date filters
| so ALL sales appeared.
|
| Now:
| Sales are filtered correctly
| by transaction date.
|
|--------------------------------------------------------------------------
*/

$salesWhere = " WHERE 1=1 ";
$salesParams = [];

if($date_from != ''){

    $salesWhere .= "
    AND DATE(t.created_at) >= ?
    ";

    $salesParams[] = $date_from;
}

if($date_to != ''){

    $salesWhere .= "
    AND DATE(t.created_at) <= ?
    ";

    $salesParams[] = $date_to;
}

/*
|--------------------------------------------------------------------------
| PERIOD FILTER FOR SALES
|--------------------------------------------------------------------------
*/

if($period == 'daily'){

    $salesWhere .= "
    AND DATE(t.created_at)=CURDATE()
    ";

}elseif($period == 'weekly'){

    $salesWhere .= "
    AND YEARWEEK(t.created_at,1)=YEARWEEK(CURDATE(),1)
    ";

}elseif($period == 'monthly'){

    $salesWhere .= "
    AND MONTH(t.created_at)=MONTH(CURRENT_DATE())
    AND YEAR(t.created_at)=YEAR(CURRENT_DATE())
    ";
}

/*
|--------------------------------------------------------------------------
| MAIN REPORT QUERY
|--------------------------------------------------------------------------
|
| FIXED:
| Sales now filtered by date.
|
|--------------------------------------------------------------------------
*/

$sql = "
SELECT

DATE(sl.created_at) as report_date,

p.id,
p.name as product_name,
p.stock as remaining_stock,

/*
|--------------------------------------------------------------------------
| STOCK IN
|--------------------------------------------------------------------------
*/
SUM(
    CASE
        WHEN sl.type='IN'
        THEN sl.quantity
        ELSE 0
    END
) as stock_in,

/*
|--------------------------------------------------------------------------
| STOCK OUT
|--------------------------------------------------------------------------
*/
SUM(
    CASE
        WHEN sl.type='OUT'
        THEN sl.quantity
        ELSE 0
    END
) as stock_out,

/*
|--------------------------------------------------------------------------
| DAMAGE
|--------------------------------------------------------------------------
*/
SUM(
    CASE
        WHEN sl.type='DAMAGE'
        THEN sl.quantity
        ELSE 0
    END
) as damage_out,

/*
|--------------------------------------------------------------------------
| USERS
|--------------------------------------------------------------------------
*/
GROUP_CONCAT(
    DISTINCT sl.created_by
    SEPARATOR ', '
) as users,

/*
|--------------------------------------------------------------------------
| SOLD QUANTITY
|--------------------------------------------------------------------------
*/
(
    SELECT IFNULL(SUM(ti.quantity),0)

    FROM transaction_items ti

    INNER JOIN transactions t
    ON t.id = ti.transaction_id

    WHERE ti.product_id = p.id

    AND DATE(t.created_at) >= ?
    AND DATE(t.created_at) <= ?

) as total_sold_qty,

/*
|--------------------------------------------------------------------------
| SALES AMOUNT
|--------------------------------------------------------------------------
*/
(
    SELECT IFNULL(SUM(ti.quantity * ti.price),0)

    FROM transaction_items ti

    INNER JOIN transactions t
    ON t.id = ti.transaction_id

    WHERE ti.product_id = p.id

    AND DATE(t.created_at) >= ?
    AND DATE(t.created_at) <= ?

) as sales_amount

FROM stock_logs sl

JOIN products p
ON p.id = sl.product_id

$where

GROUP BY
DATE(sl.created_at),
sl.product_id

ORDER BY
sl.created_at DESC
";

/*
|--------------------------------------------------------------------------
| MERGE PARAMETERS
|--------------------------------------------------------------------------
*/

$finalParams = [

    $date_from,
    $date_to,

    $date_from,
    $date_to
];

$finalParams = array_merge(
    $finalParams,
    $params
);

/*
|--------------------------------------------------------------------------
| EXECUTE REPORT
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare($sql);

$stmt->execute($finalParams);

$reports = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| TOTAL SALES
|--------------------------------------------------------------------------
|
| FIXED:
| Now respects date filters.
|
|--------------------------------------------------------------------------
*/

$salesStmt = $pdo->prepare("
SELECT IFNULL(SUM(total),0) as total
FROM transactions t
$salesWhere
");

$salesStmt->execute($salesParams);

$sales = $salesStmt->fetch();

/*
|--------------------------------------------------------------------------
| TOTAL SOLD ITEMS
|--------------------------------------------------------------------------
*/

$soldStmt = $pdo->prepare("
SELECT IFNULL(SUM(ti.quantity),0) as total

FROM transaction_items ti

INNER JOIN transactions t
ON t.id = ti.transaction_id

$salesWhere
");

$soldStmt->execute($salesParams);

$totalSold = $soldStmt->fetch();

/*
|--------------------------------------------------------------------------
| TOTAL INVENTORY
|--------------------------------------------------------------------------
*/

$totalInventory = $pdo->query("
SELECT IFNULL(SUM(stock),0) as total
FROM products
")->fetch();

/*
|--------------------------------------------------------------------------
| USERS LIST
|--------------------------------------------------------------------------
*/

$users = $pdo->query("
SELECT DISTINCT created_by
FROM stock_logs
ORDER BY created_by ASC
")->fetchAll();

/*
|--------------------------------------------------------------------------
| TOTAL TABLE SALES
|--------------------------------------------------------------------------
*/

$totalTableSales = 0;

foreach($reports as $r){

    $totalTableSales += $r['sales_amount'];
}
?>

<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<title>
    Inventory Reports
</title>

<!-- TAILWIND -->
<link rel="stylesheet" href="tailwind.min.css">

<!-- PDF LIBRARY -->
<script src="html2pdf.bundle.min.js"></script>

<style>

/*
|--------------------------------------------------------------------------
| PDF TABLE FIX
|--------------------------------------------------------------------------
*/

body{
    font-family:Arial,sans-serif;
}

.pdf-table{

    width:100%;
    border-collapse:collapse;
    table-layout:fixed;
}

.pdf-table th,
.pdf-table td{

    border:1px solid #ddd;
    padding:8px;
    font-size:12px;
    word-wrap:break-word;
}

.pdf-table tr{

    page-break-inside:avoid !important;
}

.pdf-table thead{

    display:table-header-group;
}

@media print{

    .no-print{
        display:none !important;
    }
}

</style>

</head>

<body class="bg-gray-100">

<div class="flex">

    <!-- SIDEBAR -->
    <div class="w-64 bg-gray-900 text-white min-h-screen p-5 no-print">

        <h1 class="text-3xl font-bold mb-10">
            POS SYSTEM
        </h1>

        <div class="space-y-3">

            <a
                href="dashboard.php"
                class="block hover:bg-gray-700 p-3 rounded">

                Dashboard

            </a>

            <a
                href="stocks.php"
                class="block bg-green-600 p-3 rounded">

                Stocks Reports

            </a>

            <a
                href="logout.php"
                class="block hover:bg-red-700 p-3 rounded">

                Logout

            </a>

        </div>

    </div>

    <!-- CONTENT -->
    <div class="flex-1 p-6">

        <h2 class="text-3xl font-bold mb-6">
            Inventory & Sales Reports
        </h2>

        <!-- FILTER -->
        <div class="bg-white p-5 rounded-2xl shadow mb-6 no-print">

            <form
                method="GET"
                class="grid grid-cols-1 md:grid-cols-6 gap-4">

                <!-- USER -->
                <select
                    name="created_by"
                    class="border p-3 rounded">

                    <option value="">
                        All Users
                    </option>

                    <?php foreach($users as $u): ?>

                    <option
                        value="<?= $u['created_by'] ?>"
                        <?= $created_by == $u['created_by'] ? 'selected' : '' ?>>

                        <?= $u['created_by'] ?>

                    </option>

                    <?php endforeach; ?>

                </select>

                <!-- TYPE -->
                <select
                    name="type"
                    class="border p-3 rounded">

                    <option value="">
                        All Types
                    </option>

                    <option
                        value="IN"
                        <?= $type == 'IN' ? 'selected' : '' ?>>

                        Stock IN

                    </option>

                    <option
                        value="OUT"
                        <?= $type == 'OUT' ? 'selected' : '' ?>>

                        Stock OUT

                    </option>

                    <option
                        value="DAMAGE"
                        <?= $type == 'DAMAGE' ? 'selected' : '' ?>>

                        Damage

                    </option>

                </select>

                <!-- PERIOD -->
                <select
                    name="period"
                    class="border p-3 rounded">

                    <option value="">
                        All Periods
                    </option>

                    <option
                        value="daily"
                        <?= $period == 'daily' ? 'selected' : '' ?>>

                        Daily

                    </option>

                    <option
                        value="weekly"
                        <?= $period == 'weekly' ? 'selected' : '' ?>>

                        Weekly

                    </option>

                    <option
                        value="monthly"
                        <?= $period == 'monthly' ? 'selected' : '' ?>>

                        Monthly

                    </option>

                </select>

                <!-- DATE FROM -->
                <input
                    type="date"
                    name="date_from"
                    value="<?= $date_from ?>"
                    class="border p-3 rounded">

                <!-- DATE TO -->
                <input
                    type="date"
                    name="date_to"
                    value="<?= $date_to ?>"
                    class="border p-3 rounded">

                <!-- BUTTON -->
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
                    Remaining Stocks
                </h3>

                <p class="text-3xl font-bold text-purple-600 mt-2">

                    <?= number_format($totalInventory['total']) ?>

                </p>

            </div>

        </div>

        <!-- REPORT -->
        <div class="bg-white p-6 rounded-2xl shadow overflow-auto">

            <!-- HEADER -->
            <div class="flex justify-between items-center mb-5 no-print">

                <h3 class="text-2xl font-bold">
                    Inventory + Sales Report
                </h3>

                <!-- PDF -->
                <button
                    onclick="downloadPDF()"
                    class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-lg shadow">

                    Export PDF

                </button>

            </div>

            <!-- PDF AREA -->
            <div id="reportArea">

                <h2 class="text-2xl font-bold mb-3">
                    Inventory & Sales Reports
                </h2>

                <p class="mb-4">

                    Date Range:
                    <?= $date_from ?>
                    to
                    <?= $date_to ?>

                </p>

                <!-- TABLE -->
                <table class="pdf-table">

                    <thead>

                        <tr style="background:#f3f4f6;">

                            <th>Date</th>
                            <th>Product</th>
                            <th>Stock IN</th>
                            <th>Stock OUT</th>
                            <th>Damage</th>
                            <th>Sold Qty</th>
                            <th>Sales Amount</th>
                            <th>Remaining</th>
                            <th>User</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach($reports as $r): ?>

                    <tr>

                        <td><?= $r['report_date'] ?></td>

                        <td><?= $r['product_name'] ?></td>

                        <td><?= $r['stock_in'] ?></td>

                        <td><?= $r['stock_out'] ?></td>

                        <td><?= $r['damage_out'] ?></td>

                        <td><?= $r['total_sold_qty'] ?></td>

                        <td>
                            ₱<?= number_format($r['sales_amount'],2) ?>
                        </td>

                        <td><?= $r['remaining_stock'] ?></td>

                        <td><?= $r['users'] ?></td>

                    </tr>

                    <?php endforeach; ?>

                    <!-- TOTAL -->
                    <tr style="background:#f3f4f6;font-weight:bold;">

                        <td colspan="6" style="text-align:right;">

                            TOTAL SALES AMOUNT

                        </td>

                        <td>

                            ₱<?= number_format($totalTableSales,2) ?>

                        </td>

                        <td colspan="2"></td>

                    </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<!-- PDF SCRIPT -->
<script>

/*
|--------------------------------------------------------------------------
| EXPORT PDF
|--------------------------------------------------------------------------
*/

function downloadPDF(){

    const element = document.getElementById('reportArea');

    const options = {

        margin:[0.2,0.2,0.2,0.2],

        filename:
            'inventory-report-<?= date("Y-m-d") ?>.pdf',

        image:{
            type:'jpeg',
            quality:1
        },

        html2canvas:{
            scale:1.5,
            useCORS:true
        },

        jsPDF:{
            unit:'in',
            format:'a4',
            orientation:'landscape'
        },

        pagebreak:{
            mode:['avoid-all','css','legacy']
        }
    };

    html2pdf()
        .set(options)
        .from(element)
        .save();
}

</script>

</body>
</html>