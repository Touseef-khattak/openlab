<?php

require 'config.php';
session_start();

$test_name = $_POST['add_test_name'];
//$test_name = str_replace(' ', '',$_POST['add_test_name']);
$test_price = $_POST['add_test_price'];
if(isset($_POST['add_min_test_value'])){

    $test_ref_min = $_POST['add_min_test_value'];
}
else
    $test_ref_min = 0;
if(isset($_POST['add_max_test_value'])){

    $test_ref_max = $_POST['add_max_test_value'];
}
else
    $test_ref_max = 0;
$test_value = $_POST['add_value_box'];

$sql = 'INSERT INTO tests(name,reference_min,reference_max,price,type) VALUES(:name,:ref_min,:ref_max,:price,:type)';

if ( $test_value == 'single' ) {
    if ( $test_name == '' || $test_price == '') {
        $_SESSION['add_test_msg'] = "Please Fill all Fields";
        header( 'Location: ../pages/tests.php' );
    } else {
        // -------- New Test -------  
        if ( isName($test_name) == '' ){

            $stmt = $conn ->prepare( $sql );
            $stmt->execute( array(
                ':name'=> $test_name,
                ':ref_min'=> $test_ref_min,
                ':ref_max'=> $test_ref_max,
                ':price'=> $test_price,                    
                ':type'=> 0                    
            ) );
            header( 'Location: ../pages/tests.php' );

        }else{
            $_SESSION['add_test_msg'] = "Name is already available";
            header( 'Location: ../pages/tests.php' );
        }

    }
} else if ( $test_value == 'multiple' ) {
    if ( $test_name == '' || $test_price == '' ) {
        header( 'Location: ../pages/tests.php' );    
    } else {
        if ( isName($test_name) == '' ){
            $stmt = $conn ->prepare( $sql );
            $stmt->execute( array(
                ':name'=> $test_name,
                ':ref_min'=> 0,
                ':ref_max'=> 0,
                ':price'=> $test_price ,                    
                ':type'=> 1                    
            ) );
            header( 'Location: ../pages/tests.php' );
        }else{
            $_SESSION['add_test_msg'] = "This Name is already available";
            header( 'Location: ../pages/tests.php' );
        }
    }
}

function isName($var){
    include 'config.php';   
    $adstmt = $conn -> prepare( 'SELECT * FROM `tests` WHERE name = :u' );
    $adstmt -> execute( array(
        ':u' => $var
    ) );
    $rst = $adstmt -> fetch();
    return $rst;
}

?>