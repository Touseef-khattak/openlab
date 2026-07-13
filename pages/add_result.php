<?php
if(!isset($_POST['test_id'])){
    header("location:../index.php");
}

include("../php/db.php");


if(isset($_POST['rel_test_id']))
{

    $test_ID = $_POST['test_id'];
    $rel_test_ID = $_POST['rel_test_id'];
    $updated_value = $_POST['updated_value'];
    $patient_test_id = $_POST['patient_test_id'];

    $sql = "UPDATE `patient_test_record` SET `result`= ? WHERE patient_test_ID = ? AND test_ID = ? AND rel_test_ID = ?";
    $stmt= $pdo->prepare($sql);
    $stmt->execute([$updated_value, $patient_test_id, $test_ID, $rel_test_ID]);


    //    echo "RELATED";
}
else
{

    $test_ID = $_POST['test_id'];
    $updated_value = $_POST['updated_value'];
    $patient_test_id = $_POST['patient_test_id'];
    
    $sql = "UPDATE `patient_test_record` SET `result`= ? WHERE patient_test_ID = ? AND test_ID = ?";
    $stmt= $pdo->prepare($sql);
    $stmt->execute([$updated_value, $patient_test_id, $test_ID]);
    //    echo "SIMPLE";

}
















?>