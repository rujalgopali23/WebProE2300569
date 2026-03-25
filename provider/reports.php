<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$conn = mysqli_connect("localhost","root","","ems_db");
mysqli_set_charset($conn,"utf8");
if (!isset($_SESSION['provider_id'])) { header("Location: ../login.php"); exit; }

$pid  = $_SESSION['provider_id'];
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Monthly enrolment data for chart
$monthly = [];
for ($m = 1; $m <= 12; $m++) {
    $res = mysqli_query($conn, "SELECT COUNT(*) AS c, COALESCE(SUM(e.amount_paid),0) AS rev
        FROM enrolments e JOIN courses c ON e.courseID=c.courseID
        WHERE c.providerID=$pid AND e.payment_status='PAID'
        AND YEAR(e.enrolment_date)=$year AND MONTH(e.enrolment_date)=$m");
    $monthly[$m] = mysqli_fetch_assoc($res);
}

// Yearly summary
$yearly = mysqli_query($conn, "SELECT YEAR(e.enrolment_date) AS yr,
    COUNT(*) AS total_enrol, COALESCE(SUM(e.amount_paid),0) AS revenue
    FROM enrolments e JOIN courses c ON e.courseID=c.courseID
    WHERE c.providerID=$pid AND e.payment_status='PAID'
    GROUP BY yr ORDER BY yr DESC");

// Top courses
$top_courses = mysqli_query($conn, "SELECT c.title, COUNT(e.enrolmentID) AS enrol_count,
    COALESCE(SUM(e.amount_paid),0) AS revenue
    FROM courses c LEFT JOIN enrolments e ON c.courseID=e.courseID AND e.payment_status='PAID'
    WHERE c.providerID=$pid
    GROUP BY c.courseID ORDER BY enrol_count DESC LIMIT 5");

$months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$enrol_data   = array_column($monthly, 'c');
$revenue_data = array_column($monthly, 'rev');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - EduSkill</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/shared.css" rel="stylesheet">
    <link href="../css/Student2.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="d-flex">
    <div class="sidebar" style="width:240px; min-width:240px;">
        <div class="text-white text-center py-3 px-3 mb-2">
            <i class="fas fa-building fa-2x mb-1"></i>
            <div class="fw-bold small"><?= htmlspecialchars($_SESSION['provider_name']) ?></div>
            <small class="text-muted">Training Provider</small>
        </div>
        <div class="nav-header">Navigation</div>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
        <a href="manage_courses.php"><i class="fas fa-book me-2"></i>Manage Courses</a>
        <a href="reports.php" class="active"><i class="fas fa-chart-bar me-2"></i>Reports</a>
        <div class="nav-header mt-3">Account</div>
        <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
    </div>

    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Enrolment Reports</h4>
            <!-- Year filter -->
            <form method="GET" class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 fw-semibold">Year:</label>
                <select name="year" class="form-select form-select-sm" style="width:100px" onchange="this.form.submit()">
                    <?php for ($y = date('Y'); $y >= date('Y') - 4; $y--): ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>

        <!-- Monthly Chart -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-chart-line me-2 text-primary"></i>Monthly Enrolments — <?= $year ?>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" height="100"></canvas>
            </div>
        </div>

        <div class="row">
            <!-- Top Courses -->
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-trophy me-2 text-warning"></i>Top Courses by Enrolment
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Course</th><th>Enrolled</th><th>Revenue</th></tr></thead>
                            <tbody>
                                <?php if (mysqli_num_rows($top_courses) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($top_courses)): ?>
                                    <tr>
                                        <td class="small"><?= htmlspecialchars($row['title']) ?></td>
                                        <td><?= $row['enrol_count'] ?></td>
                                        <td>RM <?= number_format($row['revenue'], 2) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center text-muted py-3">No data yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Yearly Summary -->
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-calendar me-2 text-info"></i>Yearly Summary
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Year</th><th>Total Enrolments</th><th>Revenue</th></tr></thead>
                            <tbody>
                                <?php if (mysqli_num_rows($yearly) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($yearly)): ?>
                                    <tr>
                                        <td><?= $row['yr'] ?></td>
                                        <td><?= $row['total_enrol'] ?></td>
                                        <td>RM <?= number_format($row['revenue'], 2) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center text-muted py-3">No data yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly detail table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-table me-2 text-secondary"></i>Monthly Detail — <?= $year ?>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Month</th><th>Enrolments</th><th>Revenue (RM)</th></tr></thead>
                    <tbody>
                        <?php foreach ($monthly as $m => $data): ?>
                        <tr>
                            <td><?= $months[$m - 1] ?> <?= $year ?></td>
                            <td><?= $data['c'] ?></td>
                            <td><?= number_format($data['rev'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/Student2.js"></script>
<script>
const ctx = document.getElementById('monthlyChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [
            {
                label: 'Enrolments',
                data: <?= json_encode(array_values($enrol_data)) ?>,
                backgroundColor: 'rgba(13, 110, 253, 0.7)',
                borderRadius: 4,
                yAxisID: 'y'
            },
            {
                label: 'Revenue (RM)',
                data: <?= json_encode(array_values($revenue_data)) ?>,
                type: 'line',
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255,193,7,0.1)',
                fill: true,
                tension: 0.4,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        scales: {
            y:  { type: 'linear', position: 'left',  title: { display: true, text: 'Enrolments' } },
            y1: { type: 'linear', position: 'right', title: { display: true, text: 'Revenue (RM)' }, grid: { drawOnChartArea: false } }
        }
    }
});
</script>
</body>
</html>
