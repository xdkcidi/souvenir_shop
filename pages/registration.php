<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../php/db.php';

// Подтверждение почты
$verifyToken = trim($_GET['verify_token'] ?? '');
if ($verifyToken !== '') {
    $tokenHash = hash('sha256', $verifyToken);

    $stmt = $pdo->prepare("
        SELECT id, login, email
        FROM users
        WHERE email_verify_token = :token
          AND is_email_verified = 0
        LIMIT 1
    ");
    $stmt->execute([':token' => $tokenHash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Активируем пользователя
        $upd = $pdo->prepare("
            UPDATE users
            SET is_email_verified = 1,
                email_verify_token = NULL,
                email_verify_expires_at = NULL,
                email_verified_at = NOW()
            WHERE id = :id
            LIMIT 1
        ");
        $upd->execute([':id' => $user['id']]);

        // Сразу авторизуем пользователя
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_login'] = $user['login'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['verification_success'] = true;

        header('Location: account.php');
        exit;
    }
}

if (isset($_SESSION['user_id']) && $verifyToken === '') {
    header('Location: account.php');
    exit;
}

// Получаем ошибки и данные формы из сесси
$errors = $_SESSION['reg_errors'] ?? [];
$formData = $_SESSION['reg_form_data'] ?? [];
unset($_SESSION['reg_errors'], $_SESSION['reg_form_data']);

$login            = $formData['login'] ?? '';
$email            = $formData['email'] ?? '';
$phone            = $formData['phone'] ?? '';
$address          = $formData['address'] ?? '';
$privacyConsent   = false;

// Сообщение об успешной регистрации
$registrationSuccess = $_SESSION['registration_success'] ?? '';
unset($_SESSION['registration_success']);

$isAuth = isset($_SESSION['user_id']);
$hasAuthError = !empty($_SESSION['auth_error']);
?>
<?php
$basePath = '..';
require_once __DIR__ . '/../includes/layout.php';

renderHead(
    'Регистрация — Лавка',
    'Регистрация в магазине Лавка: создайте аккаунт, чтобы сохранять избранное и быстрее оформлять заказы.',
    [
        'css/style.css',
        'css/reg.css'
    ]
);

renderHeader();
?>
<main class="container section auth-page" id="main-content" role="main" tabindex="-1">
  <div class="auth-page__inner">
    <nav class="breadcrumbs" aria-label="Хлебные крошки">
      <ol>
        <li><a href="../index.php">Главная</a></li>
        <li><span aria-current="page">Регистрация</span></li>
      </ol>
    </nav>

    <h1 class="auth-title">Регистрация</h1>
    <p class="auth-lead">
      Создайте аккаунт, чтобы сохранять избранное и быстрее оформлять заказы.
    </p>

    <section class="auth-card" aria-label="Форма регистрации">
<?php if (trim((string)$registrationSuccess) !== ''): ?>
    <div class="auth-notice auth-notice--success">
        <?php echo htmlspecialchars($registrationSuccess, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="auth-notice auth-notice--error">
        <?php foreach ($errors as $error): ?>
            <?php if (trim((string)$error) !== ''): ?>
                <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

        <form method="post" action="../php/reg.php" class="auth-form">
            <div class="auth-form__group">
                <label class="auth-form__label" for="login">Логин</label>
                <input
                    class="input auth-input"
                    type="text"
                    id="login"
                    name="login"
                    value="<?php echo htmlspecialchars($login, ENT_QUOTES, 'UTF-8'); ?>"
                    required
                />
            </div>

            <div class="auth-form__group">
                <label class="auth-form__label" for="email">Email</label>
                <input
                    class="input auth-input"
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                    required
                />
            </div>

            <div class="auth-form__group">
                <label class="auth-form__label" for="password">Пароль</label>
                <input
                    class="input auth-input"
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    required
                    autocomplete="new-password"
                />
                <p class="auth-hint">
                    Минимум 8 символов, заглавная и строчная буква, цифра
                </p>
            </div>

            <div class="auth-form__group">
                <label class="auth-form__label" for="password_confirm">Повторите пароль</label>
                <input
                    class="input auth-input"
                    type="password"
                    id="password_confirm"
                    name="password_confirm"
                    minlength="8"
                    required
                    autocomplete="new-password"
                />
            </div>

<div class="auth-form__group">
    <label class="auth-form__label" for="phone">Телефон (необязательно)</label>
    <input
        class="input auth-input"
        type="tel"
        id="phone"
        name="phone"
        placeholder="+7 (___) ___-__-__"
        value="<?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>"
    />
</div>

            <div class="auth-form__group">
                <label class="auth-form__label" for="delivery_address">Адрес доставки (необязательно)</label>
                <textarea
                    class="input auth-input auth-input--area"
                    id="delivery_address"
                    name="delivery_address"
                    rows="3"
                    placeholder="Город, улица, дом, квартира"
                ><?php echo htmlspecialchars($address, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="auth-form__group">
              <label class="auth-form__check">
    <input
        type="checkbox"
        name="privacy_consent"
        value="1"
        <?php echo $privacyConsent ? 'checked' : ''; ?>
        required
    />
    <span>
        Я соглашаюсь на
        <a href="privacy.php" target="_blank" rel="noopener noreferrer">
            обработку персональных данных
        </a>
    </span>
              </label>
            </div>

            <button type="submit" class="btn btn--dark auth-btn">Зарегистрироваться</button>

            <div class="auth-bottom">
                <span class="auth-bottom__text">Уже есть аккаунт?</span>
                <a href="#" class="auth-bottom__link" data-open-modal="authModal">Войти</a>
            </div>
        </form>
    </section>
  </div>
</main>

<?php
renderFooter();
renderAuthModal();
renderFavoritesSheet();
renderScripts([
    'js/script.js',
    'js/cart.js',
    'js/favorites.js',
    'js/phone-mask.js'
]);
?>
