<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../utils/status_utils.php';
require_once __DIR__ . '/../../../includes/crud/applications_crud.php';
require_once __DIR__ . '/../../../includes/crud/courses_crud.php';
require_once __DIR__ . '/../../../includes/crud/recruitment_periods_crud.php';
require_once __DIR__ . '/../../../includes/crud/users_crud.php';

function incrementReportStat(array &$stats, string $key, ?string $label = null): void
{
    if (!isset($stats[$key])) {
        $stats[$key] = [
                'key'   => $key,
                'label' => $label ?? $key,
                'total' => 0,
        ];
    }

    $stats[$key]['total']++;
}

function sortReportStats(array $stats): array
{
    usort($stats, static function (array $a, array $b): int {
        return ((int) $b['total'] <=> (int) $a['total'])
                ?: strcasecmp((string) $a['label'], (string) $b['label']);
    });

    return $stats;
}

$applications = getAllApplications($pdo);
$users        = getAllUsers($pdo);
$courses      = getAllCourses($pdo);
$periods      = getAllRecruitmentPeriods($pdo);

$totalApplications = count($applications);
$totalCandidates   = count(array_filter(
        $users,
        static fn (array $user): bool => in_array($user['role'] ?? '', ['candidate', 'ee'], true)
));
$totalCourses      = count($courses);
$totalPeriods      = count($periods);

$statusStatsMap     = [];
$courseStatsMap     = [];
$departmentStatsMap = [];
$periodStatsMap     = [];

foreach ($applications as $application) {
    $status = (string) ($application['status'] ?? 'unknown');
    incrementReportStat($statusStatsMap, $status, getStatusLabel($status));

    $courseName = (string) ($application['course_name'] ?? 'Unknown Course');
    incrementReportStat($courseStatsMap, $courseName);

    $departmentName = (string) ($application['department_name'] ?? 'Unknown Department');
    incrementReportStat($departmentStatsMap, $departmentName);

    $periodTitle = (string) ($application['period_title'] ?? 'Unknown Period');
    incrementReportStat($periodStatsMap, $periodTitle);
}

$statusStats     = sortReportStats($statusStatsMap);
$courseStats     = sortReportStats($courseStatsMap);
$departmentStats = sortReportStats($departmentStatsMap);
$periodStats     = sortReportStats($periodStatsMap);

$recentApplications = $applications;
usort($recentApplications, static fn (array $a, array $b): int => (int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0));
$recentApplications = array_slice($recentApplications, 0, 5);

$statusLabels     = array_map(static fn (array $row): string => (string) $row['label'], $statusStats);
$statusTotals     = array_map(static fn (array $row): int => (int) $row['total'], $statusStats);

$courseLabels     = array_map(static fn (array $row): string => (string) $row['label'], $courseStats);
$courseTotals     = array_map(static fn (array $row): int => (int) $row['total'], $courseStats);

$departmentLabels = array_map(static fn (array $row): string => (string) $row['label'], $departmentStats);
$departmentTotals = array_map(static fn (array $row): int => (int) $row['total'], $departmentStats);

$periodLabels     = array_map(static fn (array $row): string => (string) $row['label'], $periodStats);
$periodTotals     = array_map(static fn (array $row): int => (int) $row['total'], $periodStats);
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
                                    <span class="status-pill <?= getStatusCssClass((string) $row['key']); ?>">
                                        <?= htmlspecialchars((string) $row['label']); ?>
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
                                <td><?= htmlspecialchars((string) ($application['title'] ?? 'Untitled Application')); ?></td>
                                <td><?= htmlspecialchars((string) ($application['candidate_name'] ?? 'Unknown Candidate')); ?></td>
                                <td><?= htmlspecialchars((string) ($application['course_name'] ?? 'Unknown Course')); ?></td>
                                <td>
                                    <span class="status-pill <?= getStatusCssClass((string) ($application['status'] ?? '')); ?>">
                                        <?= htmlspecialchars(getStatusLabel((string) ($application['status'] ?? ''))); ?>
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