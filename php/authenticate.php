<?php
require 'config.php';
session_start();
$username = $_POST['login_username'];
$password = $_POST['login_userpass'];

if($username == "" || $password == ""){
    $_SESSION['loginMessage'] = 'Please Enter yours credentials';    
    header('Location: ../pages/login.php');
}
else{
    $adstmt = $conn-> prepare('SELECT * FROM `users` WHERE password = :p  and name = :u');
    $adstmt -> execute(array(
        ':p' => $password,
        ':u' => $username

    ));
    $rst = $adstmt -> fetch();

    if($rst == ""){
        $_SESSION['loginMessage'] = 'Incorrect Username and Password';
        header('Location: ../pages/login.php');
    }
    else{
        $_SESSION['login_username'] = $rst['name'];
        $_SESSION['login_role'] = $rst['role'];

        if($rst['role'] == 3 ){

            header('Location: ../pages/result.php');
        }
        else
            header('Location: ../index.php');

        exit();
    }
}
?>
