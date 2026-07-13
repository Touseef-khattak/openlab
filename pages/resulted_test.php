<?php


if(isset($_POST['result'])){

    if(isset($_POST['id']) && $_POST['id'] == ""){
        exit();
    }
    else{
        $id = $_POST['id'];
    }
    //exit();

    include('../php/db.php');

    $patient_query = $pdo->prepare('SELECT patients.patient_ID,patients.name,patients.cnic,patients.age,patients.gender,`Date`, `Time`, `specimen`, `amount`, `discount`, `net_bill`,doctors.name AS doc_name FROM `patient_test` JOIN patients ON patients.patient_ID = patient_test.patient_ID JOIN doctors ON doctors.doc_ID = patient_test.referred_by WHERE patient_test.patient_test_ID ='.$id);
    $patient_query->execute();
    $patient_record = $patient_query->fetchAll(PDO::FETCH_ASSOC);

    if(!$patient_record) {
        echo "<h2 style='text-align:center;'>No data found</h2>";
        exit();
    }


    $testID_query = $pdo->prepare('SELECT `test_ID`,`rel_test_ID` FROM `patient_test_record` JOIN patient_test ON patient_test_record.patient_test_ID = patient_test.patient_test_ID WHERE patient_test_record.patient_test_ID ='.$id);
    $testID_query->execute();

    $testID_record = $testID_query->fetchAll(PDO::FETCH_ASSOC);


?>


<div class="patient_test_info">
    <div><label>Test ID </label><p id="p_test_id"><?=$id;?></p></div>
    <div><label>Patient Name </label><p id="p_test_name"><?=$patient_record[0]['name'];?></p></div>
    <div><label>Patient Age </label><p id="p_test_age"><?=$patient_record[0]['age'];?></p></div>
    <div><label>Gender </label><p id="p_test_gender"><?=$patient_record[0]['gender'];?></p></div>
    <div><label>Recieved Date </label><p id="p_test_date"><?=$patient_record[0]['Date'];?></p></div>
    <div><label>Referred By </label><p id="p_test_doctor">DR. <?=$patient_record[0]['doc_name'];?></p></div>
</div>

<div class="t_upload_table">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Test</th>
                <th></th>
                <th>Ref Value</th>
                <th>Result</th>
<!--                <th>Done</th>-->
            </tr>
        </thead>
        <tbody id="resulted_test">
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
                <td><?=$testID['rel_test_ID'];?></td>
                <td><?=$related['name'];?></td>
                <td></td>
                <td><?=$related['min'];?>-<?=$related['max'];?> mg</td>
                <td><input type="text" value="<?=$related['result'];?>" class="update_rel_result" data-id21="<?=$id?>" data-id20="<?=$testID['test_ID'];?>" id="<?=$testID['rel_test_ID'];?>"></td>
<!--                <td><button><i class="fas fa-check"></i></button></td>-->

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
                <td><?=$testID['test_ID'];?></td>
                <td><?=$test_record[0]['name'];?></td>
                <td></td>
                <td><?=$test_record[0]['min'];?>-<?=$test_record[0]['max'];?> mg</td>
                <td><input type="text" value="<?=$test_record[0]['result'];?>" data-id21="<?=$id;?>" id="<?=$testID['test_ID'];?>" class="update_result"></td>
<!--                <td><button><i class="fas fa-check"></i></button></td>-->

            </tr>
            <?php    
        }
    }
            ?>
        </tbody>

    </table>

    <div class="d_border_set"></div>
</div>
<script>

    $(document).ready(function(){




        $(".save_report").on("click",function(){


            $('.update_result').each(function() {
                var updated_value = $(this).val();
                var patient_test_id = $(this).data("id21");
                var test_id = $(this).attr("id");
                //                        alert(patient_test_id+" "+test_id+" "+updated_value);

                $.ajax({
                    url: "add_result.php",
                    data: {updated_value:updated_value,patient_test_id:patient_test_id,test_id:test_id},
                    type: "POST",
                    dataType:"text",  
                    success: function (data) 
                    {
                        //                        alert(data);
                    }
                });

            });

            $('.update_rel_result').each(function() {
                var updated_value = $(this).val();
                var patient_test_id = $(this).data("id21");
                var test_id = $(this).data("id20");
                var rel_test_id = $(this).attr("id");
                //                        alert(patient_test_id+" "+test_id+" "+rel_test_id+" "+updated_value);

                $.ajax({
                    url: "add_result.php",
                    data: {updated_value:updated_value,patient_test_id:patient_test_id,test_id:test_id,rel_test_id:rel_test_id},
                    type: "POST",
                    dataType:"text",  
                    success: function (data) 
                    {
                        //                        alert(data);
                    }
                });
            });

            location.reload();
        }); 

        $(".print_result").on("click",function(){

            let id = $("#search_id").val();

            $('.update_result').each(function() {
                var updated_value = $(this).val();
                var patient_test_id = $(this).data("id21");
                var test_id = $(this).attr("id");
                //                        alert(patient_test_id+" "+test_id+" "+updated_value);

                $.ajax({
                    url: "add_result.php",
                    data: {updated_value:updated_value,patient_test_id:patient_test_id,test_id:test_id},
                    type: "POST",
                    dataType:"text",  
                    success: function (data) 
                    {
                        //                    alert(data);
                    }
                });

            });

            $('.update_rel_result').each(function() {
                var updated_value = $(this).val();
                var patient_test_id = $(this).data("id21");
                var test_id = $(this).data("id20");
                var rel_test_id = $(this).attr("id");
                //                        alert(patient_test_id+" "+test_id+" "+rel_test_id+" "+updated_value);

                $.ajax({
                    url: "add_result.php",
                    data: {updated_value:updated_value,patient_test_id:patient_test_id,test_id:test_id,rel_test_id:rel_test_id},
                    type: "POST",
                    dataType:"text",  
                    success: function (data) 
                    {
                        //                        alert(data);
                    }
                });
            });

            if(id){
                $.post( "report_template.php", {id:id },  function 
                       (returnedHtml) 
                       {

                    var mywindow = window.open('', 'PRINT', 'width=860,height=920 left=240');
                    mywindow.document.write(returnedHtml);
                    mywindow.document.close(); // necessary for IE >= 10
                    mywindow.focus(); // necessary for IE >= 10*/
                    mywindow.print();
                    //        mywindow.close();
                    mywindow.document.title = "Report";
                    printWindow.onafterprint = function() {
                        printWindow.close();

                    };
                });
            }
        }); 



    });

</script>

<?php
}
elseif(isset($_POST['report'])){

    if(isset($_POST['id']) && $_POST['id'] == ""){
        exit();
    }
    else{
        $id = $_POST['id'];
    }
    //exit();

    include('../php/db.php');

    $patient_query = $pdo->prepare('SELECT patients.patient_ID,patients.name,patients.cnic,patients.age,patients.gender,`Date`, `Time`, `specimen`, `amount`, `discount`, `net_bill`,doctors.name AS doc_name FROM `patient_test` JOIN patients ON patients.patient_ID = patient_test.patient_ID JOIN doctors ON doctors.doc_ID = patient_test.referred_by WHERE patient_test.patient_test_ID ='.$id);
    $patient_query->execute();
    $patient_record = $patient_query->fetchAll(PDO::FETCH_ASSOC);

    if(!$patient_record) {
        echo "<h2 style='text-align:center;'>No data found</h2>";
        exit();
    }


    $testID_query = $pdo->prepare('SELECT `test_ID`,`rel_test_ID` FROM `patient_test_record` JOIN patient_test ON patient_test_record.patient_test_ID = patient_test.patient_test_ID WHERE patient_test_record.patient_test_ID ='.$id);
    $testID_query->execute();

    $testID_record = $testID_query->fetchAll(PDO::FETCH_ASSOC);


?>


<div class="patient_test_info">
    <div><label>Test ID </label><p id="p_test_id"><?=$id;?></p></div>
    <div><label>Patient Name </label><p id="p_test_name"><?=$patient_record[0]['name'];?></p></div>
    <div><label>Patient Age </label><p id="p_test_age"><?=$patient_record[0]['age'];?></p></div>
    <div><label>Gender </label><p id="p_test_gender"><?=$patient_record[0]['gender'];?></p></div>
    <div><label>Recieved Date </label><p id="p_test_date"><?=$patient_record[0]['Date'];?></p></div>
    <div><label>Referred By </label><p id="p_test_doctor">DR. <?=$patient_record[0]['doc_name'];?></p></div>
</div>

<div class="t_upload_table">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Test</th>
                <th></th>
                <th>Ref Value</th>
                <th>Result</th>
<!--                <th>Done</th>-->
            </tr>
        </thead>
        <tbody id="resulted_test">
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
                <td><?=$testID['rel_test_ID'];?></td>
                <td><?=$related['name'];?></td>
                <td></td>
                <td><?=$related['min'];?>-<?=$related['max'];?> mg</td>
                <td type="text" class="update_rel_result"><?=$related['result'];?></td>
                <td>
<!--                    <button><i class="fas fa-check"></i></button>-->
                </td>

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
                <td><?=$testID['test_ID'];?></td>
                <td><?=$test_record[0]['name'];?></td>
                <td></td>
                <td><?=$test_record[0]['min'];?>-<?=$test_record[0]['max'];?> mg</td>
                <td type="text" class="update_result"><?=$test_record[0]['result'];?></td>
                <td>                   
<!--                    <button><i class="fas fa-check"></i></button>                   -->
                </td>

            </tr>
            <?php    
        }
    }
            ?>
        </tbody>

    </table>

    <div class="d_border_set"></div>
</div>
<script>

    $(document).ready(function(){

    });

</script>
<?php
}





?>

