<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$conn = mysqli_connect("localhost","root","","ems_db");
mysqli_set_charset($conn,"utf8");
if (!isset($_SESSION['provider_id'])) { header("Location: ../login.php"); exit; }

$pid     = $_SESSION['provider_id'];
$message = '';
$msg_type = 'success';

// DELETE
if (isset($_GET['delete'])) {
    $cid = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM courses WHERE courseID=$cid AND providerID=$pid");
    $message = 'Course deleted successfully.';
    $msg_type = 'danger';
}

// ADD or EDIT submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = mysqli_real_escape_string($conn, trim($_POST['title']));
    $desc     = mysqli_real_escape_string($conn, trim($_POST['description']));
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $duration = (int)$_POST['duration_hours'];
    $price    = (float)$_POST['price'];
    $seats    = (int)$_POST['seats_available'];
    $start    = $_POST['start_date'];
    $end      = $_POST['end_date'];
    $status   = $_POST['status'];

    if (empty($title) || empty($desc) || $price < 0) {
        $message  = 'Please fill in all required fields correctly.';
        $msg_type = 'danger';
    } else {
        if (isset($_POST['courseID']) && $_POST['courseID'] !== '') {
            // Edit
            $cid = (int)$_POST['courseID'];
            $sql = "UPDATE courses SET title='$title', description='$desc', category='$category',
                    duration_hours=$duration, price=$price, seats_available=$seats,
                    start_date='$start', end_date='$end', status='$status'
                    WHERE courseID=$cid AND providerID=$pid";
            mysqli_query($conn, $sql);
            $message = 'Course updated successfully.';
        } else {
            // Add
            $sql = "INSERT INTO courses (providerID, title, description, category, duration_hours, price, seats_available, start_date, end_date, status)
                    VALUES ($pid, '$title', '$desc', '$category', $duration, $price, $seats, '$start', '$end', '$status')";
            mysqli_query($conn, $sql);
            $message = 'New course added successfully.';
        }
    }
}

// Load course for editing
$edit_course = null;
if (isset($_GET['edit'])) {
    $cid = (int)$_GET['edit'];
    $res = mysqli_query($conn, "SELECT * FROM courses WHERE courseID=$cid AND providerID=$pid");
    $edit_course = mysqli_fetch_assoc($res);
}

// Get all courses for this provider
$courses = mysqli_query($conn, "SELECT c.*, COUNT(DISTINCT e.enrolmentID) AS enrolled
    FROM courses c
    LEFT JOIN enrolments e ON c.courseID=e.courseID AND e.payment_status='PAID'
    WHERE c.providerID=$pid
    GROUP BY c.courseID
    ORDER BY c.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - EduSkill</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/shared.css" rel="stylesheet">
    <link href="../css/E2300580.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <div class="sidebar" style="width:240px; min-width:240px;">
        <div class="text-white text-center py-3 px-3 mb-2">
            <i class="fas fa-building fa-2x mb-1"></i>
            <div class="fw-bold small"><?= htmlspecialchars($_SESSION['provider_name']) ?></div>
            <small class="text-muted">Training Provider</small>
        </div>
        <div class="nav-header">Navigation</div>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
        <a href="manage_courses.php" class="active"><i class="fas fa-book me-2"></i>Manage Courses</a>
        <a href="reports.php"><i class="fas fa-chart-bar me-2"></i>Reports</a>
        <div class="nav-header mt-3">Account</div>
        <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
    </div>

    <div class="flex-grow-1 p-4">
        <h4 class="fw-bold mb-4">Manage Courses</h4>

        <?php if ($message): ?>
            <div class="alert alert-<?= $msg_type ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Form Panel -->
            <div class="col-md-5">
                <div class="form-card mb-4">
                    <h6 class="fw-bold mb-3 text-primary">
                        <i class="fas fa-<?= $edit_course ? 'edit' : 'plus-circle' ?> me-2"></i>
                        <?= $edit_course ? 'Edit Course' : 'Add New Course' ?>
                    </h6>
                    <form method="POST" id="courseForm" novalidate>
                        <?php if ($edit_course): ?>
                            <input type="hidden" name="courseID" value="<?= $edit_course['courseID'] ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Course Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control"
                                   value="<?= $edit_course ? htmlspecialchars($edit_course['title']) : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="3" required><?= $edit_course ? htmlspecialchars($edit_course['description']) : '' ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select">
                                    <?php foreach (['IT','Business','Design','Language','Other'] as $cat): ?>
                                        <option value="<?= $cat ?>" <?= ($edit_course && $edit_course['category'] === $cat) ? 'selected' : '' ?>>
                                            <?= $cat ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Duration (hours)</label>
                                <input type="number" name="duration_hours" class="form-control" min="1"
                                       value="<?= $edit_course ? $edit_course['duration_hours'] : '8' ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Price (RM) <span class="text-danger">*</span></label>
                                <input type="number" name="price" class="form-control" min="0" step="0.01"
                                       value="<?= $edit_course ? $edit_course['price'] : '0' ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Seats Available</label>
                                <input type="number" name="seats_available" class="form-control" min="1"
                                       value="<?= $edit_course ? $edit_course['seats_available'] : '30' ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control"
                                       value="<?= $edit_course ? $edit_course['start_date'] : '' ?>">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control"
                                       value="<?= $edit_course ? $edit_course['end_date'] : '' ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="ACTIVE" <?= ($edit_course && $edit_course['status'] === 'ACTIVE') ? 'selected' : '' ?>>Active</option>
                                <option value="INACTIVE" <?= ($edit_course && $edit_course['status'] === 'INACTIVE') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-save me-1"></i><?= $edit_course ? 'Update Course' : 'Add Course' ?>
                            </button>
                            <?php if ($edit_course): ?>
                                <a href="manage_courses.php" class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Course List -->
            <div class="col-md-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-list me-2 text-primary"></i>Your Courses (<?= mysqli_num_rows($courses) ?>)
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr><th>Title</th><th>Price</th><th>Enrolled</th><th>Status</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($courses) > 0): ?>
                                    <?php while ($c = mysqli_fetch_assoc($courses)): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold small"><?= htmlspecialchars($c['title']) ?></div>
                                            <small class="text-muted"><?= $c['category'] ?> • <?= $c['duration_hours'] ?>hrs</small>
                                        </td>
                                        <td>RM <?= number_format($c['price'], 2) ?></td>
                                        <td><?= $c['enrolled'] ?></td>
                                        <td>
                                            <span class="badge bg-<?= $c['status'] === 'ACTIVE' ? 'success' : 'secondary' ?>">
                                                <?= $c['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="manage_courses.php?edit=<?= $c['courseID'] ?>" class="btn btn-sm btn-warning me-1">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="manage_courses.php?delete=<?= $c['courseID'] ?>" class="btn btn-sm btn-danger"
                                               onclick="return confirm('Delete this course? This cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No courses yet. Add your first course!</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/shared.js"></script>
<script src="../js/E2300580.js"></script>
<script>
document.getElementById('courseForm').addEventListener('submit', function(e) {
    let valid = true;
    this.querySelectorAll('[required]').forEach(f => {
        if (!f.value.trim()) { f.classList.add('is-invalid'); valid = false; }
        else f.classList.remove('is-invalid');
    });
    const price = document.querySelector('[name="price"]');
    if (parseFloat(price.value) < 0) { price.classList.add('is-invalid'); valid = false; }
    if (!valid) e.preventDefault();
});
</script>
</body>
</html>
