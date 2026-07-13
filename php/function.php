<?php
$cnic  = $_POST['cnic'];
$patient_name  = $_POST['patient_name'];
$age  = $_POST['age'];
$gender  = $_POST['gender'];
$patient_phone  = $_POST['patient_phone'];
$doc_id  = $_POST['doc_id'];
$date  = $_POST['date'];
$speciman  = $_POST['speciman'];
$tests  = $_POST['testArr'];
$amount  = $_POST['amount'];
$discount  = $_POST['discount'];
$net_bill  = $_POST['net_bill'];

//echo $cnic." ".$patient_name." ".$age." ".$gender." ".$patient_phone." ".$doc_id." ".$date. " " . $amount." ".$discount." ".$speciman." " . $net_bill;
//print_r($tests);
//
//exit();


include('db.php');
$patient = $pdo->prepare('INSERT INTO `patients`( `name`, `cnic`, `gender`, `age`, `mobile`) VALUES (?,?, ?, ?, ?)');
$patient->execute([ $patient_name , $cnic , $gender , $age , $patient_phone]);
$patient_id = $pdo->lastInsertId();


$patient_test = $pdo->prepare('INSERT INTO `patient_test`( `patient_ID`, `referred_by`, `Date`, `Time`, `specimen`, `amount`, `discount`, `net_bill`) VALUES (?, ?, ?, ?, ? ,? ,? ,?)');


date_default_timezone_set('Asia/Karachi');
$ctime = date("h:i:s A");
$patient_test->execute([  $patient_id , $doc_id, $date, $ctime , $speciman, $amount, $discount, $net_bill]);
$patient_test_id = $pdo->lastInsertId();
//$patient_test_id = 8;



foreach ($tests as $testid){

    $test = $pdo->prepare('INSERT INTO `patient_test_record`(`patient_test_ID`, `test_ID`, `result`, `rel_test_ID`) VALUES (?, ?,?,?)');

    $related_test_query = $pdo->prepare("SELECT rel_test_ID FROM `related_tests` WHERE test_ID =".$testid);
    $related_test_query->execute();

    $related_tests = $related_test_query->fetchAll(PDO::FETCH_ASSOC);
    if($related_tests){
        foreach($related_tests as $rel){
            $test->execute([ $patient_test_id, $testid, 0 , $rel['rel_test_ID'] ]);
        }
    }
    else{
        $test->execute([ $patient_test_id, $testid,0,0 ]);
    }



}

unset($patient);
unset($patient_test);
unset($test);
unset($pdo);


if(isset($_POST['print'])){
    echo $patient_test_id;
}
else{
    echo "Test Record Created.";
}





?>