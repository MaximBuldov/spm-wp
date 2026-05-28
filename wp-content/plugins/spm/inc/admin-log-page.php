<?php
defined('ABSPATH') || exit;

add_action('admin_menu', function () {
    add_menu_page(
        'SPM Logs',
        'SPM Logs',
        'manage_options',
        'spm-logs',
        'spm_render_log_page',
        'dashicons-list-view',
        99
    );
});

function spm_render_log_page(): void {
    $log_file = WP_CONTENT_DIR . '/debug.log';
    $lines    = [];

    if (file_exists($log_file)) {
        $all   = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_filter($all, fn($l) => str_contains($l, '[SPM]'));
        $lines = array_reverse(array_values($lines));
    }

    $clear = isset($_POST['spm_clear_log']) && check_admin_referer('spm_clear_log');
    if ($clear) {
        $filtered = array_filter(
            file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [],
            fn($l) => !str_contains($l, '[SPM]')
        );
        file_put_contents($log_file, implode("\n", $filtered) . "\n");
        $lines = [];
    }
    ?>
    <div class="wrap">
        <h1>SPM Logs</h1>
        <form method="post" style="margin-bottom:12px">
            <?php wp_nonce_field('spm_clear_log'); ?>
            <button name="spm_clear_log" class="button">Clear SPM logs</button>
            <button type="button" class="button" onclick="location.reload()">Refresh</button>
        </form>
        <div style="background:#1e1e1e;color:#d4d4d4;font-family:monospace;font-size:13px;padding:16px;border-radius:4px;height:70vh;overflow-y:auto">
            <?php if (empty($lines)): ?>
                <span style="color:#888">No SPM log entries yet.</span>
            <?php else: ?>
                <?php foreach ($lines as $line):
                    if (str_contains($line, '✗') || str_contains($line, 'error') || str_contains($line, '4') || str_contains($line, '5')) {
                        $color = '#f48771';
                    } else {
                        $color = '#d4d4d4';
                    }
                    ?>
                    <div style="color:<?= $color ?>;border-bottom:1px solid #2d2d2d;padding:4px 0">
                        <?= esc_html($line) ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
