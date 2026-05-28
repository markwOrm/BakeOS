<?php

/*
|--------------------------------------------------------------------------
| PROTECT PAGE
|--------------------------------------------------------------------------
| Redirect user to login page if not logged in
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
| FETCH PRODUCTS
|--------------------------------------------------------------------------
*/
$stmt = $pdo->query("
SELECT *
FROM products
ORDER BY name ASC
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<!--
|--------------------------------------------------------------------------
| MOBILE VIEWPORT
|--------------------------------------------------------------------------
-->
<meta name="viewport"
      content="width=device-width,
               initial-scale=1.0,
               maximum-scale=1.0,
               user-scalable=no">

<title>POS System</title>

<link rel="stylesheet" href="tailwind.min.css">

<style>

/*
|--------------------------------------------------------------------------
| GLOBAL
|--------------------------------------------------------------------------
*/
html,
body{
    height:100%;
    margin:0;
    overscroll-behavior-y:contain;
    touch-action:pan-x pan-y;
    font-family:sans-serif;
    background:#f3f4f6;
}

/*
|--------------------------------------------------------------------------
| REMOVE PAGE OVERFLOW
|--------------------------------------------------------------------------
*/
body{
    overflow:hidden;
}

/*
|--------------------------------------------------------------------------
| MAIN LAYOUT
|--------------------------------------------------------------------------
*/
.main-layout{
    display:flex;
    height:100vh;
    overflow:hidden;
}

/*
|--------------------------------------------------------------------------
| LEFT PRODUCTS PANEL
|--------------------------------------------------------------------------
*/
.products-panel{
    width:68%;
    display:flex;
    flex-direction:column;
    overflow:hidden;
    padding:8px;
}

/*
|--------------------------------------------------------------------------
| PRODUCTS SCROLL AREA
|--------------------------------------------------------------------------
*/
.products-scroll{
    flex:1;
    overflow-y:auto;
    -webkit-overflow-scrolling:touch;
    padding-bottom:20px;
}

/*
|--------------------------------------------------------------------------
| RIGHT BILLING PANEL
|--------------------------------------------------------------------------
*/
.billing-panel{
    width:32%;
    background:#ffffff;
    display:flex;
    flex-direction:column;
    overflow:hidden;
    border-left:1px solid #e5e7eb;
    padding:10px;
}

/*
|--------------------------------------------------------------------------
| RIGHT PANEL SCROLLABLE CONTENT
|--------------------------------------------------------------------------
*/
.billing-scroll{
    flex:1;
    overflow-y:auto;
    -webkit-overflow-scrolling:touch;
    padding-bottom:20px;
}

/*
|--------------------------------------------------------------------------
| PRODUCT GRID
|--------------------------------------------------------------------------
*/
.product-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(120px,1fr));
    gap:10px;
}

/*
|--------------------------------------------------------------------------
| PRODUCT CARD
|--------------------------------------------------------------------------
*/
.product-card{
    background:#ffffff;
    border-radius:14px;
    padding:10px;
    cursor:pointer;
    transition:0.2s;
    box-shadow:0 1px 4px rgba(0,0,0,0.08);
}

.product-card:hover{
    background:#dcfce7;
}

/*
|--------------------------------------------------------------------------
| PRODUCT IMAGE
|--------------------------------------------------------------------------
*/
.product-image{
    height:75px;
    width:auto;
    object-fit:contain;
    margin:auto;
}

/*
|--------------------------------------------------------------------------
| PRODUCT TEXT
|--------------------------------------------------------------------------
*/
.product-name{
    font-size:13px;
    font-weight:600;
    text-align:center;
    margin-top:8px;
    line-height:1.2;
}

.product-price{
    text-align:center;
    color:#16a34a;
    font-weight:bold;
    font-size:14px;
    margin-top:4px;
}

.stock-text{
    text-align:center;
    font-size:11px;
    color:#6b7280;
    margin-top:2px;
}

/*
|--------------------------------------------------------------------------
| BILL ITEMS
|--------------------------------------------------------------------------
*/
.bill-items{
    border:1px solid #d1d5db;
    border-radius:10px;
    background:#f9fafb;
    padding:10px;
    max-height:260px;
    overflow-y:auto;
    -webkit-overflow-scrolling:touch;
}

/*
|--------------------------------------------------------------------------
| FORM ELEMENTS
|--------------------------------------------------------------------------
*/
.input-field{
    width:100%;
    padding:10px;
    border:1px solid #d1d5db;
    border-radius:8px;
    font-size:14px;
}

/*
|--------------------------------------------------------------------------
| CHECKOUT BUTTON
|--------------------------------------------------------------------------
*/
.checkout-button{
    width:100%;
    background:#16a34a;
    color:white;
    padding:14px;
    border:none;
    border-radius:10px;
    font-weight:bold;
    font-size:16px;
    margin-top:10px;
}

.checkout-button:hover{
    background:#15803d;
}

/*
|--------------------------------------------------------------------------
| SIDE BY SIDE CUSTOMER FIELDS
|--------------------------------------------------------------------------
*/
.customer-row{
    display:flex;
    gap:8px;
    margin-top:10px;
}

.customer-row input,
.customer-row select{
    flex:1;
}

/*
|--------------------------------------------------------------------------
| REMOVE NUMBER ARROWS
|--------------------------------------------------------------------------
*/
input[type=number]::-webkit-outer-spin-button,
input[type=number]::-webkit-inner-spin-button{
    -webkit-appearance:none;
    margin:0;
}

input[type=number]{
    -moz-appearance:textfield;
}

/*
|--------------------------------------------------------------------------
| TABLET OPTIMIZATION
|--------------------------------------------------------------------------
*/
@media(max-width:1024px){

    .products-panel{
        width:65%;
    }

    .billing-panel{
        width:35%;
    }

    .product-grid{
        grid-template-columns:repeat(auto-fill,minmax(105px,1fr));
    }

    .product-image{
        height:60px;
    }

    .product-name{
        font-size:12px;
    }

    .checkout-button{
        padding:12px;
        font-size:15px;
    }
}

/*
|--------------------------------------------------------------------------
| MOBILE VIEW
|--------------------------------------------------------------------------
*/
@media(max-width:767px){

    body{
        overflow:auto;
    }

    .main-layout{
        flex-direction:column;
        height:auto;
    }

    .products-panel,
    .billing-panel{
        width:100%;
        height:auto;
    }

    .products-scroll,
    .billing-scroll{
        overflow:visible;
    }

    .bill-items{
        max-height:none;
    }

    .customer-row{
        flex-direction:column;
    }
}

</style>

</head>

<body>

<div class="main-layout">

    <!-- ===================================================== -->
    <!-- PRODUCTS PANEL -->
    <!-- ===================================================== -->
    <div class="products-panel">

        <!-- TOP BAR -->
        <div class="flex justify-between items-center mb-3">

            <div>

                <h2 class="text-xl font-bold">
                    POS Products
                </h2>

                <p class="text-gray-500 text-sm">
                    Welcome <?= htmlspecialchars($currentUser) ?>
                    (<?= strtoupper($currentRole) ?>)
                </p>

            </div>

            <div class="flex gap-2">

                <!-- DASHBOARD -->
                <a href="dashboard.php"
                   class="bg-blue-600 text-white px-3 py-2 rounded text-sm">

                    Dashboard

                </a>

                <!-- LOGOUT -->
                <a href="logout.php"
                   class="bg-red-600 text-white px-3 py-2 rounded text-sm">

                    Logout

                </a>

            </div>

        </div>

        <!-- PRODUCTS SCROLL -->
        <div class="products-scroll">

            <!-- PRODUCT GRID -->
            <div class="product-grid">

                <?php foreach($products as $p): ?>

                <div
                    class="product-card"
                    onclick="addProduct(
                        <?= $p['id'] ?>,
                        '<?= htmlspecialchars(addslashes($p['name'])) ?>',
                        <?= $p['price'] ?>,
                        <?= $p['stock'] ?>
                    )">

                    <!-- PRODUCT IMAGE -->
                    <img
                        src="./images/<?= htmlspecialchars($p['image']) ?>"
                        class="product-image"
                    >

                    <!-- PRODUCT NAME -->
                    <div class="product-name">
                        <?= htmlspecialchars($p['name']) ?>
                    </div>

                    <!-- PRODUCT PRICE -->
                    <div class="product-price">
                        ₱<?= number_format($p['price'],2) ?>
                    </div>

                    <!-- STOCK -->
                    <div class="stock-text">
                        Stock: <?= $p['stock'] ?>
                    </div>

                </div>

                <?php endforeach; ?>

            </div>

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- BILLING PANEL -->
    <!-- ===================================================== -->
    <div class="billing-panel">

        <!-- SCROLLABLE RIGHT PANEL -->
        <div class="billing-scroll">

            <!-- TITLE -->
            <h2 class="text-xl font-bold mb-3">
                Billing
            </h2>

            <!-- BILL ITEMS -->
            <div id="billItems"
                 class="bill-items mb-3">
            </div>

            <!-- TOTAL -->
            <div class="mb-3">

                <h3 class="text-xl font-bold">
                    Total:
                    <span class="text-green-600">
                        ₱<span id="total">0.00</span>
                    </span>
                </h3>

            </div>

            <!-- CASH INPUT -->
            <div class="mb-3">

                <label class="block text-sm font-semibold mb-1">
                    Cash Tendered
                </label>

                <input
                    type="number"
                    id="cashInput"
                    step="0.01"
                    min="0"
                    placeholder="Enter cash amount"
                    class="input-field"
                    oninput="computeChange()"
                >

            </div>

            <!-- CHANGE -->
            <div class="mb-3">

                <p class="text-lg font-bold">
                    Change:
                    <span class="text-blue-600">
                        ₱<span id="change">0.00</span>
                    </span>
                </p>

            </div>

            <!-- ERROR -->
            <p id="paymentError"
               class="text-red-600 text-sm mb-3 hidden">
            </p>

            <!-- CHECKOUT FORM -->
            <form method="POST"
                  action="save.php"
                  onsubmit="return validateCheckout()">

                <!-- HIDDEN INPUTS -->
                <input type="hidden" name="cart" id="cartInput">
                <input type="hidden" name="cash" id="cashHidden">
                <input type="hidden" name="change" id="changeHidden">

                <!-- CUSTOMER ROW -->
                <div class="customer-row">

                    <!-- CUSTOMER NAME -->
                    <input
                        type="text"
                        name="name"
                        value="CASH"
                        placeholder="Customer Name"
                        class="input-field"
                    >

                    <!-- CUSTOMER -->
                    <select
                        name="customer_id"
                        class="input-field">

                        <option value="">
                            Walk-in Customer
                        </option>

                        <?php

                        /*
                        |--------------------------------------------------------------------------
                        | FETCH CUSTOMERS
                        |--------------------------------------------------------------------------
                        */
                        $customers = $pdo->query("
                        SELECT *
                        FROM customers
                        ORDER BY name ASC
                        ")->fetchAll();

                        foreach($customers as $c):
                        ?>

                        <option value="<?= $c['id'] ?>">
                            <?= htmlspecialchars($c['name']) ?>
                        </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- CHECKOUT BUTTON -->
                <button
                    type="submit"
                    class="checkout-button">

                    Checkout

                </button>
                <br> <br> <br> <br><br><br>

            </form>

        </div>

    </div>

</div>

<script>

/*
|--------------------------------------------------------------------------
| SAFE PULL-TO-REFRESH PREVENTION
|--------------------------------------------------------------------------
| Prevent accidental browser refresh WITHOUT breaking scrolling
|--------------------------------------------------------------------------
*/
document.documentElement.style.overscrollBehavior = 'contain';
document.body.style.overscrollBehavior = 'contain';

/*
|--------------------------------------------------------------------------
| CART STORAGE
|--------------------------------------------------------------------------
*/
let cart = {};

/*
|--------------------------------------------------------------------------
| TOUCH / SCROLL DETECTION
|--------------------------------------------------------------------------
| Prevent accidental product tap while user is scrolling
|--------------------------------------------------------------------------
*/
let isScrolling = false;
let touchStartX = 0;
let touchStartY = 0;

/*
|--------------------------------------------------------------------------
| DETECT TOUCH START
|--------------------------------------------------------------------------
*/
document.addEventListener('touchstart', function(e){

    touchStartX = e.touches[0].clientX;
    touchStartY = e.touches[0].clientY;

    isScrolling = false;

}, { passive:true });

/*
|--------------------------------------------------------------------------
| DETECT TOUCH MOVE
|--------------------------------------------------------------------------
*/
document.addEventListener('touchmove', function(e){

    let moveX =
        Math.abs(
            e.touches[0].clientX - touchStartX
        );

    let moveY =
        Math.abs(
            e.touches[0].clientY - touchStartY
        );

    /*
    |--------------------------------------------------------------------------
    | USER IS SCROLLING
    |--------------------------------------------------------------------------
    */
    if(moveX > 8 || moveY > 8){
        isScrolling = true;
    }

}, { passive:true });

/*
|--------------------------------------------------------------------------
| SAFE PRODUCT CLICK
|--------------------------------------------------------------------------
| Adds delay protection against accidental taps while scrolling
|--------------------------------------------------------------------------
*/
function productClick(id,name,price,stock){

    /*
    |--------------------------------------------------------------------------
    | IGNORE CLICK IF USER WAS SCROLLING
    |--------------------------------------------------------------------------
    */
    if(isScrolling){
        return;
    }

    addProduct(id,name,price,stock);
}

/*
|--------------------------------------------------------------------------
| ADD PRODUCT
|--------------------------------------------------------------------------
*/
function addProduct(id,name,price,stock){

    /*
    |--------------------------------------------------------------------------
    | CHECK PRODUCT STOCK
    |--------------------------------------------------------------------------
    */
    if(stock <= 0){

        alert('Product is out of stock.');
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | NEW ITEM
    |--------------------------------------------------------------------------
    */
    if(!cart[id]){

        cart[id] = {
            name:name,
            price:parseFloat(price),
            qty:1,
            stock:parseInt(stock)
        };

    }else{

        /*
        |--------------------------------------------------------------------------
        | PREVENT EXCEEDING STOCK
        |--------------------------------------------------------------------------
        */
        if(cart[id].qty >= cart[id].stock){

            alert('Not enough stock available.');
            return;
        }

        cart[id].qty++;
    }

    renderCart();
}

/*
|--------------------------------------------------------------------------
| REMOVE PRODUCT
|--------------------------------------------------------------------------
*/
function removeProduct(id){

    delete cart[id];

    renderCart();
}

/*
|--------------------------------------------------------------------------
| UPDATE QUANTITY
|--------------------------------------------------------------------------
*/
function updateQty(id, qty){

    qty = parseInt(qty);

    /*
    |--------------------------------------------------------------------------
    | REMOVE IF ZERO
    |--------------------------------------------------------------------------
    */
    if(qty <= 0){

        removeProduct(id);
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | STOCK VALIDATION
    |--------------------------------------------------------------------------
    */
    if(qty > cart[id].stock){

        alert('Quantity exceeds available stock.');

        qty = cart[id].stock;
    }

    cart[id].qty = qty;

    renderCart();
}

/*
|--------------------------------------------------------------------------
| RENDER CART
|--------------------------------------------------------------------------
*/
function renderCart(){

    let container =
        document.getElementById('billItems');

    let total = 0;

    container.innerHTML = '';

    /*
    |--------------------------------------------------------------------------
    | LOOP CART ITEMS
    |--------------------------------------------------------------------------
    */
    Object.keys(cart).forEach(id=>{

        let item = cart[id];

        let subtotal =
            item.price * item.qty;

        total += subtotal;

        container.innerHTML += `
            <div class="border-b py-3">

                <p class="font-bold">
                    ${item.name}
                </p>

                <div class="flex items-center gap-2 mt-2">

                    <input
                        type="number"
                        min="1"
                        max="${item.stock}"
                        value="${item.qty}"
                        onchange="updateQty(${id},this.value)"
                        class="w-20 border p-2 rounded"
                    >

                    <span class="font-semibold">
                        ₱${subtotal.toFixed(2)}
                    </span>

                    <button
                        type="button"
                        onclick="removeProduct(${id})"
                        class="text-red-600 ml-auto text-lg">

                        ❌

                    </button>

                </div>

            </div>
        `;
    });

    /*
    |--------------------------------------------------------------------------
    | UPDATE TOTAL
    |--------------------------------------------------------------------------
    */
    document.getElementById('total').innerText =
        total.toFixed(2);

    /*
    |--------------------------------------------------------------------------
    | SAVE CART JSON
    |--------------------------------------------------------------------------
    */
    document.getElementById('cartInput').value =
        JSON.stringify(cart);

    /*
    |--------------------------------------------------------------------------
    | UPDATE CHANGE
    |--------------------------------------------------------------------------
    */
    computeChange();
}

/*
|--------------------------------------------------------------------------
| COMPUTE CHANGE
|--------------------------------------------------------------------------
*/
function computeChange(){

    let total =
        parseFloat(
            document.getElementById('total').innerText
        ) || 0;

    let cash =
        parseFloat(
            document.getElementById('cashInput').value
        ) || 0;

    let change = cash - total;

    /*
    |--------------------------------------------------------------------------
    | DISPLAY CHANGE
    |--------------------------------------------------------------------------
    */
    document.getElementById('change').innerText =
        change > 0
            ? change.toFixed(2)
            : '0.00';

    /*
    |--------------------------------------------------------------------------
    | SAVE HIDDEN VALUES
    |--------------------------------------------------------------------------
    */
    document.getElementById('cashHidden').value =
        cash.toFixed(2);

    document.getElementById('changeHidden').value =
        change > 0
            ? change.toFixed(2)
            : '0.00';
}

/*
|--------------------------------------------------------------------------
| VALIDATE CHECKOUT
|--------------------------------------------------------------------------
*/
function validateCheckout(){

    let total =
        parseFloat(
            document.getElementById('total').innerText
        ) || 0;

    let cash =
        parseFloat(
            document.getElementById('cashInput').value
        ) || 0;

    let error =
        document.getElementById('paymentError');

    /*
    |--------------------------------------------------------------------------
    | EMPTY CART
    |--------------------------------------------------------------------------
    */
    if(Object.keys(cart).length === 0){

        error.classList.remove('hidden');

        error.innerText =
            'Cart is empty.';

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | INVALID CASH
    |--------------------------------------------------------------------------
    */
    if(cash <= 0){

        error.classList.remove('hidden');

        error.innerText =
            'Please enter valid cash amount.';

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | NO CREDIT ALLOWED
    |--------------------------------------------------------------------------
    */
    if(cash < total){

        error.classList.remove('hidden');

        error.innerText =
            'Insufficient cash. Credit transactions are not allowed.';

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | VALID
    |--------------------------------------------------------------------------
    */
    error.classList.add('hidden');

    return true;
}

</script>

</body>
</html>