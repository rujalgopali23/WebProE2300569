<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['learner_id'])) { header("Location: ../login.php"); exit; }
$conn = mysqli_connect('localhost','root','','ems_db');
mysqli_set_charset($conn,"utf8");
$lid = $_SESSION['learner_id'];
$enrolments = mysqli_query($conn,"SELECT e.*, c.title, c.duration_hours, c.category, tp.org_name, r.rating
    FROM enrolments e
    JOIN courses c ON e.courseID=c.courseID
    JOIN training_providers tp ON c.providerID=tp.providerID
    LEFT JOIN reviews r ON r.enrolmentID=e.enrolmentID
    WHERE e.learnerID=$lid AND e.payment_status='PAID'
    ORDER BY e.enrolment_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - EduSkill</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/shared.css" rel="stylesheet">
    <link href="../css/E2300569.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php"><i class="fas fa-graduation-cap me-2"></i>EduSkill Marketplace</a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-white small"><i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['learner_name']) ?></span>
            <a href="../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>
<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">My Courses</h4>
        <a href="../index.php" class="btn btn-primary"><i class="fas fa-search me-2"></i>Browse More Courses</a>
    </div>
    <?php if (mysqli_num_rows($enrolments) > 0): ?>
    <div class="row">
    <?php while ($row = mysqli_fetch_assoc($enrolments)): ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm course-card">
                <div class="card-body">
                    <span class="badge bg-info text-dark mb-2"><?= htmlspecialchars($row['category']) ?></span>
                    <h5 class="card-title fw-bold"><?= htmlspecialchars($row['title']) ?></h5>
                    <p class="text-muted small"><i class="fas fa-building me-1"></i><?= htmlspecialchars($row['org_name']) ?></p>
                    <p class="text-muted small"><i class="fas fa-clock me-1"></i><?= $row['duration_hours'] ?> hours</p>
                    <p class="text-muted small"><i class="fas fa-calendar me-1"></i>Enrolled: <?= date('d M Y', strtotime($row['enrolment_date'])) ?></p>
                    <span class="badge bg-<?= $row['completion_status']==='COMPLETED'?'success':'primary' ?> mb-2"><?= $row['completion_status'] ?></span>
                    <?php if ($row['rating']): ?>
                        <div class="mt-1">
                            <?php for($i=1;$i<=5;$i++) echo '<i class="fa'.($i<=$row['rating']?'s':'r').' fa-star text-warning"></i>'; ?>
                            <small class="text-muted ms-1">Your rating</small>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white border-0 d-flex gap-2">
                    <a href="receipt.php?enrolmentID=<?= $row['enrolmentID'] ?>" class="btn btn-sm btn-outline-primary flex-grow-1">
                        <i class="fas fa-receipt me-1"></i>Receipt
                    </a>
                    <?php if ($row['completion_status']==='COMPLETED' && !$row['rating']): ?>
                        <a href="reviews.php?enrolmentID=<?= $row['enrolmentID'] ?>" class="btn btn-sm btn-warning flex-grow-1">
                            <i class="fas fa-star me-1"></i>Review
                        </a>
                    <?php elseif ($row['completion_status']==='ENROLLED'): ?>
                        <a href="mark_complete.php?enrolmentID=<?= $row['enrolmentID'] ?>" class="btn btn-sm btn-success flex-grow-1"
                           onclick="return confirm('Mark this course as completed?')">
                            <i class="fas fa-check me-1"></i>Complete
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
    </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-book-open fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">You haven't enrolled in any courses yet.</h5>
            <a href="../index.php" class="btn btn-primary mt-2">Browse Courses</a>
        </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
