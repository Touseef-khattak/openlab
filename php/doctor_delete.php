<?php
require 'config.php';

if ( isset( $_GET['id'] ) ) {
    $id = $_GET['id'];
    $sql = "DELETE FROM `doctors` WHERE `doc_ID` = :d";
    $adstmt = $conn ->prepare( $sql );
    $adstmt -> execute( array(
        ':d' => $id
    ) );
    if ( $adstmt == TRUE ) {
        header( 'Location: ../pages/doctors.php' );
    }
}

?>
