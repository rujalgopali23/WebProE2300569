<?php
require_once 'config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $org_name       = trim(mysqli_real_escape_string($conn, $_POST['org_name']));
    $contact_number = trim(mysqli_real_escape_string($conn, $_POST['contact_number']));
    $email          = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $org_profile    = trim(mysqli_real_escape_string($conn, $_POST['org_profile']));
    $password       = $_POST['password'];
    $confirm        = $_POST['confirm_password'];

    if (empty($org_name) || empty($contact_number) || empty($email) || empty($org_profile) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!isset($_FILES['document']) || $_FILES['document']['error'] === 4) {
        $error = 'Please upload a supporting document.';
    } else {
        // Check duplicate email
        $check = mysqli_query($conn, "SELECT providerID FROM training_providers WHERE email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = 'This email is already registered.';
        } else {
            // Handle file upload
            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
            $file    = $_FILES['document'];
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $error = 'Only PDF, JPG, JPEG, PNG files are allowed.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = 'File size must not exceed 5MB.';
            } else {
                $upload_dir  = '../uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $filename    = 'doc_' . time() . '_' . uniqid() . '.' . $ext;
                $destination = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $doc_path = 'uploads/' . $filename;
                    $hashed   = password_hash($password, PASSWORD_BCRYPT);
                    $sql = "INSERT INTO training_providers (org_name, contact_number, email, org_profile, password, document_path, status)
                            VALUES ('$org_name', '$contact_number', '$email', '$org_profile', '$hashed', '$doc_path', 'PENDING')";
                    if (mysqli_query($conn, $sql)) {
                        $success = true;
                    } else {
                        $error = 'Database error. Please try again.';
                    }
                } else {
                    $error = 'Failed to upload document. Check that the uploads/ folder exists.';
                }
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
    <title>Register as Training Provider - EduSkill</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="css/shared.css" rel="stylesheet">
    <link href="css/E2300569.css" rel="stylesheet">
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
        <div class="col-md-7">

            <?php if ($success): ?>
            <!-- Success State -->
            <div class="form-card text-center py-5">
                <i class="fas fa-clock fa-4x text-warning mb-3"></i>
                <h4 class="fw-bold">Registration Submitted!</h4>
                <p class="text-muted">Your application is now <span class="badge bg-warning text-dark">PENDING</span> review by the Ministry of Human Resources.</p>
                <p class="text-muted">You will be notified once your account is approved. This may take 1–3 business days.</p>
                <a href="index.php" class="btn btn-primary mt-2">Back to Homepage</a>
            </div>
            <?php else: ?>

            <div class="form-card">
                <h4 class="fw-bold mb-1 text-center">Training Provider Registration</h4>
                <p class="text-muted text-center mb-4">Register your organisation to list courses on EduSkill</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="providerForm" novalidate>

                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-building me-2"></i>Organisation Details</h6>

                    <div class="mb-3">
                        <label class="form-label">Organisation Name <span class="text-danger">*</span></label>
                        <input type="text" name="org_name" class="form-control" placeholder="e.g. TechAcademy Sdn Bhd"
                               value="<?= isset($_POST['org_name']) ? htmlspecialchars($_POST['org_name']) : '' ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="text" name="contact_number" class="form-control" placeholder="e.g. 0312345678"
                                   value="<?= isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : '' ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="admin@yourorg.com"
                                   value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Organisation Profile <span class="text-danger">*</span></label>
                        <textarea name="org_profile" class="form-control" rows="3"
                                  placeholder="Describe your organisation, specialisation, and experience..." required><?= isset($_POST['org_profile']) ? htmlspecialchars($_POST['org_profile']) : '' ?></textarea>
                        <div class="form-text">Min. 50 characters. Tell the Ministry about your organisation.</div>
                    </div>

                    <hr>
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-file-upload me-2"></i>Supporting Documents</h6>

                    <div class="mb-3">
                        <label class="form-label">Upload Document <span class="text-danger">*</span></label>
                        <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                        <div class="form-text">Upload company registration, SSM certificate, or accreditation letter. Max 5MB. (PDF, JPG, PNG)</div>
                    </div>

                    <hr>
                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-lock me-2"></i>Account Credentials</h6>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Min. 6 characters" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter password" required>
                            <div class="invalid-feedback" id="confirmError">Passwords do not match.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mt-2">
                        <i class="fas fa-paper-plane me-2"></i>Submit Registration
                    </button>
                </form>
                <p class="text-center text-muted small mt-3">Already registered? <a href="login.php">Login here</a></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/shared.js"></script>
<script src="js/E2300569.js"></script>
<script>
document.getElementById('providerForm') && document.getElementById('providerForm').addEventListener('submit', function(e) {
    let valid = true;
    this.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) { field.classList.add('is-invalid'); valid = false; }
        else field.classList.remove('is-invalid');
    });
    const pw  = document.getElementById('password');
    const cpw = document.getElementById('confirm_password');
    if (pw && cpw && pw.value !== cpw.value) {
        cpw.classList.add('is-invalid');
        valid = false;
    }
    const profile = document.querySelector('[name="org_profile"]');
    if (profile && profile.value.trim().length < 50) {
        profile.classList.add('is-invalid');
        valid = false;
    }
    if (!valid) e.preventDefault();
});
</script>
</body>
</html>
