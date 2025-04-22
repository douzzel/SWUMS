<?php
require_once 'includes/config.php';
require_once "includes/common.php";

// Initialize database connection
$db = new PDO($dsn, $username, $password, $options);
if ($db->connect_error) {
    $message = "Database connection failed: " . $db->connect_error;
}

// Check if already installed
$tablesExist = false;
try {
    $result = $db->query("SELECT 1 FROM users LIMIT 1");
    $tablesExist = $result !== false;
} catch (Exception $e) {
    $tablesExist = false;
}

if ($tablesExist) {
    $message = "The system is already installed.<br /><br /><a href='/login.php' style='color: white;'><strong>> Go to login page <</strong></a><br />";
    $installCheck = "The system is already installed.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create tables
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(64) NOT NULL,
        is_admin BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
    CREATE TABLE IF NOT EXISTS websites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        url VARCHAR(255) NOT NULL,
        check_interval INT DEFAULT 5 COMMENT 'Minutes between checks',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_by INT,
        FOREIGN KEY (created_by) REFERENCES users(id)
    );
    
    CREATE TABLE IF NOT EXISTS user_website_access (
        user_id INT,
        website_id INT,
        PRIMARY KEY (user_id, website_id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (website_id) REFERENCES websites(id)
    );
    
    CREATE TABLE IF NOT EXISTS status_checks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        website_id INT,
        status_code INT,
        response_time FLOAT COMMENT 'Response time in seconds',
        status ENUM('up', 'down') NOT NULL,
        checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (website_id) REFERENCES websites(id)
    );
    
    CREATE TABLE IF NOT EXISTS outages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        website_id INT,
        start_time TIMESTAMP,
        end_time TIMESTAMP NULL,
        duration INT COMMENT 'Duration in seconds',
        resolved BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (website_id) REFERENCES websites(id)
    );";

    // Execute multi-query
    if ($db->multi_query($sql)) {
        do {
            if ($result = $db->store_result()) {
                $result->free();
            }
        } while ($db->more_results() && $db->next_result());

        // Create admin user
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $passwordHash = hash('sha256', $password);

        $stmt = $db->prepare("INSERT INTO users (username, password_hash, is_admin) VALUES (?, ?, TRUE)");
        $stmt->bind_param("ss", $username, $passwordHash);

        if ($stmt->execute()) {
            header("Location: login.php?installed=1");
            exit();
        } else {
            $message = "Failed to create admin user: " . $db->error;
        }
    } else {
        $message = "Database setup failed: " . $db->error;
    }
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
                    SWUMS - INSTALLER
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
                            </div>
                            <div class="card-body">
                                <?php if (isset($_GET['installed'])): ?>
                                    <div class="alert alert-success">Installation successful! Please go to the <a href='/login.php'>Login</a> page.</div>
                                <?php endif; ?>
                                <?php if (!empty($message)): ?>
                                    <div class="alert alert-danger">
                                        <?= $message ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (empty($installCheck) || isset($_GET['installed'])): ?>
                                    <div class="input-group no-border input-lg">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="now-ui-icons users_circle-08"></i>
                                            </span>
                                        </div>
                                        <input type="text" class="form-control" placeholder="Admin Username..." name="username" required />
                                    </div>
                                    <div class="input-group no-border input-lg">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="now-ui-icons objects_key-25"></i>
                                            </span>
                                        </div>
                                        <input type="password" placeholder="Admin Password..." class="form-control" name="password" required />
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer text-center">
                                <?php if (empty($installCheck) || isset($_GET['installed'])): ?>
                                    <button type="submit" class="btn btn-primary btn-round btn-lg btn-block"><strong>Install</strong></button>
                                <?php endif; ?>
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