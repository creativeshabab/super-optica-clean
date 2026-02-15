<?php require_once 'header.php'; ?>

<?php
// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    setFlash('success', 'Appointment deleted');
    redirect('appointments.php');
}

// Handle Status Update
if (isset($_GET['status']) && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->execute([$_GET['status'], $_GET['id']]);
    setFlash('success', 'Status updated');
    redirect('appointments.php');
}

// Filter
$filter = $_GET['filter'] ?? 'upcoming';
$where = "WHERE 1=1";

if ($filter === 'upcoming') {
    $where .= " AND appointment_date >= CURDATE() AND status != 'cancelled'";
} elseif ($filter === 'today') {
    $where .= " AND appointment_date = CURDATE()";
}

$appointments = $pdo->query("SELECT * FROM appointments $where ORDER BY appointment_date ASC, time_slot ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
    <h1 class="admin-title" style="margin-bottom: 0;">Appointment Manager</h1>
    <a href="../book_appointment.php" target="_blank" class="btn btn-secondary"><i class="fa-solid fa-eye"></i> View Booking Page</a>
</div>

<div class="card mb-4">
    <div style="display: flex; gap: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; margin-bottom: 1rem;">
        <a href="appointments.php?filter=upcoming" class="btn btn-sm <?= $filter == 'upcoming' ? 'btn-primary' : 'btn-secondary' ?>">Upcoming</a>
        <a href="appointments.php?filter=today" class="btn btn-sm <?= $filter == 'today' ? 'btn-primary' : 'btn-secondary' ?>">Today</a>
        <a href="appointments.php?filter=all" class="btn btn-sm <?= $filter == 'all' ? 'btn-primary' : 'btn-secondary' ?>">All History</a>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time Slot</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($appointments)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No appointments found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($appointments as $app): ?>
                    <tr>
                        <td style="font-weight: 500;">
                            <?= date('M d, Y', strtotime($app['appointment_date'])) ?>
                            <?php if ($app['appointment_date'] == date('Y-m-d')) echo '<span class="badge badge-success" style="margin-left:5px;">Today</span>'; ?>
                        </td>
                        <td><?= htmlspecialchars($app['time_slot']) ?></td>
                        <td><?= htmlspecialchars($app['name']) ?></td>
                        <td><?= htmlspecialchars($app['phone']) ?></td>
                        <td><?= htmlspecialchars($app['email']) ?></td>
                        <td>
                            <?php
                            $statusColors = [
                                'pending' => 'warning',
                                'confirmed' => 'primary',
                                'completed' => 'success',
                                'cancelled' => 'danger'
                            ];
                            $color = $statusColors[$app['status']] ?? 'secondary';
                            ?>
                            <span class="badge badge-<?= $color ?>"><?= ucfirst($app['status']) ?></span>
                        </td>
                        <td>
                            <?php if ($app['status'] !== 'completed' && $app['status'] !== 'cancelled'): ?>
                                <a href="appointments.php?id=<?= $app['id'] ?>&status=completed" class="btn-icon" title="Mark Completed" style="color: var(--success);"><i class="fa-solid fa-check"></i></a>
                                <a href="appointments.php?id=<?= $app['id'] ?>&status=cancelled" class="btn-icon" title="Cancel" style="color: var(--danger);"><i class="fa-solid fa-xmark"></i></a>
                            <?php endif; ?>
                            <a href="appointments.php?delete=<?= $app['id'] ?>" class="btn-icon delete-btn" onclick="return confirm('Delete this appointment?');"><i class="fa-solid fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
