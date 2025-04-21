<?php
require_once __DIR__ . '/../../includes/config.php';

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header("Location: /login.php");
    exit();
}

if ($auth->isAdmin()) {
    header("Location: /admin/dashboard.php");
    exit();
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$userId = $auth->getCurrentUserId();

include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <h2>User Dashboard</h2>

    <div class="card mt-4">
        <div class="card-header">
            Websites You Can Monitor
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>URL</th>
                            <th>Status</th>
                            <th>Uptime</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $websites = getAccessibleWebsites($userId);

                        while ($row = $websites->fetch_assoc()):
                            $status = getWebsiteStatus($row['id']);
                            $statusClass = $status === 'up' ? 'text-success' : 'text-danger';
                            $uptime = getUptimePercentage($row['id']);
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['url']) ?></td>
                                <td class="<?= $statusClass ?>"><?= strtoupper($status) ?></td>
                                <td><?= $uptime ?>%</td>
                                <td>
                                    <a href="/admin/websites/view.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">View Details</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>