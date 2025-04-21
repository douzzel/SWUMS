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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0;

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } else {
        if ($auth->createUser($username, $password, $isAdmin)) {
            $success = 'User created successfully!';
            $_POST = []; // Clear form
        } else {
            $error = 'Failed to create user: ' . $db->error;
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Add New User</h4>
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
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin" <?= isset($_POST['is_admin']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_admin">Admin User</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>