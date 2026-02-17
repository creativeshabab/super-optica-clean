<?php require_once 'header.php'; ?>

<div class="page-header">
    <div class="page-header-info">
        <h1 class="page-title">Bulk Product Upload</h1>
    </div>
    <div class="page-header-actions">
        <a href="bulk_upload_guide.php" class="btn btn-secondary" target="_blank">
            <i class="fa-solid fa-book-open"></i> View Guide
        </a>
        <a href="generate_sample_csv.php" class="btn btn-secondary">
            <i class="fa-solid fa-download"></i> Download Template
        </a>
        <a href="products.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Back to Products
        </a>
    </div>
</div>

<div class="admin-form-layout">
    <div class="form-grid">
        <div class="form-column-main">
            <div class="card mb-4">
                <h3 class="card-title mb-4"><i class="fa-solid fa-upload me-2" style="color: var(--admin-primary);"></i> Upload Files</h3>
                
                <form id="bulkUploadForm" action="bulk_process.php" method="POST" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    
                    <div class="form-group mb-4">
                        <label class="form-label">Step 1: Product Data (CSV)</label>
                        <div class="drag-upload-container">
                            <div id="csvDragBox" class="drag-upload-box" style="min-height: 120px;">
                                <div class="drag-upload-icon"><i class="fa-solid fa-file-csv"></i></div>
                                <div class="drag-upload-text">Drop CSV file here or click to browse</div>
                                <input type="file" name="csv_file" id="csvInput" accept=".csv" style="display: none;" required>
                            </div>
                            <div id="csvInfo" class="mt-2 text-sm text-muted" style="display:none; padding: 0.5rem; background: #f8fafc; border-radius: 6px; border: 1px solid var(--admin-border);">
                                <i class="fa-solid fa-check-circle text-success me-1"></i> <span id="csvFileName"></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label">Step 2: Images Archive (ZIP)</label>
                        <div class="drag-upload-container">
                            <div id="zipDragBox" class="drag-upload-box" style="min-height: 120px; border-color: #cbd5e1;">
                                <div class="drag-upload-icon" style="color: #94a3b8;"><i class="fa-solid fa-file-zipper"></i></div>
                                <div class="drag-upload-text">Drop ZIP file with images here</div>
                                <input type="file" name="zip_file" id="zipInput" accept=".zip" style="display: none;" required>
                            </div>
                            <div id="zipInfo" class="mt-2 text-sm text-muted" style="display:none; padding: 0.5rem; background: #f8fafc; border-radius: 6px; border: 1px solid var(--admin-border);">
                                <i class="fa-solid fa-check-circle text-success me-1"></i> <span id="zipFileName"></span>
                            </div>
                        </div>
                    </div>

                    <div id="processingStatus" style="display:none;" class="mb-4">
                        <div class="progress-bar-container" style="height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; margin-bottom: 0.5rem;">
                            <div id="uploadProgress" style="width: 0%; height: 100%; background: var(--admin-primary); transition: width 0.3s ease;"></div>
                        </div>
                        <div id="statusMessage" class="text-sm fw-600 text-center color-primary">Uploading files...</div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" id="startUpload" class="btn btn-primary btn-lg">
                            <i class="fa-solid fa-rocket"></i> Start Bulk Processing
                        </button>
                    </div>
                </form>
            </div>

            <div id="logResults" class="card" style="display:none;">
                <h3 class="card-title mb-4">Processing Log</h3>
                <div id="logContent" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 0.85rem; padding: 1rem; background: #0f172a; color: #cbd5e1; border-radius: 8px;">
                </div>
            </div>
        </div>

        <div class="form-column-sidebar">
            <div class="card mb-4" style="background: #f0f9ff; border: 1px solid #bae6fd;">
                <h3 class="card-title text-primary mb-3" style="font-size: 1.1rem;"><i class="fa-solid fa-circle-info me-2"></i> Instructions</h3>
                <ul class="list-unstyled mb-0" style="font-size: 0.9rem; line-height: 1.6;">
                    <li class="mb-2"><strong>1. CSV Template</strong>: Use our template. Maintain identical <strong>Product SKU</strong> for multiple variants.</li>
                    <li class="mb-2"><strong>2. Variants</strong>: List each variant on a new row. The system groups them by SKU.</li>
                    <li class="mb-2"><strong>3. Images</strong>: In the CSV, list image filenames (e.g. <code>p1_blue.jpg</code>). Use comma for multiple images per variant.</li>
                    <li class="mb-2"><strong>4. ZIP Root</strong>: Put all images at the root of the ZIP file (no folders).</li>
                    <li><strong>5. Automation</strong>: The system handles WebP conversion and resizing automatically.</li>
                </ul>
            </div>

            <div class="card">
                <h3 class="card-title mb-3" style="font-size: 1.1rem;">CSV Column Guide</h3>
                <div style="font-size: 0.8rem; color: var(--admin-text-light);">
                    <div class="mb-2"><code class="color-primary">product_sku</code> - Unique ID for Product</div>
                    <div class="mb-2"><code class="color-primary">variant_sku</code> - Unique ID for Variant</div>
                    <div class="mb-2"><code>color_name</code> - Name of the color</div>
                    <div class="mb-2"><code>color_code</code> - Hex code (e.g. #000000)</div>
                    <div class="mb-2"><code>variant_images</code> - Comma separated filenames</div>
                    <div class="mb-2"><code>price</code> - Variant Price</div>
                    <div><code>stock</code> - Stock Quantity</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    initFileHandling('csvDragBox', 'csvInput', 'csvInfo', 'csvFileName');
    initFileHandling('zipDragBox', 'zipInput', 'zipInfo', 'zipFileName');

    const form = document.getElementById('bulkUploadForm');
    const logResults = document.getElementById('logResults');
    const logContent = document.getElementById('logContent');
    const processingStatus = document.getElementById('processingStatus');
    const uploadProgress = document.getElementById('uploadProgress');
    const statusMessage = document.getElementById('statusMessage');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        
        processingStatus.style.display = 'block';
        logResults.style.display = 'block';
        logContent.innerHTML = '<div class="mb-1 text-info">> Handshaking with server...</div>';
        
        try {
            const response = await fetch('bulk_process.php', {
                method: 'POST',
                body: formData
            });

            // Note: In a real environment with large files, we'd use XHR with progress or WebSockets.
            // For this implementation, we'll handle the JSON response.
            const result = await response.json();
            
            uploadProgress.style.width = '100%';
            statusMessage.textContent = 'Processing Complete!';
            
            if (result.logs) {
                result.logs.forEach(log => {
                    const line = document.createElement('div');
                    line.className = 'mb-1';
                    line.style.color = log.type === 'error' ? '#ef4444' : (log.type === 'success' ? '#22c55e' : '#cbd5e1');
                    line.textContent = `> [${log.type.toUpperCase()}] ${log.message}`;
                    logContent.appendChild(line);
                });
            }

            if (result.success) {
                statusMessage.className = 'text-sm fw-600 text-center text-success';
                statusMessage.textContent = 'Bulk upload successful! Redirecting...';
                setTimeout(() => window.location.href = 'products.php', 2000);
            } else {
                statusMessage.className = 'text-sm fw-600 text-center text-danger';
                statusMessage.textContent = 'Processing failed. Check logs.';
            }

        } catch (err) {
            logContent.innerHTML += `<div class="mb-1 text-danger">> ERROR: ${err.message}</div>`;
            statusMessage.textContent = 'Connection Error.';
        }
    });
});

function initFileHandling(boxId, inputId, infoId, nameId) {
    const box = document.getElementById(boxId);
    const input = document.getElementById(inputId);
    const info = document.getElementById(infoId);
    const nameSpan = document.getElementById(nameId);

    box.onclick = () => input.click();

    box.ondragover = (e) => {
        e.preventDefault();
        box.style.borderColor = 'var(--admin-primary)';
        box.style.background = 'rgba(37, 99, 235, 0.05)';
    };

    box.ondragleave = () => {
        box.style.borderColor = 'var(--admin-border)';
        box.style.background = 'none';
    };

    box.ondrop = (e) => {
        e.preventDefault();
        input.files = e.dataTransfer.files;
        updateUI();
    };

    input.onchange = () => updateUI();

    function updateUI() {
        if (input.files.length > 0) {
            info.style.display = 'block';
            nameSpan.textContent = input.files[0].name;
            box.style.borderColor = '#22c55e';
            box.style.background = 'rgba(34, 197, 94, 0.05)';
        }
    }
}
</script>

<style>
.progress-bar-container { border: 1px solid #e2e8f0; }
.color-primary { color: var(--admin-primary); }
#logContent::-webkit-scrollbar { width: 6px; }
#logContent::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
</style>

<?php require_once 'footer.php'; ?>
