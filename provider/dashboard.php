<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$conn = mysqli_connect("localhost","root","","ems_db");
mysqli_set_charset($conn,"utf8");
if (!isset($_SESSION['provider_id'])) { header("Location: ../login.php"); exit; }

$pid = $_SESSION['provider_id'];

$total_courses  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM courses WHERE providerID=$pid"))['c'];
$total_enrol    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM enrolments e JOIN courses c ON e.courseID=c.courseID WHERE c.providerID=$pid AND e.payment_status='PAID'"))['c'];
$total_revenue  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(e.amount_paid),0) AS total FROM enrolments e JOIN courses c ON e.courseID=c.courseID WHERE c.providerID=$pid AND e.payment_status='PAID'"))['total'];
$avg_rating     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(AVG(r.rating),0) AS avg FROM reviews r JOIN courses c ON r.courseID=c.courseID WHERE c.providerID=$pid"))['avg'];

// Recent enrolments
$recent = mysqli_query($conn, "SELECT e.*, l.full_name, c.title FROM enrolments e
    JOIN learners l ON e.learnerID=l.learnerID
    JOIN courses c ON e.courseID=c.courseID
    WHERE c.providerID=$pid AND e.payment_status='PAID'
    ORDER BY e.enrolment_date DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provider Dashboard - EduSkill</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/shared.css" rel="stylesheet">
    <link href="../css/Student2.css" rel="stylesheet">
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
        <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
        <a href="manage_courses.php"><i class="fas fa-book me-2"></i>Manage Courses</a>
        <a href="reports.php"><i class="fas fa-chart-bar me-2"></i>Reports</a>
        <div class="nav-header mt-3">Account</div>
        <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
    </div>
    <div class="flex-grow-1 p-4">
        <h4 class="fw-bold mb-1">Provider Dashboard</h4>
        <p class="text-muted mb-4">Welcome, <?= htmlspecialchars($_SESSION['provider_name']) ?></p>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-book fa-2x text-primary mb-2"></i>
                        <h3 class="fw-bold"><?= $total_courses ?></h3>
                        <small class="text-muted">Total Courses</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x text-success mb-2"></i>
                        <h3 class="fw-bold"><?= $total_enrol ?></h3>
                        <small class="text-muted">Enrolments</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-ringgit-sign fa-2x text-warning mb-2"></i>
                        <h3 class="fw-bold">RM <?= number_format($total_revenue, 2) ?></h3>
                        <small class="text-muted">Total Revenue</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-star fa-2x text-warning mb-2"></i>
                        <h3 class="fw-bold"><?= number_format($avg_rating, 1) ?></h3>
                        <small class="text-muted">Avg Rating</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-list me-2 text-primary"></i>Recent Enrolments
                <a href="reports.php" class="btn btn-sm btn-outline-primary float-end">View Reports</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Learner</th><th>Course</th><th>Date</th><th>Amount</th></tr></thead>
                    <tbody>
                        <?php if (mysqli_num_rows($recent) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($recent)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['title']) ?></td>
                                <td><?= date('d M Y', strtotime($row['enrolment_date'])) ?></td>
                                <td>RM <?= number_format($row['amount_paid'], 2) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No enrolments yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
