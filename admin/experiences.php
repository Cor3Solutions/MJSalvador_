<?php
require_once '../config.php';

// Authentication Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$experiences = [];
$error = '';

try {
    $conn = getDBConnection();

    // Basic CRUD: Delete Action
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $exp_id = (int) $_GET['id'];
        $stmt = $conn->prepare("DELETE FROM experiences WHERE exp_id = :id");
        $stmt->execute([':id' => $exp_id]);

        header('Location: experiences.php?status=deleted');
        exit;
    }

    // Fetch all experiences, sorted by category and sort_order
    $stmt = $conn->query("SELECT * FROM experiences ORDER BY category ASC, sort_order ASC");
    $experiences = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Experiences error: " . $e->getMessage());
    $error = 'Database error: Could not load experiences.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Experiences</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>
    <?php include 'admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="pt-3 pb-2 mb-4 border-bottom d-flex justify-content-between align-items-center">
                    <h1 class="h2">Manage Experiences / Portfolio Items</h1>
                    <a href="experience_form.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add New Item</a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo h($error); ?></div>
                <?php endif; ?>
                <?php if (isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
                    <div class="alert alert-success">Experience deleted successfully!</div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Category</th>
                                        <th>Title</th>
                                        <th>Subtitle</th>
                                        <th>Date Range</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($experiences)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No experiences found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($experiences as $e): ?>
                                            <tr>
                                                <td><?php echo $e['sort_order']; ?></td>
                                                <td><span class="badge bg-secondary"><?php echo h($e['category']); ?></span>
                                                </td>
                                                <td><?php echo h($e['title']); ?></td>
                                                <td><?php echo h($e['subtitle']); ?></td>
                                                <td><?php echo h($e['date_range']); ?></td>
                                                <td>
                                                    <a href="experience_form.php?id=<?php echo $e['exp_id']; ?>"
                                                        class="btn btn-sm btn-warning me-1">Edit</a>
                                                    <a href="?action=delete&id=<?php echo $e['exp_id']; ?>"
                                                        onclick="return confirm('Are you sure you want to delete this experience?');"
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