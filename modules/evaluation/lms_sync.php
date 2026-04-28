<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['hr', 'admin', 'ee'], true)) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../utils/time_utils.php';
require_once __DIR__ . '/../../utils/pdf_utils.php';

$uploadDir = __DIR__ . '/../../uploads/materials/';
$uploadWebPath = '../../uploads/materials/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$pageTitle = 'LMS Sync & Materials';
$isEE = ($_SESSION['role'] === 'ee');
$targetCourseId = isset($_GET['manage_course']) ? (int)$_GET['manage_course'] : null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_material']) && $isEE) {
        $title = trim($_POST['material_title']);
        $cId = (int)$_POST['course_id'];

        $uploadResults = uploadPdf('material_file', 'material', $errors);

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO course_materials (course_id, title, pdf_path) VALUES (?, ?, ?)");
            $stmt->execute([$cId, $title, $uploadResults['path'] ?? null]);
            $_SESSION['flash'] = ['type' => 'success', 'title' => 'Success', 'text' => 'Material uploaded.'];
            header("Location: lms_sync.php?manage_course=$cId"); exit;
        }
    }

    if (isset($_POST['delete_material']) && $isEE) {
        $mId = (int)$_POST['material_id'];
        $cId = (int)$_POST['course_id'];
        $pdo->prepare("DELETE FROM course_materials WHERE id = ?")->execute([$mId]);
        $_SESSION['flash'] = ['type' => 'success', 'title' => 'Deleted', 'text' => 'Material removed.'];
        header("Location: lms_sync.php?manage_course=$cId"); exit;
    }
}

$whereSql = "WHERE u.role = 'ee'";
if ($isEE) { $whereSql .= " AND u.id = " . (int)$_SESSION['user_id']; }

$stmt = $pdo->prepare("
    SELECT u.username, u.email, c.id AS course_id, c.code AS course_code, c.name AS course_name, 
           le.lms_access, le.synced_at
    FROM users u
    LEFT JOIN lms_enrollments le ON le.user_id = u.id
    LEFT JOIN courses c ON c.id = le.course_id
    $whereSql
    ORDER BY u.username ASC
");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$materials = [];
if ($targetCourseId) {
    $stmt = $pdo->prepare("SELECT * FROM course_materials WHERE course_id = ? ORDER BY created_at DESC");
    $stmt->execute([$targetCourseId]);
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <style>
        .file-input-fix {
            display: block;
            width: 100%;
            padding: 7px;
            font-size: 0.85rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #fff;
        }
        .action-btns-wrapper {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php if ($flash): ?>
    <div id="flash-data" data-type="<?= $flash['type'] ?>" data-title="<?= $flash['title'] ?>" data-text="<?= $flash['text'] ?>"></div>
<?php endif; ?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../../includes/evaluation_sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="page-shell">
            <?php require_once __DIR__ . '/../../includes/protected_topbar.php'; ?>

            <section class="page-card list-card">
                <div class="list-header">
                    <div>
                        <h2 class="page-title"><?= $isEE ? 'My Course Materials' : 'LMS Sync' ?></h2>
                        <p class="page-subtitle">Manage resources for assigned courses and track sync status.</p>
                    </div>
                </div>
            </section>

            <?php if (!empty($errors)): ?>
                <div style="margin: 20px; padding: 15px; background: #fee2e2; color: #991b1b; border-radius: 8px; border: 1px solid #fecaca;">
                    <ul style="margin:0; padding-left:20px;">
                        <?php foreach($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <section class="page-card list-card">
                <div class="table-card">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Course</th>
                                <th>LMS Access</th>
                                <th>Last Sync</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($rows as $row):
                                $active = (int)($row['lms_access'] ?? 0) === 1;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['username']) ?></td>
                                    <td><strong><?= htmlspecialchars($row['course_code'] ?? 'N/A') ?></strong></td>
                                    <td><span class="status-pill <?= $active ? 'status-approved' : 'status-rejected' ?>"><?= $active ? 'Active' : 'No Access' ?></span></td>
                                    <td><?= $row['synced_at'] ? formatFullDateTime($row['synced_at']) : 'Never' ?></td>
                                    <td>
                                        <?php if ($row['course_id'] && $active): ?>
                                            <a href="?manage_course=<?= $row['course_id'] ?>" class="btn btn-primary btn-sm-custom">Manage Materials</a>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <?php if ($targetCourseId): ?>
                <section class="page-card list-card" id="materials-section">
                    <div class="list-header">
                        <h2 class="page-title">Manage Course Content</h2>
                    </div>

                    <?php if ($isEE): ?>
                        <form method="POST" enctype="multipart/form-data" class="admin-form" style="margin-bottom: 2rem; border: 1px solid #e5e7eb; padding: 20px; border-radius: 8px;">
                            <input type="hidden" name="course_id" value="<?= $targetCourseId ?>">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Material Title</label>
                                    <input type="text" name="material_title" class="admin-input" required placeholder="e.g. Week 1 Slides">

                                    <div class="action-btns-wrapper">
                                        <button type="submit" name="add_material" class="btn btn-primary">Upload</button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>PDF File (Optional)</label>
                                    <input type="file" name="material_file" class="file-input-fix" accept="application/pdf">
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="table-card">
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>File</th>
                                    <th>Date Added</th>
                                    <?php if ($isEE): ?><th>Action</th><?php endif; ?>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($materials)): ?>
                                    <tr><td colspan="4" class="empty-state">No materials found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($materials as $m): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($m['title']) ?></strong></td>
                                            <td>
                                                <?php if ($m['pdf_path']): ?>
                                                    <a href="<?= htmlspecialchars($m['pdf_path']) ?>" target="_blank" style="color:#2563eb; text-decoration:underline;">View PDF</a>
                                                <?php else: ?><span class="text-muted">No File</span><?php endif; ?>
                                            </td>
                                            <td><?= date('Y-m-d', strtotime($m['created_at'])) ?></td>
                                            <?php if ($isEE): ?>
                                                <td>
                                                    <form method="POST" class="js-confirm-form" style="display:inline;">
                                                        <input type="hidden" name="material_id" value="<?= $m['id'] ?>">
                                                        <input type="hidden" name="course_id" value="<?= $targetCourseId ?>">
                                                        <button type="submit" name="delete_material" class="btn btn-danger btn-sm-custom">Delete</button>
                                                    </form>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const flash = document.getElementById('flash-data');
        if (flash && typeof Swal !== 'undefined') {
            Swal.fire({ icon: flash.dataset.type, title: flash.dataset.title, text: flash.dataset.text });
        }
        document.querySelectorAll('.js-confirm-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Delete material?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
                    confirmButtonColor: '#dc2626'
                }).then(result => { if (result.isConfirmed) form.submit(); });
            });
        });
    });
</script>
<?php require_once __DIR__ . '/../../includes/protected_footer.php'; ?>
</body>
</html>