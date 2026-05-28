<?php
require 'auth.php';
require 'db.php';

if(isset($_POST['product_id'])){

    $type = $_POST['type'];
    $qty = $_POST['qty'];

    if($type == 'IN'){
        $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id=?")
            ->execute([$qty,$_POST['product_id']]);
    } else {
        $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id=?")
            ->execute([$qty,$_POST['product_id']]);
    }

    $pdo->prepare("
        INSERT INTO stock_logs (product_id,type,quantity,note)
        VALUES (?,?,?,?)
    ")->execute([
        $_POST['product_id'],
        $type,
        $qty,
        $_POST['note']
    ]);
}

$products = $pdo->query("SELECT * FROM products")->fetchAll();
?>

<h2>Inventory</h2>

<form method="POST">
<select name="product_id">
<?php foreach($products as $p): ?>
<option value="<?= $p['id'] ?>"><?= $p['name'] ?></option>
<?php endforeach; ?>
</select>

<select name="type">
<option value="IN">Stock IN</option>
<option value="DAMAGE">Damage</option>
</select>

<input name="qty" placeholder="Qty">
<input name="note" placeholder="Note">

<button>Submit</button>
</form>