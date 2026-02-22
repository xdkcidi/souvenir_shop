<?php include __DIR__ . '/footer.php'; ?>
<?php include __DIR__ . '/auth-modal.php'; ?>
<?php include __DIR__ . '/favorites-sheet.php'; ?>

<script src="/souvenir_shop/js/script.js" defer></script>

<?php if (!empty($pageScripts)): ?>
  <?php foreach ($pageScripts as $script): ?>
    <script src="<?= htmlspecialchars($script) ?>" defer></script>
  <?php endforeach; ?>
<?php endif; ?>

</body>
</html>