<?php

require 'config.php';
session_start();

$doctor_name = $_POST['add_doctor_name'];
$doctor_dep = $_POST['add_doctor_dep'];
$doctor_mobile = $_POST['add_doctor_mobile'];
$doctor_hospital = $_POST['add_doctor_hospital'];



$sql = 'INSERT INTO doctors(name,department,hospital,phone) VALUES(:name,:dep,:hosp,:phone)';


if ( $doctor_name == '' || $doctor_dep == '' || $doctor_mobile == ''  || $doctor_hospital == '' ) {
    $_SESSION['add_doctor_Message'] = 'Fill all the Fields';    
    header( 'Location: ../pages/doctors.php' );
} else {
    
    $adstmt = $conn -> prepare( 'SELECT * FROM `doctors` WHERE name = :u' );
    $adstmt -> execute( array(
        ':u' => $doctor_name
    ) );
    
    $rst = $adstmt -> fetch();

    if ( $rst == '' ) {
        $adstmt = $conn -> prepare( 'SELECT * FROM `doctors` WHERE phone = :p' );
        $adstmt -> execute( array(
            ':p' => $doctor_mobile
        ) );
        
        $rst = $adstmt -> fetch();
        if ( $rst == '' ) {
            
                $stmt = $conn ->prepare( $sql );
                $stmt->execute( array(
                    ':name'=> $doctor_name,
                    ':dep'=> $doctor_dep,
                    ':hosp'=> $doctor_hospital,
                    ':phone'=> $doctor_mobile
                ) );
                header( 'Location: ../pages/doctors.php' );

            } else {
                $_SESSION['add_doctor_Message'] = 'This number is already exist';
                header( 'Location: ../pages/doctors.php' );
            }

        } else {
            $_SESSION['add_doctor_Message'] = 'This name is already taken';
            header( 'Location: ../pages/doctors.php' );

        }
    }

?>
