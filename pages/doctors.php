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

                $(".doc_search_bar").on("keyup", function() {
                    var value = $(this).val().toLowerCase();
                    //			alert(value);
                    $(".doc_tbody tr").filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                    });
                });


                $(".edit_doc").on('click',function(){
                    //                    alert($(this).data("id11"));
                    var id = $(this).data("id11");
                    var name = $(this).parent().parent().children("td:nth-child(2)").text();
                    var dept = $(this).parent().parent().children("td:nth-child(3)").text();
                    var hosp = $(this).parent().parent().children("td:nth-child(4)").text();
                    var phone = $(this).parent().parent().children("td:nth-child(5)").text();


                    $('#edit_doctor_id').val(id);
                    $('#edit_doctor_name').val(name)
                    $('#edit_doctor_dept').val(dept)
                    $('#edit_doctor_hospital').val(hosp)
                    $('#edit_doctor_mobile').val(phone)
                    $('#edit_doctor_btn').click();



                });

                $("#edit_doctor_reg").on('click',function(){

                    var docUpdate = $('#edit_doctor_id').val();
                    var name = $('#edit_doctor_name').val();
                    var dept = $('#edit_doctor_dept').val();
                    var hosp = $('#edit_doctor_hospital').val();
                    var phone = $('#edit_doctor_mobile').val();
                    $("#edit_form")[0].reset();

                    $.ajax({
                        type: "POST",
                        dataType:"text",
                        url: "../php/user_model_ajax.php",
                        data: {
                            docUpdate:docUpdate,
                            name:name,
                            dept:dept,
                            hosp:hosp,
                            phone:phone,
                        },
                        cache: false,
                        success: function (data) {
                            alert(data); 
                            document.location.reload(true);
                        }
                    });

                });


            });


        </script>

    </head>

    <body>
        <?php 
        include 'header.php';
        include '../php/config.php';

        if($_SESSION['login_role'] == 2 ){
            header('Location: ../index.php');
        }else if($_SESSION['login_role'] == 3){
            header('Location: result.php');
        }

        ?>
        <?php

        if(isset($_SESSION['add_doctor_Message'])) {  ?>


        <script type="text/javascript">
            $(document).ready(function() {
                $("#AD_modal_name").css({
                    "display": "block"
                });
            });

        </script>
        <?php } ?>

        <!------------ User Table ----------------->
        <section class="doctor_container User_container">
            <div class="header_container">
                <div class="doctor_heading user_heading">
                    <h1>DOCTORS</h1>
                </div>
                <div class="create_doctor create_user">
                    <div class="search_doctor">
                        <input class="doc_search_bar" type="text" placeholder="Search Doctor">
                        <div class="search_doc_btn"><i class="fas fa-search"></i></div>
                    </div>

                    <div class="add_user_btn register_btn AD_modal_trigger" title="Add Doctor" data-modal="AD_modal_name">
                        Add Doctor
                    </div>
                    <div style="display:none;" id="edit_doctor_btn" class="add_user_btn ED_modal_trigger" data-modal="ED_modal_name">
                        Edit Doctor
                    </div>


                </div>
            </div>
            <div class="doctor_table">
                <?php
                $doc_query = 'SELECT * FROM `doctors`';
                $sts = $conn -> query($doc_query);
                $count = 1;
                ?>
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Doctor Name</th>
                            <th>Department</th>
                            <th>Hospital</th>
                            <th>Mobile</th>
                            <th>Delete</th>
                            <th>Edit</th>
                        </tr>
                    </thead>
                    <tbody class="doc_tbody">
                        <?php
                        foreach($sts as $data){

                        ?>
                        <tr>
                            <td><?php echo $count;  ?></td>
                            <td><?php echo $data['name'];  ?></td>
                            <td><?php echo $data['department'];  ?></td>
                            <td><?php echo $data['hospital'];  ?></td>
                            <td><?php echo $data['phone'];  ?></td>
                            <td class="user_delete_btn">
                                <a onclick="return confirm()" href="../php/doctor_delete.php?id=<?php echo $data['doc_ID'];?>"><i class="far fa-trash-alt"></i></a>

                            </td>
                            <td class="user_edit_btn">
                                <div data-id11="<?php echo $data['doc_ID'] ?>" class="edit_doc" id="<?php echo $data['doc_ID'] ?>"><i class="far fa-edit"></i></div>
                            </td>

                        </tr>

                        <?php $count++;
                        }
                        ?>

                    </tbody>

                </table>

                <div class="d_border_set"></div>
            </div>

        </section>
        <!--   User Table End  -->


        <!------------- Add Doctor Modal --------------->

        <div class="AD_modal" id="AD_modal_name">
            <div class="R_modal_sandbox"></div>
            <div class="R_modal_box">
                <div class="R_modal_header">
                    <h3>ADD DOCTOR</h3>
                    <div class="AD_close_modal">&#10006;</div>
                </div>
                <div class="R_modal_body">
                    <form method="post" action="../php/adddoctors.php">
                        <div class="row width">
                            <label>Doctor Name <span>*</span></label><input type="text" name="add_doctor_name">
                        </div>
                        <div class="row width">
                            <label>Department <span>*</span></label><input type="text" name="add_doctor_dep">
                        </div>
                        <div class="row width">
                            <label>Hospital <span></span></label><input type="text" name="add_doctor_hospital" id="add_doctor_hospital">
                        </div>

                        <div class="row width">
                            <label>Mobile No <span>*</span></label><input type="number" name="add_doctor_mobile" maxlength="11">
                        </div>

                        <div class="row width"><label></label>

                            <?php 

                            if(isset($_SESSION['add_doctor_Message'])) { 
                                echo'<p class="add_user_error">'.$_SESSION['add_doctor_Message'].'</p>';
                                unset($_SESSION['add_doctor_Message']); 
                            }

                            ?>

                        </div>

                        <div class="signup_btn_sec row width">
                            <button class="signup_sbtn" name="add_doctor_reg">
                                Add
                            </button>

                            <div class="add_doctor_cancel AD_close_modal" name="Cancel">
                                Cancel
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <!------------- Doctor Edit Modal --------------->

        <div class="ED_modal" id="ED_modal_name">
            <div class="R_modal_sandbox"></div>
            <div class="R_modal_box">
                <div class="R_modal_header">
                    <h3>EDIT DOCTOR</h3>
                    <div class="ED_close_modal">&#10006;</div>
                </div>
                <div class="R_modal_body">
                    <form method="post" id="edit_form">
                        <div class="row width" style="display:none;">
                            <label>Doctor ID <span>*</span></label><input type="text" name="edit_doctor_id" id="edit_doctor_id" >
                        </div>
                        <div class="row width">
                            <label>Doctor Name <span>*</span></label><input type="text" name="edit_doctor_name" id="edit_doctor_name" >
                        </div>
                        <div class="row width">
                            <label>Department <span>*</span></label><input type="text" name="edit_doctor_dep" id="edit_doctor_dept" >
                        </div>
                        <div class="row width">
                            <label>Hospital <span></span></label><input type="text" name="edit_doctor_hopital" id="edit_doctor_hospital" >
                        </div>

                        <div class="row width">
                            <label>Mobile No <span>*</span></label><input type="number" maxlength="11" size="11" name="edit_doctor_mobile" id="edit_doctor_mobile" maxlength="11">
                        </div>

                        <div class="row width"><label></label>
                        </div>
                        <div class="signup_btn_sec row width">
                            <button type="" class="signup_sbtn" name="edit_doctor_reg" id="edit_doctor_reg">
                                UPDATE
                            </button>

                            <div class="edit_doctor_cancel ED_close_modal" name="Cancel">
                                Cancel
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>



    </body>

</html>
