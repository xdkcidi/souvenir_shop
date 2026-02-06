<?php
// pages/account.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../php/db.php'; // $pdo

$userId = (int)$_SESSION['user_id'];
$errors = [];
$success = '';

// Загрузка данных пользователя
$stmt = $pdo->prepare("
    SELECT id, login, email, phone, delivery_address
    FROM users
    WHERE id = :id
    LIMIT 1
");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    // если вдруг в сессии мусор — выходим
    session_destroy();
    header('Location: login.php');
    exit;
}

// Обновление профиля (телефон, адрес)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['delivery_address'] ?? '');

    // тут можно добавить валидацию телефона/адреса, если хочется
    $stmt = $pdo->prepare("
        UPDATE users
        SET phone = :phone,
            delivery_address = :address
        WHERE id = :id
    ");
    $ok = $stmt->execute([
        ':phone'   => $phone,
        ':address' => $address,
        ':id'      => $userId,
    ]);

    if ($ok) {
        $success = 'Данные профиля обновлены.';
        $user['phone'] = $phone;
        $user['delivery_address'] = $address;
    } else {
        $errors[] = 'Не удалось обновить данные. Попробуйте ещё раз.';
    }
}

$isAuth = true;
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Личный кабинет — Лавка</title>
  <meta name="description" content="Личный кабинет Лавка: ваши данные, адрес доставки, избранное и купоны." />
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/reg.css" />
</head>
<body>
  <div id="screen-reader-announcer" class="visually-hidden" aria-live="assertive" aria-atomic="true"></div>
  
  <!-- ШАПКА -->
  <header class="nav" role="banner">
    <div class="container nav__inner">
      <a class="brand" href="../index.php" aria-label="Лавка - вернуться на главную страницу">
        <div class="brand__mark" aria-hidden="true"><img src="../img/placeholder.webp" alt="Логотип"></div>
        <div class="brand__name">Лавка</div>
      </a>

      <button class="nav__burger" type="button" 
              aria-label="Открыть меню навигации" 
              aria-expanded="false"
              aria-controls="main-menu"
              data-burger>
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
      </button>

      <nav class="nav__menu" id="main-menu" data-menu role="navigation" aria-label="Основное меню">
        <a class="nav__link" href="../index.php">Главная</a>
        <a class="nav__link" href="catalog.php">Каталог</a>
        
        <div class="nav__drop" data-dropdown>
          <button class="nav__link nav__link--btn" 
                  type="button" 
                  aria-expanded="false"
                  aria-haspopup="true"
                  aria-controls="mega-menu"
                  data-dropdown-btn>
            Категории
            <svg class="chev" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M7 10l5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>

          <div class="mega" id="mega-menu" data-dropdown-menu role="menu" aria-label="Категории товаров">
            <div class="mega__grid">
              <div>
                <h2 class="mega__title" id="mega-title">Основные категории</h2>

                <div class="mega__cards" role="group" aria-labelledby="mega-title">
                  <a class="mega__card" href="catalog.php#group-candles" role="menuitem" data-close-mega>
                    <div class="mega__cardTitle">Свечи</div>
                    <div class="mega__cardText">Интерьерные, ароматные, необычные</div>
                  </a>
                  <a class="mega__card" href="catalog.php#group-ceramics" role="menuitem" data-close-mega>
                    <div class="mega__cardTitle">Керамика</div>
                    <div class="mega__cardText">Кружки, тарелки, миски, фигурки</div>
                  </a>
                  <a class="mega__card" href="catalog.php#group-decor" role="menuitem" data-close-mega>
                    <div class="mega__cardTitle">Декор</div>
                    <div class="mega__cardText">Фигурки, вазы, подсвечники</div>
                  </a>
                  <a class="mega__card" href="catalog.php#group-textile" role="menuitem" data-close-mega>
                    <div class="mega__cardTitle">Текстиль</div>
                    <div class="mega__cardText">Игрушки, мешочки, панно, шарфы</div>
                  </a>
                  <a class="mega__card" href="catalog.php#group-postcards" role="menuitem" data-close-mega>
                    <div class="mega__cardTitle">Открытки</div>
                    <div class="mega__cardText">Авторские, минимал, наборы</div>
                  </a>
                  <a class="mega__card" href="catalog.php#group-sets" role="menuitem" data-close-mega>
                    <div class="mega__cardTitle">Подарочные наборы</div>
                    <div class="mega__cardText">Готовые боксы для подарка</div>
                  </a>
                </div>
              </div>

              <div class="mega__feature">
                <div class="mega__featureTop">
                  <div>
                    <div class="mega__featureTitle">Подбор по случаю</div>
                    <div class="mega__featureText">Для дома, "просто так", знак внимания</div>
                  </div>
                  <a class="btn btn--dark btn--sm" href="catalog.php#collectionsNav">Открыть</a>
                </div>

                <div class="mega__preview" 
                     role="img" 
                     aria-label="Подарочный набор из свечи и керамической кружки"
                     data-bg="../img/mega-preview.png">
                </div>
                <div class="mega__note">Быстрая навигация и фильтры — в каталоге.</div>
              </div>
            </div>
          </div>
        </div>

        <a class="nav__link" href="about.php">О компании</a>

        <div class="nav__actions">
          <button class="iconBtn iconBtn--rel" 
                  type="button" 
                  aria-label="Избранное"
                  aria-describedby="favorites-count-desc"
                  data-open-sheet="favoritesSheet">
            <span class="badge" id="favoritesCount" aria-hidden="true">0</span>
            <span id="favorites-count-desc" class="visually-hidden">Товаров в избранном: 0</span>
            <svg viewBox="0 0 24 24" aria-hidden="true" class="favorites-icon">
              <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="1.6"/>
            </svg>
          </button>

          <!-- Иконка кабинета (мы уже авторизованы) -->
          <a class="iconBtn" href="account.php" aria-label="Личный кабинет">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.6"></circle>
              <path d="M5 19c1.2-3 3.5-4.5 7-4.5s5.8 1.5 7 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
            </svg>
          </a>

          <a class="btn btn--dark btn--sm hide-sm" href="cart.php">Корзина</a>
        </div>
      </nav>
    </div>
  </header>

<main class="container section auth-page" id="main-content" role="main" tabindex="-1">
    <div class="account-page__inner">
        <nav class="breadcrumbs" aria-label="Хлебные крошки">
            <ol>
                <li><a href="../index.php">Главная</a></li>
                <li><span aria-current="page">Личный кабинет</span></li>
            </ol>
        </nav>

        <h1 class="auth-title">Личный кабинет</h1>
        <p class="auth-lead">
            Управляйте профилем, отслеживайте заказы и используйте бонусы
        </p>

        <?php if (!empty($success)): ?>
            <div class="auth-success" aria-live="polite">
                <?php echo htmlspecialchars($success, ENT_QUOTES); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="auth-errors" aria-live="polite">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e, ENT_QUOTES); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Навигация по вкладкам -->
        <div class="account-tabs" role="tablist" aria-label="Разделы личного кабинета">
            <button class="account-tab active" role="tab" aria-selected="true" data-tab="profile">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Профиль
            </button>
            <button class="account-tab" role="tab" data-tab="orders">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                Заказы
            </button>
            <button class="account-tab" role="tab" data-tab="favorites">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path>
                </svg>
                Избранное
                <span class="badge">3</span>
            </button>
            <button class="account-tab" role="tab" data-tab="coupons">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="8" width="18" height="12" rx="2" ry="2"></rect>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <path d="M12 8v-4a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v4"></path>
                </svg>
                Бонусы
            </button>
            <button class="account-tab" role="tab" data-tab="security">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                Безопасность
            </button>
        </div>

        <!-- Вкладка "Профиль" -->
        <section class="account-card active" id="profile-tab" role="tabpanel" aria-labelledby="profile-tab">
            <div class="profile-header">
                <div class="avatar-section">
                    <div class="avatar" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <?= mb_strtoupper(mb_substr($user['login'], 0, 1, 'UTF-8')) ?>
                    </div>
                    <button class="avatar-change" type="button">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Изменить фото
                    </button>
                </div>
                
                <div class="profile-info">
                    <h2 class="profile-name"><?= htmlspecialchars($user['login']) ?></h2>
                    <p class="profile-email"><?= htmlspecialchars($user['email']) ?></p>
                    
                    <div class="profile-stats">
                        <div class="stat">
                            <span class="stat-value">5</span>
                            <span class="stat-label">заказов</span>
                        </div>
                        <div class="stat">
                            <span class="stat-value">350</span>
                            <span class="stat-label">бонусов</span>
                        </div>
                        <div class="stat">
                            <span class="stat-value">3</span>
                            <span class="stat-label">в избранном</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="profile-card">
                <h3 class="profile-card__title">Личные данные</h3>
                
                <form method="post" class="profile-form" novalidate>
                    <div class="profile-form__grid">
                        <div class="profile-form__group">
                            <label class="profile-form__label" for="phone">Телефон</label>
                            <input
                                class="input profile-input"
                                type="tel"
                                id="phone"
                                name="phone"
                                placeholder="+7 (999) 000-00-00"
                                value="<?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES); ?>"
                            />
                        </div>

                        <div class="profile-form__group">
                            <label class="profile-form__label" for="delivery_address">Адрес доставки</label>
                            <textarea
                                class="input profile-input profile-input--area"
                                id="delivery_address"
                                name="delivery_address"
                                rows="3"
                                placeholder="Город, улица, дом, квартира"
                            ><?php echo htmlspecialchars($user['delivery_address'] ?? '', ENT_QUOTES); ?></textarea>
                        </div>
                    </div>

                    <!-- В профильной карточке замените форму выхода на это: -->
<div class="profile-logout">
    <a href="../php/logout.php" class="btn btn--outline logout-link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
        </svg>
        Выйти из аккаунта
    </a>
</div>
                </form>
            </div>
        </section>

        <!-- Вкладка "Заказы" -->
        <section class="account-card" id="orders-tab" role="tabpanel" aria-labelledby="orders-tab" hidden>
            <div class="section-header">
                <h2 class="section-title">История заказов</h2>
                <a href="orders.php" class="link-all">Все заказы →</a>
            </div>
            
            <div class="orders-list">
                <!-- Пример заказа 1 -->
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <h3 class="order-number">Заказ №14257</h3>
                            <span class="order-date">15.03.2025</span>
                        </div>
                        <span class="order-status status-delivered">
                            Доставлен
                        </span>
                    </div>
                    
                    <div class="order-body">
                        <div class="order-products">
                            <div class="product-preview">
                                <div class="product-preview__image" style="background-color: #f0f0f0;"></div>
                                <span class="product-preview__name">Свеча "Весенний ветер"</span>
                            </div>
                            <div class="product-preview">
                                <div class="product-preview__image" style="background-color: #e0e0e0;"></div>
                                <span class="product-preview__name">Керамическая кружка</span>
                            </div>
                        </div>
                        
                        <div class="order-footer">
                            <div class="order-total">3 450 ₽</div>
                            <div class="order-tracking">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <span>Трекер: RA789654321RU</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <button class="btn btn--outline btn--sm" data-order-details>
                            Подробнее
                        </button>
                        <button class="btn btn--outline btn--sm" data-order-repeat>
                            Повторить заказ
                        </button>
                    </div>
                </div>

                <!-- Пример заказа 2 -->
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <h3 class="order-number">Заказ №14201</h3>
                            <span class="order-date">02.03.2025</span>
                        </div>
                        <span class="order-status status-processing">
                            В обработке
                        </span>
                    </div>
                    
                    <div class="order-body">
                        <div class="order-products">
                            <div class="product-preview">
                                <div class="product-preview__image" style="background-color: #f5f0e1;"></div>
                                <span class="product-preview__name">Набор открыток</span>
                            </div>
                        </div>
                        
                        <div class="order-footer">
                            <div class="order-total">890 ₽</div>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <button class="btn btn--outline btn--sm" data-order-details>
                            Подробнее
                        </button>
                    </div>
                </div>
            </div>

            <div class="empty-state" style="display: none;">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                <h3>Заказов пока нет</h3>
                <p>Совершите первую покупку в нашем магазине</p>
                <a href="catalog.php" class="btn btn--dark">Перейти в каталог</a>
            </div>
        </section>

        <!-- Вкладка "Избранное" -->
        <section class="account-card" id="favorites-tab" role="tabpanel" aria-labelledby="favorites-tab" hidden>
            <div class="section-header">
                <h2 class="section-title">Избранное</h2>
                <button class="btn btn--outline btn--sm" id="clear-favorites-btn">
                    Очистить все
                </button>
            </div>
            
            <div class="favorites-grid">
                <!-- Пример товара в избранном -->
                <div class="favorite-item">
                    <div class="favorite-item__image" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);"></div>
                    <div class="favorite-item__info">
                        <h3 class="favorite-item__name">Ароматическая свеча "Лаванда"</h3>
                        <p class="favorite-item__price">1 890 ₽</p>
                        <div class="favorite-item__actions">
                            <button class="btn btn--dark btn--sm">В корзину</button>
                            <button class="btn btn--text btn--sm" data-remove-favorite>
                                Удалить
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="favorite-item">
                    <div class="favorite-item__image" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);"></div>
                    <div class="favorite-item__info">
                        <h3 class="favorite-item__name">Керамическая ваза "Минимал"</h3>
                        <p class="favorite-item__price">3 250 ₽</p>
                        <div class="favorite-item__actions">
                            <button class="btn btn--dark btn--sm">В корзину</button>
                            <button class="btn btn--text btn--sm" data-remove-favorite>
                                Удалить
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="empty-state" style="display: none;">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                <h3>В избранном пока пусто</h3>
                <p>Добавляйте понравившиеся товары, чтобы не потерять</p>
                <a href="catalog.php" class="btn btn--dark">Перейти в каталог</a>
            </div>
        </section>

        <!-- Вкладка "Бонусы" -->
        <section class="account-card" id="coupons-tab" role="tabpanel" aria-labelledby="coupons-tab" hidden>
            <div class="coupons-header">
                <div class="bonus-summary">
                    <h2 class="section-title">Мои бонусы</h2>
                    <div class="bonus-balance-card">
                        <div class="bonus-balance">
                            <span class="bonus-balance__label">Баланс</span>
                            <span class="bonus-balance__value">350</span>
                            <span class="bonus-balance__unit">баллов</span>
                        </div>
                        <p class="bonus-info">1 балл = 1 ₽ при оплате</p>
                    </div>
                </div>
                
                <div class="coupon-activate">
                    <input 
                        type="text" 
                        class="input coupon-input" 
                        placeholder="Введите код купона"
                        id="coupon-code"
                    >
                    <button class="btn btn--dark" id="activate-coupon-btn">
                        Активировать
                    </button>
                </div>
            </div>

            <div class="bonus-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 35%"></div>
                </div>
                <div class="progress-labels">
                    <span>350/1000 баллов</span>
                    <span>До статуса "Постоянный"</span>
                </div>
            </div>

            <div class="coupons-section">
                <h3 class="coupons-section__title">Активные купоны</h3>
                <div class="coupons-grid">
                    <div class="coupon-card coupon-card--active">
                        <div class="coupon-discount">10%</div>
                        <div class="coupon-info">
                            <h4 class="coupon-title">На первую покупку</h4>
                            <p class="coupon-code">WELCOME10</p>
                            <p class="coupon-expiry">Действует до 30.04.2025</p>
                        </div>
                        <button class="btn btn--dark btn--sm coupon-copy" data-coupon-code="WELCOME10">
                            Скопировать
                        </button>
                    </div>
                    
                    <div class="coupon-card coupon-card--active">
                        <div class="coupon-discount">15%</div>
                        <div class="coupon-info">
                            <h4 class="coupon-title">Весенняя скидка</h4>
                            <p class="coupon-code">SPRING15</p>
                            <p class="coupon-expiry">Действует до 15.05.2025</p>
                        </div>
                        <button class="btn btn--dark btn--sm coupon-copy" data-coupon-code="SPRING15">
                            Скопировать
                        </button>
                    </div>
                </div>
            </div>

            <div class="coupons-section">
                <h3 class="coupons-section__title">История начислений</h3>
                <div class="bonus-history">
                    <div class="bonus-history-item bonus-history-item--plus">
                        <div class="bonus-history-info">
                            <h4 class="bonus-history-title">Начисление бонусов</h4>
                            <p class="bonus-history-date">12.03.2025</p>
                        </div>
                        <div class="bonus-history-amount">+50</div>
                    </div>
                    
                    <div class="bonus-history-item bonus-history-item--plus">
                        <div class="bonus-history-info">
                            <h4 class="bonus-history-title">За отзыв</h4>
                            <p class="bonus-history-date">05.03.2025</p>
                        </div>
                        <div class="bonus-history-amount">+20</div>
                    </div>
                    
                    <div class="bonus-history-item bonus-history-item--minus">
                        <div class="bonus-history-info">
                            <h4 class="bonus-history-title">Списание бонусов</h4>
                            <p class="bonus-history-date">01.03.2025</p>
                        </div>
                        <div class="bonus-history-amount">-100</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Вкладка "Безопасность" -->
        <section class="account-card" id="security-tab" role="tabpanel" aria-labelledby="security-tab" hidden>
            <h2 class="section-title">Безопасность</h2>
            
            <div class="security-list">
                <div class="security-item">
                    <div class="security-item__info">
                        <h3 class="security-item__title">Смена пароля</h3>
                        <p class="security-item__desc">Рекомендуем менять пароль каждые 3 месяца</p>
                    </div>
                    <button class="btn btn--outline" id="change-password-btn">
                        Изменить пароль
                    </button>
                </div>
                
                <div class="security-item">
                    <div class="security-item__info">
                        <h3 class="security-item__title">Двухфакторная аутентификация</h3>
                        <p class="security-item__desc">Дополнительная защита вашего аккаунта</p>
                    </div>
                    <div class="toggle-switch">
                        <input type="checkbox" id="2fa-toggle" class="toggle-switch__input">
                        <label for="2fa-toggle" class="toggle-switch__label"></label>
                    </div>
                </div>
                
                <div class="security-item security-item--danger">
                    <div class="security-item__info">
                        <h3 class="security-item__title">Удаление аккаунта</h3>
                        <p class="security-item__desc">Это действие необратимо. Все данные будут удалены.</p>
                    </div>
                    <button class="btn btn--danger" id="delete-account-btn">
                        Удалить аккаунт
                    </button>
                </div>
            </div>
        </section>
    </div>
</main>

  <!-- ПОДВАЛ -->
  <footer class="footer" role="contentinfo">
    <div class="container">
      <div class="footer__grid">
        <div>
          <div class="footer__brand">
            <div class="brand__mark" aria-hidden="true"><img src="../img/placeholder.webp" alt="Логотип"></div>
            <div class="brand__name">Лавка</div>
          </div>
          <p class="muted">Сувениры ручной работы. Упаковка, доставка, забота о деталях.</p>
        </div>

        <div>
          <h3 class="footer__title">Навигация</h3>
          <ul class="footer__list">
            <li><a class="footer__link" href="../index.php">Главная</a></li>
            <li><a class="footer__link" href="catalog.php">Каталог</a></li>
            <li><a class="footer__link" href="about.php">О компании</a></li>
          </ul>
        </div>

        <div>
          <h3 class="footer__title">Информация</h3>
          <ul class="footer__list">
            <li><a class="footer__link" href="about.php#delivery">Доставка</a></li>
            <li><a class="footer__link" href="about.php#returns">Возврат</a></li>
            <li><a class="footer__link" href="about.php#warranty">Гарантия</a></li>
          </ul>
        </div>

        <div>
          <h3 class="footer__title">Рассылка</h3>
          <p class="muted small">Новости и новые коллекции без спама.</p>
          <form class="sub" data-newsletter-form>
            <label for="newsletter-email" class="visually-hidden">Email для рассылки</label>
            <input id="newsletter-email" class="input" type="email" placeholder="Email" required />
            <button class="btn btn--dark" type="submit">Подписаться</button>
          </form>
        </div>
      </div>
      
      <div class="footer__bottom">
        <p class="muted small">&copy; 2026 «Лавка». Все права защищены.</p>
        <div class="footer__social">
          <a href="#" aria-label="Лавка в Instagram"><span aria-hidden="true">Instagram</span></a>
          <a href="#" aria-label="Лавка во ВКонтакте"><span aria-hidden="true">VK</span></a>
          <a href="#" aria-label="Лавка в Telegram"><span aria-hidden="true">Telegram</span></a>
        </div>
      </div>
    </div>
  </footer>

  <div class="sheet" id="favoritesSheet" aria-hidden="true" role="dialog" aria-modal="false" aria-labelledby="favorites-title-sheet">
    <div class="sheet__backdrop" data-close-sheet></div>
    <div class="sheet__panel">
      <div class="sheet__head">
        <h2 id="favorites-title-sheet" class="sheet__title">Избранное</h2>
        <button class="iconBtn" type="button" aria-label="Закрыть избранное" data-close-sheet>✕</button>
      </div>

      <div id="favorites-content" aria-live="polite">
        <p class="muted">В избранном пока ничего нет.</p>
      </div>

      <div class="favorites-actions" style="display: none;">
        <button class="btn btn--dark btn--full" id="add-all-to-cart">
          Добавить все в корзину
        </button>
        <button class="btn btn--outline btn--full" id="clear-favorites">
          Очистить избранное
        </button>
      </div>
    </div>
  </div>
  <script src="../js/script.js" defer></script>

  <script>
// Переключение вкладок личного кабинета
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация вкладок
    const tabs = document.querySelectorAll('.account-tab');
    const tabPanels = document.querySelectorAll('.account-card');
    
    // Показываем первую вкладку при загрузке
    if (tabs.length > 0) {
        showTab('profile');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.dataset.tab;
                showTab(tabId);
            });
        });
    }
    
    function showTab(tabId) {
        // Обновляем активную вкладку
        tabs.forEach(tab => {
            if (tab.dataset.tab === tabId) {
                tab.classList.add('active');
                tab.setAttribute('aria-selected', 'true');
            } else {
                tab.classList.remove('active');
                tab.setAttribute('aria-selected', 'false');
            }
        });
        
        // Показываем активную панель
        tabPanels.forEach(panel => {
            if (panel.id === `${tabId}-tab`) {
                panel.style.display = 'block';
                panel.setAttribute('hidden', false);
                panel.classList.add('active');
            } else {
                panel.style.display = 'none';
                panel.setAttribute('hidden', true);
                panel.classList.remove('active');
            }
        });
        
        // Прокручиваем к началу раздела
        document.querySelector('.account-page__inner').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
    
    // Копирование купонов
    document.querySelectorAll('.coupon-copy').forEach(button => {
        button.addEventListener('click', function() {
            const couponCode = this.dataset.couponCode;
            navigator.clipboard.writeText(couponCode).then(() => {
                const originalText = this.textContent;
                this.textContent = 'Скопировано!';
                this.classList.add('btn--success');
                
                setTimeout(() => {
                    this.textContent = originalText;
                    this.classList.remove('btn--success');
                }, 2000);
            });
        });
    });
    
    // Активация купона
    const activateCouponBtn = document.getElementById('activate-coupon-btn');
    const couponCodeInput = document.getElementById('coupon-code');
    
    if (activateCouponBtn && couponCodeInput) {
        activateCouponBtn.addEventListener('click', function() {
            const code = couponCodeInput.value.trim();
            if (!code) {
                showNotification('Введите код купона', 'error');
                return;
            }
            
            // Эмуляция отправки на сервер
            this.disabled = true;
            this.textContent = 'Активация...';
            
            setTimeout(() => {
                showNotification(`Купон ${code} успешно активирован!`, 'success');
                this.disabled = false;
                this.textContent = 'Активировать';
                couponCodeInput.value = '';
                
                // Обновляем список купонов (в реальном приложении здесь был бы AJAX)
                updateCouponsList(code);
            }, 1000);
        });
    }
    
    // Удаление из избранного
    document.querySelectorAll('[data-remove-favorite]').forEach(button => {
        button.addEventListener('click', function() {
            const favoriteItem = this.closest('.favorite-item');
            if (favoriteItem) {
                favoriteItem.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => {
                    favoriteItem.remove();
                    updateFavoritesCount();
                    checkEmptyFavorites();
                }, 300);
            }
        });
    });
    
    // Очистка всего избранного
    const clearFavoritesBtn = document.getElementById('clear-favorites-btn');
    if (clearFavoritesBtn) {
        clearFavoritesBtn.addEventListener('click', function() {
            if (confirm('Удалить все товары из избранного?')) {
                const favoritesGrid = document.querySelector('.favorites-grid');
                if (favoritesGrid) {
                    favoritesGrid.innerHTML = '';
                    updateFavoritesCount();
                    checkEmptyFavorites();
                    showNotification('Избранное очищено', 'info');
                }
            }
        });
    }
    
    // Изменение пароля
    const changePasswordBtn = document.getElementById('change-password-btn');
    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', function() {
            const newPassword = prompt('Введите новый пароль:');
            if (newPassword && newPassword.length >= 6) {
                showNotification('Пароль успешно изменен', 'success');
            } else if (newPassword) {
                showNotification('Пароль должен содержать не менее 6 символов', 'error');
            }
        });
    }
    
    // Удаление аккаунта
    const deleteAccountBtn = document.getElementById('delete-account-btn');
    if (deleteAccountBtn) {
        deleteAccountBtn.addEventListener('click', function() {
            if (confirm('Вы уверены? Это действие нельзя отменить. Все ваши данные будут удалены.')) {
                const secondConfirm = prompt('Для подтверждения введите "УДАЛИТЬ":');
                if (secondConfirm === 'УДАЛИТЬ') {
                    showNotification('Запрос на удаление аккаунта отправлен', 'info');
                    // В реальном приложении здесь был бы AJAX запрос
                }
            }
        });
    }
    
    // Вспомогательные функции
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification--${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
            color: white;
            border-radius: 8px;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    function updateFavoritesCount() {
        const favoritesCount = document.querySelectorAll('.favorite-item').length;
        const badge = document.querySelector('.account-tab[data-tab="favorites"] .badge');
        if (badge) {
            badge.textContent = favoritesCount;
        }
    }
    
    function checkEmptyFavorites() {
        const favoritesGrid = document.querySelector('.favorites-grid');
        const emptyState = document.querySelector('#favorites-tab .empty-state');
        if (favoritesGrid && emptyState) {
            if (favoritesGrid.children.length === 0) {
                emptyState.style.display = 'block';
            } else {
                emptyState.style.display = 'none';
            }
        }
    }
    
    function updateCouponsList(code) {
        // В реальном приложении здесь было бы обновление через AJAX
        const couponsGrid = document.querySelector('.coupons-grid');
        if (couponsGrid) {
            const newCoupon = document.createElement('div');
            newCoupon.className = 'coupon-card coupon-card--active';
            newCoupon.innerHTML = `
                <div class="coupon-discount">5%</div>
                <div class="coupon-info">
                    <h4 class="coupon-title">Новый купон</h4>
                    <p class="coupon-code">${code}</p>
                    <p class="coupon-expiry">Действует 30 дней</p>
                </div>
                <button class="btn btn--dark btn--sm coupon-copy" data-coupon-code="${code}">
                    Скопировать
                </button>
            `;
            
            couponsGrid.insertBefore(newCoupon, couponsGrid.firstChild);
            
            // Добавляем обработчик для новой кнопки
            newCoupon.querySelector('.coupon-copy').addEventListener('click', function() {
                navigator.clipboard.writeText(code).then(() => {
                    const originalText = this.textContent;
                    this.textContent = 'Скопировано!';
                    setTimeout(() => {
                        this.textContent = originalText;
                    }, 2000);
                });
            });
        }
    }
    
    // Анимации
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0; transform: scale(0.95); }
        }
    `;
    document.head.appendChild(style);
});

// Интеграция с главным скриптом
if (typeof window.initAccountPage === 'function') {
    window.initAccountPage();
}
</script>
</body>
</html>
