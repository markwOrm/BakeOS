<?php
require 'auth.php';
require 'db.php';

/*
|--------------------------------------------------------------------------
| GET DATA
|--------------------------------------------------------------------------
*/
$cart = json_decode($_POST['cart'], true);

$customer_id = $_POST['customer_id'] ?: null;

$total = 0;

/*
|--------------------------------------------------------------------------
| COMPUTE TOTAL
|--------------------------------------------------------------------------
*/
foreach($cart as $item){

    $total += $item['price'] * $item['qty'];
}

/*
|--------------------------------------------------------------------------
| SAVE TRANSACTION
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
INSERT INTO transactions
(customer_id,total,created_by)
VALUES (?,?,?)
");

$stmt->execute([
    $customer_id,
    $total,
    $currentUser
]);

$tid = $pdo->lastInsertId();

/*
|--------------------------------------------------------------------------
| LOOP ITEMS
|--------------------------------------------------------------------------
*/
foreach($cart as $pid => $item){

    /*
    |--------------------------------------------------------------------------
    | SAVE ITEMS
    |--------------------------------------------------------------------------
    */
    $stmt = $pdo->prepare("
    INSERT INTO transaction_items
    (transaction_id,product_id,quantity,price)
    VALUES (?,?,?,?)
    ");

    $stmt->execute([
        $tid,
        $pid,
        $item['qty'],
        $item['price']
    ]);

    /*
    |--------------------------------------------------------------------------
    | DEDUCT STOCK
    |--------------------------------------------------------------------------
    */
    $pdo->prepare("
    UPDATE products
    SET stock = stock - ?
    WHERE id=?
    ")->execute([
        $item['qty'],
        $pid
    ]);

    /*
    |--------------------------------------------------------------------------
    | STOCK LOG
    |--------------------------------------------------------------------------
    */
    $stmt = $pdo->prepare("
    INSERT INTO stock_logs
    (product_id,type,quantity,note,created_by)
    VALUES (?,?,?,?,?)
    ");

    $stmt->execute([
        $pid,
        'OUT',
        $item['qty'],
        'Sale',
        $currentUser
    ]);
}

/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/
header("Location: receipt.php?id=".$tid);
exit;
?>