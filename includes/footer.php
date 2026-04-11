</main>
<?php
if (!function_exists('setting')) require_once dirname(__DIR__) . '/includes/settings.php';
$footer = setting('footer_text', '');
if (!$footer) $footer = '&copy; ' . date('Y') . ' Walter Sisulu University. All rights reserved.';
?>
<footer class="footer"><?= $footer ?></footer>
</body>
</html>
