<?php
require 'config.php';

if ( isset( $_GET['id'] ) ) {
    $id = $_GET['id'];
    $str = ltrim( $id, 'd_' );

    $getrole = $conn -> prepare("SELECT role FROM `users` WHERE user_ID = ".$str);
    $getrole -> execute();
    $user = $getrole -> fetch();
    if($user['role'] == 1){
        echo "You ca not delete Admin";
        header( 'Location: ../pages/users.php' );
    }
    else{

        $sql = "DELETE FROM `users` WHERE `user_ID` = :d";
        $adstmt = $conn ->prepare( $sql );
        $adstmt -> execute( array(
            ':d' => $str
        ) );
        if ( $adstmt == TRUE ) {
            header( 'Location: ../pages/users.php' );
        }
    }
}

?>
