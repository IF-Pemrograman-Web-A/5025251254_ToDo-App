    <footer>
        <p>&copy; 2026 DO . IT! - 5025251254</p>
    </footer>

    <div id="toast-region" class="toast-region" role="status" aria-live="polite" aria-label="Notifications">
        <?php if (!empty($flash)): ?>
            <div class="toast" data-auto>
                <div class="toast-text"><strong><?= h($flash) ?></strong></div>
                <button type="button" class="toast-btn toast-close" aria-label="Dismiss notification">&times;</button>
            </div>
        <?php endif; ?>
    </div>

    <script>window.REMINDERS = <?= json_encode($reminders ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;</script>
    <script src="script.js"></script>
</body>
</html>
