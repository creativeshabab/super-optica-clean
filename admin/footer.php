    </div><!-- .admin-container -->
    </main>
</div>
    <div id="bulkActionsBar" style="position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); background: var(--admin-card); border: 2px solid var(--admin-primary); padding: 0.75rem 2rem; border-radius: 100px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); display: flex; align-items: center; gap: 2rem; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); z-index: 9999; opacity: 0; visibility: hidden; pointer-events: none;">
        <div style="font-size: 0.9rem; font-weight: 600; color: var(--admin-text);">
            <span id="selectedCount">0</span> <?= __('items_selected') ?>
        </div>
        <div style="height: 24px; width: 1px; background: var(--admin-border);"></div>
        <div style="display: flex; gap: 0.75rem;">
            <button class="btn btn-secondary" style="height: 36px; font-size: 0.8rem; padding: 0 1rem; border-radius: 50px; background: rgba(37, 99, 235, 0.05); color: var(--admin-primary); border: 1px solid rgba(37, 99, 235, 0.1);">
                <i class="fa-solid fa-eye-slash"></i> <?= __('hide_selected') ?>
            </button>
            <button class="btn btn-secondary" style="height: 36px; font-size: 0.8rem; padding: 0 1rem; border-radius: 50px; background: rgba(239, 68, 68, 0.05); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.1);">
                <i class="fa-solid fa-trash-can"></i> <?= __('delete_selected') ?>
            </button>
        </div>
        <button id="cancelSelection" style="background: none; border: none; color: var(--admin-text-light); cursor: pointer; padding: 0.25rem;">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>
