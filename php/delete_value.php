<?php
require 'config.php';

    if ( isset( $_GET['id'] ) ) {
        $id = $_GET['id'];
        $rt_sql = "DELETE FROM `related_tests` WHERE `rel_test_ID` = :r";
            
        $adstmt2 = $conn ->prepare( $rt_sql );
        $adstmt2 -> execute( array(
            ':r' => $id
        ) );
        if ( $adstmt2 == TRUE ) {
            header( 'Location: ../pages/tests.php' );
        }
    }

?>
