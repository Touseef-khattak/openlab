<?php
if(!isset($_SESSION)) 
{ 
    session_start(); 
} 
if(!isset($_SESSION['login_username'])){
    header('Location: login.php');
};

if(!isset($_SESSION['login_role'])){
    $_SESSION['login_role'] = 2;

}
?>
<!--   Header   -->
<section class="header">
    <div class="logo">AL HAFIZ LABORATORY</div>
    <div class="user_info">
        <div class="user_name">
            <?php if(isset($_SESSION['login_username'])){
    echo'<p>'. $_SESSION["login_username"] .'</p>';              
}else{
    echo "No user";
}
            ?>
        </div>
        <a href="../php/logout.php" class="user_signout">
            <p>LogOut</p>
            <i class="fas fa-sign-out-alt"></i>

        </a>
    </div>
</section>
<!--   Header End   -->

<!--   Navigation   -->
<section class="nav">
    <nav>
        <ul class="remove_line">

            <?php
            $role = $_SESSION['login_role'];
            if($role == 2 || $role == 1){ ?>

            <li><a href="index.php">
                <div class="nav_box">
                    <i class="fas fa-file-invoice"></i>
                    <p>Invoice</p>
                </div>
                </a>
            </li>

            <?php    
            }
            ?>
            <?php
            $role = $_SESSION['login_role'];
            if($role == 1){ ?>

            <li id="nav_users"><a href="users.php">
                <div class="nav_box">
                    <i class="fas fa-users-cog"></i>
                    <p>Users</p>
                </div>
                </a>
            </li>

            <li><a href="doctors.php">
                <div class="nav_box">
                    <i class="fas fa-user-md"></i>
                    <p>Doctors</p>
                </div>
                </a>
            </li>

            <li><a href="tests.php">
                <div class="nav_box">
                    <i class="fas fa-vial"></i>
                    <p>Tests</p>
                </div>
                </a>
            </li>
            <?php    
            }
            ?>
            <?php
            $role = $_SESSION['login_role'];
            if($role == 1 || $role == 2 ){ ?>
            <li><a href="all_tests.php">
                <div class="nav_box">
                    <i class="far fa-chart-bar"></i>
                    <p>All Tests</p>
                </div>
                </a>
            </li>
            <?php    
            }
            ?>
            <?php
            $role = $_SESSION['login_role'];
            if($role == 1 || $role == 3 ){ ?>
            <li><a href="result.php">
                <div class="nav_box">
                    <i class="fas fa-file-signature"></i>
                    <p>Result</p>
                </div>
                </a>
            </li>
            <?php    
            }
            ?>
            <?php
            $role = $_SESSION['login_role'];
            if($role == 2 || $role == 1|| $role == 3){ ?>
            <li><a href="report.php">
                <div class="nav_box">
                    <i class="fas fa-file-medical"></i>
                    <p>Report</p>
                </div>
                </a>
            </li>
            <?php    
            }

            $role = $_SESSION['login_role'];
            if($role == 1){ ?>
            <li><a id="backup" href="backup.php">
                <div class="nav_box">
                    <i class="fas fa-database"></i>
                    <p>BackUp</p>
                </div>
                </a>
            </li>
            <?php    
            }
            ?>
        </ul>
    </nav>
</section>
<div id="main_company_text">
    <p>all rights reserved <i class="fas fa-copyright"></i> by TK DEVELOPERs <a href="http://tkdeveloper.ml">tkdeveloper.ml</a></p>

</div>
<script>

    $(document).ready(function(){

      

        var link = document.querySelector("link[rel~='icon']");
        if (!link) {
            link = document.createElement('link');
            link.rel = 'icon';
            document.getElementsByTagName('head')[0].appendChild(link);
        }
        link.href = '../images/minilab_logo.png';


    })

</script>
