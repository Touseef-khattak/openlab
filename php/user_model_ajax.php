<?php
require 'config.php';
session_start();

// POST Data
if ( isset( $_POST['id'] ) ) {

    $data = $_POST['id'];
    $str = ltrim( $data, 'e_' );

    $_SESSION['user_id_str'] = $str;

    $adstmt = $conn -> prepare( 'SELECT * FROM `users` WHERE user_ID = :u' );
    $adstmt -> execute( array(
        ':u' => $str
    ) );
    $rst = $adstmt -> fetch();

    $stmt = $conn -> prepare( 'SELECT * FROM `user_role` WHERE role_ID = :r' );
    $stmt -> execute( array(
        ':r' => $rst["role"]
    ) );
    $getr = $stmt -> fetch();

    $user["name"] = $rst["name"];
    $user["cnic"] = $rst["cnic"];
    $user["mobile"] = $rst["mobile"];
    $user["role"] = $getr["name"];

    echo json_encode( $user );
    exit;
}

if ( isset( $_POST['d_id'] ) ) {

    $data = $_POST['d_id'];
    $str = ltrim( $data, 'ed_' );

    $_SESSION['doc_id_str'] = $str;

    $adstmt = $conn -> prepare( 'SELECT * FROM `doctors` WHERE doc_ID = :u' );
    $adstmt -> execute( array(
        ':u' => $str
    ) );
    $rst = $adstmt -> fetch();

    $user["name"] = $rst["name"];
    $user["dep"] = $rst["department"];
    $user["mobile"] = $rst["phone"];
    $user["hospital"] = $rst["hospital"];
    echo json_encode( $user );
    exit;
}

if ( isset( $_POST['add_view'] ) ) {

    $data = $_POST['add_view'];
    $sql = 'SELECT * FROM `related_tests` WHERE test_ID = :r';
    $t_sql = 'SELECT * FROM `tests` WHERE test_ID = :t';



    $html = "";
    $sts = $conn ->prepare( $sql );
    $sts->execute( array(
        ':r'=> $data                  
    ) );

    $fetch = $sts -> fetch(PDO::FETCH_ASSOC);
    if ( is_array($fetch)){
        $sts2 = $conn ->prepare( $sql );
        $sts2->execute( array(
            ':r'=> $data                  
        ) );

        foreach ( $sts2 as $data ) {   
            $id = $data['rel_test_ID'];
            $html .= "<tr>";
            $html .= "<td>".$data['related_test']."</td>";   
            $html .= "<td>".$data['min']."</td>";   
            $html .= "<td>".$data['max']."</td>";   
            $html .= "<td class='cat_delete_btn'><a onclick='return confirm()' href='../php/delete_value.php?id=$id'><i class='far fa-trash-alt'></i></a></td>";   
            $html .= "</tr>";         
        }
        echo $html;
        exit;
    }    
    else{

        $adstmt = $conn -> prepare($t_sql);
        $adstmt -> execute( array(
            ':t' => $data
        ) );            
        $rst = $adstmt -> fetch();

        $id = $rst['test_ID'];
        $html .= "<tr>";
        $html .= "<td>".$rst['name']."</td>";

        $html .= "<td>".$rst['reference_min']."</td>";

        $html .= "<td>".$rst['reference_max']."</td>";

        $html .= "<td class='cat_delete_btn'><a>---</a></td>";
        $html .= "</tr>";
    }
    echo $html;
    exit;

}
if ( isset( $_POST['testUid'] ) ) {

    $id = $_POST['testUid'];
    $name = $_POST['testUname'];
    $price = $_POST['testUprice'];
    //    echo $id." " . $name." ".$price;exit();
    include('db.php');
    $testUpdate = $pdo->prepare("UPDATE `tests` SET `name`= ?,`price`=? WHERE test_ID = ".$id);

    $testUpdate->execute([ $name ,$price]);
    if($testUpdate){
        echo "Test Data Updated.";
    }

}


if ( isset( $_POST['docUpdate'] ) ) {

    $id = $_POST['docUpdate'];
    $name = $_POST['name'];
    $dept = $_POST['dept'];
    $hosp = $_POST['hosp'];
    $phone = $_POST['phone'];
    //    echo $id." " . $name." ".$price;exit();
    include('db.php');
    $docUpd = $pdo->prepare("UPDATE `doctors` SET`name`= ?,`department`= ? ,`hospital`= ? ,`phone`= ? WHERE  doc_ID = ".$id);

    $docUpd->execute([ $name , $dept , $hosp , $phone ]);
    if($docUpd){
        echo "Doctor Data Updated.";
    }
}

if ( isset( $_POST['updateUser'] ) ) {

    $id = $_POST['updateUser'];
    $name = $_POST['name'];
    $cnic = $_POST['cnic'];
    $mobile = $_POST['mobile'];
    $role = $_POST['role'];
    $pass = $_POST['pass'];
    //    echo $id." " . $name." ".$price;exit();
    include('db.php');
    $userUpdate = $pdo->prepare("UPDATE `users` SET `name`= ?,`cnic`= ?,`role`= ?,`mobile`= ?,`password`= ? WHERE user_ID = ".$id);

    $userUpdate->execute([ $name, $cnic , $role , $mobile , $pass]);
    if($userUpdate){
        echo "User Data Updated.";
    }    

}



?>