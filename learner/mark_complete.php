<?php
require_once '../config/db.php';
if (!isset($_SESSION['learner_id'])) { header("Location: ../login.php"); exit; }

$lid = $_SESSION['learner_id'];
$eid = (int)$_GET['enrolmentID'];

mysqli_query($conn, "UPDATE enrolments SET completion_status='COMPLETED' WHERE enrolmentID=$eid AND learnerID=$lid AND payment_status='PAID'");
header("Location: ../learner/dashboard.php");
exit;
?>
