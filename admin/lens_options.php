<?php
require_once 'header.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'add') {
                $stmt = $pdo->prepare("INSERT INTO lens_options (name, description, price, is_active) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['price'],
                    isset($_POST['is_active']) ? 1 : 0
                ]);
                setFlash('success', 'Lens option added successfully');
            } elseif ($_POST['action'] === 'edit') {
                $stmt = $pdo->prepare("UPDATE lens_options SET name = ?, description = ?, price = ?, is_active = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['price'],
                    isset($_POST['is_active']) ? 1 : 0,
                    $_POST['id']
                ]);
                setFlash('success', 'Lens option updated successfully');
            } elseif ($_POST['action'] === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM lens_options WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                setFlash('success', 'Lens option deleted successfully');
            } elseif ($_POST['action'] === 'toggle_status') {
                $stmt = $pdo->prepare("UPDATE lens_options SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                setFlash('success', 'Status updated successfully');
            }
        } catch (PDOException $e) {
            setFlash('error', 'Database Error: ' . $e->getMessage());
        }
        redirect('lens_options.php');
    }
}

// Fetch Options
$stmt = $pdo->query("SELECT * FROM lens_options ORDER BY price ASC");
$lens_options = $stmt->fetchAll();
?>

<div class="admin-content">
    <div class="admin-header">
        <h1 class="admin-title">
            <i class="fa-solid fa-eye text-primary"></i> Lens Options
        </h1>
        <button onclick="openModal('addModal')" class="btn btn-primary mb-1">
            <i class="fa-solid fa-plus"></i> Add New Lens
        </button>
    </div>

    <!-- Lens Options Grid -->
    <!-- Lens Options Grid -->
    <!-- Lens Options Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
        <?php foreach ($lens_options as $lens): ?>
        <div class="lens-card group">
            <!-- Header -->
            <div class="flex justify-between items-start mb-4">
                <div class="flex-1 pr-3">
                    <h3 class="font-bold text-lg text-gray-800 leading-tight mb-1"><?= htmlspecialchars($lens['name']) ?></h3>
                    <!-- Status Badge -->
                    <?php if($lens['is_active']): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-50 text-green-700 border border-green-100">
                            <i class="fa-solid fa-circle text-[6px] mr-1.5 text-green-500"></i> Active
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-gray-50 text-gray-500 border border-gray-100">
                            <i class="fa-solid fa-circle text-[6px] mr-1.5 text-gray-400"></i> Inactive
                        </span>
                    <?php endif; ?>
                </div>
                
                <!-- Price -->
                <div class="text-right">
                    <span class="block text-xl font-black text-primary">₹<?= number_format($lens['price'], 0) ?></span>
                </div>
            </div>
            
            <!-- Description -->
            <p class="text-gray-500 text-sm mb-6 flex-grow leading-relaxed">
                <?= htmlspecialchars($lens['description']) ?>
            </p>
            
            <!-- Action Area (Inline Row) -->
            <div class="flex items-center gap-2 mt-auto pt-4 border-t border-gray-100 lens-option-btn">
                <!-- Edit Button -->
                <button onclick='editLens(<?= json_encode($lens) ?>)' class="btn btn-sm btn-primary flex-1 flex items-center justify-center gap-2 shadow-sm">
                    <i class="fa-solid fa-pen-to-square"></i> Edit
                </button>
                
                <!-- Status Toggle Button -->
                <form method="POST" class="flex-1" onsubmit="return confirm('Change status?')">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="id" value="<?= $lens['id'] ?>">
                    <?php if($lens['is_active']): ?>
                        <button type="submit" class="btn btn-sm btn-outline-secondary w-full flex items-center justify-center gap-2 hover:bg-gray-50" title="Deactivate">
                            <i class="fa-solid fa-power-off"></i> Disable
                        </button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-sm btn-outline-success w-full flex items-center justify-center gap-2 hover:bg-green-50" title="Activate">
                            <i class="fa-solid fa-power-off"></i> Enable
                        </button>
                    <?php endif; ?>
                </form>

                <!-- Delete Button (Icon Only) -->
                <form method="POST" onsubmit="return confirm('Delete this lens package permanently?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $lens['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger w-9 h-9 flex items-center justify-center p-0 rounded-lg hover:bg-red-50" title="Delete">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Add New Card (Empty State) -->
        <button onclick="openModal('addModal')" class="lens-card items-center justify-center text-gray-400 hover:text-primary transition-all min-h-[200px] cursor-pointer group" style="border-style: dashed; border-width: 2px;">
            <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center mb-4 group-hover:bg-red-50 transition-colors">
                <i class="fa-solid fa-plus text-2xl group-hover:text-primary transition-colors"></i>
            </div>
            <span class="font-bold text-lg">Add New Package</span>
        </button>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Add Lens Option</h2>
            <button onclick="closeModal('addModal')" class="close-modal">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" class="form-control" required placeholder="e.g. Blue Cut Lenses">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Describe the lens features..."></textarea>
            </div>
            <div class="form-group">
                <label>Price (₹)</label>
                <input type="number" name="price" class="form-control" required min="0" step="0.01">
            </div>
            <div class="form-group">
                <label class="checkbox-container">
                    <input type="checkbox" name="is_active" checked>
                    <span class="checkmark"></span>
                    Active Status
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Lens</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Edit Lens Option</h2>
            <button onclick="closeModal('editModal')" class="close-modal">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Price (₹)</label>
                <input type="number" name="price" id="edit_price" class="form-control" required min="0" step="0.01">
            </div>
            <div class="form-group">
                <label class="checkbox-container">
                    <input type="checkbox" name="is_active" id="edit_active">
                    <span class="checkmark"></span>
                    Active Status
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Lens</button>
            </div>
        </form>
    </div>
</div>

<script>

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

function editLens(lens) {
    document.getElementById('edit_id').value = lens.id;
    document.getElementById('edit_name').value = lens.name;
    document.getElementById('edit_description').value = lens.description;
    document.getElementById('edit_price').value = lens.price;
    document.getElementById('edit_active').checked = lens.is_active == 1;
    openModal('editModal');
}
</script>

<?php require_once 'footer.php'; ?>
