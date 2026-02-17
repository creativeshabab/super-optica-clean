<?php require_once 'header.php'; ?>

<div class="page-header">
    <div class="page-header-info">
        <h1 class="page-title">Bulk Upload Guide</h1>
    </div>
    <div class="page-header-actions">
        <a href="bulk_upload.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Back to Upload
        </a>
    </div>
</div>

<div class="admin-form-layout">
    <div class="form-column-main" style="width: 100%; max-width: 900px; margin: 0 auto;">
        <div class="card p-5">
            <h2 class="mb-4" style="color: var(--admin-primary); border-bottom: 2px solid var(--admin-border); padding-bottom: 0.75rem;">
                <i class="fa-solid fa-book-open me-2"></i> Getting Started
            </h2>
            <p class="mb-4">Welcome to the Super Optical Bulk Upload System. This tool allows you to add or update hundreds of products and variants in minutes using a simple CSV and ZIP workflow.</p>

            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; height: 100%; border: 1px solid var(--admin-border);">
                        <h4 class="mb-3 text-primary"><i class="fa-solid fa-file-csv me-2"></i> 1. The CSV Data</h4>
                        <ul class="list-unstyled" style="font-size: 0.95rem; line-height: 1.8;">
                            <li><i class="fa-solid fa-check text-success me-2"></i> <strong>Product SKU</strong>: Critical ID. Shared by all variants.</li>
                            <li><i class="fa-solid fa-check text-success me-2"></i> <strong>Variant SKU</strong>: Unique code for specific colors.</li>
                            <li><i class="fa-solid fa-check text-success me-2"></i> <strong>Category ID</strong>: Numeric ID from categories menu.</li>
                            <li><i class="fa-solid fa-check text-success me-2"></i> <strong>Image Names</strong>: Comma-separated filenames.</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="background: #f0fdf4; padding: 1.5rem; border-radius: 12px; height: 100%; border: 1px solid #bbf7d0;">
                        <h4 class="mb-3" style="color: #15803d;"><i class="fa-solid fa-file-zipper me-2"></i> 2. The Images (ZIP)</h4>
                        <ul class="list-unstyled" style="font-size: 0.95rem; line-height: 1.8;">
                            <li><i class="fa-solid fa-check text-success me-2"></i> ZIP all images referenced in your CSV.</li>
                            <li><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i> <strong>No Folders</strong>: Put images in ZIP root.</li>
                            <li><i class="fa-solid fa-check text-success me-2"></i> Filenames must match CSV exactly.</li>
                            <li><i class="fa-solid fa-check text-success me-2"></i> JPG, PNG, and WebP are supported.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <h3 class="mb-4 px-3" style="font-size: 1.25rem; font-weight: 700;">System Intelligence</h3>
            <div class="alert alert-info border-0 shadow-sm mb-4" style="background: white; border-left: 4px solid var(--admin-primary) !important;">
                <div class="d-flex gap-3">
                    <div style="font-size: 1.5rem; color: var(--admin-primary);"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                    <div>
                        <h5 class="mb-1" style="font-weight: 700;">Automatic Optimization</h5>
                        <p class="mb-0 text-muted" style="font-size: 0.9rem;">The system automatically resizes images to 1920px max width and generates <strong>WebP versions</strong> for high-performance loading.</p>
                    </div>
                </div>
            </div>

            <div class="alert alert-info border-0 shadow-sm mb-5" style="background: white; border-left: 4px solid #10b981 !important;">
                <div class="d-flex gap-3">
                    <div style="font-size: 1.5rem; color: #10b981;"><i class="fa-solid fa-arrows-rotate"></i></div>
                    <div>
                        <h5 class="mb-1" style="font-weight: 700;">Intelligent Updates</h5>
                        <p class="mb-0 text-muted" style="font-size: 0.9rem;">If a Product SKU already exists, the system will update its prices, description, and features instead of creating a duplicate.</p>
                    </div>
                </div>
            </div>

            <h3 class="mb-4" style="font-size: 1.25rem;">Final Tips</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="p-3 border rounded">
                    <div class="fw-bold mb-1">Batching</div>
                    <p class="small text-muted mb-0">For catalogs > 100 items, upload in batches of 30 for maximum stability.</p>
                </div>
                <div class="p-3 border rounded">
                    <div class="fw-bold mb-1">Thumbnails</div>
                    <p class="small text-muted mb-0">The FIRST image name listed for a variant is used as its main display photo.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.admin-form-layout { display: flex; flex-direction: column; }
.card { border: 1px solid var(--admin-border); border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.text-primary { color: var(--admin-primary) !important; }
</style>

<?php require_once 'footer.php'; ?>
