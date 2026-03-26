<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$conn = mysqli_connect("localhost","root","","ems_db");
mysqli_set_charset($conn,"utf8");
if (!isset($_SESSION['learner_id'])) { header("Location: ../login.php"); exit; }

$lid        = $_SESSION['learner_id'];
$enrolmentID = (int)$_GET['enrolmentID'];

$res = mysqli_query($conn, "SELECT e.*, c.title, c.duration_hours, c.category, c.start_date,
    tp.org_name, tp.contact_number AS provider_contact, tp.email AS provider_email,
    l.full_name, l.email AS learner_email, l.phone
    FROM enrolments e
    JOIN courses c ON e.courseID=c.courseID
    JOIN training_providers tp ON c.providerID=tp.providerID
    JOIN learners l ON e.learnerID=l.learnerID
    WHERE e.enrolmentID=$enrolmentID AND e.learnerID=$lid AND e.payment_status='PAID'");

$data = mysqli_fetch_assoc($res);
if (!$data) { header("Location: dashboard.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrolment Receipt - EduSkill</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/shared.css" rel="stylesheet">
    <link href="../css/E2300569.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
        }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary no-print">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php"><i class="fas fa-graduation-cap me-2"></i>EduSkill Marketplace</a>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">My Courses</a>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <!-- Action buttons -->
            <div class="d-flex gap-2 mb-3 no-print">
                <button onclick="window.print()" class="btn btn-outline-primary">
                    <i class="fas fa-print me-2"></i>Print Receipt
                </button>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to My Courses
                </a>
            </div>

            <!-- Receipt Box -->
            <div class="receipt-box">
                <!-- Header -->
                <div class="text-center mb-4">
                    <i class="fas fa-graduation-cap fa-3x text-primary mb-2"></i>
                    <h4 class="fw-bold text-primary">EduSkill Marketplace</h4>
                    <p class="text-muted small mb-0">Ministry of Human Resources Malaysia</p>
                    <hr>
                    <h5 class="fw-bold mt-2">Official Enrolment Receipt</h5>
                    <span class="badge bg-success fs-6"><?= htmlspecialchars($data['receipt_number']) ?></span>
                </div>

                <!-- Learner Info -->
                <div class="row mb-3">
                    <div class="col-6">
                        <p class="mb-1 small text-muted">LEARNER</p>
                        <p class="fw-semibold mb-0"><?= htmlspecialchars($data['full_name']) ?></p>
                        <p class="small text-muted mb-0"><?= htmlspecialchars($data['learner_email']) ?></p>
                        <?php if ($data['phone']): ?>
                            <p class="small text-muted mb-0"><?= htmlspecialchars($data['phone']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-6 text-end">
                        <p class="mb-1 small text-muted">DATE ISSUED</p>
                        <p class="fw-semibold mb-0"><?= date('d F Y', strtotime($data['enrolment_date'])) ?></p>
                        <p class="small text-muted mb-0"><?= date('h:i A', strtotime($data['enrolment_date'])) ?></p>
                    </div>
                </div>

                <hr>

                <!-- Course Info -->
                <p class="mb-1 small text-muted text-uppercase fw-bold">Course Details</p>
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted small ps-0">Course Title</td>
                        <td class="fw-semibold text-end"><?= htmlspecialchars($data['title']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted small ps-0">Training Provider</td>
                        <td class="text-end"><?= htmlspecialchars($data['org_name']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted small ps-0">Category</td>
                        <td class="text-end"><?= htmlspecialchars($data['category']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted small ps-0">Duration</td>
                        <td class="text-end"><?= $data['duration_hours'] ?> hours</td>
                    </tr>
                    <?php if ($data['start_date']): ?>
                    <tr>
                        <td class="text-muted small ps-0">Start Date</td>
                        <td class="text-end"><?= date('d M Y', strtotime($data['start_date'])) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>

                <hr>

                <!-- Payment Info -->
                <p class="mb-1 small text-muted text-uppercase fw-bold">Payment Details</p>
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted small ps-0">Payment Method</td>
                        <td class="text-end text-capitalize"><?= str_replace('_', ' ', $data['payment_method']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted small ps-0">Transaction Reference</td>
                        <td class="text-end"><code><?= $data['payment_reference'] ?></code></td>
                    </tr>
                    <tr>
                        <td class="text-muted small ps-0">Status</td>
                        <td class="text-end"><span class="badge bg-success">PAID</span></td>
                    </tr>
                </table>

                <hr>

                <!-- Total -->
                <div class="d-flex justify-content-between align-items-center bg-primary text-white rounded p-3 mt-2">
                    <span class="fw-bold fs-5">TOTAL PAID</span>
                    <span class="fw-bold fs-4">RM <?= number_format($data['amount_paid'], 2) ?></span>
                </div>

                <p class="text-center text-muted small mt-4 mb-0">
                    Thank you for enrolling with EduSkill Marketplace.<br>
                    For enquiries, contact: <?= htmlspecialchars($data['provider_email']) ?>
                </p>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
