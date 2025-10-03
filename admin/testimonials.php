<?php
require_once '../config.php';

// Authentication Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$testimonials = [];
$error = '';

try {
    $conn = getDBConnection();

    // Logic to handle approval/rejection/deletion
    if (isset($_GET['action'], $_GET['id'])) {
        $testimonial_id = (int) $_GET['id'];

        if ($_GET['action'] == 'approve') {
            $stmt = $conn->prepare("UPDATE testimonials SET is_approved = 1 WHERE testimonial_id = :id");
            $stmt->execute([':id' => $testimonial_id]);
        } elseif ($_GET['action'] == 'reject') {
            $stmt = $conn->prepare("UPDATE testimonials SET is_approved = 0 WHERE testimonial_id = :id");
            $stmt->execute([':id' => $testimonial_id]);
        } elseif ($_GET['action'] == 'delete') {
            $stmt = $conn->prepare("DELETE FROM testimonials WHERE testimonial_id = :id");
            $stmt->execute([':id' => $testimonial_id]);
        }

        header('Location: testimonials.php');
        exit;
    }

    // Fetch all testimonials, order unapproved (0) first, then by ID
    $stmt = $conn->query("SELECT * FROM testimonials ORDER BY is_approved ASC, testimonial_id DESC");
    $testimonials = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Testimonials error: " . $e->getMessage());
    $error = 'Database error: Could not load testimonials.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Testimonials</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>
    <?php include 'admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">

            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="pt-3 pb-2 mb-4 border-bottom">
                    <h1 class="h2">Manage Testimonials</h1>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo h($error); ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Client Name</th>
                                        <th>Client Title</th>
                                        <th>Quote Preview</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($testimonials)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No testimonials found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($testimonials as $t): ?>
                                            <tr class="<?php echo $t['is_approved'] ? '' : 'table-warning'; ?>">
                                                <td><?php echo $t['testimonial_id']; ?></td>
                                                <td><?php echo h($t['client_name']); ?></td>
                                                <td><?php echo h($t['client_title']); ?></td>
                                                <td><?php echo h(substr($t['quote_text'], 0, 50)) . (strlen($t['quote_text']) > 50 ? '...' : ''); ?>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?php echo $t['is_approved'] ? 'success' : 'secondary'; ?>">
                                                        <?php echo $t['is_approved'] ? 'Approved' : 'Pending'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!$t['is_approved']): ?>
                                                        <a href="?action=approve&id=<?php echo $t['testimonial_id']; ?>"
                                                            class="btn btn-sm btn-success me-1">Approve</a>
                                                    <?php else: ?>
                                                        <a href="?action=reject&id=<?php echo $t['testimonial_id']; ?>"
                                                            class="btn btn-sm btn-secondary me-1">Reject</a>
                                                    <?php endif; ?>
                                                    <a href="?action=delete&id=<?php echo $t['testimonial_id']; ?>"
                                                        onclick="return confirm('Are you sure you want to delete this testimonial?');"
                                                        class="btn btn-sm btn-danger">Delete</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>