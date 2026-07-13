<?php
require 'config.php';

    if ( isset( $_GET['id'] ) ) {
        $id = $_GET['id'];
        $str = ltrim( $id, 'dt_' );
        $sql = "DELETE FROM `tests` WHERE `test_ID` = :d";
        $rt_sql = "DELETE FROM `related_tests` WHERE `test_ID` = :r";
        
        $adstmt = $conn ->prepare( $rt_sql );
        $adstmt -> execute( array(
            ':r' => $str
        ) );
        
        $adstmt2 = $conn ->prepare( $sql );
        $adstmt2 -> execute( array(
            ':d' => $str
        ) );
        if ( $adstmt2 == TRUE ) {
            header( 'Location: ../pages/tests.php' );
        }
    }

?>
