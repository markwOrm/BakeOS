<?php
session_start();
require 'db.php';

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $username = $_POST['username'];
    $password = $_POST['password'];

    /*
    |--------------------------------------------------------------------------
    | GET USER
    |--------------------------------------------------------------------------
    */
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$username]);

    $user = $stmt->fetch();

    /*
    |--------------------------------------------------------------------------
    | VERIFY PASSWORD
    |--------------------------------------------------------------------------
    */
    if($user && password_verify($password, $user['password'])){

        $_SESSION['user'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        header("Location: dashboard.php");
        exit;

    }else{
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>BakeOS Login</title>

<link rel="stylesheet" href="tailwind.min.css">

</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">

<div class="bg-white p-8 rounded-2xl shadow-xl w-96">

    <h1 class="text-3xl font-bold text-center mb-6">
        BakeOS LOGIN
    </h1>

    <?php if($error): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <input 
            type="text"
            name="username"
            placeholder="Username"
            required
            class="w-full border p-3 rounded mb-4"
        >

        <input 
            type="password"
            name="password"
            placeholder="Password"
            required
            class="w-full border p-3 rounded mb-4"
        >

        <button class="w-full bg-green-600 text-white p-3 rounded-lg">
            Login
        </button>

    </form>

</div>

</body>
</html>