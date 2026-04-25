<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../utils/status_utils.php';

$totalApplications = (int) $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$totalCandidates   = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('candidate', 'ee')")->fetchColumn();
$totalCourses      = (int) $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$totalPeriods      = (int) $pdo->query("SELECT COUNT(*) FROM recruitment_periods")->fetchColumn();

$statusStats = $pdo->query("
    SELECT status, COUNT(*) AS total
    FROM applications
    GROUP BY status
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

$courseStats = $pdo->query("
    SELECT c.name AS course_name, COUNT(a.id) AS total
    FROM applications a
    INNER JOIN courses c ON a.course_id = c.id
    GROUP BY c.id, c.name
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

$departmentStats = $pdo->query("
    SELECT d.name AS department_name, COUNT(a.id) AS total
    FROM applications a
    INNER JOIN courses c     ON a.course_id     = c.id
    INNER JOIN departments d ON c.department_id = d.id
    GROUP BY d.id, d.name
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

$periodStats = $pdo->query("
    SELECT rp.title AS period_title, COUNT(a.id) AS total
    FROM applications a
    INNER JOIN recruitment_periods rp ON a.period_id = rp.id
    GROUP BY rp.id, rp.title
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

$recentApplications = $pdo->query("
    SELECT
        a.id,
        a.title,
        a.status,
        a.created_at,
        u.username AS candidate_name,
        c.name     AS course_name
    FROM applications a
    INNER JOIN users   u ON a.user_id   = u.id
    INNER JOIN courses c ON a.course_id = c.id
    ORDER BY a.id DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$statusLabels     = array_map(fn($row) => getStatusLabel($row['status']),    $statusStats);
$statusTotals     = array_map(fn($row) => (int) $row['total'],               $statusStats);

$courseLabels     = array_map(fn($row) => $row['course_name'],               $courseStats);
$courseTotals     = array_map(fn($row) => (int) $row['total'],               $courseStats);

$departmentLabels = array_map(fn($row) => $row['department_name'],           $departmentStats);
$departmentTotals = array_map(fn($row) => (int) $row['total'],               $departmentStats);

$periodLabels     = array_map(fn($row) => $row['period_title'],              $periodStats);
$periodTotals     = array_map(fn($row) => (int) $row['total'],               $periodStats);
?>

<section class="page-card list-card">
    <div class="list-header">
        <div>
            <h1 class="page-title">Reports</h1>
            <p class="page-subtitle">Application statistics and recruitment overview.</p>
        </div>
    </div>

    <div class="report-summary-grid">
        <div class="report-summary-card">
            <span>Total Applications</span>
            <strong><?= $totalApplications; ?></strong>
        </div>

        <div class="report-summary-card">
            <span>Candidates</span>
            <strong><?= $totalCandidates; ?></strong>
        </div>

        <div class="report-summary-card">
            <span>Courses</span>
            <strong><?= $totalCourses; ?></strong>
        </div>

        <div class="report-summary-card">
            <span>Periods</span>
            <strong><?= $totalPeriods; ?></strong>
        </div>
    </div>

    <div class="report-chart-grid">
        <div class="report-chart-card">
            <h3>Applications by Status</h3>
            <canvas id="statusChart"></canvas>
        </div>

        <div class="report-chart-card">
            <h3>Applications by Course</h3>
            <canvas id="courseChart"></canvas>
        </div>

        <div class="report-chart-card">
            <h3>Applications by Department</h3>
            <canvas id="departmentChart"></canvas>
        </div>

        <div class="report-chart-card">
            <h3>Applications by Period</h3>
            <canvas id="periodChart"></canvas>
        </div>
    </div>

    <div class="report-section-grid">
        <div class="table-card">
            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>Status</th>
                        <th>Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($statusStats)): ?>
                        <tr>
                            <td colspan="2" class="empty-state">No status data found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($statusStats as $row): ?>
                            <tr>
                                <td>
                                        <span class="status-pill <?= getStatusCssClass($row['status']); ?>">
                                            <?= htmlspecialchars(getStatusLabel($row['status'])); ?>
                                        </span>
                                </td>
                                <td><?= (int) $row['total']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-card">
            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>Recent Application</th>
                        <th>Candidate</th>
                        <th>Course</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recentApplications)): ?>
                        <tr>
                            <td colspan="4" class="empty-state">No recent applications found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentApplications as $application): ?>
                            <tr>
                                <td><?= htmlspecialchars($application['title']); ?></td>
                                <td><?= htmlspecialchars($application['candidate_name']); ?></td>
                                <td><?= htmlspecialchars($application['course_name']); ?></td>
                                <td>
                                        <span class="status-pill <?= getStatusCssClass($application['status']); ?>">
                                            <?= htmlspecialchars(getStatusLabel($application['status'])); ?>
                                        </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script>
    function initReportCharts() {
        createDoughnutChart('statusChart',    <?= json_encode($statusLabels); ?>,    <?= json_encode($statusTotals); ?>);
        createBarChart('courseChart',         <?= json_encode($courseLabels); ?>,     <?= json_encode($courseTotals); ?>);
        createBarChart('departmentChart',     <?= json_encode($departmentLabels); ?>, <?= json_encode($departmentTotals); ?>);
        createBarChart('periodChart',         <?= json_encode($periodLabels); ?>,     <?= json_encode($periodTotals); ?>);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReportCharts);
    } else {
        initReportCharts();
    }
</script>