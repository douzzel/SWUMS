<?php
require_once __DIR__ . '/../../includes/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header("Location: /login.php");
    exit();
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$userId = $_GET['id'] ?? 0;

// Get user info
$stmt = $db->prepare("SELECT id, username FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: /admin/dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['websites'])) {
    // First, remove all existing access
    $db->query("DELETE FROM user_website_access WHERE user_id = $userId");

    // Then add selected access
    foreach ($_POST['websites'] as $websiteId) {
        $websiteId = intval($websiteId);
        $db->query("INSERT INTO user_website_access (user_id, website_id) VALUES ($userId, $websiteId)");
    }

    header("Location: /admin/users/access.php?id=$userId&success=1");
    exit();
}

// Get all websites
$websites = $db->query("SELECT id, name FROM websites");

// Get user's current access
$userAccess = $db->query("SELECT website_id FROM user_website_access WHERE user_id = $userId");
$currentAccess = [];
while ($row = $userAccess->fetch_assoc()) {
    $currentAccess[] = $row['website_id'];
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Manage Access for <?= htmlspecialchars($user['username']) ?></h4>
                    <a href="/admin/dashboard.php" class="btn btn-sm btn-secondary float-end">Back to Dashboard</a>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">Access updated successfully!</div>
                    <?php endif; ?>

                    <form method="post">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Website</th>
                                    <th>Grant Access</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($website = $websites->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($website['name']) ?></td>
                                        <td>
                                            <input type="checkbox" name="websites[]" value="<?= $website['id'] ?>"
                                                <?= in_array($website['id'], $currentAccess) ? 'checked' : '' ?>>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>