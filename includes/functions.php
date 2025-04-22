# File: c:\Users\douzz\Downloads\SWUMS\includes\functions.php
<?php
function getWebsiteStatus($websiteId, $db)
{
    $stmt = $db->prepare("SELECT status FROM status_checks WHERE website_id = ? ORDER BY checked_at DESC LIMIT 1");
    $stmt->bind_param("i", $websiteId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0 ? $result->fetch_assoc()['status'] : 'unknown';
}

function getUptimePercentage($websiteId, $days = 30, $db)
{
    $stmt = $db->prepare("SELECT 
                          COUNT(*) as total_checks,
                          SUM(status = 'up') as up_checks
                          FROM status_checks 
                          WHERE website_id = ? AND checked_at >= DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->bind_param("ii", $websiteId, $days);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result['total_checks'] > 0 ? round(($result['up_checks'] / $result['total_checks']) * 100, 2) : 0;
}

function checkWebsiteAccess($userId, $websiteId, $db)
{
    // Admins have access to everything
    $auth = new Auth();
    if ($auth->isAdmin()) {
        return true;
    }

    $stmt = $db->prepare("SELECT 1 FROM user_website_access WHERE user_id = ? AND website_id = ?");
    $stmt->bind_param("ii", $userId, $websiteId);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}

function getAccessibleWebsites($userId, $db)
{
    $auth = new Auth();
    if ($auth->isAdmin()) {
        $query = "SELECT w.id, w.name, w.url FROM websites w";
    } else {
        $query = "SELECT w.id, w.name, w.url FROM websites w
                  JOIN user_website_access uwa ON w.id = uwa.website_id
                  WHERE uwa.user_id = ?";
    }

    $stmt = $db->prepare($query);
    if (!$auth->isAdmin()) {
        $stmt->bind_param("i", $userId);
    }
    $stmt->execute();
    return $stmt->get_result();
}
