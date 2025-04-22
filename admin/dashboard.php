<?php
require_once __DIR__ . '/../../includes/config.php';

$auth = new Auth();

// Debug output (remove in production)
echo "<!-- Session Status: " . session_status() . " -->\n";
echo "<!-- Session Data: " . print_r($_SESSION, true) . " -->\n";

// Check authentication
if (!$auth->isLoggedIn()) {
    header("Location: " . BASE_URL . "/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

if (!$auth->isAdmin()) {
    header("Location: " . BASE_URL . "/user/dashboard.php");
    exit();
}

// Database connection
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}

// Get websites data
$websites = [];
$query = "SELECT w.id, w.name, w.url, 
         (SELECT status FROM status_checks WHERE website_id = w.id ORDER BY checked_at DESC LIMIT 1) as status,
         (SELECT checked_at FROM status_checks WHERE website_id = w.id ORDER BY checked_at DESC LIMIT 1) as last_checked
         FROM websites w";
$result = $db->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $websites[] = $row;
    }
} else {
    $error = "Database error: " . $db->error;
}

// Get recent outages
$outages = [];
$query = "SELECT o.id, w.name as website_name, o.start_time, o.end_time, o.duration 
          FROM outages o
          JOIN websites w ON o.website_id = w.id
          ORDER BY o.start_time DESC
          LIMIT 5";
$result = $db->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $outages[] = $row;
    }
}

// Close connection
$db->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,700,200" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/now-ui-dashboard.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar" data-color="orange">
            <div class="logo">
                <a href="/" class="simple-text logo-mini">UM</a>
                <a href="/" class="simple-text logo-normal"><?= SITE_NAME ?></a>
            </div>
            <div class="sidebar-wrapper">
                <ul class="nav">
                    <li class="active">
                        <a href="dashboard.php">
                            <i class="now-ui-icons design_app"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li>
                        <a href="websites/add.php">
                            <i class="now-ui-icons ui-1_simple-add"></i>
                            <p>Add Website</p>
                        </a>
                    </li>
                    <li>
                        <a href="users/add.php">
                            <i class="now-ui-icons users_single-02"></i>
                            <p>Add User</p>
                        </a>
                    </li>
                    <li>
                        <a href="../logout.php">
                            <i class="now-ui-icons media-1_button-power"></i>
                            <p>Logout</p>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="main-panel">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-transparent navbar-absolute bg-primary fixed-top">
                <div class="container-fluid">
                    <div class="navbar-wrapper">
                        <div class="navbar-toggle">
                            <button type="button" class="navbar-toggler">
                                <span class="navbar-toggler-bar bar1"></span>
                                <span class="navbar-toggler-bar bar2"></span>
                                <span class="navbar-toggler-bar bar3"></span>
                            </button>
                        </div>
                        <a class="navbar-brand" href="dashboard.php">Dashboard</a>
                    </div>
                    <div class="collapse navbar-collapse justify-content-end">
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link" href="#">
                                    <i class="now-ui-icons users_single-02"></i>
                                    <p><?= htmlspecialchars($_SESSION['username']) ?></p>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            <!-- End Navbar -->

            <div class="content">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Website Status</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead class="text-primary">
                                            <tr>
                                                <th>Website</th>
                                                <th>URL</th>
                                                <th>Status</th>
                                                <th>Last Checked</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($websites as $website): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($website['name']) ?></td>
                                                    <td><?= htmlspecialchars($website['url']) ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $website['status'] === 'up' ? 'success' : 'danger' ?>">
                                                            <?= strtoupper($website['status'] ?? 'UNKNOWN') ?>
                                                        </span>
                                                    </td>
                                                    <td><?= $website['last_checked'] ? date('M j, Y H:i', strtotime($website['last_checked'])) : 'Never' ?></td>
                                                    <td>
                                                        <a href="websites/view.php?id=<?= $website['id'] ?>" class="btn btn-info btn-sm">View</a>
                                                        <a href="websites/edit.php?id=<?= $website['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Recent Outages</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead class="text-primary">
                                            <tr>
                                                <th>Website</th>
                                                <th>Start Time</th>
                                                <th>End Time</th>
                                                <th>Duration</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($outages as $outage): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($outage['website_name']) ?></td>
                                                    <td><?= date('M j, Y H:i', strtotime($outage['start_time'])) ?></td>
                                                    <td><?= $outage['end_time'] ? date('M j, Y H:i', strtotime($outage['end_time'])) : 'Ongoing' ?></td>
                                                    <td><?= $outage['duration'] ? gmdate("H:i:s", $outage['duration']) : '--' ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="footer">
                <div class="container-fluid">
                    <div class="copyright">
                        &copy; <?= date('Y') ?> <?= SITE_NAME ?>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="../assets/js/core/jquery.min.js"></script>
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/now-ui-dashboard.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize dashboard functions
            $().ready(function() {
                $sidebar = $('.sidebar');
                $navbar = $('.navbar');

                // Initialize perfectScrollbar
                if ($('.sidebar .sidebar-wrapper').length != 0) {
                    var ps = new PerfectScrollbar('.sidebar .sidebar-wrapper');
                }
            });
        });
    </script>
</body>

</html>