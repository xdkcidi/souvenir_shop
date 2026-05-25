<?php
session_start();
require_once __DIR__ . '/../php/admin_guard.php';
require_once __DIR__ . '/../php/db.php';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function statusLabel(string $status): string
{
    return match ($status) {
        'new' => 'Новая',
        'in_progress' => 'В работе',
        'done' => 'Завершена',
        default => 'Неизвестно',
    };
}

function statusBadgeStyle(string $status): string
{
    return match ($status) {
        'new' => 'background:#fff4e5;color:#b26a00;',
        'in_progress' => 'background:#eef3ff;color:#2d5bd1;',
        'done' => 'background:#eef8f0;color:#1f7a43;',
        default => 'background:#f4f4f4;color:#555;',
    };
}

/* Обработка статусов */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = trim((string)($_POST['action'] ?? ''));

    if ($id <= 0) {
        $_SESSION['admin_error'] = 'Некорректный ID заявки.';
        header('Location: ../pages/admin_personalization_requests.php');
        exit;
    }

    $newStatus = match ($action) {
        'mark_new' => 'new',
        'mark_in_progress' => 'in_progress',
        'mark_done' => 'done',
        default => '',
    };

    if ($newStatus === '') {
        $_SESSION['admin_error'] = 'Неизвестное действие.';
        header('Location: ../pages/admin_personalization_requests.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE personalization_requests
            SET status = ?
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$newStatus, $id]);

        $_SESSION['admin_success'] = 'Статус заявки обновлён.';
    } catch (PDOException $e) {
        $_SESSION['admin_error'] = 'Не удалось обновить статус заявки.';
    }

    header('Location: ../pages/admin_personalization_requests.php');
    exit;
}

/* Список заявок */
$stmt = $pdo->query("
    SELECT
        pr.id,
        pr.user_id,
        pr.customer_name,
        pr.phone,
        pr.email,
        pr.preferred_contact,
        pr.item_type,
        pr.engraving_text,
        pr.urgency,
        pr.target_date,
        pr.comment,
        pr.status,
        pr.created_at,
        u.login AS user_login
    FROM personalization_requests pr
    LEFT JOIN users u ON u.id = pr.user_id
    ORDER BY pr.created_at DESC, pr.id DESC
");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$basePath = '..';
require_once __DIR__ . '/../includes/layout.php';

renderHead(
    'Заявки на персонализацию — Админка',
    'Административная страница заявок на персонализацию товаров Лавки.',
    [
        'css/style.css',
        'css/admin.css'
    ]
);

renderHeader();
?>

<main class="admin-wrap admin-wrap--md">
  <div class="admin-head">
    <div>
      <h1 class="admin-head__title">Заявки на персонализацию</h1>
      <p class="admin-head__text">Просмотр заявок на гравировку и изменение их статуса.</p>
    </div>
    <a class="btn btn--outline" href="../pages/admin.php">Назад</a>
  </div>

  <?php if (!empty($_SESSION['admin_success'])): ?>
    <div style="margin-bottom:16px; padding:12px 14px; border-radius:14px; background:#eef8f0; color:#1f7a43;">
      <?= h($_SESSION['admin_success']) ?>
    </div>
    <?php unset($_SESSION['admin_success']); ?>
  <?php endif; ?>

  <?php if (!empty($_SESSION['admin_error'])): ?>
    <div style="margin-bottom:16px; padding:12px 14px; border-radius:14px; background:#fff0f0; color:#b00020;">
      <?= h($_SESSION['admin_error']) ?>
    </div>
    <?php unset($_SESSION['admin_error']); ?>
  <?php endif; ?>

  <?php if (!$requests): ?>
    <div class="empty">Заявок пока нет.</div>
  <?php else: ?>
    <section class="reviews-list">
      <?php foreach ($requests as $req): ?>
        <?php $status = (string)($req['status'] ?? 'new'); ?>
        <article class="review-card">
          <div class="review-top">
            <div>
              <h2 class="review-title">
                Заявка №<?= (int)$req['id'] ?>
              </h2>

              <p class="review-meta">Имя: <?= h($req['customer_name']) ?: '—' ?></p>
              <p class="review-meta">Телефон: <?= h($req['phone']) ?: '—' ?></p>
              <p class="review-meta">Email: <?= h($req['email']) ?: '—' ?></p>
              <p class="review-meta">Способ связи: <?= h($req['preferred_contact']) ?: '—' ?></p>
              <p class="review-meta">Изделие: <?= h($req['item_type']) ?: '—' ?></p>
              <p class="review-meta">Текст гравировки: <?= h($req['engraving_text']) ?: '—' ?></p>
              <p class="review-meta">Срок: <?= h($req['urgency']) ?: '—' ?></p>
              <p class="review-meta">Нужная дата: <?= h($req['target_date']) ?: '—' ?></p>
              <p class="review-meta">Дата заявки: <?= h($req['created_at']) ?: '—' ?></p>
              <p class="review-meta">ID пользователя: <?= !empty($req['user_id']) ? (int)$req['user_id'] : '—' ?></p>
              <p class="review-meta">Логин пользователя: <?= h($req['user_login']) ?: 'Гость' ?></p>
            </div>

            <div class="review-side">
              <span style="display:inline-flex; align-items:center; padding:8px 12px; border-radius:999px; font-size:13px; font-weight:700; <?= statusBadgeStyle($status) ?>">
                <?= h(statusLabel($status)) ?>
              </span>

              <?php if ($status !== 'new'): ?>
                <form class="inline" action="../pages/admin_personalization_requests.php" method="post">
                  <input type="hidden" name="id" value="<?= (int)$req['id'] ?>">
                  <input type="hidden" name="action" value="mark_new">
                  <button class="btn btn--sm" type="submit">В новые</button>
                </form>
              <?php endif; ?>

              <?php if ($status !== 'in_progress'): ?>
                <form class="inline" action="../pages/admin_personalization_requests.php" method="post">
                  <input type="hidden" name="id" value="<?= (int)$req['id'] ?>">
                  <input type="hidden" name="action" value="mark_in_progress">
                  <button class="btn btn--sm" type="submit">В работу</button>
                </form>
              <?php endif; ?>

              <?php if ($status !== 'done'): ?>
                <form class="inline" action="../pages/admin_personalization_requests.php" method="post">
                  <input type="hidden" name="id" value="<?= (int)$req['id'] ?>">
                  <input type="hidden" name="action" value="mark_done">
                  <button class="btn btn--sm" type="submit">Завершить</button>
                </form>
              <?php endif; ?>
            </div>
          </div>

          <div class="review-text">
            <?php if (!empty($req['comment'])): ?>
              <?= nl2br(h($req['comment'])) ?>
            <?php else: ?>
              <span class="empty">Без комментария</span>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>
</main>
<?php
renderFooter();
renderAuthModal();
renderFavoritesSheet();

renderScripts([
    'js/script.js',
    'js/cart.js',
    'js/favorites.js'
]);
?>
