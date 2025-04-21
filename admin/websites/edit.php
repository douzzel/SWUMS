<?php
require_once __DIR__ . '/../../includes/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header("Location: /login.php");
    exit();
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$error = '';
$success = '';

$websiteId = $_GET['id'] ?? 0;
$stmt = $db->prepare("SELECT id, name, url, check_interval FROM websites WHERE id = ?");
$stmt->bind_param("i", $websiteId);
$stmt->execute();
$website = $stmt->get_result()->fetch_assoc();

if (!$website) {
    header("Location: /admin/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $url = trim($_POST['url']);
    $interval = intval($_POST['check_interval']);

    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid URL (include http:// or https://)';
    } elseif ($interval < 1 || $interval > 1440) {
        $error = 'Check interval must be between 1 and 1440 minutes';
    } else {
        // Update website
        $stmt = $db->prepare("UPDATE websites SET name = ?, url = ?, check_interval = ? WHERE id = ?");
        $stmt->bind_param("ssii", $name, $url, $interval, $websiteId);

        if ($stmt->execute()) {
            $success = 'Website updated successfully!';
            $website['name'] = $name;
            $website['url'] = $url;
            $website['check_interval'] = $interval;
        } else {
            $error = 'Failed to update website: ' . $db->error;
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Edit Website</h4>
                    <a href="/admin/dashboard.php" class="btn btn-sm btn-secondary float-end">Back to Dashboard</a>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Website Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($website['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="url" class="form-label">Website URL</label>
                            <input type="url" class="form-control" id="url" name="url" value="<?= htmlspecialchars($website['url']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="check_interval" class="form-label">Check Interval (minutes)</label>
                            <input type="number" class="form-control" id="check_interval" name="check_interval" value="<?= htmlspecialchars($website['check_interval']) ?>" min="1" max="1440" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Website</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>