<?php
require_once __DIR__ . '/../../includes/config.php';

$auth = new Auth();

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header("Location: /login.php");
    exit();
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <h2>Admin Dashboard</h2>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Websites</span>
                    <a href="/admin/websites/add.php" class="btn btn-sm btn-primary">Add Website</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Uptime</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT w.id, w.name, 
                                         (SELECT status FROM status_checks WHERE website_id = w.id ORDER BY checked_at DESC LIMIT 1) as last_status
                                         FROM websites w";
                                $result = $db->query($query);

                                while ($row = $result->fetch_assoc()):
                                    $status = $row['last_status'] ?? 'unknown';
                                    $statusClass = $status === 'up' ? 'text-success' : 'text-danger';
                                    $uptime = getUptimePercentage($row['id']);
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['name']) ?></td>
                                        <td class="<?= $statusClass ?>"><?= strtoupper($status) ?></td>
                                        <td><?= $uptime ?>%</td>
                                        <td>
                                            <a href="/admin/websites/view.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">View</a>
                                            <a href="/admin/websites/edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <a href="/admin/websites/delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Users</span>
                    <a href="/admin/users/add.php" class="btn btn-sm btn-primary">Add User</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT id, username, is_admin FROM users";
                                $result = $db->query($query);

                                while ($row = $result->fetch_assoc()):
                                    $role = $row['is_admin'] ? 'Admin' : 'User';
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['username']) ?></td>
                                        <td><?= $role ?></td>
                                        <td>
                                            <a href="/admin/users/edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                                <a href="/admin/users/delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                            <?php endif; ?>
                                            <a href="/admin/users/access.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">Access</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            Recent Outages
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Website</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT w.name, o.start_time, o.end_time, o.duration 
                                 FROM outages o
                                 JOIN websites w ON o.website_id = w.id
                                 ORDER BY o.start_time DESC
                                 LIMIT 5";
                        $result = $db->query($query);

                        while ($row = $result->fetch_assoc()):
                            $duration = $row['duration'] ? gmdate("H:i:s", $row['duration']) : 'Ongoing';
                        ?>
                            <tr class="<?= is_null($row['end_time']) ? 'table-danger' : '' ?>">
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= date('M j, Y H:i:s', strtotime($row['start_time'])) ?></td>
                                <td><?= $row['end_time'] ? date('M j, Y H:i:s', strtotime($row['end_time'])) : '--' ?></td>
                                <td><?= $duration ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>