<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="fontawesome/css/all.css">

        <link href="https://fonts.googleapis.com/css?family=Nunito+Sans:200,300,400,400i,600,600i,700,700i,800,800i&display=swap" rel="stylesheet">

        <title>Invoice</title>
        <script src="javascript/jquery.min.js"></script>
        <script src="jquery/printthis/printThis.js"></script>
        <link rel="stylesheet" href="css/stylesheet.css">
        <script>
            $(document).ready(function() {

                $('.btn').click(function() {
                    $('.invoice_temp').printThis();

                });
            });

        </script>
    </head>

    <body>
        <div class="invoice_temp">
            <div class="lab_info">
                <h1>AL Hafiz Laboratory</h1>
                <div>
                    <p class="address"> XYZ Madical Centre Dabgari Gardens Peshawar </p>
                </div>
                <div>
                    <p class="phone">Phone: 578901</p>
                    <p class="fax">Fax: 1213123</p>
                </div>
            </div>




            <?php


            $id = $_GET['id'];
            include('php/db.php');

            $patient_query = $pdo->prepare('SELECT patients.patient_ID,patients.name,patients.cnic,patients.age,patients.gender,`Date`, `Time`, `specimen`, `amount`, `discount`, `net_bill`,doctors.name AS doc_name FROM `patient_test` JOIN patients ON patients.patient_ID = patient_test.patient_ID JOIN doctors ON doctors.doc_ID = patient_test.referred_by WHERE patient_test.patient_test_ID ='.$id);
            $patient_query->execute();

            $patient_record = $patient_query->fetchAll(PDO::FETCH_ASSOC);

            $testID_query = $pdo->prepare('SELECT `test_ID`,`rel_test_ID` FROM `patient_test_record` JOIN patient_test ON patient_test_record.patient_test_ID = patient_test.patient_test_ID WHERE patient_test_record.patient_test_ID ='.$id);
            $testID_query->execute();

            $testID_record = $testID_query->fetchAll(PDO::FETCH_ASSOC);




//            print_r($patient_record);
//            echo "<br>tesetid-------------<br><pre>";
//            print_r($testID_record);
//            echo "</pre><br><br>";
//            foreach($testID_record as $testID){
//                $test = $pdo->prepare('SELECT  `name`, `price` FROM `tests` WHERE test_ID ='.$testID['test_ID']);
//                $test->execute();
//
//                $test_record = $test->fetchAll(PDO::FETCH_ASSOC);
//                echo $test_record[0]['name'] . " " . $test_record[0]['price'] . "<br>";
//            }
//
            


            ?>






            <div class="invoice_patient_info">
                <div>
                    <div>
                        <p class="patient_id_label">Test ID: </p>
                        <p id="patient_id_text"><?=$id;?></p>
                    </div>
                    <div>
                        <p class="invoice_date_label">Date: </p>
                        <p class="invoice_date_text"><?=$patient_record[0]['Date'];?></p>
                    </div>
                </div>
                <div>
                    <div>
                        <p class="patient_name_label">Name: </p>
                        <p id="patient_name_text"><?=$patient_record[0]['name'];?></p>
                    </div>

                    <div>
                        <p class="patient_age_label">Age: </p>
                        <p id="patient_age_text"><?=$patient_record[0]['age'];?></p>
                    </div>
                </div>
                <div>
                    <div>
                        <p class="invoice_gender_label">Gender: </p>
                        <p class="invoice_gender_text"><?=$patient_record[0]['gender'];?></p>
                    </div>
                    <div>
                        <p class="invoice_specimen_label">Specimen: </p>
                        <p class="invoice_specimen_text"><?=$patient_record[0]['specimen'];?></p>
                    </div>
                </div>
                <div>
                    <div>
                        <p class="invoice_gender_label">Referred By: </p>
                        <p class="invoice_gender_text">Dr. <?=$patient_record[0]['doc_name'];?></p>
                    </div>
                </div>

            </div>
            <div class="test_req">
                <div>
                    <h4>Test Required</h4>
                </div>
                <div class="invoice_test_table">
                    <table>
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
                            <td><?=$related['name'];?>(<?=$related['parent'];?>)</td>
                            <td></td>
                        </tr>
                        <?php
                                }
                            }
                            else{


                                $test = $pdo->prepare('SELECT  `name`, `price` FROM `tests` WHERE test_ID ='.$testID['test_ID']);
                                $test->execute();
                                $test_record = $test->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <tr>
                            <td><?=$test_record[0]['name'];?></td>
                            <td></td>
                        </tr>

                        <?php    }
                        }


                        ?>

                        
                    </table>
                </div>
            </div>
            <div class="invoice_header">
                <div>
                    <div>
                        <p class=""><i>Dated:</i></p>
                    </div>
                    <div>
                        <p><?=$patient_record[0]['Date'];?>&nbsp;</p>
                        <p><?=$patient_record[0]['Time'];?></p>
                    </div>
                </div>
                <div>
                    <div>
                        <p class=""><i>Printed by:</i></p>
                    </div>
                    <div>
                        <p>Gull Khan</p>
                    </div>
                </div>
            </div>
        </div>

    </body>

</html>