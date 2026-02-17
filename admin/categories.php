<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
    setFlash('success', __('category_deleted_success', 'Category deleted successfully'));
    redirect('categories.php');
}

// Handle Add/Edit
$editCategory = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editCategory = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $slug = strtolower(str_replace(' ', '-', $name));
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
    
    try {
        if (isset($_POST['id'])) {
            // Prevent self-reference
            if ($parent_id == $_POST['id']) {
                $error = "A category cannot be its own parent.";
            } else {
                $stmt = $pdo->prepare("UPDATE categories SET name=?, slug=?, parent_id=? WHERE id=?");
                $stmt->execute([$name, $slug, $parent_id, $_POST['id']]);
                setFlash('success', __('category_updated_success', 'Category updated successfully'));
                redirect('categories.php');
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, parent_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $slug, $parent_id]);
            setFlash('success', __('category_created_success', 'Category added successfully'));
            redirect('categories.php');
        }
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            $error = __('duplicate_product_error', "Duplicate entry: Category already exists.");
        } else {
            $error = __('database_error', "Database Error") . ": " . $e->getMessage();
        }
    }
}

// Fetch all categories with parent info
$categories = $pdo->query("
    SELECT c.*, p.name as parent_name 
    FROM categories c 
    LEFT JOIN categories p ON c.parent_id = p.id 
    ORDER BY COALESCE(c.parent_id, c.id), c.parent_id IS NULL DESC, c.name
")->fetchAll();

// Get ALL categories for dropdown (except self is handled in loop)
$all_categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

function buildCategoryOptions($categories, $parent_id = null, $prefix = '', $exclude_id = null) {
    $html = '';
    foreach ($categories as $cat) {
        if ($cat['parent_id'] == $parent_id) {
            if ($exclude_id && $cat['id'] == $exclude_id) continue; // Skip self
            
            $selected = (isset($_POST['parent_id']) && $_POST['parent_id'] == $cat['id']) ? 'selected' : '';
            // If editing, check against the saved parent_id
            global $editCategory;
            if ($editCategory && $editCategory['parent_id'] == $cat['id']) $selected = 'selected';
            
            $html .= '<option value="' . $cat['id'] . '" ' . $selected . '>' . $prefix . htmlspecialchars($cat['name']) . '</option>';
            // Recursive call for children
            $html .= buildCategoryOptions($categories, $cat['id'], $prefix . '— ', $exclude_id);
        }
    }
    return $html;
}
?>

<?php require_once 'header.php'; ?>

<div class="page-header">
    <div class="page-header-info">
        <h1 class="page-title"><?= __('categories_title') ?></h1>
        <p class="page-subtitle"><?= __('categories_subtitle') ?></p>
    </div>
</div>

<div class="admin-grid container-wide">
    <!-- Form -->
    <div class="card" style="height: fit-content; padding: 2rem;">
        <h3 class="card-title" style="font-weight: 800; color: var(--admin-sidebar); margin-bottom: 2rem;">
            <?= $editCategory ? __('edit_category') : __('add_category') ?>
        </h3>
        <?php if (isset($error)): ?>
            <div class="alert" style="background: #fff1f2; color: #991b1b; padding: 1.25rem; border-radius: 12px; margin-bottom: 2rem; font-size: 0.85rem; border: 1px solid #fee2e2; font-weight: 600;">
                <i class="fa-solid fa-circle-exclamation" style="margin-right: 0.5rem; color: var(--admin-primary);"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <?php if ($editCategory): ?>
                <input type="hidden" name="id" value="<?= $editCategory['id'] ?>">
            <?php endif; ?>
            <div class="form-group">
                <label style="font-weight: 700; color: var(--admin-text);"><?= __('category_name') ?></label>
                <input type="text" name="name" value="<?= $editCategory['name'] ?? '' ?>" placeholder="e.g. Designer Frames" required style="padding: 1rem; border-radius: 12px;">
            </div>
            <div class="form-group">
                <label style="font-weight: 700; color: var(--admin-text);"><?= __('parent_category_optional') ?></label>
                <select name="parent_id" style="padding: 1rem; border-radius: 12px; font-family: 'Montserrat';">
                    <option value=""><?= __('no_parent') ?></option>
                    <?php 
                        $exclude = $editCategory ? $editCategory['id'] : null;
                        echo buildCategoryOptions($all_categories, null, '', $exclude);
                    ?>
                </select>
                <span style="font-size: 0.75rem; color: var(--admin-text-light); margin-top: 0.5rem; display: block;"><?= __('select_parent_help') ?></span>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1; padding: 1rem;"><?= $editCategory ? __('update') : __('add_category') ?></button>
                <?php if ($editCategory): ?>
                    <a href="categories.php" class="btn btn-secondary" style="padding: 1rem;"><?= __('cancel') ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- List -->
    <div class="admin-table-widget">
        <div class="widget-header">
            <div class="widget-title">
                <i class="fa-solid fa-layer-group" style="color: var(--admin-primary);"></i>
                <?= __('existing_categories') ?>
            </div>
            <div style="font-size: 0.85rem; color: var(--admin-text-light);">
                <?= count($categories) ?> <?= __('items') ?>
            </div>
        </div>
        <div class="widget-content">
            <table class="widget-table responsive-table">
                <thead>
                    <tr>
                        <th width="60" style="text-align: center;">ID</th>
                        <th style="padding-left: 1rem;"><?= __('category_name') ?></th>
                        <th><?= __('slug') ?></th>
                        <th width="150" style="text-align: center;"><?= __('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Helper to render rows recursively
                    function renderCategoryRow($category, $level = 0) {
                        global $pdo;
                        $padding = 1 + ($level * 2); // Indent based on level
                        $icon = $level === 0 ? '<i class="fa-solid fa-folder" style="color: var(--admin-primary); margin-right: 0.5rem;"></i>' : '<i class="fa-solid fa-arrow-turn-up" style="transform: rotate(90deg); color: var(--admin-text-light); margin-right: 0.5rem; font-size: 0.75rem;"></i>';
                        
                        echo '<tr>';
                        echo '<td data-label="ID" style="text-align: center; font-family: monospace; font-size: 0.8rem; color: var(--admin-primary); font-weight: 700;">#' . $category['id'] . '</td>';
                        echo '<td data-label="' . __('category_name') . '" style="padding-left: ' . $padding . 'rem; font-weight: ' . (700 - ($level*100)) . '; color: var(--admin-sidebar);">';
                        echo $icon . htmlspecialchars($category['name']);
                        echo '</td>';
                        echo '<td data-label="' . __('slug') . '" style="color: var(--admin-text-light); font-size: 0.85rem; font-family: \'Montserrat\', sans-serif; font-weight: 600;">' . htmlspecialchars($category['slug']) . '</td>';
                        echo '<td data-label="' . __('actions') . '">
                        <div class="flex items-center gap-2 justify-center">
                            <a href="categories.php?edit=' . $category['id'] . '" class="btn-action btn-action-edit" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <a href="categories.php?delete=' . $category['id'] . '" class="btn-action btn-action-delete" onclick="return confirm(\'Are you sure?\')" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </div>
                              </td>';
                        echo '</tr>';

                        // Check for children (Optimized: In a real app, build a tree first to avoid N+1 queries, but standard for small cats)
                        $children = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY name");
                        $children->execute([$category['id']]);
                        $child_cats = $children->fetchAll();
                        
                        foreach ($child_cats as $child) {
                            renderCategoryRow($child, $level + 1);
                        }
                    }

                    // Start with top-level categories
                    $roots = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name")->fetchAll();
                    foreach ($roots as $root) {
                        renderCategoryRow($root);
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
