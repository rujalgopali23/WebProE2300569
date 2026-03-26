<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$conn = mysqli_connect("localhost","root","","ems_db");
mysqli_set_charset($conn,"utf8");
if (!isset($_SESSION['officer_id'])) { header("Location: ../login.php"); exit; }

$message = '';
$msg_type = 'success';

// Handle Approve / Reject action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $providerID = (int)$_POST['providerID'];
    $action     = $_POST['action'];

    if ($action === 'approve') {
        mysqli_query($conn, "UPDATE training_providers SET status='APPROVED' WHERE providerID=$providerID");
        $message  = 'Training provider has been approved successfully.';
    } elseif ($action === 'reject') {
        $reason = mysqli_real_escape_string($conn, trim($_POST['rejection_reason']));
        mysqli_query($conn, "UPDATE training_providers SET status='REJECTED', rejection_reason='$reason' WHERE providerID=$providerID");
        $message  = 'Training provider has been rejected.';
        $msg_type = 'warning';
    }
}

// View single provider detail
$view_provider = null;
if (isset($_GET['view'])) {
    $pid = (int)$_GET['view'];
    $res = mysqli_query($conn, "SELECT * FROM training_providers WHERE providerID=$pid");
    $view_provider = mysqli_fetch_assoc($res);
}

// Filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'PENDING';
$allowed_filters = ['PENDING', 'APPROVED', 'REJECTED', 'ALL'];
if (!in_array($filter, $allowed_filters)) $filter = 'PENDING';

$where = ($filter === 'ALL') ? '' : "WHERE status='$filter'";
$providers = mysqli_query($conn, "SELECT * FROM training_providers $where ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Providers - EduSkill</title>
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
        <a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
        <a href="manage_providers.php" class="active"><i class="fas fa-building me-2"></i>Manage Providers</a>
        <div class="nav-header mt-3">Account</div>
        <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
    </div>

    <!-- Main content -->
    <div class="flex-grow-1 p-4">
        <h4 class="fw-bold mb-4">Manage Training Providers</h4>

        <?php if ($message): ?>
            <div class="alert alert-<?= $msg_type ?>"><i class="fas fa-check-circle me-2"></i><?= $message ?></div>
        <?php endif; ?>

        <?php if ($view_provider): ?>
        <!-- ===== DETAIL VIEW ===== -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <a href="manage_providers.php" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i>Back to List
                </a>
                <strong>Provider Application Detail</strong>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Organisation:</strong> <?= htmlspecialchars($view_provider['org_name']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($view_provider['email']) ?></p>
                        <p><strong>Contact:</strong> <?= htmlspecialchars($view_provider['contact_number']) ?></p>
                        <p><strong>Applied On:</strong> <?= date('d M Y, h:i A', strtotime($view_provider['created_at'])) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong>
                            <span class="badge badge-<?= strtolower($view_provider['status']) ?>">
                                <?= $view_provider['status'] ?>
                            </span>
                        </p>
                        <p><strong>Supporting Document:</strong>
                            <?php if ($view_provider['document_path']): ?>
                                <a href="../<?= htmlspecialchars($view_provider['document_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-file me-1"></i>View Document
                                </a>
                            <?php else: ?>
                                <span class="text-muted">Not uploaded</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <p><strong>Organisation Profile:</strong></p>
                <div class="bg-light rounded p-3 mb-4"><?= nl2br(htmlspecialchars($view_provider['org_profile'])) ?></div>

                <?php if ($view_provider['status'] === 'REJECTED' && $view_provider['rejection_reason']): ?>
                    <div class="alert alert-warning">
                        <strong>Rejection Reason:</strong> <?= htmlspecialchars($view_provider['rejection_reason']) ?>
                    </div>
                <?php endif; ?>

                <?php if ($view_provider['status'] === 'PENDING'): ?>
                <div class="d-flex gap-3">
                    <!-- Approve -->
                    <form method="POST">
                        <input type="hidden" name="providerID" value="<?= $view_provider['providerID'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-success"
                                onclick="return confirm('Approve this provider?')">
                            <i class="fas fa-check me-2"></i>Approve
                        </button>
                    </form>
                    <!-- Reject -->
                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="fas fa-times me-2"></i>Reject
                    </button>
                </div>

                <!-- Reject Modal -->
                <div class="modal fade" id="rejectModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Reject Application</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="providerID" value="<?= $view_provider['providerID'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                                    <textarea name="rejection_reason" class="form-control" rows="4"
                                              placeholder="Provide a clear reason for rejection..." required></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger">Confirm Reject</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php else: ?>
        <!-- ===== LIST VIEW ===== -->

        <!-- Filter tabs -->
        <ul class="nav nav-tabs mb-4">
            <?php foreach (['PENDING' => 'warning', 'APPROVED' => 'success', 'REJECTED' => 'danger', 'ALL' => 'secondary'] as $f => $color): ?>
            <li class="nav-item">
                <a class="nav-link <?= $filter === $f ? 'active' : '' ?>" href="manage_providers.php?filter=<?= $f ?>">
                    <?= ucfirst(strtolower($f)) ?>
                    <?php
                        $cnt = mysqli_fetch_assoc(mysqli_query($conn,
                            $f === 'ALL'
                                ? "SELECT COUNT(*) AS c FROM training_providers"
                                : "SELECT COUNT(*) AS c FROM training_providers WHERE status='$f'"
                        ))['c'];
                        echo "<span class='badge bg-$color ms-1'>$cnt</span>";
                    ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Organisation</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Applied On</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if (mysqli_num_rows($providers) > 0):
                            while ($row = mysqli_fetch_assoc($providers)):
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['org_name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['contact_number']) ?></td>
                            <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <span class="badge badge-<?= strtolower($row['status']) ?>">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                            <td>
                                <a href="manage_providers.php?view=<?= $row['providerID'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye me-1"></i>Review
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/shared.js"></script>
</body>
</html>
