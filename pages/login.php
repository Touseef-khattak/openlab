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

        <title>Login</title>
    </head>

    <body>
        <div class="login_container">
            <form class="login_box" action="../php/authenticate.php" method="post">
                <div id="login_lab_imag">
                    <img src="../images/lab_logo.png" width="150">
                </div>
                <div class="login_heading">
                    <h1>Login</h1>
                </div>
                <input type="text" id="login_username" name="login_username" placeholder="User Name">
                <input type="password" id="login_userpass" name="login_userpass" placeholder="Password">
                <?php 
                session_start();
                if(isset($_SESSION['loginMessage'])) { 
                    echo'<p class="check_error">'.$_SESSION['loginMessage'].'</p>';
                    unset($_SESSION['loginMessage']); 
                }

                ?>

                <button id="login_btn" type="submit">Login</button>
            </form>

            <div id="main_company_text">
                <p>all rights reserved <i class="fas fa-copyright"></i> by TK DEVELOPERs <a href="http://tkdeveloper.ml">tkdeveloper.ml</a></p>

            </div>
        </div>
    </body>

</html>
