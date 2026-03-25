<?php
require_once 'config/db.php';

if (isset($_SESSION['officer_id']))  { header("Location: officer/dashboard.php");  exit; }
if (isset($_SESSION['provider_id'])) { header("Location: provider/dashboard.php"); exit; }
if (isset($_SESSION['learner_id']))  { header("Location: learner/dashboard.php");  exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if (empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill in all fields and select your role.';
    } else {
        $email_safe = mysqli_real_escape_string($conn, $email);

        if ($role === 'learner') {
            $res = mysqli_query($conn, "SELECT * FROM learners WHERE email = '$email_safe'");
            $row = mysqli_fetch_assoc($res);
            if ($row && password_verify($password, $row['password'])) {
                $_SESSION['learner_id']   = $row['learnerID'];
                $_SESSION['learner_name'] = $row['full_name'];
                header("Location: learner/dashboard.php");
                exit;
            }
            $error = 'Invalid email or password.';

        } elseif ($role === 'provider') {
            $res = mysqli_query($conn, "SELECT * FROM training_providers WHERE email = '$email_safe'");
            $row = mysqli_fetch_assoc($res);
            if ($row) {
                if (!password_verify($password, $row['password'])) {
                    $error = 'Invalid email or password.';
                } elseif ($row['status'] === 'PENDING') {
                    $error = 'Your account is still pending approval by the Ministry.';
                } elseif ($row['status'] === 'REJECTED') {
                    $error = 'Your registration was rejected. Contact the Ministry for details.';
                } else {
                    $_SESSION['provider_id']   = $row['providerID'];
                    $_SESSION['provider_name'] = $row['org_name'];
                    header("Location: provider/dashboard.php");
                    exit;
                }
            } else {
                $error = 'Invalid email or password.';
            }

        } elseif ($role === 'officer') {
            $res = mysqli_query($conn, "SELECT * FROM ministry_officers WHERE email = '$email_safe'");
            $row = mysqli_fetch_assoc($res);
            if ($row && password_verify($password, $row['password'])) {
                $_SESSION['officer_id']   = $row['officerID'];
                $_SESSION['officer_name'] = $row['name'];
                header("Location: officer/dashboard.php");
                exit;
            }
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EduSkill Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/shared.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="fas fa-graduation-cap me-2"></i>EduSkill Marketplace</a>
    </div>
</nav>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="form-card">
                <h4 class="text-center fw-bold mb-1">Welcome Back</h4>
                <p class="text-center text-muted mb-4">Sign in to your account</p>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST" id="loginForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">I am a:</label>
                        <div class="d-flex gap-2">
                            <div class="form-check flex-fill border rounded p-3 text-center role-option">
                                <input class="form-check-input" type="radio" name="role" id="roleLearner" value="learner" <?= (isset($_POST['role']) && $_POST['role']==='learner')?'checked':'' ?>>
                                <label class="form-check-label d-block" for="roleLearner">
                                    <i class="fas fa-user-graduate d-block fs-4 mb-1 text-primary"></i><strong>Learner</strong>
                                </label>
                            </div>
                            <div class="form-check flex-fill border rounded p-3 text-center role-option">
                                <input class="form-check-input" type="radio" name="role" id="roleProvider" value="provider" <?= (isset($_POST['role']) && $_POST['role']==='provider')?'checked':'' ?>>
                                <label class="form-check-label d-block" for="roleProvider">
                                    <i class="fas fa-building d-block fs-4 mb-1 text-success"></i><strong>Provider</strong>
                                </label>
                            </div>
                            <div class="form-check flex-fill border rounded p-3 text-center role-option">
                                <input class="form-check-input" type="radio" name="role" id="roleOfficer" value="officer" <?= (isset($_POST['role']) && $_POST['role']==='officer')?'checked':'' ?>>
                                <label class="form-check-label d-block" for="roleOfficer">
                                    <i class="fas fa-user-tie d-block fs-4 mb-1 text-danger"></i><strong>Officer</strong>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="text" name="email" class="form-control" placeholder="Enter your email"
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="passwordField" class="form-control" placeholder="Enter password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </form>
                <hr>
                <p class="text-center text-muted small mb-0">
                    New learner? <a href="learner_register.php">Register here</a><br>
                    Training provider? <a href="register_provider.php">Apply for account</a>
                </p>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('input[name="role"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.role-option').forEach(el => el.classList.remove('border-primary','bg-light'));
        this.closest('.role-option').classList.add('border-primary','bg-light');
    });
    if (radio.checked) radio.closest('.role-option').classList.add('border-primary','bg-light');
});
function togglePassword() {
    const f = document.getElementById('passwordField'), i = document.getElementById('eyeIcon');
    f.type = f.type==='password' ? 'text' : 'password';
    i.classList.toggle('fa-eye'); i.classList.toggle('fa-eye-slash');
}
document.getElementById('loginForm').addEventListener('submit', function(e) {
    if (!document.querySelector('input[name="role"]:checked')) { e.preventDefault(); alert('Please select your role.'); }
});
</script>
</body>
</html>
