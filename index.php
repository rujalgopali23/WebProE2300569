<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$conn = mysqli_connect("localhost","root","","ems_db");
mysqli_set_charset($conn,"utf8");

// Fetch active courses with provider name
$sql = "SELECT c.*, tp.org_name, 
        COALESCE(AVG(r.rating), 0) AS avg_rating,
        COUNT(DISTINCT e.enrolmentID) AS total_enrolled
        FROM courses c
        JOIN training_providers tp ON c.providerID = tp.providerID
        LEFT JOIN enrolments e ON c.courseID = e.courseID AND e.payment_status = 'PAID'
        LEFT JOIN reviews r ON c.courseID = r.courseID
        WHERE c.status = 'ACTIVE' AND tp.status = 'APPROVED'
        GROUP BY c.courseID
        ORDER BY c.created_at DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduSkill Marketplace</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Shared CSS -->
    <link href="css/shared.css" rel="stylesheet">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="fas fa-graduation-cap me-2"></i>EduSkill Marketplace
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>

                <?php if (isset($_SESSION['learner_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="learner/dashboard.php">My Courses</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout (<?= htmlspecialchars($_SESSION['learner_name']) ?>)</a></li>
                <?php elseif (isset($_SESSION['provider_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="provider/dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php elseif (isset($_SESSION['officer_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="officer/dashboard.php">Officer Panel</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="learner_register.php">Register as Learner</a></li>
                    <li class="nav-item"><a class="nav-link" href="register_provider.php">Register as Provider</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- HERO SECTION -->
<div class="hero-section text-white text-center py-5" style="background: linear-gradient(135deg, #0d6efd, #0dcaf0);">
    <div class="container">
        <h1 class="display-5 fw-bold">Upskill Yourself Today</h1>
        <p class="lead">Browse certified courses from approved training providers across Malaysia</p>
        <!-- Search bar -->
        <div class="row justify-content-center mt-3">
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" id="searchInput" class="form-control form-control-lg" placeholder="Search courses...">
                    <button class="btn btn-warning fw-bold" onclick="searchCourses()">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- COURSE LISTING -->
<div class="container my-5">
    <h4 class="mb-4 fw-bold">Available Courses</h4>

    <!-- Filter buttons -->
    <div class="mb-4" id="filterButtons">
        <button class="btn btn-outline-primary btn-sm me-2 filter-btn active" data-category="all">All</button>
        <button class="btn btn-outline-primary btn-sm me-2 filter-btn" data-category="IT">IT & Technology</button>
        <button class="btn btn-outline-primary btn-sm me-2 filter-btn" data-category="Business">Business</button>
        <button class="btn btn-outline-primary btn-sm me-2 filter-btn" data-category="Design">Design</button>
        <button class="btn btn-outline-primary btn-sm me-2 filter-btn" data-category="Language">Language</button>
        <button class="btn btn-outline-primary btn-sm me-2 filter-btn" data-category="Other">Other</button>
    </div>

    <div class="row" id="courseGrid">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($course = mysqli_fetch_assoc($result)): ?>
            <div class="col-md-4 mb-4 course-card-wrapper" data-category="<?= htmlspecialchars($course['category']) ?>">
                <div class="card h-100 shadow-sm course-card">
                    <div class="card-body">
                        <span class="badge bg-info text-dark mb-2"><?= htmlspecialchars($course['category']) ?></span>
                        <h5 class="card-title fw-bold"><?= htmlspecialchars($course['title']) ?></h5>
                        <p class="text-muted small"><i class="fas fa-building me-1"></i><?= htmlspecialchars($course['org_name']) ?></p>
                        <p class="card-text small"><?= htmlspecialchars(substr($course['description'], 0, 100)) ?>...</p>

                        <!-- Star rating display -->
                        <div class="mb-2">
                            <?php
                            $rating = round($course['avg_rating']);
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $rating
                                    ? '<i class="fas fa-star text-warning"></i>'
                                    : '<i class="far fa-star text-warning"></i>';
                            }
                            ?>
                            <small class="text-muted ms-1">(<?= number_format($course['avg_rating'], 1) ?>)</small>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="fw-bold text-primary fs-5">RM <?= number_format($course['price'], 2) ?></span>
                            <small class="text-muted"><i class="fas fa-clock me-1"></i><?= $course['duration_hours'] ?> hrs</small>
                        </div>
                        <small class="text-muted"><i class="fas fa-users me-1"></i><?= $course['total_enrolled'] ?> enrolled</small>
                    </div>
                    <div class="card-footer bg-white border-0">
                        <a href="learner/enrol.php?courseID=<?= $course['courseID'] ?>" class="btn btn-primary w-100">
                            Enrol Now
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                <p class="text-muted">No courses available yet. Check back soon!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- FOOTER -->
<footer class="bg-dark text-white text-center py-3 mt-5">
    <p class="mb-0">&copy; <?= date('Y') ?> EduSkill Marketplace System. Ministry of Human Resources Malaysia.</p>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Shared JS -->
<script src="js/shared.js"></script>
</body>
</html>