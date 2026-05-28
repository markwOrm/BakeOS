<?php
session_start();

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| FIX SESSION VALUES
|--------------------------------------------------------------------------
*/

/*
If session user is array:
$_SESSION['user'] = [
   'username' => 'admin',
   'role' => 'admin'
];
*/

if(is_array($_SESSION['user'])){

    $currentUser = $_SESSION['user']['username'];
    $currentRole = $_SESSION['user']['role'];

}else{

    $currentUser = $_SESSION['user'];
    $currentRole = $_SESSION['role'] ?? 'cashier';
}
?>