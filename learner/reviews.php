<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$conn = mysqli_connect("localhost","root","","ems_db");
mysqli_set_charset($conn,"utf8");
if (!isset($_SESSION['learner_id'])) { header("Location: ../login.php"); exit; }

$lid = $_SESSION['learner_id'];
$message  = '';
$msg_type = 'success';

// Submit review
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enrolmentID = (int)$_POST['enrolmentID'];
    $courseID    = (int)$_POST['courseID'];
    $rating      = (int)$_POST['rating'];
    $feedback    = mysqli_real_escape_string($conn, trim($_POST['feedback']));

    if ($rating < 1 || $rating > 5) {
        $message  = 'Please select a rating between 1 and 5 stars.';
        $msg_type = 'danger';
    } elseif (empty($feedback)) {
        $message  = 'Please write some feedback.';
        $msg_type = 'danger';
    } else {
        // Verify this enrolment belongs to this learner and is COMPLETED
        $check = mysqli_query($conn, "SELECT * FROM enrolments WHERE enrolmentID=$enrolmentID AND learnerID=$lid AND completion_status='COMPLETED' AND payment_status='PAID'");
        if (mysqli_num_rows($check) === 0) {
            $message  = 'You can only review completed courses.';
            $msg_type = 'danger';
        } else {
            $sql = "INSERT INTO reviews (enrolmentID, learnerID, courseID, rating, feedback)
                    VALUES ($enrolmentID, $lid, $courseID, $rating, '$feedback')
                    ON DUPLICATE KEY UPDATE rating=$rating, feedback='$feedback'";
            if (mysqli_query($conn, $sql)) {
                $message = 'Your review has been submitted. Thank you!';
            } else {
                $message  = 'Something went wrong. Please try again.';
                $msg_type = 'danger';
            }
        }
    }
}

// Load specific enrolment for review (from dashboard link)
$review_target = null;
if (isset($_GET['enrolmentID'])) {
    $eid = (int)$_GET['enrolmentID'];
    $res = mysqli_query($conn, "SELECT e.*, c.title, c.courseID, tp.org_name, r.rating AS existing_rating, r.feedback AS existing_feedback
        FROM enrolments e
        JOIN courses c ON e.courseID=c.courseID
        JOIN training_providers tp ON c.providerID=tp.providerID
        LEFT JOIN reviews r ON r.enrolmentID=e.enrolmentID
        WHERE e.enrolmentID=$eid AND e.learnerID=$lid AND e.completion_status='COMPLETED' AND e.payment_status='PAID'");
    $review_target = mysqli_fetch_assoc($res);
}

// Load all completed courses available for review
$completed = mysqli_query($conn, "SELECT e.*, c.title, c.courseID, tp.org_name, r.rating, r.feedback
    FROM enrolments e
    JOIN courses c ON e.courseID=c.courseID
    JOIN training_providers tp ON c.providerID=tp.providerID
    LEFT JOIN reviews r ON r.enrolmentID=e.enrolmentID
    WHERE e.learnerID=$lid AND e.completion_status='COMPLETED' AND e.payment_status='PAID'
    ORDER BY e.enrolment_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Reviews - EduSkill</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/shared.css" rel="stylesheet">
    <link href="../css/Student3.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php"><i class="fas fa-graduation-cap me-2"></i>EduSkill Marketplace</a>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">My Courses</a>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <h4 class="fw-bold mb-4">Course Reviews</h4>

    <?php if ($message): ?>
        <div class="alert alert-<?= $msg_type ?>"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($review_target): ?>
    <!-- Review Form for specific course -->
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="form-card">
                <h5 class="fw-bold mb-1"><?= htmlspecialchars($review_target['title']) ?></h5>
                <p class="text-muted small mb-3"><i class="fas fa-building me-1"></i><?= htmlspecialchars($review_target['org_name']) ?></p>

                <?php if ($review_target['existing_rating']): ?>
                    <div class="alert alert-info">You already reviewed this course. You can update your review below.</div>
                <?php endif; ?>

                <form method="POST" id="reviewForm" novalidate>
                    <input type="hidden" name="enrolmentID" value="<?= $review_target['enrolmentID'] ?>">
                    <input type="hidden" name="courseID" value="<?= $review_target['courseID'] ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Your Rating <span class="text-danger">*</span></label>
                        <div class="star-rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>"
                                    <?= ($review_target['existing_rating'] == $i) ? 'checked' : '' ?> required>
                                <label for="star<?= $i ?>"><i class="fas fa-star"></i></label>
                            <?php endfor; ?>
                        </div>
                        <div id="ratingText" class="small text-muted mt-1"></div>
                        <div class="invalid-feedback d-block" id="ratingError"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Written Feedback <span class="text-danger">*</span></label>
                        <textarea name="feedback" id="feedbackText" class="form-control" rows="4"
                                  placeholder="Share your experience with this course..."
                                  maxlength="500" required><?= $review_target['existing_feedback'] ? htmlspecialchars($review_target['existing_feedback']) : '' ?></textarea>
                        <div class="form-text"><span id="charCount">0</span>/500 characters</div>
                    </div>

                    <button type="submit" class="btn btn-warning w-100 py-2 fw-bold">
                        <i class="fas fa-star me-2"></i>Submit Review
                    </button>
                </form>
                <a href="reviews.php" class="d-block text-center text-muted small mt-3">View all reviews</a>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- All completed courses -->
    <?php if (mysqli_num_rows($completed) > 0): ?>
        <div class="row">
            <?php while ($row = mysqli_fetch_assoc($completed)): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold"><?= htmlspecialchars($row['title']) ?></h6>
                        <p class="text-muted small mb-2"><i class="fas fa-building me-1"></i><?= htmlspecialchars($row['org_name']) ?></p>
                        <?php if ($row['rating']): ?>
                            <div class="mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fa<?= $i <= $row['rating'] ? 's' : 'r' ?> fa-star text-warning"></i>
                                <?php endfor; ?>
                                <small class="text-muted ms-1"><?= $row['rating'] ?>/5</small>
                            </div>
                            <p class="small text-muted"><?= htmlspecialchars(substr($row['feedback'], 0, 80)) ?>...</p>
                            <a href="reviews.php?enrolmentID=<?= $row['enrolmentID'] ?>" class="btn btn-sm btn-outline-warning">
                                <i class="fas fa-edit me-1"></i>Edit Review
                            </a>
                        <?php else: ?>
                            <p class="text-muted small">You haven't reviewed this course yet.</p>
                            <a href="reviews.php?enrolmentID=<?= $row['enrolmentID'] ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-star me-1"></i>Write Review
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-star fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">No completed courses to review yet.</h5>
            <a href="dashboard.php" class="btn btn-primary mt-2">Go to My Courses</a>
        </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/shared.js"></script>
<script src="../js/Student3.js"></script>
<script>
const ratingLabels = { 1:'Poor', 2:'Fair', 3:'Good', 4:'Very Good', 5:'Excellent' };
document.querySelectorAll('input[name="rating"]').forEach(r => {
    r.addEventListener('change', function() {
        document.getElementById('ratingText').textContent = ratingLabels[this.value] + ' (' + this.value + '/5)';
    });
});
// Set initial label if pre-selected
const checked = document.querySelector('input[name="rating"]:checked');
if (checked) document.getElementById('ratingText') && (document.getElementById('ratingText').textContent = ratingLabels[checked.value] + ' (' + checked.value + '/5)');

// Char counter
const fb = document.getElementById('feedbackText');
const cc = document.getElementById('charCount');
if (fb && cc) {
    cc.textContent = fb.value.length;
    fb.addEventListener('input', () => cc.textContent = fb.value.length);
}

// Validation
document.getElementById('reviewForm') && document.getElementById('reviewForm').addEventListener('submit', function(e) {
    let valid = true;
    const rating = document.querySelector('input[name="rating"]:checked');
    if (!rating) {
        document.getElementById('ratingError').textContent = 'Please select a star rating.';
        valid = false;
    }
    const feedback = document.getElementById('feedbackText');
    if (feedback && feedback.value.trim().length < 10) {
        feedback.classList.add('is-invalid');
        valid = false;
    }
    if (!valid) e.preventDefault();
});
</script>
</body>
</html>
