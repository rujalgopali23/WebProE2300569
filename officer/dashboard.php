<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$conn = mysqli_connect("localhost","root","","ems_db");
mysqli_set_charset($conn,"utf8");
if (!isset($_SESSION['officer_id'])) { header("Location: ../login.php"); exit; }

// Stats
$total_pending  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM training_providers WHERE status='PENDING'"))['c'];
$total_approved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM training_providers WHERE status='APPROVED'"))['c'];
$total_rejected = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM training_providers WHERE status='REJECTED'"))['c'];
$total_courses  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM courses WHERE status='ACTIVE'"))['c'];
$total_learners = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM learners"))['c'];
$total_enrol    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM enrolments WHERE payment_status='PAID'"))['c'];

// Recent pending providers
$recent = mysqli_query($conn, "SELECT * FROM training_providers WHERE status='PENDING' ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Dashboard - EduSkill</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/shared.css" rel="stylesheet">
    <link href="../css/E2300569.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar" style="width:240px; min-width:240px;">
        <div class="text-white text-center py-3 px-3 mb-2">
            <i class="fas fa-user-tie fa-2x mb-1"></i>
            <div class="fw-bold"><?= htmlspecialchars($_SESSION['officer_name']) ?></div>
            <small class="text-muted">Ministry Officer</small>
        </div>
        <div class="nav-header">Navigation</div>
        <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
        <a href="manage_providers.php"><i class="fas fa-building me-2"></i>Manage Providers</a>
        <div class="nav-header mt-3">Account</div>
        <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
    </div>

    <!-- Main content -->
    <div class="flex-grow-1 p-4">
        <h4 class="fw-bold mb-1">Officer Dashboard</h4>
        <p class="text-muted mb-4">Welcome back, <?= htmlspecialchars($_SESSION['officer_name']) ?></p>

        <!-- Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-2">
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-hourglass-half fa-2x text-warning mb-2"></i>
                        <h3 class="fw-bold"><?= $total_pending ?></h3>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <h3 class="fw-bold"><?= $total_approved ?></h3>
                        <small class="text-muted">Approved</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                        <h3 class="fw-bold"><?= $total_rejected ?></h3>
                        <small class="text-muted">Rejected</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-book fa-2x text-primary mb-2"></i>
                        <h3 class="fw-bold"><?= $total_courses ?></h3>
                        <small class="text-muted">Courses</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x text-info mb-2"></i>
                        <h3 class="fw-bold"><?= $total_learners ?></h3>
                        <small class="text-muted">Learners</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center border-0 shadow-sm">
                    <div class="card-body">
                        <i class="fas fa-clipboard-check fa-2x text-secondary mb-2"></i>
                        <h3 class="fw-bold"><?= $total_enrol ?></h3>
                        <small class="text-muted">Enrolments</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Applications -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-clock text-warning me-2"></i>Recent Pending Applications
                <a href="manage_providers.php" class="btn btn-sm btn-outline-primary float-end">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Organisation</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Applied On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($recent) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($recent)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['org_name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['contact_number']) ?></td>
                                <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <a href="manage_providers.php?view=<?= $row['providerID'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye me-1"></i>Review
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No pending applications.</td></tr>
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
