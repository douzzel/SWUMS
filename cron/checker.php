<?php
require_once __DIR__ . '/../includes/config.php';

// This script should be called via cron or external service
// For InfinityFree, we'll need to use an external cron service like EasyCron

// Simple security check
if (php_sapi_name() !== 'cli' && !isset($_GET['secret_key'])) {
    die("Access denied");
}

// Verify secret key if not running in CLI
if (isset($_GET['secret_key']) && $_GET['secret_key'] !== 'YOUR_SECRET_KEY') {
    die("Invalid secret key");
}

class UptimeChecker
{
    private $db;

    public function __construct()
    {
        $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->db->connect_error) {
            die("Connection failed: " . $this->db->connect_error);
        }
    }

    public function checkAllWebsites()
    {
        $query = "SELECT id, url FROM websites";
        $result = $this->db->query($query);

        while ($row = $result->fetch_assoc()) {
            $this->checkWebsite($row['id'], $row['url']);
        }
    }

    private function checkWebsite($websiteId, $url)
    {
        $startTime = microtime(true);

        // Initialize cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'UptimeMonitor/1.0');

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $endTime = microtime(true);
        $responseTime = round($endTime - $startTime, 3);

        // Determine status
        $status = ($httpCode >= 200 && $httpCode < 400 && empty($error)) ? 'up' : 'down';

        // Log the check
        $this->logCheck($websiteId, $httpCode, $responseTime, $status);

        // Update outage tracking
        $this->trackOutage($websiteId, $status);
    }

    private function logCheck($websiteId, $httpCode, $responseTime, $status)
    {
        $stmt = $this->db->prepare("INSERT INTO status_checks (website_id, status_code, response_time, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iids", $websiteId, $httpCode, $responseTime, $status);
        $stmt->execute();
        $stmt->close();
    }

    private function trackOutage($websiteId, $status)
    {
        // Check for existing unresolved outage
        $query = "SELECT id FROM outages WHERE website_id = ? AND resolved = FALSE LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $websiteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $existingOutage = $result->fetch_assoc();
        $stmt->close();

        if ($status === 'down') {
            if (!$existingOutage) {
                // Start new outage
                $stmt = $this->db->prepare("INSERT INTO outages (website_id, start_time, resolved) VALUES (?, NOW(), FALSE)");
                $stmt->bind_param("i", $websiteId);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            if ($existingOutage) {
                // Resolve existing outage
                $stmt = $this->db->prepare("UPDATE outages SET end_time = NOW(), resolved = TRUE, duration = TIMESTAMPDIFF(SECOND, start_time, NOW()) WHERE id = ?");
                $stmt->bind_param("i", $existingOutage['id']);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

// Run the checker
$checker = new UptimeChecker();
$checker->checkAllWebsites();

echo "Uptime checks completed at " . date('Y-m-d H:i:s');
