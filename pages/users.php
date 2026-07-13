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
            $(document).ready(function() {
                function confirm() {
                    var result = confirm("Are You sure to delete this User ");
                    if (result == true) {
                        return true;
                    } else {
                        return false;
                    }
                }


                $(".edit_user").on('click' , function(){
                    //                    alert($(this).data("id11"));
                    var id = $(this).data("id11");
                    var name = $(this).parent().parent().children("td:nth-child(2)").text();
                    var cnic = $(this).parent().parent().children("td:nth-child(3)").text();
                    var mobile = $(this).parent().parent().children("td:nth-child(4)").text();
                    var role = $(this).data("id22");
                    var pass = $(this).data("id30");


                    $('#edit_user_id').val(id);
                    $('#edit_user_name').val(name);
                    $('#edit_user_cnic').val(cnic);
                    $('#edit_user_mobile').val(mobile);
                    $('#edit_role').val(role);
                    $('#edit_user_pass').val(pass);
                    $('#edit_user_btn').click();

                })

                $("#edit_user_reg").on('click' , function(){

                    var updateUser = $('#edit_user_id').val();
                    var name = $('#edit_user_name').val();
                    var cnic = $('#edit_user_cnic').val();
                    var mobile = $('#edit_user_mobile').val();
                    var role = $('#edit_role').val();
                    var pass = $('#edit_user_pass').val();
                    $("#edit_form")[0].reset();
                    
                    $.ajax({
                        type: "POST",
                        dataType:"text",
                        url: "../php/user_model_ajax.php",
                        data: {
                            updateUser:updateUser,
                            name:name,
                            cnic:cnic,
                            mobile:mobile,
                            role:role,
                            pass:pass,
                        },
                        cache: false,
                        success: function (data) {
                            alert(data);
                            document.location.reload(true);
                        }
                    });

                })


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
        if(isset($_SESSION['add_user_Message'])) {  ?>
        <script type="text/javascript">
            $(document).ready(function() {
                $("#R_modal_name").css({
                    "display": "block"
                });

            });

        </script>
        <?php } ?>

        <?php
        if(isset($_SESSION['edit_user_Message'])) {  ?>

        <?php } ?>

        <!------------ User Table ----------------->
        <section class="User_container">
            <div class="header_container">
                <div class="user_heading">
                    <h1>Users</h1>
                </div>
                <div class="create_user">
                    <div class="add_user_btn register_btn R_modal_trigger" title="Get Registered" data-modal="R_modal_name">
                        Add User
                    </div>
                    <div style="display:none;" class="edit_user_btn E_modal_trigger" data-modal="E_modal_name" id="edit_user_btn">
                        Edit User
                    </div>

                </div>
            </div>
            <div class="user_table">
                <?php
                $user_query = 'SELECT * FROM `users`';
                $sts = $conn -> query($user_query);

                ?>
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Name</th>
                            <th>CNIC</th>
                            <th>Mobile</th>
                            <th>Role</th>
                            <th>Delete</th>
                            <th>Edit</th>
                        </tr>

                    </thead>
                    <tbody>
                        <?php
                        foreach($sts as $data){
                            $getrole = $conn -> prepare('SELECT * FROM `user_role` WHERE role_ID = :r');
                            $getrole -> execute(array(
                                ':r' => $data['role']
                            ));
                            $getr = $getrole -> fetch();  
                        ?>
                        <tr>
                            <td><?php echo $data['user_ID'];  ?></td>
                            <td><?php echo $data['name'];  ?></td>
                            <td><?php echo $data['cnic'];  ?></td>
                            <td><?php echo $data['mobile'];  ?></td>
                            <td><?php echo $getr['name'];  ?></td>

                            <td class="user_delete_btn">
                                <a onclick="return confirm()" href="../php/delete.php?id=d_<?php echo $data['user_ID'];?>"><i class="far fa-trash-alt"></i></a>

                            </td>
                            <td class="user_edit_btn">
                                <div class="edit_user" data-id22="<?=$data['role'];?>" data-id30="<?=$data['password'];?>" data-id11="<?php echo $data['user_ID'] ?>" class="E_modal_trigger" data-modal="E_modal_name"><i class="far fa-edit"></i></div>
                            </td>
                        </tr>
                        <?php
                        }
                        ?>
                    </tbody>

                </table>

                <div class="border_set"></div>
            </div>

        </section>
        <!--   User Table End  -->


        <!------------- Add User Modal --------------->

        <div class="R_modal" id="R_modal_name">
            <div class="R_modal_sandbox"></div>
            <div class="R_modal_box">
                <div class="R_modal_header">
                    <h3>ADD USER</h3>
                    <div class="R_close_modal">&#10006;</div>
                </div>
                <div class="R_modal_body">
                    <form method="post" action="../php/adduser.php">
                        <div class="row width">
                            <label>Name <span>*</span></label><input type="text" name="add_user_name">
                        </div>
                        <div class="row width">
                            <label>CNIC <span>*</span></label><input type="text" name="add_user_cnic" id="add_user_cnic" maxlength="15">
                        </div>
                        <div class="row width">
                            <label>Mobile No <span></span></label><input type="number" maxlength="11" size="11" name="add_user_mobile">
                        </div>
                        <div class="row width">
                            <label>Role <span>*</span></label>
                            <select name="add_user_role" id="add_role">
                                <option value="Counter">Counter</option>
                                <option value="Pathologist">Pathologist</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                        <div class="row width">
                            <label>Password <span>*</span></label><input type="password" name="add_user_pass">
                        </div>
                        <div class="row width"><label></label>

                            <?php 

                            if(isset($_SESSION['add_user_Message'])) { 
                                echo'<p class="add_user_error">'.$_SESSION['add_user_Message'].'</p>';
                                unset($_SESSION['add_user_Message']); 
                            }

                            ?>

                        </div>

                        <div class="signup_btn_sec row width">
                            <button type="submit" class="signup_sbtn" name="add_user_reg">
                                Register
                            </button>

                            <div class="add_user_cancel R_close_modal" name="Cancel">
                                Cancel
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <!------------- User Edit Modal --------------->

        <div class="E_modal" id="E_modal_name">
            <div class="R_modal_sandbox"></div>
            <div class="R_modal_box">
                <div class="R_modal_header">
                    <h3>EDIT USER</h3>
                    <div class="E_close_modal">&#10006;</div>
                </div>
                <div class="R_modal_body">

                    <form method="post" action="" id="edit_form">
                        <div class="row width" style="display:none;">
                            <label>ID <span>*</span></label><input type="text" value="" id="edit_user_id" name="edit_user_id">
                        </div>
                        <div class="row width">
                            <label>Name <span>*</span></label><input type="text" value="" id="edit_user_name" name="edit_user_name">
                        </div>
                        <div class="row width">
                            <label>CNIC <span>*</span></label><input type="text" name="edit_user_cnic" id="edit_user_cnic" value="11201-1234567-8" maxlength="15">
                        </div>
                        <div class="row width">
                            <label>Mobile No. <span>*</span></label><input type="number" maxlength="11" size="11" value="01234567891" id="edit_user_mobile" name="edit_user_mobile">
                        </div>
                        <div class="row width">
                            <label>Role <span>*</span></label><select name="edit_user_role" id="edit_role">
                            <?php
                            include('../php/db.php');
                            $rolesQ = $pdo->prepare('SELECT * FROM `user_role`');
                            $rolesQ->execute();

                            $roles = $rolesQ->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($roles as $role): ?>
                            <option value="<?=$role['role_ID'];?>"><?=$role['name'];?></option>
                            <?php endforeach ?>
                            </select>
                        </div>
                        <div class="row width">
                            <label>New Password <span>*</span></label><input type="password" maxlength="8" id="edit_user_pass" name="edit_user_pass">
                        </div>
                        <div class="signup_btn_sec row width">
                            <button type="submit" class="signup_sbtn" name="edit_user_reg" id="edit_user_reg">
                                Update
                            </button>

                            <div class="edit_user_cancel E_close_modal" name="Cancel">
                                Cancel
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>


    </body>

</html>
