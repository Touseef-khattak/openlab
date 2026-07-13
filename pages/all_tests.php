<!DOCTYPE html>
<html lang="">

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

            $(document).ready(function(){

                $(".view").click(function(){

                    var id = $(this).attr('id');
                    var win =  window.open("report_template.php?id="+id, "_blank", "width= 800,height=1000", "toolbar=yes,scrollbars=yes,resizable=yes");
                    //                    setTimeout (function() {win.opener.close();},1000);

                });

                $(".print_receipt").click(function(){
                    var id = $(this).attr('id');
                    $.post( "invoice.php", {id:id },  function 
                           (returnedHtml) 
                           {

                        var mywindow = window.open('', 'PRINT', 'width=860,height=920 left=240');
                        mywindow.document.write(returnedHtml);
                        mywindow.document.close(); // necessary for IE >= 10
                        mywindow.focus(); // necessary for IE >= 10*/
                        mywindow.print();
                        //                        mywindow.close();
                        mywindow.document.title = "Invoice";
                        printWindow.onafterprint = function() {
                            printWindow.close();

                        };
                    });
                })




                $('.all_test').click(function(){
                    //                    alert(this.value);         
                    //Date in full format alert(new Date(this.value));
                    location.href = "all_tests.php";
                });

                $('#day_filter').change(function(){
                    //                    alert(this.value);         
                    //Date in full format alert(new Date(this.value));
                    location.href = "all_tests.php?day="+this.value;
                });


                $('#month_filter').change(function(){
                    //                    alert(new Date(this.value)); 
                    var d = new Date(this.value);
                    var m = d.getMonth() + 1;
                    //Date in full format alert(new Date(this.value));
                    location.href = "all_tests.php?month="+m;
                });


                $("#search_test").on("keyup", function() {
                    var value = $(this).val().toLowerCase();
                    //			alert(value);
                    $(".tests tr").filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                    });
                });


            });


        </script>

    </head>

    <body>
        <?php include 'header.php';include '../php/config.php';

        if(isset($_GET['day'])){
            $day = $_GET['day'];
        }
        if(isset($_GET['month'])){
            $month = $_GET['month'];
        }
        ?>

        <!--   Navigation End   -->

        <!------------- Add Test Section ----------------->

        <section class="test_container">
            <div class="test_head">
                <div class="test_heading all_test_heading">
                    <div><i class="fas fa-filter"></i></div>
                    <h1>Filter</h1>
                </div>
                <div class="test_option">
                    <div class="search_doctor">
                        <input type="text" placeholder="Search" id="search_test">

                    </div>
                    <div class="all_test">All</div>
                    <div class="filter_month">
                        <div>Month</div><input type="month" id="month_filter" value="<?=date('Y-m',strtotime($day));;?>">
                    </div>
                    <div class="filter_month">
                        <div>Date</div><input type="date" id="day_filter" value="<?=$day;?>">
                    </div>
                    <div class="total_test">
                        <p class="total_label">Total:</p>
                        <p class="total_text"><?php $nRows = $conn -> query('SELECT count(*) FROM `patient_test`')->fetchColumn(); 
                            echo $nRows; ?></p>
                    </div>

                </div>
            </div>
            <!----------- Table ------------>

            <div class="all_test_list">
                <table class="all_test_table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient Name</th>
                            <th>CNIC</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>View</th>
                        </tr>
                    </thead>
                    <tbody class="tests">
                        <?php
                        include('../php/db.php');

                        if(isset($_GET['day'])){
                            $day = $_GET['day'];
                            $all_test = $pdo->prepare("SELECT  patients.name As name,patients.cnic AS cnic ,`patient_test_ID`, `Date`, `Time`, `net_bill` FROM `patient_test` JOIN patients ON patients.patient_ID = patient_test.patient_ID WHERE patient_test.Date='".$day."' ORDER BY patient_test_ID DESC");
                        }
                        elseif(isset($_GET['month'])){
                            $month = $_GET['month'];
                            $all_test = $pdo->prepare("SELECT  patients.name As name,patients.cnic AS cnic ,`patient_test_ID`, `Date`, `Time`, `net_bill` FROM `patient_test` JOIN patients ON patients.patient_ID = patient_test.patient_ID WHERE MONTH(patient_test.Date) = '".$month."' ORDER BY patient_test_ID DESC");
                        }
                        else{
                            $all_test = $pdo->prepare("SELECT  patients.name As name,patients.cnic AS cnic ,`patient_test_ID`, `Date`, `Time`, `net_bill` FROM `patient_test` JOIN patients ON patients.patient_ID = patient_test.patient_ID ORDER BY patient_test_ID DESC");
                        }

                        $all_test->execute();   
                        $tests = $all_test->fetchAll(PDO::FETCH_ASSOC);
                        $count = 1;
                        foreach($tests as $test){
                        ?>
                        <tr>
                            <td><?=$count; ?></td>
                            <td><?=$test['name'];?></td>
                            <td><?=$test['cnic'];?></td>
                            <td><?=$test['net_bill'];?></td>
                            <td><?=$test['Date'];?></td>
                            <td><?=$test['Time'];?></td>
                            <td class="all_test_view">
                                <button class="view" id="<?=$test['patient_test_ID'];?>">view</button>
                                <button class="print_receipt" id="<?=$test['patient_test_ID'];?>">Reciept</button>
                            </td>
                        </tr>

                        <?php $count++; } ?>

                    </tbody>
                </table>  
            </div>
        </section>




    </body>

</html>
