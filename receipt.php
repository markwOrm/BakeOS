<?php
require 'db.php';

$id = $_GET['id'];

// GET TRANSACTION
$t = $pdo->query("SELECT * FROM transactions WHERE id=$id")->fetch();

// GET ITEMS
$items = $pdo->query("
SELECT ti.*, p.name 
FROM transaction_items ti
JOIN products p ON p.id=ti.product_id
WHERE transaction_id=$id
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receipt</title>

<style>
/* RESET */
body {
  font-family: monospace;
  background: #f3f4f6;
  display: flex;
  justify-content: center;
  padding: 20px;
}

/* RECEIPT CONTAINER */
.receipt {
  width: 320px;
  background: #fff;
  padding: 15px;
  border-radius: 10px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* HEADER */
.header {
  text-align: center;
  border-bottom: 1px dashed #000;
  padding-bottom: 10px;
  margin-bottom: 10px;
}

.header h2 {
  margin: 0;
}

.header p {
  font-size: 12px;
  margin: 2px 0;
}

/* ITEMS */
.items {
  width: 100%;
  font-size: 14px;
}

.items th {
  text-align: left;
  border-bottom: 1px dashed #000;
  padding-bottom: 5px;
}

.items td {
  padding: 5px 0;
}

/* TOTAL */
.total {
  border-top: 1px dashed #000;
  margin-top: 10px;
  padding-top: 10px;
  font-weight: bold;
  display: flex;
  justify-content: space-between;
}

/* FOOTER */
.footer {
  text-align: center;
  margin-top: 15px;
  font-size: 12px;
}

/* BUTTONS */
.actions {
  text-align: center;
  margin-top: 15px;
}

button {
  padding: 10px 15px;
  margin: 5px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

.print {
  background: #16a34a;
  color: white;
}

.skip {
  background: #6b7280;
  color: white;
}

/* PRINT MODE */
@media print {
  body {
    background: white;
  }

  .actions {
    display: none;
  }

  .receipt {
    box-shadow: none;
    border-radius: 0;
  }
}
</style>

</head>
<body>

<div class="receipt">

  <!-- HEADER -->
  <div class="header">
    <h2>Bread OS</h2>
    <p>For Inventory Only</p>
    <p><?= date('Y-m-d H:i') ?></p>
    <p>Receipt #: <?= $id ?></p>
  </div>

  <!-- ITEMS -->
  <table class="items">
    <tr>
      <th>Item</th>
      <th>Qty</th>
      <th>Total</th>
    </tr>

    <?php foreach($items as $i): ?>
    <tr>
      <td><?= $i['name'] ?></td>
      <td><?= $i['quantity'] ?></td>
      <td>₱<?= number_format($i['price'] * $i['quantity'], 2) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>

  <!-- TOTAL -->
  <div class="total">
    <span>Total</span>
    <span>₱<?= number_format($t['total'], 2) ?></span>
  </div>

  <!-- FOOTER -->
  <div class="footer">
    <p>Thank you for your purchase!</p>
  </div>
<!-- ACTIONS -->
<div class="actions">
  <button class="print" onclick="window.print()">🖨 Print</button>
  <button class="skip" onclick="window.location='index.php'">New Sale</button>
</div>
</div>



</body>
</html>