<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$conn = mysqli_connect("localhost","root","","ems_db");
mysqli_set_charset($conn,"utf8");
if (!isset($_SESSION['learner_id'])) {
    header("Location: ../login.php?redirect=learner/enrol.php?courseID=" . (int)$_GET['courseID']);
    exit;
}

$lid = $_SESSION['learner_id'];
$courseID = isset($_GET['courseID']) ? (int)$_GET['courseID'] : 0;

// Load course
$res    = mysqli_query($conn, "SELECT c.*, tp.org_name FROM courses c JOIN training_providers tp ON c.providerID=tp.providerID WHERE c.courseID=$courseID AND c.status='ACTIVE' AND tp.status='APPROVED'");
$course = mysqli_fetch_assoc($res);
if (!$course) { header("Location: ../index.php"); exit; }

// Check if already enrolled
$already = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM enrolments WHERE learnerID=$lid AND courseID=$courseID AND payment_status='PAID'"));

$error   = '';
$success = false;
$receipt_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already) {
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $card_name      = mysqli_real_escape_string($conn, trim($_POST['card_name'] ?? ''));

    if (empty($payment_method)) {
        $error = 'Please select a payment method.';
    } elseif (in_array($payment_method, ['credit_card','debit_card']) && empty($card_name)) {
        $error = 'Please enter the cardholder name.';
    } else {
        $payment_ref  = strtoupper('TXN' . date('YmdHis') . rand(100, 999));
        $receipt_num  = strtoupper('RCP' . date('Ymd') . rand(1000, 9999));
        $amount       = $course['price'];

        $sql = "INSERT INTO enrolments (learnerID, courseID, payment_status, payment_method, payment_reference, amount_paid, receipt_number)
                VALUES ($lid, $courseID, 'PAID', '$payment_method', '$payment_ref', $amount, '$receipt_num')";
        if (mysqli_query($conn, $sql)) {
            $receipt_id = mysqli_insert_id($conn);
            $success    = true;
        } else {
            $error = 'Enrolment failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrol - <?= htmlspecialchars($course['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/shared.css" rel="stylesheet">
    <link href="../css/E2300569.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php"><i class="fas fa-graduation-cap me-2"></i>EduSkill Marketplace</a>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">My Courses</a>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <?php if ($success): ?>
    <!-- Success -->
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="form-card py-5">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h4 class="fw-bold">Enrolment Successful!</h4>
                <p class="text-muted">You are now enrolled in <strong><?= htmlspecialchars($course['title']) ?></strong></p>
                <div class="d-flex gap-2 justify-content-center mt-3">
                    <a href="receipt.php?enrolmentID=<?= $receipt_id ?>" class="btn btn-primary">
                        <i class="fas fa-receipt me-2"></i>View Receipt
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-primary">My Courses</a>
                </div>
            </div>
        </div>
    </div>

    <?php elseif ($already): ?>
    <!-- Already enrolled -->
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="form-card py-5">
                <i class="fas fa-info-circle fa-4x text-info mb-3"></i>
                <h5>You are already enrolled in this course.</h5>
                <a href="dashboard.php" class="btn btn-primary mt-3">Go to My Courses</a>
            </div>
        </div>
    </div>

    <?php else: ?>
    <div class="row justify-content-center">
        <div class="col-md-7">
            <!-- Course Summary -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="fw-bold"><?= htmlspecialchars($course['title']) ?></h5>
                    <p class="text-muted small mb-1"><i class="fas fa-building me-1"></i><?= htmlspecialchars($course['org_name']) ?></p>
                    <p class="text-muted small mb-1"><i class="fas fa-clock me-1"></i><?= $course['duration_hours'] ?> hours &nbsp;|&nbsp; <i class="fas fa-tag me-1"></i><?= $course['category'] ?></p>
                    <p class="small mb-0"><?= htmlspecialchars(substr($course['description'], 0, 200)) ?>...</p>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <span class="fw-bold fs-5 text-primary">RM <?= number_format($course['price'], 2) ?></span>
                    <span class="text-muted small"><?= $course['seats_available'] ?> seats available</span>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="form-card">
                <h5 class="fw-bold mb-4"><i class="fas fa-credit-card me-2 text-primary"></i>Payment Details</h5>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" id="enrolForm" novalidate>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                        <div class="row g-2" id="paymentMethods">
                            <?php
                            $methods = [
                                'credit_card'  => ['fa-credit-card', 'Credit Card'],
                                'debit_card'   => ['fa-wallet', 'Debit Card'],
                                'online_banking' => ['fa-university', 'Online Banking'],
                                'ewallet'      => ['fa-mobile-alt', 'E-Wallet']
                            ];
                            foreach ($methods as $val => [$icon, $label]):
                            ?>
                            <div class="col-6">
                                <div class="border rounded p-3 text-center payment-option" data-value="<?= $val ?>">
                                    <i class="fas <?= $icon ?> fa-2x text-primary mb-1"></i>
                                    <div class="small fw-semibold"><?= $label ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="payment_method" id="paymentMethodInput" required>
                        <div class="invalid-feedback d-block" id="methodError"></div>
                    </div>

                    <!-- Card fields (shown only for card payments) -->
                    <div id="cardFields" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label">Cardholder Name</label>
                            <input type="text" name="card_name" class="form-control" placeholder="Name on card">
                        </div>
                        <div class="row">
                            <div class="col-8 mb-3">
                                <label class="form-label">Card Number</label>
                                <input type="text" id="cardNumber" class="form-control" placeholder="•••• •••• •••• ••••" maxlength="19">
                            </div>
                            <div class="col-4 mb-3">
                                <label class="form-label">CVV</label>
                                <input type="text" id="cvv" class="form-control" placeholder="•••" maxlength="3">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="text" id="expiry" class="form-control" placeholder="MM/YY" maxlength="5">
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="bg-light rounded p-3 mb-4">
                        <div class="d-flex justify-content-between">
                            <span>Course Fee</span>
                            <span>RM <?= number_format($course['price'], 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Processing Fee</span>
                            <span>RM 0.00</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total</span>
                            <span class="text-primary">RM <?= number_format($course['price'], 2) ?></span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                        <i class="fas fa-lock me-2"></i>Pay & Enrol — RM <?= number_format($course['price'], 2) ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/shared.js"></script>
<script src="../js/E2300569.js"></script>
<script>
// Payment method selection
document.querySelectorAll('.payment-option').forEach(opt => {
    opt.addEventListener('click', function() {
        document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('border-primary', 'bg-light'));
        this.classList.add('border-primary', 'bg-light');
        const val = this.dataset.value;
        document.getElementById('paymentMethodInput').value = val;
        document.getElementById('methodError').textContent = '';
        // Show card fields for card payments
        document.getElementById('cardFields').style.display =
            (val === 'credit_card' || val === 'debit_card') ? 'block' : 'none';
    });
});

// Card number formatting
const cardInput = document.getElementById('cardNumber');
if (cardInput) {
    cardInput.addEventListener('input', function() {
        let val = this.value.replace(/\D/g, '').substring(0, 16);
        this.value = val.match(/.{1,4}/g)?.join(' ') || val;
    });
}

// Expiry formatting
const expiryInput = document.getElementById('expiry');
if (expiryInput) {
    expiryInput.addEventListener('input', function() {
        let val = this.value.replace(/\D/g, '');
        if (val.length >= 2) val = val.substring(0,2) + '/' + val.substring(2,4);
        this.value = val;
    });
}

// Form validation
document.getElementById('enrolForm') && document.getElementById('enrolForm').addEventListener('submit', function(e) {
    const method = document.getElementById('paymentMethodInput').value;
    if (!method) {
        e.preventDefault();
        document.getElementById('methodError').textContent = 'Please select a payment method.';
    }
});
</script>
</body>
</html>
