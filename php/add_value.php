<?php

require 'config.php';
session_start();

$test_name = $_POST['add_value_name'];
$test_ref_min = $_POST['add_min_ref_value'];
$test_ref_max = $_POST['add_max_ref_value'];
$test_value = $_POST['add_value_test'];

$sql = 'INSERT INTO related_tests(test_ID,related_test,min,max) VALUES(:id,:name,:min,:max)';

if ( $test_name == '' || $test_ref_min == '' || $test_ref_max == '' ) {
    $_SESSION['add_value'] = "Please Fill all Fields";
        header( 'Location: ../pages/tests.php' );
    
} else if ( $test_value == 's0' ) {
    $_SESSION['add_value'] = "Please Select the Test";
        header( 'Location: ../pages/tests.php' );
} else {

    $bol = searchName( $test_name, $test_value );
    if ( $bol ) {
        $_SESSION['add_value'] = "Already Available";
        header( 'Location: ../pages/tests.php' );
    } else {
        $stmt = $conn ->prepare( $sql );
        $stmt->execute( array(
            ':name'=> $test_name,
            ':min'=> $test_ref_min,
            ':max'=> $test_ref_max,
            ':id'=> $test_value
        ) );
        header( 'Location: ../pages/tests.php' );

    }


}

function searchName( $name, $id ) {
    include 'config.php';

    $sts = $conn -> query( 'SELECT related_test FROM `related_tests` WHERE test_ID = '.$id );
    foreach ( $sts as $data ) {
        if (str_replace(' ', '', strtoupper($data['related_test'])) == str_replace(' ', '', strtoupper($name)) ) {
            return true;
        }

    }

}

?>
