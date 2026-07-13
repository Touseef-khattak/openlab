<!DOCTYPE html>
<html lang="">
    <?php
    session_start();
    if(!isset($_SESSION['login_username'])){
        header('Location: login.php');
    };

    ?>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="../fontawesome/css/all.css">
        <link href="https://fonts.googleapis.com/css?family=Nunito+Sans:200,300,400,400i,600,600i,700,700i,800,800i&display=swap" rel="stylesheet">

        <link rel="stylesheet" href="../css/stylesheet.css">
        <script src="../javascript/jquery.min.js"></script>
        <script src="../javascript/script.js"></script>
        <title>Open Lab</title>

        <script>
            $(document).ready(function() {



                var testArr = [];
                var amount = 0;

                //Setting up date value to current date
                var now = new Date();
                var day = ("0" + now.getDate()).slice(-2);
                var month = ("0" + (now.getMonth() + 1)).slice(-2);
                var today = now.getFullYear()+"-"+(month)+"-"+(day) ;
                $('#datePicker').val(today);



                // Add test
                $(".add_test").click(function(){

                    // first <td> with element class id
                    var testid = $(this).children("td:first").attr("id");


                    if(jQuery.inArray(testid, testArr) !== -1){
                        alert("Already added");
                    }
                    else{
                        testArr.push(testid);
                        var price = parseInt($(this).children("td:last").text(), 10);
                        var min = $(this).children("td:first").data("id10");
                        var max = $(this).children("td:first").data("id11");
                        amount = amount + price;
                        $("#total_tests").val(testArr.length);
                        $("#amount").val(amount);
                        $("#net_bill").val(amount - (amount * $("#discount").val())/100);
                        //                        alert(testArr);

                        var test = $(this).clone().append("<td>"+min+" - "+max+" g/ml</td><td data-id22="+price+" class='remove'><i class='fas fa-minus-circle'></i></td>");
                        test.appendTo("#patient_tests").children("tr:last");
                    }
                });

                // Remove test
                $('.container').on('click','.remove',function(){
                    var testid = $(this).parent().children("td:first").attr("id");
                    var price = parseInt($(this).data("id22"), 10);
                    amount = amount - price;

                    testArr.splice( $.inArray(testid, testArr), 1 );
                    $("#total_tests").val(testArr.length);
                    $("#amount").val(amount);
                    $("#net_bill").val(amount - (amount * $("#discount").val())/100);
                    //                        alert(testArr);
                    $(this).parent().remove();

                }); 

                function clear_all(){
                    $("#cnic").val("");
                    $("#patient_name").val("");
                    $("#age").val("");
                    $("#gender").val("Male");
                    $("#patient_phone").val("");
                    $("#doc").val("");
                    $("#speciman").val("");
                    $("#discount").val("");
                    $("#net_bill").val("");
                    testArr = [];
                    amount = null;
                    $("#total_tests").val(testArr.length);
                    $("#amount").val("");
                    $("#patient_tests").html(" ");
                }

                // Clear All tests
                $('#clear_test').on('click',function(){
                    while (testArr.length) { testArr.pop(); }
                    $("#total_tests").val(testArr.length);
                    $("#amount").val("");
                    $("#patient_tests").html(" ");
                    clear_all();
                });



                $('#discount').keyup(function () {

                    $("#net_bill").val(amount - (amount * $("#discount").val())/100);

                });



                $('#save_invoice').click(function() {


                    let cnic = $("#cnic").val();
                    let patient_name = $("#patient_name").val();
                    let age = $("#age").val();
                    let gender = $("#gender").val();
                    let patient_phone = $("#patient_phone").val();
                    let doctor_id = $("#doc").val();
                    let date = $("#datePicker").val();
                    let speciman = $("#speciman").val();
                    let discount = $("#discount").val();
                    let net_bill = $("#net_bill").val();


                    if(cnic && cnic.length == 15) {

                        if(patient_name) {

                            if(age) {


                                if(gender) {


                                    if(patient_phone && patient_phone.length == 11) {


                                        if(doctor_id) {


                                            if(date) {


                                                if(speciman) {


                                                    if(testArr.length){

                                                        $.ajax({
                                                            url: "../php/function.php",
                                                            data: {cnic:cnic,patient_name:patient_name,age:age,gender:gender,patient_phone:patient_phone,doc_id:doctor_id,date:date,speciman:speciman,testArr:testArr,amount:amount,discount:discount,net_bill:net_bill},
                                                            type: "POST",
                                                            dataType:"text",  
                                                            success: function (data) 
                                                            {
//                                                                alert(data);

                                                                clear_all();

                                                            }
                                                        });

                                                    }
                                                    else{
                                                        alert("please select a test.")
                                                    }

                                                }
                                                else{
                                                    alert("please provide speciman");
                                                }

                                            }
                                            else{
                                                alert("please provide date");
                                            }

                                        }
                                        else{
                                            alert("please select Doctor");
                                        }

                                    }
                                    else{
                                        alert("please provide patient phone");
                                    }

                                }
                                else{
                                    alert("please provide gender");
                                }

                            }
                            else{
                                alert("please provide age");
                            }

                        }
                        else{
                            alert("please provide patient name");
                        }

                    }
                    else{
                        alert("please provide CNIC");
                    }





                });




                $('.print_invoice').click(function() {

                    let cnic = $("#cnic").val();
                    let patient_name = $("#patient_name").val();
                    let age = $("#age").val();
                    let gender = $("#gender").val();
                    let patient_phone = $("#patient_phone").val();
                    let doctor_id = $("#doc").val();
                    let date = $("#datePicker").val();
                    let speciman = $("#speciman").val();
                    let discount = $("#discount").val();
                    let net_bill = $("#net_bill").val();


                    if(cnic && cnic.length == 15) {

                        if(patient_name) {

                            if(age) {


                                if(gender) {


                                    if(patient_phone && patient_phone.length == 11) {


                                        if(doctor_id) {


                                            if(date) {


                                                if(speciman) {


                                                    if(testArr.length){

                                                        $.ajax({
                                                            url: "../php/function.php",
                                                            data: {cnic:cnic,patient_name:patient_name,age:age,gender:gender,patient_phone:patient_phone,doc_id:doctor_id,date:date,speciman:speciman,testArr:testArr,amount:amount,discount:discount,net_bill:net_bill,print:'yes'},
                                                            type: "POST",
                                                            dataType:"text",  
                                                            success: function (data) 
                                                            {
                                                                //                                                                alert(data);

                                                                if(isNaN(data)){
                                                                    alert(data);
                                                                }
                                                                else{
                                                                    $.post( "invoice.php", {id:data },  function 
                                                                           (returnedHtml) 
                                                                           {

                                                                        var mywindow = window.open('', 'PRINT', 'width=860,height=920 left=240');
                                                                        mywindow.document.write(returnedHtml);
                                                                        mywindow.document.close(); // necessary for IE >= 10
                                                                        mywindow.focus(); // necessary for IE >= 10*/
                                                                        mywindow.print();
                                                                        //        mywindow.close();
                                                                        mywindow.document.title = "Invoice";
                                                                        printWindow.onafterprint = function() {
                                                                            printWindow.close();

                                                                        };
                                                                    });
                                                                }
                                                               clear_all();

                                                            }
                                                        });

                                                    }
                                                    else{
                                                        alert("please select a test.")
                                                    }

                                                }
                                                else{
                                                    alert("please provide speciman");
                                                }

                                            }
                                            else{
                                                alert("please provide date");
                                            }

                                        }
                                        else{
                                            alert("please select Doctor");
                                        }

                                    }
                                    else{
                                        alert("please provide 11 character patient phone");
                                    }

                                }
                                else{
                                    alert("please provide gender");
                                }

                            }
                            else{
                                alert("please provide age");
                            }

                        }
                        else{
                            alert("please provide patient name");
                        }

                    }
                    else{
                        alert("please provide 13 character CNIC");
                    }


                    //                                        $.ajax({
                    //                                            url: "./pages/invoice.php.php",
                    //                                            data: {id:id},
                    //                                            type: "POST",
                    //                                            dataType:"text",  
                    //                                            success: function (data) 
                    //                                            {
                    //                                                var printWindow = window.open(data,'PrintWindow', 'width=860,height=920 left=240');
                    //                                                printWindow.print();
                    //                    
                    //                                                //Close window once print is finished
                    //                                                printWindow.onafterprint = function() {
                    //                                                    printWindow.close()
                    //                                                };
                    //                                            }
                    //                                        });




                });


                $("#search_tests").on("keyup", function() {
                    var value = $(this).val().toLowerCase();
                    //			alert(value);
                    $(".test_result tr").filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                    });
                });




                function isEmpty (v) {
                    if (typeof v === "undefined") {
                        return true;
                    }
                    if (v === null) {
                        return true;
                    }
                    if (typeof v === "object" && Object.keys(v).length === 0) {
                        return true;
                    }

                    if (Array.isArray(v) && v.length === 0) {
                        return true;
                    }

                    if (typeof v === "string" && v.trim().length === 0) {
                        return true;
                    }

                    return false;
                }


            });

        </script>

    </head>

    <body>


        <?php

        include('header.php');
        include('../php/db.php');

        $stmt1 = $pdo->prepare('SELECT * FROM `tests`');
        $stmt1->execute();

        $tests = $stmt1->fetchAll(PDO::FETCH_ASSOC);

        // Close statement
        unset($stmt1);

        ?>



        <!--   Patient Info   -->
        <section class="container">
            <div class="left_container">

                <div class="patient_info">
                    <div class="info patient_cnic">
                        <p>Patient CNIC</p>
                        <input type="text" id="cnic" placeholder="XXXXX-XXXXXXX-X" maxlength="15">
                    </div>
                    <div class="info patient_name">
                        <p>Patient Name</p>
                        <input type="text" id="patient_name">
                    </div>
                    <div class="info patient_age">
                        <p>Age</p>
                        <input type="number" id="age">
                    </div>
                    <div class="info patient_gender">
                        <p>Gender</p>
                        <select name="gender" id="gender">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="info patient_mobile">
                        <p>Mobile</p>
                        <input type="text" id="patient_phone" maxlength="11" size="11" style="width:150px;">
                    </div>
                    <div class="info patient_date">
                        <p>Date</p>
                        <input type="date" id="datePicker">
                    </div>

                    <div class="info patient_referred">
                        <p>Referred By</p>
                        <select name="doctors" id="doc">
                            <option value="">Select Doctor</option>
                            <?php
                            $stmt2 = $pdo->prepare('SELECT doc_ID,name FROM `doctors`');
                            $stmt2->execute();

                            $doctors = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($doctors as $doctor): ?>
                            <option value="<?=$doctor['doc_ID'];?>">Dr.<?=$doctor['name'];?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="info">
                        <p>Spaciman</p>
                        <input type="text" id="speciman">
                    </div>
                </div>

                <!--  Patient Test Table-->
                <div class="patient_test" id="ptest">

                    <table class="test_table">
                        <thead>
                            <tr>
                                <th>Test_ID</th>
                                <th>Test Name</th>
                                <th>Price</th>
                                <th>Ref Value</th>
                                <th><i class="fas fa-trash-alt"></i></th>
                            </tr>
                        </thead>
                        <tbody id="patient_tests">

                        </tbody>
                    </table>
                </div>

            </div>

            <!--  Save and Print Invoice  -->
            <div class="option_buttons">
                <div class="print_invoice">
                    <img src="../images/print.png" alt="Print" height="30px" >
                    <p>Print</p>
                </div>
                <div class="save_invoice" id="save_invoice">
                    <img src="../images/save.png" alt="" height="30px">
                    <p>Save </p>
                </div>
                <div class="clear_test" id="clear_test">
                    <i class="fas fa-eraser"></i>
                    <p>Clear All</p>
                </div>
            </div>


            <!--   Test Select Box   -->
            <div class="right_container">
                <div>
                    <h3>Select Test Category</h3>
                </div>
                <div class="test_tabs">
                    <div id="test" class="active">Test</div>
                </div>
                <!-- ====== Category End ====== -->

                <!-- ====== Test ====== -->
                <div class="test_search test_search_tab">
                    <input type="text" placeholder="Search Test" id="search_tests">
                </div>
                <div class="test_list test_tab">
                    <table class="list_table">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Test</th>
                                <th>Rs</th>
                            </tr>
                        </thead>
                        <tbody class="test_result">
                            <?php foreach ($tests as $test): ?>
                            <tr  class="add_test">
                                <td data-id10="<?=$test['reference_min'];?>" data-id11="<?=$test['reference_max'];?>" id="<?=$test['test_ID'];?>"><?=$test['test_ID'];?></td>
                                <td id="<?=$test['test_ID'];?>"><?=$test['name'];?></td>
                                <td><?=$test['price'];?></td>
                            </tr>
                            <?php endforeach; // Close connection
                            unset($pdo);
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- ====== Test End ====== -->

                <div class="test_invoice">
                    <div>
                        <p>Total Tests</p><input type="number" placeholder="0" id="total_tests" readonly>
                    </div>
                    <div>
                        <p>Total Amounts</p><input type="number" placeholder="0.0" id="amount" readonly>
                    </div>
                    <div>
                        <p>Discount%</p><input type="number" placeholder="0" min="0" value="0" id="discount">
                    </div>
                    <div>
                        <p>Net Amount</p><input type="number" placeholder="0.0" id="net_bill" readonly>
                    </div>
                    <!--
<div>
<p>Total Paid</p><input type="number" placeholder="0.0">
</div>
-->


                </div>

            </div>
            <!--   Test Select Box End   -->

        </section>
        <!--   Patient Info End  -->

    </body>

</html>
