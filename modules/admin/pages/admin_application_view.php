<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/crud/applications_crud.php';
require_once __DIR__ . '/../../../utils/status_utils.php';
require_once __DIR__ . '/../../../utils/user_utils.php';
require_once __DIR__ . '/../../../utils/time_utils.php';
require_once __DIR__ . '/../../../includes/admin_guard.php';


$id = (int) ($_GET['id'] ?? 0);
$application = $id > 0 ? getApplicationById($pdo, $id) : null;

if (!$application) {
    echo '<section class="page-card list-card"><h1 class="page-title">Application not found</h1></section>';
    return;
}
?>

<section class="page-card list-card">
    <div class="list-header">
        <div>
            <h1 class="page-title"><?= htmlspecialchars($application['title'] ?? 'Application Details'); ?></h1>
        </div>

        <div class="list-actions">
            <a href="admin.php?page=applications" class="btn btn-secondary">Back</a>

            <?php if (($application['status'] ?? '') === 'draft'): ?>
                <a href="admin.php?page=applications&action=edit&id=<?= (int) $application['id']; ?>" class="btn btn-primary">
                    Edit
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="application-admin-head">
        <span class="application-admin-id">#<?= (int) $application['id']; ?></span>

        <span class="status-pill <?= getStatusCssClass($application['status'] ?? 'draft'); ?>">
            <?= htmlspecialchars(getStatusLabel($application['status'] ?? 'draft')); ?>
        </span>
    </div>

    <div class="application-admin-meta">
        <div>
            <span>Candidate</span>
            <strong><?= htmlspecialchars($application['candidate_name'] ?? 'Unknown candidate'); ?></strong>
            <small><?= htmlspecialchars($application['candidate_email'] ?? 'No email'); ?></small>
        </div>

        <div>
            <span>Role</span>
            <strong><?= htmlspecialchars(getRoleLabel($application['candidate_role'] ?? 'candidate')); ?></strong>
        </div>

        <div>
            <span>Course</span>
            <strong><?= htmlspecialchars($application['course_name'] ?? 'No course'); ?></strong>
            <small><?= htmlspecialchars($application['course_code'] ?? 'No code'); ?></small>
        </div>

        <div>
            <span>Department</span>
            <strong><?= htmlspecialchars($application['department_name'] ?? 'No department'); ?></strong>
            <small><?= htmlspecialchars($application['faculty_name'] ?? 'No faculty'); ?></small>
        </div>

        <div>
            <span>Period</span>
            <strong><?= htmlspecialchars($application['period_title'] ?? 'No period'); ?></strong>
            <small>
                <?= htmlspecialchars(formatDate($application['start_date'] ?? null)); ?>
                -
                <?= htmlspecialchars(formatDate($application['end_date'] ?? null)); ?>
            </small>
        </div>

        <div>
            <span>Created</span>
            <strong><?= htmlspecialchars(formatFullDateTime($application['created_at'] ?? null)); ?></strong>
        </div>

        <div>
            <span>Updated</span>
            <strong><?= htmlspecialchars(formatFullDateTime($application['updated_at'] ?? $application['created_at'] ?? null)); ?></strong>
        </div>

        <div>
            <span>CV</span>
            <?php if (!empty($application['cv_file_path'])): ?>
                <a href="<?= htmlspecialchars($application['cv_file_path']); ?>" target="_blank" class="btn btn-secondary btn-sm-custom">
                    View CV
                </a>
            <?php else: ?>
                <strong>Not uploaded</strong>
            <?php endif; ?>
        </div>
    </div>

    <div class="placeholder-box" style="margin-top: 20px;">
        <h3>Cover Letter</h3>
        <div class="fixed-text-panel">
            <p><?= nl2br(htmlspecialchars($application['cover_letter'] ?: 'No cover letter provided.')); ?></p>
        </div>
    </div>

    <div class="placeholder-box" style="margin-top: 20px;">
        <h3>Qualifications</h3>
        <div class="fixed-text-panel">
            <p><?= nl2br(htmlspecialchars($application['qualifications'] ?: 'No qualifications provided.')); ?></p>
        </div>
    </div>
</section>