<?php
if(!isset($_SESSION)) 
{ 
    session_start(); 
}
if(isset($_POST['id']) && $_POST['id'] == ""){
    exit();
}
elseif(isset($_GET['id'])){
    $id = $_GET['id'];
}
else{
    $id = $_POST['id'];
}
//exit();

include('../php/db.php');

date_default_timezone_set('Asia/Karachi');
$current_time = date("h:i:s A");
$current_date = date("Y-m-d");

$patient_query = $pdo->prepare('SELECT patients.patient_ID,patients.name,patients.cnic,patients.age,patients.gender,`Date`, `Time`, `specimen`, `amount`, `discount`, `net_bill`,doctors.name AS doc_name FROM `patient_test` JOIN patients ON patients.patient_ID = patient_test.patient_ID JOIN doctors ON doctors.doc_ID = patient_test.referred_by WHERE patient_test.patient_test_ID ='.$id);
$patient_query->execute();
$patient_record = $patient_query->fetchAll(PDO::FETCH_ASSOC);

$testID_query = $pdo->prepare('SELECT `test_ID`,`rel_test_ID` FROM `patient_test_record` JOIN patient_test ON patient_test_record.patient_test_ID = patient_test.patient_test_ID WHERE patient_test_record.patient_test_ID ='.$id);
$testID_query->execute();

$testID_record = $testID_query->fetchAll(PDO::FETCH_ASSOC);


?>



<!DOCTYPE html>
<html lang="">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://fonts.googleapis.com/css?family=Nunito+Sans:200,300,400,400i,600,600i,700,700i,800,800i&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../css/stylesheet.css">
        <script src="../javascript/jquery.min.js"></script>
        <script src="../javascript/script.js"></script>
        <title>Report</title>

    </head>

    <body>
        <section class="template">
            <div id="lab_header">
                <div class="lab_info">

                    <h1>AL Hafiz Madical Laboratory</h1>
                    <div>
                        <p class="address"> Shah Madical Centre Ground Floor, Shop # 13 Dabgari Gardens Peshawar </p>
                    </div>
                    <div>
                        <p class="phone">Phone: 03339157542</p>
                        <p class="fax">Fax: ?</p>
                    </div>
                </div>
                <div class="lab_logo">
                    <img src="../images/lab_logo.png" width="70">
                </div>
            </div>
            <div class="p_info">

                <div class="pers_info">
                    <div><label>TEST ID</label><p>:</p><h4><?=$id;?></h4></div>
                    <div><label>Name</label><p>:</p><h4><?=$patient_record[0]['name'];?></h4></div>
                    <div><label>Age</label><p>:</p><h4><?=$patient_record[0]['age'];?></h4></div>
                    <div><label>Gender</label><p>:</p><h4><?=$patient_record[0]['gender'];?></h4></div>
                </div>
                <div class="r_info">
                    <div><label>Referred By</label><p>:</p><h4>Dr. <?php $patient_record[0]['doc_name'];?></h4></div>
                    <div><label>Specimen</label><p>:</p><h4><?=$patient_record[0]['specimen'];?></h4></div>
                    <div><label>Received</label><p>:</p><h4><?=$patient_record[0]['Date'];?></h4><h4><?=$patient_record[0]['Time'];?></h4></div>
                    <div><label>Print Date</label><p>:</p><h4><?=$current_date;?></h4><h4><?=$current_time;?></h4></div>
                </div>

            </div>
            <div class="r_header">
                <h3>Test Report</h3> 
            </div>
            <div class="r_table">
                <table>
                    <thead>
                        <tr>
                            <th>TEST(s)</th>
                            <th>NORMAL</th>
                            <th>RESULT</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php

                        foreach($testID_record as $testID){

                            if($testID['rel_test_ID'] != 0){
                                //                                
                                $test = $pdo->prepare("SELECT tests.name AS parent,related_tests.related_test AS name,related_tests.min, related_tests.max, result FROM `patient_test_record` JOIN related_tests ON patient_test_record.rel_test_ID = related_tests.rel_test_ID JOIN tests ON tests.test_ID = related_tests.test_ID WHERE patient_test_record.test_ID = '".$testID['test_ID']."' AND patient_test_record.rel_test_ID = '".$testID['rel_test_ID']."' AND patient_test_record.patient_test_ID =".$id);
                                $test->execute();
                                $test_record = $test->fetchAll(PDO::FETCH_ASSOC);

                                foreach($test_record as $related){


                        ?>

                        <tr>
                            <td><?=$related['name'];?></td>
                            <td><?=$related['min'];?>-<?=$related['max'];?></td>
                            <td><?=$related['result'];?></td>
                        </tr>
                        <?php

                                }
                            }
                            else{

                                $test = $pdo->prepare("SELECT  tests.name AS name,reference_min AS min, reference_max AS max,result FROM patient_test_record JOIN tests ON tests.test_ID = patient_test_record.test_ID WHERE patient_test_record.test_ID = '".$testID['test_ID']."' AND patient_test_record.patient_test_ID =".$id);
                                $test->execute();
                                $test_record = $test->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <tr>
                            <td><?=$test_record[0]['name'];?></td>
                            <td><?=$test_record[0]['min'];?>-<?=$test_record[0]['max'];?></td>
                            <td><?=$test_record[0]['result'];?></td>
                        </tr>

                        <?php    
                            }
                        }
                        ?>
                    </tbody>
                </table>

            </div>

            <!--            -------- Footer ----------->
            <div class="r_footer">
                <div class="r_docs_info">
                    <div>
                        <h4>Dr. Walayat Khan Afridi</h4>
                        <p>MBBS's, DLD</p>
                        <p>H/O Pathology</p>
                    </div>
                    <div>
                        <h4>Hafiz M. Farooq</h4>
                        <p>D.M.L.T (C Path) Med. Faculty [KTH]</p>
                        <p>Pathology Technician</p>
                        <p>Khyber Teaching Hospital Peshawar</p>
                    </div>
                    <div>
                        <h4>Mr. M. Numan</h4>
                        <p>BSc. MLT[KMU] Peshawar</p>
                    </div>
                    <div>
                        <h4>Mr. Kamal Gul</h4>
                        <p>D.M.L.T RIMS. Med. Faculty</p>
                        <p>Pathology Technician KPK</p>
                    </div>
                    <div>
                        <h4>Mr. M. Zubair</h4>
                        <p>D.M.L.T RIMS. Med Faculty KPK</p>
                        <p>Pathology Technician</p>
                    </div>
                    <div>
                        <h4>Mr. Siraj Ud Din</h4>
                        <p>FS.C MLT Peshawar Board</p>
                        <p>BSC. BIOG, University Peshawar</p>
                    </div>

                </div>
                <div class="r_desc">
                    <p>All Qeries/ Discrepancies If any Be To Our Lab. Within 24 Hrs Of Reporting For Re-ualuarion/Confirmation</p>
                </div>
                <div class="r_cont">
                    <p>Quality Lab Services For All Diagnostic Tests</p> <p id="court_disc">Not Valid For Court</p> <div class="print_pers"><h6><i>Printed By</i></h6>

                    <h5><?php 
                        echo $_SESSION['login_username'];
                        ?>
                    </h5>

                    </div>
                </div>
                <div class="company_text">
                    <h5>Software Developed and Maintain By TK Developers</h5>
                    <p>website: <b>https://tkdeveloper.ml</b></p>
                </div>
            </div>



        </section>
    </body>
</html>
