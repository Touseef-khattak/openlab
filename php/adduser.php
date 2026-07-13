<?php

require 'config.php';
session_start();

$user_name = $_POST['add_user_name'];
$user_cnic = $_POST['add_user_cnic'];
$user_mobile = $_POST['add_user_mobile'];
$user_role = $_POST['add_user_role'];
$user_password = $_POST['add_user_pass'];


$sql = 'INSERT INTO users(name,cnic,mobile,role,password) VALUES(:name,:cnic,:mobile,:role,:password)';



if ( $user_name == '' || $user_cnic == '' || $user_mobile == ''  || $user_password == '' ) {
    $_SESSION['add_user_Message'] = 'Fill all the Fields';    
    header( 'Location: ../pages/users.php' );
} else {
    
    $adstmt = $conn -> prepare( 'SELECT * FROM `users` WHERE name = :u' );
    $adstmt -> execute( array(
        ':u' => $user_name
    ) );
    
    $rst = $adstmt -> fetch();

    if ( $rst == '' ) {
        $adstmt = $conn -> prepare( 'SELECT * FROM `users` WHERE mobile = :m' );
        $adstmt -> execute( array(
            ':m' => $user_mobile
        ) );
        
        $rst = $adstmt -> fetch();
        if ( $rst == '' ) {
            $adstmt = $conn -> prepare( 'SELECT * FROM `users` WHERE cnic = :c' );
            $adstmt -> execute( array(
                ':c' => $user_cnic
            ) );
            
            $rst = $adstmt -> fetch();
            if ( $rst == '' ) {

                //get role ID   
                $stmt = $conn -> prepare('SELECT * FROM `user_role` WHERE name = :n');
                $stmt -> execute(array(
                    ':n' => $user_role
                ));
                $getr = $stmt -> fetch();                
                
                $stmt = $conn ->prepare( $sql );
                $stmt->execute( array(
                    ':name'=> $user_name,
                    ':cnic'=> $user_cnic,
                    ':mobile'=> $user_mobile,
                    ':role'=> $getr["role_ID"],
                    ':password'=> $user_password
                ) );
                header( 'Location: ../pages/users.php' );

            } else {
                $_SESSION['add_user_Message'] = 'This CNIC number is already exist';
                header( 'Location: ../pages/users.php' );
            }

        } else {
            $_SESSION['add_user_Message'] = 'This mobile number is already exist';
            header( 'Location: ../pages/users.php' );

        }
    } else {
        $_SESSION['add_user_Message'] = 'username is already taken';
        header( 'Location: ../pages/users.php' );

    }

}

?>
