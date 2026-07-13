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
                $('input[type=radio][name="add_value_box"]').change(function() {
                    if($(this).val() == 'multiple'){
                        $('.single_value_box').hide();
                    }else if($(this).val() == 'single'){
                        $('.single_value_box').show();
                    }
                });

                $('.cat_view').click(function(e){
                    $id = $(this).attr('id');

                    $.ajax({
                        type: "POST",
                        dataType: "html",
                        url: "../php/user_model_ajax.php",
                        data: {
                            add_view: $id
                        },
                        cache: false,
                        success: function (data) {
                            $('.view_tb').html(data);                
                        },
                        error: function (xhr, status, error) {
                            console.error(xhr);
                        }
                    });

                });


                $(".cat_searchbar").on("keyup", function() {
                    var value = $(this).val().toLowerCase();
                    //			alert(value);
                    $(".test_result tr").filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                    });
                });


                $(".edit_modal").on('click',function(){
                    //                    alert($(this).data("id10"));
                    var id = $(this).data("id10");
                    var name = $(this).parent().parent().children("td:first").text();
                    var price = $(this).parent().parent().children("td:nth-child(2)").text();


                    $('#edit_test_id').val(id);
                    $('#edit_test_name').val(name)
                    $('#edit_test_price').val(price)
                    $('#et_modal').click();


                });

                $("#edit_test_add").on('click',function(){

                    var id = $('#edit_test_id').val();
                    var name = $('#edit_test_name').val();
                    var price = $('#edit_test_price').val();
                    $("#edit_form")[0].reset();

                    $.ajax({
                        type: "POST",
                        dataType:"text",
                        url: "../php/user_model_ajax.php",
                        data: {
                            testUid : id,
                            testUname : name,
                            testUprice:price,
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

        <?php include 'header.php';
        include '../php/config.php';        
        if($_SESSION['login_role'] == 2 ){
            header('Location: ../index.php');
        }else if($_SESSION['login_role'] == 3){
            header('Location: result.php');
        }    

        ;?>

        <!------------- Add Test Section ----------------->

        <section class="test_container">
            <div class="test_head">
                <div class="test_heading ">
                    <h1>TESTS</h1>
                </div>
                <div class="test_option">
                    <div class="category_btn AC_modal_trigger" data-modal="AC_modal_name"> Add Test</div>
                    <div id="et_modal" class="category_btn ET_modal_trigger" data-modal="ET_modal_name" style="display:none;"> Edit Test</div>
                    <div class="add_test_btn AT_modal_trigger" data-modal="AT_modal_name"> Add Value</div>
                    <div class="total_test">
                        <p class="total_label">Total Test:</p>
                        <p class="total_text"><?php $nRows = $conn -> query('SELECT count(*) FROM `tests`')->fetchColumn(); 
                            echo $nRows; ?></p>
                    </div>
                </div>
            </div>
            <div class="test_body">
                <div class="test_category_section">
                    <div class="test_cat_heading">
                        <label>Search Test</label>
                        <div>
                            <input class="cat_searchbar" type="text" placeholder="Search Tests">
                            <div class="cat_search_btn">Search</div>
                        </div>
                    </div>

                    <!----------- Table ------------>

                    <div class="category_list">
                        <table class="cat_table">
                            <?php
                            $user_query = 'SELECT * FROM `tests`';
                            $sts = $conn -> query($user_query);

                            ?>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>View</th>
                                    <th>Edit</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody class="test_result">
                                <?php
                                foreach($sts as $data){
                                ?>
                                <tr>
                                    <td value="<?php echo $data['name'];  ?>"><?php echo $data['name'];  ?></td>
                                    <td><?php echo $data['price'];  ?></td>
                                    <td class="cat_view" id="<?php echo $data['test_ID'];?>">
                                        <div>view</div>
                                    </td>
                                    <td class="cat_edit_btn">
                                        <a class="edit_modal" data-id10="<?php echo $data['test_ID'];?>"><i class="far fa-edit"></i></a>
                                    </td>
                                    <td class="cat_delete_btn">
                                        <a onclick="return confirm()" href="../php/delete_test.php?id=dt_<?php echo $data['test_ID'];?>"><i class="far fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                                <?php
                                }
                                ?>

                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="test_section">
                    <div class="test_cat_heading top_height">

                    </div>

                    <!----------- Table ------------>

                    <div class="category_list">
                        <table class="test_list_table">

                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Ref Min</th>
                                    <th>Ref Max</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody class="view_tb">

                                <!--
<tr>
<td>RBC</td>
<td>14 mg</td>
<td>30 mg</td>
<td class="test_delete_btn">
<a><i class="far fa-trash-alt"></i></a>
</td>

</tr>
-->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!------------- Add Category Modal --------------->
        <div class="AC_modal" id="AC_modal_name">
            <div class="R_modal_sandbox"></div>
            <div class="R_modal_box">
                <div class="R_modal_header">
                    <h3>ADD Test</h3>
                    <div class="AC_close_modal">&#10006;</div>
                </div>
                <div class="R_modal_body">
                    <form method="post" action="../php/add_test.php">
                        <div class="row width">
                            <label>Test Name <span></span></label><input type="text" name="add_test_name">
                        </div>
                        <div class="row width">
                            <label>Price <span></span></label><input type="number" name="add_test_price">
                        </div>
                        <div class="row width">
                            <label>Value <span></span></label>
                            <div class="value_choice">
                                <input type="radio" id="single_value" name="add_value_box" checked value="single"><label for="single_value">Single</label>
                                <input type="radio" id="multiple_value" name="add_value_box" value="multiple"> <label for="multiple_value">Multiple</label>
                            </div>
                        </div> 

                        <div class="single_value_box row width">
                            <label>Min Ref Value <span></span></label><input type="text" name="add_min_test_value">
                        </div>
                        <div class="single_value_box row width">
                            <label>Max Ref Value <span></span></label><input type="text" name="add_max_test_value">
                        </div> 



                        <div class="signup_btn_sec row width">
                            <button type="" class="signup_sbtn" name="add_category_add">
                                Add
                            </button>

                            <div class="add_category_cancel AC_close_modal" name="Cancel">
                                Cancel
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <!------------- Add Test Modal ------------------->
        <div class="AT_modal" id="AT_modal_name">
            <div class="R_modal_sandbox"></div>
            <div class="R_modal_box">
                <div class="R_modal_header">
                    <h3>ADD Value</h3>
                    <div class="AT_close_modal">&#10006;</div>
                </div>
                <div class="R_modal_body">
                    <form method="post" action="../php/add_value.php">
                        <div class="row width">
                            <label>Name <span></span></label><input type="text" name="add_value_name">
                        </div>

                        <div class="row width">
                            <label>Test <span></span></label>
                            <select class="select_outline" name="add_value_test" id="add_value_test">
                                <option value="s0">Select Test</option>
                                <?php
                                $stmt2 = $conn->prepare("SELECT `test_ID`, `name`FROM `tests` WHERE `type` = 1");
                                $stmt2->execute();

                                $tests = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($tests as $test): ?>
                                <option value="<?=$test['test_ID'];?>"><?=$test['name'];?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="row width">
                            <label>Min Ref Value <span></span></label><input type="text" name="add_min_ref_value">
                        </div>
                        <div class="row width">
                            <label>Max Ref Value <span></span></label><input type="text" name="add_max_ref_value">
                        </div>

                        <div class="signup_btn_sec row width">
                            <button type="" class="signup_sbtn" name="add_test_add">
                                Add
                            </button>

                            <div class="add_test_cancel AT_close_modal" name="Cancel">
                                Cancel
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <!------------- Edit Category Modal ------------------->

        <!--
<div class="ET_modal" id="ET_modal_name">
<div class="R_modal_sandbox"></div>
<div class="R_modal_box">
<div class="R_modal_header">
<h3>EDIT TEST</h3>
<div class="ET_close_modal">&#10006;</div>
</div>
<div class="R_modal_body">
<form method="post">
<div class="row width">
<label>Test Name <span></span></label><input type="text" name="edit_test_name" value="Sample">
</div>

<div class="row width">
<label>Category <span></span></label><select class="select_outline" name="test_test_category" id="edit_test_category">
<option value="">A</option>
<option value="">B</option>
<option value="">C</option>
</select>
</div>
<div class="row width">
<label>Min Ref Value <span></span></label><input type="text" name="edit_min_ref_value" placeholder="70">
</div>
<div class="row width">
<label>Max Ref Value <span></span></label><input type="text" name="edit_max_ref_value" placeholder="110">
</div>
<div class="row width">
<label>Price <span></span></label><input type="number" name="edit_test_price" value="0000">
</div>

<div class="signup_btn_sec row width">
<button type="" class="signup_sbtn" name="edit_test_add">
Update
</button>

<div class="add_test_cancel ET_close_modal" name="Cancel">
Cancel
</div>
</div>
</form>

</div>
</div>
</div>   
-->





        <!------------- Edit Price Modal ------------------->
        <div class="ET_modal" id="ET_modal_name">
            <div class="R_modal_sandbox"></div>
            <div class="R_modal_box">
                <div class="R_modal_header">
                    <h3>EDIT TEST</h3>
                    <div class="ET_close_modal">&#10006;</div>
                </div>
                <div class="R_modal_body">
                    <form method="post" id="edit_form">
                        <div class="row width">
                            <label>Test ID <span></span></label><input type="text" name="edit_test_name" id="edit_test_id" readonly>
                        </div>
                        <div class="row width">
                            <label>Test Name <span></span></label><input type="text" name="edit_test_name" id="edit_test_name">
                        </div>
                        <div class="row width">
                            <label>Price <span></span></label><input type="number" name="edit_test_price" id="edit_test_price" min="0">
                        </div>

                        <div class="signup_btn_sec row width">
                            <button type="" class="signup_sbtn" name="edit_test_add" id="edit_test_add">
                                Update
                            </button>

                            <div class="add_test_cancel ET_close_modal" name="Cancel">
                                Cancel
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>


    </body>

</html>
