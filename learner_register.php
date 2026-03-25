<?php
require_once 'config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim(mysqli_real_escape_string($conn, $_POST['full_name']));
    $email     = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $phone     = trim(mysqli_real_escape_string($conn, $_POST['phone']));
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];

    if (empty($full_name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check duplicate email
        $check = mysqli_query($conn, "SELECT learnerID FROM learners WHERE email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = 'This email is already registered. Please login.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $sql = "INSERT INTO learners (full_name, email, password, phone)
                    VALUES ('$full_name', '$email', '$hashed', '$phone')";
            if (mysqli_query($conn, $sql)) {
                $success = 'Registration successful! You can now login.';
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learner Registration - EduSkill</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/shared.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="fas fa-graduation-cap me-2"></i>EduSkill Marketplace</a>
        <a href="login.php" class="btn btn-outline-light btn-sm">Login</a>
    </div>
</nav>
<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="form-card">
                <h4 class="fw-bold mb-1 text-center">Create Learner Account</h4>
                <p class="text-muted text-center mb-4">Join EduSkill and start learning today</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= $success ?>
                        <a href="login.php" class="btn btn-success btn-sm ms-2">Login Now</a>
                    </div>
                <?php endif; ?>

                <form method="POST" id="registerForm" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. John Cena"
                               value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>" required>
                        <div class="invalid-feedback">Please enter your full name.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="you@example.com"
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                        <div class="invalid-feedback">Please enter a valid email.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="e.g. 0123456789"
                               value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Min. 6 characters" required>
                        <div class="invalid-feedback">Password must be at least 6 characters.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter password" required>
                        <div class="invalid-feedback" id="confirmError">Passwords do not match.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                        <i class="fas fa-user-plus me-2"></i>Register
                    </button>
                </form>
                <p class="text-center text-muted small mt-3">Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/shared.js"></script>
<script>
document.getElementById('registerForm').addEventListener('submit', function(e) {
    let valid = true;
    const password = document.getElementById('password');
    const confirm  = document.getElementById('confirm_password');

    this.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) { field.classList.add('is-invalid'); valid = false; }
        else field.classList.remove('is-invalid');
    });

    if (password.value.length < 6) { password.classList.add('is-invalid'); valid = false; }
    if (password.value !== confirm.value) {
        confirm.classList.add('is-invalid');
        document.getElementById('confirmError').textContent = 'Passwords do not match.';
        valid = false;
    }
    if (!valid) e.preventDefault();
});
</script>
</body>
</html>
