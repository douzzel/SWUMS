<?php
require_once __DIR__ . '/../../includes/config.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header("Location: /login.php");
    exit();
}

$websiteId = $_GET['id'] ?? 0;
$userId = $auth->getCurrentUserId();

// Check access
if (!$auth->isAdmin() && !checkWebsiteAccess($userId, $websiteId)) {
    header("Location: /" . ($auth->isAdmin() ? 'admin/dashboard.php' : 'user/dashboard.php'));
    exit();
}

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Get website info
$stmt = $db->prepare("SELECT name, url, created_at FROM websites WHERE id = ?");
$stmt->bind_param("i", $websiteId);
$stmt->execute();
$website = $stmt->get_result()->fetch_assoc();

// Get current status
$stmt = $db->prepare("SELECT status, checked_at FROM status_checks WHERE website_id = ? ORDER BY checked_at DESC LIMIT 1");
$stmt->bind_param("i", $websiteId);
$stmt->execute();
$currentStatus = $stmt->get_result()->fetch_assoc();

// Get outages
$stmt = $db->prepare("SELECT start_time, end_time, duration FROM outages WHERE website_id = ? ORDER BY start_time DESC");
$stmt->bind_param("i", $websiteId);
$stmt->execute();
$outages = $stmt->get_result();

// Get data for chart (last 30 days)
$stmt = $db->prepare("SELECT DATE(checked_at) as day, 
                     AVG(response_time) as avg_response, 
                     SUM(status = 'down') as down_count,
                     COUNT(*) as total_checks
                     FROM status_checks 
                     WHERE website_id = ? AND checked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                     GROUP BY DATE(checked_at)");
$stmt->bind_param("i", $websiteId);
$stmt->execute();
$chartData = $stmt->get_result();

include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <h2><?= htmlspecialchars($website['name']) ?> Monitoring</h2>
    <p>URL: <?= htmlspecialchars($website['url']) ?></p>
    <p>Monitoring since: <?= date('M j, Y', strtotime($website['created_at'])) ?></p>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Current Status
                </div>
                <div class="card-body">
                    <h3 class="<?= $currentStatus['status'] === 'up' ? 'text-success' : 'text-danger' ?>">
                        <?= strtoupper($currentStatus['status']) ?>
                    </h3>
                    <p>Last checked: <?= date('M j, Y H:i:s', strtotime($currentStatus['checked_at'])) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Uptime Statistics (Last 30 Days)
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $db->prepare("SELECT 
                                         COUNT(*) as total_checks,
                                         SUM(status = 'up') as up_checks,
                                         SUM(status = 'down') as down_checks
                                         FROM status_checks 
                                         WHERE website_id = ? AND checked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                    $stmt->bind_param("i", $websiteId);
                    $stmt->execute();
                    $stats = $stmt->get_result()->fetch_assoc();

                    $uptimePercent = $stats['total_checks'] > 0 ? round(($stats['up_checks'] / $stats['total_checks']) * 100, 2) : 0;
                    ?>
                    <h3><?= $uptimePercent ?>% Uptime</h3>
                    <p>Total checks: <?= $stats['total_checks'] ?></p>
                    <p>Up: <?= $stats['up_checks'] ?>, Down: <?= $stats['down_checks'] ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            Response Time & Status Chart (Last 30 Days)
        </div>
        <div class="card-body">
            <canvas id="statusChart" height="150"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Outage History
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($outage = $outages->fetch_assoc()): ?>
                        <tr class="<?= is_null($outage['end_time']) ? 'table-danger' : '' ?>">
                            <td><?= date('M j, Y H:i:s', strtotime($outage['start_time'])) ?></td>
                            <td><?= $outage['end_time'] ? date('M j, Y H:i:s', strtotime($outage['end_time'])) : 'Ongoing' ?></td>
                            <td><?= $outage['duration'] ? gmdate("H:i:s", $outage['duration']) : '--' ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('statusChart').getContext('2d');
    const chartData = {
        labels: [
            <?php while ($row = $chartData->fetch_assoc()): ?> "<?= date('M j', strtotime($row['day'])) ?>",
            <?php endwhile; ?>
        ],
        datasets: [{
                label: 'Response Time (ms)',
                data: [
                    <?php
                    $chartData->data_seek(0);
                    while ($row = $chartData->fetch_assoc()):
                    ?>
                        <?= $row['avg_response'] * 1000 ?>,
                    <?php endwhile; ?>
                ],
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                yAxisID: 'y',
                type: 'line'
            },
            {
                label: 'Downtime',
                data: [
                    <?php
                    $chartData->data_seek(0);
                    while ($row = $chartData->fetch_assoc()):
                    ?>
                        <?= $row['down_count'] > 0 ? ($row['down_count'] / $row['total_checks']) * 100 : 0 ?>,
                    <?php endwhile; ?>
                ],
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                yAxisID: 'y1',
                type: 'bar'
            }
        ]
    };

    const statusChart = new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Response Time (ms)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Downtime %'
                    },
                    min: 0,
                    max: 100
                }
            }
        }
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>