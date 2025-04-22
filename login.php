<!--

=========================================================
* Now UI Kit - v1.3.0
=========================================================

* Product Page: https://www.creative-tim.com/product/now-ui-kit
* Copyright 2019 Creative Tim (http://www.creative-tim.com)
* Licensed under MIT (https://github.com/creativetimofficial/now-ui-kit/blob/master/LICENSE.md)

* Designed by www.invisionapp.com Coded by www.creative-tim.com

=========================================================

* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

-->
<?php
session_start();
require_once "includes/config.php";
require_once "includes/common.php";

// process sign-in
try {
    $connect = new PDO($dsn, $username, $password, $options);
    if (isset($_POST["sign-in"])) {
        if (empty($_POST["username"]) || empty($_POST["password"])) {
            $message = 'Username and password are required';
        } else {
            $query = "SELECT `username`, `is_admin` FROM `users` WHERE `username` = :username AND `password_hash` = :password";
            $statement = $connect->prepare($query);
            $statement->execute(
                array(
                    'username' => $_POST["username"],
                    'password' => hash('sha256', $_POST["password"])
                )
            );
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result as $row) :
                $userIsAdmin = escape($row["is_admin"]);
            endforeach;
            $count = $statement->rowCount();
            if ($count > 0) {
                $_SESSION["username"] = $_POST["username"];
                $_SESSION["type"] = "";
                $_SESSION["action"] = "";
                if ($userIsAdmin == 1) {
?>
                    <script>
                        window.location = "/admin/dashboard.php";
                    </script>
                <?php
                } else {
                ?>
                    <script>
                        window.location = "/user/dashboard.php";
                    </script>
<?php
                }
            } else {
                $message = 'Invalid username or password';
            }
        }
    }
} catch (PDOException $error) {
    $message = $error->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <link rel="apple-touch-icon" sizes="76x76" href="assets/img/now-logo.png">
    <link rel="icon" type="image/png" href="assets/img/now-logo.png">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <title>
        SWUMS - Simple Website Uptime Monitoring System
    </title>
    <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no'
        name='viewport' />
    <!--     Fonts and icons     -->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet" />
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css"
        integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
    <!-- CSS Files -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/now-ui-kit.css?v=1.3.0" rel="stylesheet" />
</head>

<body class="login-page sidebar-collapse">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-primary fixed-top navbar-transparent " color-on-scroll="400">
        <div class="container">
            <div class="navbar-translate">
                <a class="navbar-brand" href="" rel="tooltip" title="Simple Website Uptime Monitoring System"
                    data-placement="bottom">
                    SWUMS
                </a>
            </div>
            <div class="collapse navbar-collapse justify-content-end" id="navigation"
                data-nav-image="../assets/img/blurred-image-1.jpg">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="https://mygenghis-web-demo.graphene-bsm.com/Accueil" target="_blank">myGenghis-Web</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="https://mygenghis.graphene-bsm.com" target="_blank">myGenghis BSM</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- End Navbar -->

    <div class="page-header clear-filter" filter-color="orange">
        <div class="page-header-image" style="background-image:url(assets/img/login.jpg)"></div>
        <div class="content">
            <div class="container">
                <div class="col-md-4 ml-auto mr-auto">
                    <div class="card card-login card-plain">
                        <form class="form" method="post">
                            <div class="card-header text-center">
                                <div class="logo-container">
                                    <img src="assets/img/now-logo.png" alt="">
                                </div>
                                <?php if (isset($_GET['installed'])): ?>
                                    <div class="alert alert-success">Installation successful! Please login.</div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($message)): ?>
                                    <div class="alert alert-danger">
                                        <?= htmlspecialchars($message) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="input-group no-border input-lg">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="now-ui-icons users_circle-08"></i>
                                        </span>
                                    </div>
                                    <input type="text" class="form-control" placeholder="Username..." name="username" required>
                                </div>
                                <div class="input-group no-border input-lg">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="now-ui-icons objects_key-25"></i>
                                        </span>
                                    </div>
                                    <input type="password" placeholder="Password..." class="form-control" name="password" required />
                                </div>
                            </div>
                            <div class="card-footer text-center">
                                <button type="submit" class="btn btn-primary btn-round btn-lg btn-block" name="sign-in"><strong>Sign In</strong></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class=" container ">
            <nav>
                <ul>
                    <li>
                        <a href="https://gbsm-support.infy.uk">
                            Other Graphene-BSM Tools & Support
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="copyright" id="copyright">
                &copy;
                <script>
                    document.getElementById('copyright').appendChild(document.createTextNode(new Date().getFullYear()))
                </script>, <a href="https://graphene-bsm.com" target=_blank">Graphene-BSM, LTD.</a>
            </div>
        </div>
    </footer>

    <!--   Core JS Files   -->
    <script src="assets/js/core/jquery.min.js" type="text/javascript"></script>
    <script src="assets/js/core/popper.min.js" type="text/javascript"></script>
    <script src="assets/js/core/bootstrap.min.js" type="text/javascript"></script>
    <!--  Plugin for Switches, full documentation here: http://www.jque.re/plugins/version3/bootstrap.switch/ -->
    <script src="assets/js/plugins/bootstrap-switch.js"></script>
    <!--  Plugin for the Sliders, full documentation here: http://refreshless.com/nouislider/ -->
    <script src="assets/js/plugins/nouislider.min.js" type="text/javascript"></script>
    <!--  Plugin for the DatePicker, full documentation here: https://github.com/uxsolutions/bootstrap-datepicker -->
    <script src="assets/js/plugins/bootstrap-datepicker.js" type="text/javascript"></script>
    <!-- Control Center for Now Ui Kit: parallax effects, scripts for the example pages etc -->
    <script src="assets/js/now-ui-kit.js?v=1.3.0" type="text/javascript"></script>

</body>

</html>