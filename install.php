<?php
require_once 'includes/config.php';

// Check if already installed
$tablesExist = false;
try {
    $result = $db->query("SELECT 1 FROM users LIMIT 1");
    $tablesExist = $result !== false;
} catch (Exception $e) {
    $tablesExist = false;
}

if ($tablesExist) {
    die("The system is already installed. <a href='login.php'>Go to login page</a>");
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
            $error = "Failed to create admin user: " . $db->error;
        }
    } else {
        $error = "Database setup failed: " . $db->error;
    }
}

include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Install Uptime Monitor</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Admin Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Admin Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Install</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>