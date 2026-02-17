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

// Get Listing Parameters
$params = getListingParams('appointment_date', 'ASC');
$page = $params['page'];
$search = $params['search'];
$sort = $params['sort'];
$order = $params['order'];
$limit = $params['limit'];

// Filter Logic from original
$filter = $_GET['filter'] ?? 'upcoming';
$where = "1=1";
$sqlParams = [];

if ($filter === 'upcoming') {
    $where .= " AND appointment_date >= CURDATE() AND status != 'cancelled'";
} elseif ($filter === 'today') {
    $where .= " AND appointment_date = CURDATE()";
}

if ($search) {
    $where .= " AND (name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $sqlParams[] = "%$search%";
    $sqlParams[] = "%$search%";
    $sqlParams[] = "%$search%";
}

// Get Total Count
$countQuery = "SELECT COUNT(*) FROM appointments WHERE $where";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($sqlParams);
$totalItems = $stmt->fetchColumn();

// Get Pagination Data
$pagination = getPaginationData($totalItems, $limit);
$offset = $pagination['offset'];

// Fetch Paginated Appointments
$allowedSorts = ['appointment_date', 'name', 'status'];
if (!in_array($sort, $allowedSorts)) { $sort = 'appointment_date'; }

$query = "SELECT * FROM appointments 
          WHERE $where 
          ORDER BY $sort $order, time_slot ASC 
          LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($sqlParams);
$appointments = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-header-info">
        <h1 class="page-title">Appointment Manager</h1>
        <p class="page-subtitle">Track and manage customer bookings and eye test schedules.</p>
    </div>
    <div class="page-header-actions">
        <a href="../book_appointment.php" target="_blank" class="btn btn-secondary">
            <i class="fa-solid fa-eye"></i> View Booking Page
        </a>
    </div>
</div>

<!-- Listing Controls -->
<div class="listing-controls">
    <form method="GET" class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, phone or email..." class="form-control">
        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
        <?php if($search): ?>
            <a href="appointments.php?filter=<?= $filter ?>" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--admin-text-light);"><i class="fa-solid fa-xmark" style="position: static; padding: 0;"></i></a>
        <?php endif; ?>
    </form>

    <div class="filter-group">
        <form method="GET" style="display: flex; gap: 0.75rem; align-items: center;">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <select name="sort" onchange="this.form.submit()" class="form-control">
                <option value="appointment_date" <?= $sort == 'appointment_date' ? 'selected' : '' ?>>Date</option>
                <option value="name" <?= $sort == 'name' ? 'selected' : '' ?>>Customer Name</option>
                <option value="status" <?= $sort == 'status' ? 'selected' : '' ?>>Status</option>
            </select>
            <select name="order" onchange="this.form.submit()" class="form-control" style="min-width: 100px;">
                <option value="ASC" <?= $order == 'ASC' ? 'selected' : '' ?>>ASC</option>
                <option value="DESC" <?= $order == 'DESC' ? 'selected' : '' ?>>DESC</option>
            </select>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div style="display: flex; gap: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; margin-bottom: 1rem;">
        <a href="appointments.php?filter=upcoming" class="btn btn-sm <?= $filter == 'upcoming' ? 'btn-primary' : 'btn-secondary' ?>">Upcoming</a>
        <a href="appointments.php?filter=today" class="btn btn-sm <?= $filter == 'today' ? 'btn-primary' : 'btn-secondary' ?>">Today</a>
        <a href="appointments.php?filter=all" class="btn btn-sm <?= $filter == 'all' ? 'btn-primary' : 'btn-secondary' ?>">All History</a>
    </div>

    <div class="table-container">
        <table class="data-table responsive-table">
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
                        <td data-label="Date" style="font-weight: 500;">
                            <?= date('M d, Y', strtotime($app['appointment_date'])) ?>
                            <?php if ($app['appointment_date'] == date('Y-m-d')) echo '<span class="badge badge-success" style="margin-left:5px;">Today</span>'; ?>
                        </td>
                        <td data-label="Time Slot"><?= htmlspecialchars($app['time_slot']) ?></td>
                        <td data-label="Customer"><?= htmlspecialchars($app['name']) ?></td>
                        <td data-label="Phone"><?= htmlspecialchars($app['phone']) ?></td>
                        <td data-label="Email"><?= htmlspecialchars($app['email']) ?></td>
                        <td data-label="Status">
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
                        <td data-label="Action">
                            <div class="flex items-center gap-2 justify-center">
                                <?php if ($app['status'] !== 'completed' && $app['status'] !== 'cancelled'): ?>
                                    <a href="appointments.php?id=<?= $app['id'] ?>&status=completed" class="btn-action btn-action-success" title="Mark Completed"><i class="fa-solid fa-check"></i></a>
                                    <a href="appointments.php?id=<?= $app['id'] ?>&status=cancelled" class="btn-action btn-action-warning" title="Cancel"><i class="fa-solid fa-xmark"></i></a>
                                <?php endif; ?>
                                <a href="appointments.php?delete=<?= $app['id'] ?>" class="btn-action btn-action-delete delete-btn" onclick="return confirm('Delete this appointment?');"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= renderPagination($page, $pagination['total_pages']) ?>

<?php require_once 'footer.php'; ?>
