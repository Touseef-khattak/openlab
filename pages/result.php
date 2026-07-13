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


                $("#search_id").on("keyup",function(){

                    let id = $("#search_id").val();

                    $.ajax({
                        url: "resulted_test.php",
                        data: {id:id,result:'yes'},
                        type: "POST",
                        dataType:"text",  
                        success: function (data) 
                        {
                            $("#result").html(" ");
                            $("#result").html(data);
                        }
                    }); 


                });

                $(".print_result").on("click",function(){

                    let id = $("#search_id").val();

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

    </head>

    <body>
        <?php include 'header.php';?>

        <!------------ Test Table ----------------->
        <section class="User_container result_container">
            <div class="header_container">
                <div class="result_heading">
                    <h1>RESULT</h1>
                </div>
                <div class="create_doctor create_user">
                    <div class="search_doctor">
                        <input type="text" placeholder="Search Test" id="search_id">
                        <div class="search_patient_test_btn"><i class="fas fa-search"></i></div>
                    </div>
                    <div class="print_result" aria-label="Print"><img src="../images/print.png" alt="Print Report"></div>
                    <div class="save_report"><img src="../images/save.png" alt="Save Report" aria-label="Print"></div>
                </div>
            </div>

            <div id="result">

            </div>

        </section>
        <!--   Test Table End  -->

    </body>

</html>
