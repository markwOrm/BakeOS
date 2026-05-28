<?php

/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/
require 'auth.php';

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/
require 'db.php';

/*
|--------------------------------------------------------------------------
| DAILY SALES
|--------------------------------------------------------------------------
*/
$daily = $pdo->query("
SELECT IFNULL(SUM(total),0) as total
FROM transactions
WHERE DATE(created_at)=CURDATE()
")->fetch();

/*
|--------------------------------------------------------------------------
| MONTHLY SALES
|--------------------------------------------------------------------------
*/
$monthly = $pdo->query("
SELECT IFNULL(SUM(total),0) as total
FROM transactions
WHERE MONTH(created_at)=MONTH(CURRENT_DATE())
")->fetch();

/*
|--------------------------------------------------------------------------
| TOTAL PRODUCTS
|--------------------------------------------------------------------------
*/
$totalProducts = $pdo->query("
SELECT COUNT(*) as total
FROM products
")->fetch();

/*
|--------------------------------------------------------------------------
| LOW STOCK PRODUCTS
|--------------------------------------------------------------------------
*/
$lowStocks = $pdo->query("
SELECT *
FROM products
WHERE stock <= 30
ORDER BY stock ASC
")->fetchAll();

/*
|--------------------------------------------------------------------------
| SALES GRAPH
|--------------------------------------------------------------------------
*/
$salesGraph = $pdo->query("
SELECT
p.name,
SUM(ti.quantity) as qty
FROM transaction_items ti
JOIN products p ON p.id=ti.product_id
GROUP BY ti.product_id
ORDER BY qty DESC
LIMIT 10
")->fetchAll();

/*
|--------------------------------------------------------------------------
| PRODUCTS
|--------------------------------------------------------------------------
*/
$products = $pdo->query("
SELECT *
FROM products
ORDER BY name ASC
")->fetchAll();

/*
|--------------------------------------------------------------------------
| STOCK MANAGEMENT
|--------------------------------------------------------------------------
*/
$message = '';

if(isset($_POST['save_stock'])){

    $product_id = $_POST['product_id'];
    $qty = (int)$_POST['quantity'];
    $type = $_POST['type'];
    $note = trim($_POST['note']);

    /*
    |--------------------------------------------------------------------------
    | CHECK PRODUCT
    |--------------------------------------------------------------------------
    */
    $stmt = $pdo->prepare("
    SELECT *
    FROM products
    WHERE id=?
    ");

    $stmt->execute([$product_id]);

    $product = $stmt->fetch();

    if($product){

        /*
        |--------------------------------------------------------------------------
        | STOCK IN
        |--------------------------------------------------------------------------
        */
        if($type == 'IN'){

            $pdo->prepare("
            UPDATE products
            SET stock = stock + ?
            WHERE id=?
            ")->execute([$qty, $product_id]);

        }

        /*
        |--------------------------------------------------------------------------
        | STOCK OUT
        |--------------------------------------------------------------------------
        */
        elseif($type == 'OUT'){

            if($currentRole != 'admin'){
                die("Only admin can subtract stocks.");
            }

            $pdo->prepare("
            UPDATE products
            SET stock = stock - ?
            WHERE id=?
            ")->execute([$qty, $product_id]);

        }

        /*
        |--------------------------------------------------------------------------
        | DAMAGE
        |--------------------------------------------------------------------------
        */
        elseif($type == 'DAMAGE'){

            if($currentRole != 'admin'){
                die("Only admin can mark damage.");
            }

            $pdo->prepare("
            UPDATE products
            SET stock = stock - ?
            WHERE id=?
            ")->execute([$qty, $product_id]);

        }

        /*
        |--------------------------------------------------------------------------
        | SAVE STOCK LOG
        |--------------------------------------------------------------------------
        */
        $stmt = $pdo->prepare("
        INSERT INTO stock_logs
        (product_id,type,quantity,note,created_by)
        VALUES (?,?,?,?,?)
        ");

        $stmt->execute([
            $product_id,
            $type,
            $qty,
            $note,
            $currentUser
        ]);

        $message = "Stock updated successfully.";

    }

}

/*
|--------------------------------------------------------------------------
| USER MANAGEMENT
|--------------------------------------------------------------------------
*/
$userMessage = '';

if(isset($_POST['create_user'])){

    /*
    |--------------------------------------------------------------------------
    | ADMIN ONLY
    |--------------------------------------------------------------------------
    */
    if($currentRole != 'admin'){
        die("Only admin can create users.");
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    /*
    |--------------------------------------------------------------------------
    | CHECK EXISTING USER
    |--------------------------------------------------------------------------
    */
    $check = $pdo->prepare("
    SELECT id
    FROM users
    WHERE username=?
    ");

    $check->execute([$username]);

    if($check->rowCount() > 0){

        $userMessage = "Username already exists.";

    }else{

        /*
        |--------------------------------------------------------------------------
        | HASH PASSWORD
        |--------------------------------------------------------------------------
        */
        $hashedPassword =
            password_hash($password, PASSWORD_DEFAULT);

        /*
        |--------------------------------------------------------------------------
        | INSERT USER
        |--------------------------------------------------------------------------
        */
        $stmt = $pdo->prepare("
        INSERT INTO users
        (username,password,role)
        VALUES (?,?,?)
        ");

        $stmt->execute([
            $username,
            $hashedPassword,
            $role
        ]);

        $userMessage =
            "New user created successfully.";

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width,
               initial-scale=1.0,
               maximum-scale=1.0,
               user-scalable=no">

<title>Dashboard</title>

<!--
|--------------------------------------------------------------------------
| LOCAL CHART.JS
|--------------------------------------------------------------------------
-->
<script src="chart.js"></script>

<style>

/*
|--------------------------------------------------------------------------
| GLOBAL
|--------------------------------------------------------------------------
*/
*{
    box-sizing:border-box;
    margin:0;
    padding:0;
    font-family:Arial,sans-serif;
}

body{
    background:#f3f4f6;
    color:#111;
}

/*
|--------------------------------------------------------------------------
| MAIN LAYOUT
|--------------------------------------------------------------------------
*/
.main-layout{
    display:flex;
    min-height:100vh;
}

/*
|--------------------------------------------------------------------------
| SIDEBAR
|--------------------------------------------------------------------------
*/
.sidebar{
    width:240px;
    background:#111827;
    color:white;
    padding:20px;
}

.sidebar-title{
    font-size:28px;
    font-weight:bold;
    margin-bottom:30px;
}

.sidebar-menu a{
    display:block;
    padding:12px;
    margin-bottom:8px;
    border-radius:8px;
    text-decoration:none;
    color:white;
    background:#1f2937;
}

.sidebar-menu a.active{
    background:#16a34a;
}

.sidebar-menu a:hover{
    background:#374151;
}

/*
|--------------------------------------------------------------------------
| CONTENT
|--------------------------------------------------------------------------
*/
.content{
    flex:1;
    padding:15px;
}

/*
|--------------------------------------------------------------------------
| PAGE HEADER
|--------------------------------------------------------------------------
*/
.page-title{
    font-size:28px;
    font-weight:bold;
}

.page-subtitle{
    color:#666;
    margin-top:5px;
    margin-bottom:20px;
}

/*
|--------------------------------------------------------------------------
| ALERTS
|--------------------------------------------------------------------------
*/
.alert-success{
    background:#dcfce7;
    color:#166534;
    padding:12px;
    border-radius:8px;
    margin-bottom:15px;
}

.alert-info{
    background:#dbeafe;
    color:#1d4ed8;
    padding:12px;
    border-radius:8px;
    margin-bottom:15px;
}

/*
|--------------------------------------------------------------------------
| STATS
|--------------------------------------------------------------------------
*/
.stats-grid{
    display:flex;
    gap:15px;
    flex-wrap:wrap;
    margin-bottom:20px;
}

.stat-card{
    flex:1;
    min-width:220px;
    background:white;
    padding:20px;
    border-radius:14px;
    box-shadow:0 1px 5px rgba(0,0,0,0.1);
}

.stat-title{
    color:#666;
    font-size:14px;
}

.stat-value{
    font-size:30px;
    font-weight:bold;
    margin-top:8px;
}

/*
|--------------------------------------------------------------------------
| CARD
|--------------------------------------------------------------------------
*/
.card{
    background:white;
    padding:20px;
    border-radius:14px;
    margin-bottom:20px;
    box-shadow:0 1px 5px rgba(0,0,0,0.1);
}

.card-title{
    font-size:24px;
    font-weight:bold;
    margin-bottom:15px;
}

/*
|--------------------------------------------------------------------------
| CHART FIX
|--------------------------------------------------------------------------
| Prevent Android infinite resize loop
|--------------------------------------------------------------------------
*/
.chart-container{
    position:relative;
    width:100%;
    height:320px;
}

.chart-container canvas{
    width:100% !important;
    height:100% !important;
}

/*
|--------------------------------------------------------------------------
| FORM
|--------------------------------------------------------------------------
*/
.form-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:10px;
}

.form-control{
    width:100%;
    padding:12px;
    border:1px solid #ccc;
    border-radius:8px;
}

.btn{
    border:none;
    border-radius:8px;
    padding:12px;
    color:white;
    font-weight:bold;
    cursor:pointer;
}

.btn-green{
    background:#16a34a;
}

.btn-blue{
    background:#2563eb;
}

/*
|--------------------------------------------------------------------------
| TABLE
|--------------------------------------------------------------------------
*/
.table-wrapper{
    overflow-x:auto;
}

.table{
    width:100%;
    border-collapse:collapse;
}

.table th,
.table td{
    padding:12px;
    border-bottom:1px solid #ddd;
    text-align:left;
}

.text-red{
    color:#dc2626;
    font-weight:bold;
}

/*
|--------------------------------------------------------------------------
| MOBILE RESPONSIVE
|--------------------------------------------------------------------------
*/
@media(max-width:768px){

    .main-layout{
        flex-direction:column;
    }

    .sidebar{
        width:100%;
        padding:15px;
    }

    .sidebar-title{
        font-size:22px;
        margin-bottom:15px;
    }

    .sidebar-menu{
        display:flex;
        flex-wrap:wrap;
        gap:8px;
    }

    .sidebar-menu a{
        margin-bottom:0;
        flex:1;
        text-align:center;
        min-width:120px;
    }

    .content{
        padding:10px;
    }

    .page-title{
        font-size:22px;
    }

    .stats-grid{
        flex-direction:column;
    }

    .form-grid{
        grid-template-columns:1fr;
    }

    .card{
        padding:15px;
    }

    /*
    |--------------------------------------------------------------------------
    | MOBILE CHART HEIGHT
    |--------------------------------------------------------------------------
    */
    .chart-container{
        height:220px;
    }

}

</style>

</head>

<body>

<div class="main-layout">

    <!-- SIDEBAR -->
    <div class="sidebar">

        <div class="sidebar-title">
            POS SYSTEM
        </div>

        <div class="sidebar-menu">

            <a href="dashboard.php" class="active">
                Dashboard
            </a>

            <a href="index.php">
                POS
            </a>

            <a href="stocks.php">
                Stocks
            </a>

            <a href="reports.php">
                Reports
            </a>

            <a href="logout.php">
                Logout
            </a>

        </div>

    </div>

    <!-- CONTENT -->
    <div class="content">

        <!-- HEADER -->
        <div class="page-title">
            Dashboard
        </div>

        <div class="page-subtitle">
            Welcome <?= htmlspecialchars($currentUser) ?>
            (<?= strtoupper($currentRole) ?>)
        </div>

        <!-- STOCK MESSAGE -->
        <?php if($message): ?>

            <div class="alert-success">
                <?= $message ?>
            </div>

        <?php endif; ?>

        <!-- USER MESSAGE -->
        <?php if($userMessage): ?>

            <div class="alert-info">
                <?= $userMessage ?>
            </div>

        <?php endif; ?>

        <!-- STATS -->
        <div class="stats-grid">

            <div class="stat-card">

                <div class="stat-title">
                    Daily Sales
                </div>

                <div class="stat-value" style="color:#16a34a;">
                    ₱<?= number_format($daily['total'],2) ?>
                </div>

            </div>

            <div class="stat-card">

                <div class="stat-title">
                    Monthly Sales
                </div>

                <div class="stat-value" style="color:#2563eb;">
                    ₱<?= number_format($monthly['total'],2) ?>
                </div>

            </div>

            <div class="stat-card">

                <div class="stat-title">
                    Products
                </div>

                <div class="stat-value" style="color:#7c3aed;">
                    <?= $totalProducts['total'] ?>
                </div>

            </div>

        </div>

        <!-- GRAPH -->
        <div class="card">

            <div class="card-title">
                Sales By Product
            </div>

            <!--
            |--------------------------------------------------------------------------
            | FIXED HEIGHT CONTAINER
            |--------------------------------------------------------------------------
            -->
            <div class="chart-container">

                <canvas id="salesChart"></canvas>

            </div>

        </div>

        <!-- STOCK MANAGEMENT -->
        <div class="card">

            <div class="card-title">
                Stock Management
            </div>

            <form method="POST" class="form-grid">

                <select
                    name="product_id"
                    required
                    class="form-control">

                    <option value="">
                        Select Product
                    </option>

                    <?php foreach($products as $p): ?>

                        <option value="<?= $p['id'] ?>">

                            <?= $p['name'] ?>
                            (Stock: <?= $p['stock'] ?>)

                        </option>

                    <?php endforeach; ?>

                </select>

                <input
                    type="number"
                    name="quantity"
                    placeholder="Quantity"
                    required
                    class="form-control"
                >

                <select
                    name="type"
                    class="form-control">

                    <option value="IN">
                        STOCK IN
                    </option>

                    <?php if($currentRole == 'admin'): ?>

                        <option value="OUT">
                            STOCK OUT
                        </option>

                        <option value="DAMAGE">
                            DAMAGE
                        </option>

                    <?php endif; ?>

                </select>

                <input
                    type="text"
                    name="note"
                    placeholder="Note"
                    class="form-control"
                >

                <button
                    type="submit"
                    name="save_stock"
                    class="btn btn-green">

                    Save Stock Transaction

                </button>

            </form>

        </div>

        <!-- USER MANAGEMENT -->
        <?php if($currentRole == 'admin'): ?>

        <div class="card">

            <div class="card-title">
                User Management
            </div>

            <form method="POST" class="form-grid">

                <input
                    type="text"
                    name="username"
                    placeholder="Username"
                    required
                    class="form-control"
                >

                <input
                    type="password"
                    name="password"
                    placeholder="Password"
                    required
                    class="form-control"
                >

                <select
                    name="role"
                    class="form-control">

                    <option value="cashier">
                        Cashier
                    </option>

                    <option value="admin">
                        Admin
                    </option>

                </select>

                <button
                    type="submit"
                    name="create_user"
                    class="btn btn-blue">

                    Create User

                </button>

            </form>

        </div>

        <?php endif; ?>

        <!-- LOW STOCK -->
        <div class="card">

            <div class="card-title" style="color:#dc2626;">
                Low Stock Alerts
            </div>

            <div class="table-wrapper">

                <table class="table">

                    <tr>

                        <th>
                            Product
                        </th>

                        <th>
                            Stock
                        </th>

                    </tr>

                    <?php foreach($lowStocks as $ls): ?>

                    <tr>

                        <td>
                            <?= $ls['name'] ?>
                        </td>

                        <td class="text-red">
                            <?= $ls['stock'] ?>
                        </td>

                    </tr>

                    <?php endforeach; ?>

                </table>

            </div>

        </div>

    </div>

</div>

<script>

/*
|--------------------------------------------------------------------------
| SALES CHART
|--------------------------------------------------------------------------
| Android optimized
|--------------------------------------------------------------------------
*/
const ctx =
    document.getElementById('salesChart');

new Chart(ctx, {

    type: 'bar',

    data: {

        labels: [

            <?php
            foreach($salesGraph as $g){
                echo "'".$g['name']."',";
            }
            ?>

        ],

        datasets: [{

            label: 'Units Sold',

            data: [

                <?php
                foreach($salesGraph as $g){
                    echo $g['qty'].",";
                }
                ?>

            ],

            borderWidth: 1

        }]

    },

    options: {

        /*
        |--------------------------------------------------------------------------
        | MOBILE FIX
        |--------------------------------------------------------------------------
        */
        responsive: true,
        maintainAspectRatio: true,
        animation: false,

        plugins: {

            legend: {
                display: true
            }

        },

        scales: {

            y: {
                beginAtZero: true
            }

        }

    }

});

</script>

</body>
</html>