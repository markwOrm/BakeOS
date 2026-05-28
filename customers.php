<?php
require 'auth.php';
require 'db.php';

// ADD CUSTOMER
if(isset($_POST['name'])){
    $stmt = $pdo->prepare("INSERT INTO customers (name, contact) VALUES (?,?)");
    $stmt->execute([$_POST['name'], $_POST['contact']]);
}

// GET CUSTOMERS
$customers = $pdo->query("SELECT * FROM customers")->fetchAll();
?>

<h2>Customers</h2>

<form method="POST">
<input name="name" placeholder="Name">
<input name="contact" placeholder="Contact">
<button>Add</button>
</form>

<ul>
<?php foreach($customers as $c): ?>
<li><?= $c['name'] ?></li>
<?php endforeach; ?>
</ul>