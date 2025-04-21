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

$userId = $_GET['id'] ?? 0;
$stmt = $db->prepare("SELECT id, username, is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: /admin/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
    $password = trim($_POST['password']);

    if (!empty($password)) {
        $passwordHash = hash('sha256', $password);
        $stmt = $db->prepare("UPDATE users SET is_admin = ?, password_hash = ? WHERE id = ?");
        $stmt->bind_param("isi", $isAdmin, $passwordHash, $userId);
    } else {
        $stmt = $db->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
        $stmt->bind_param("ii", $isAdmin, $userId);
    }

    if ($stmt->execute()) {
        $success = 'User updated successfully!';
        $user['is_admin'] = $isAdmin;
    } else {
        $error = 'Failed to update user: ' . $db->error;
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Edit User: <?= htmlspecialchars($user['username']) ?></h4>
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
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin" <?= $user['is_admin'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_admin">Admin User</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>