<?php
session_start();
$isAuth = isset($_SESSION['user_id']);
$hasAuthError = !empty($_SESSION['auth_error']);
?>
<!doctype html>
<html lang="ru" data-auth="<?php echo $isAuth ? '1' : '0'; ?>">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Каталог сувениров ручной работы — Лавка</title>
  <meta name="description" content="Каталог сувениров ручной работы: керамика, свечи, текстиль, декор и открытки. Подарочные наборы и персонализация." />
  <link rel="stylesheet" href="../css/style.css"/>
  <link rel="stylesheet" href="../css/main.css"/>
  <link rel="stylesheet" href="../css/catalog.css" />
  <link rel="stylesheet" href="../css/cart.css" />
</head>

<body>

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

        <!-- MEGA MENU -->
        <div class="mega" id="mega-menu" data-dropdown-menu role="menu" aria-label="Категории товаров">
          <div class="mega__grid">
            <div>
              <h2 class="mega__title" id="mega-title">Основные категории</h2>

              <div class="mega__cards" role="group" aria-labelledby="mega-title">
                <a class="mega__card" href="#group-candles" role="menuitem" data-close-mega>
                  <div class="mega__cardTitle">Свечи</div>
                  <div class="mega__cardText">Интерьерные, ароматные, необычные</div>
                </a>

                <a class="mega__card" href="#group-ceramics" role="menuitem" data-close-mega>
                  <div class="mega__cardTitle">Керамика</div>
                  <div class="mega__cardText">Кружки, тарелки, миски, фигурки</div>
                </a>

                <a class="mega__card" href="#group-decor" role="menuitem" data-close-mega>
                  <div class="mega__cardTitle">Декор</div>
                  <div class="mega__cardText">Фигурки, вазы, подсвечники</div>
                </a>

                <a class="mega__card" href="#group-textile" role="menuitem" data-close-mega>
                  <div class="mega__cardTitle">Текстиль</div>
                  <div class="mega__cardText">Игрушки, мешочки, панно, шарфы</div>
                </a>

                <a class="mega__card" href="#group-postcards" role="menuitem" data-close-mega>
                  <div class="mega__cardTitle">Открытки</div>
                  <div class="mega__cardText">Авторские, минимал, наборы</div>
                </a>

                <a class="mega__card" href="#group-sets" role="menuitem" data-close-mega>
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
                <a class="btn btn--dark btn--sm" href="#collectionsNav">Открыть</a>
              </div>

              <div class="mega__preview"
                   role="img"
                   aria-label="Подарочный набор из свечи и керамической кружки"
                   data-bg="../img/mega-preview.png">
              </div>

              <div class="mega__note">Быстрая навигация и фильтры — сверху каталога.</div>
            </div>
          </div>
        </div>
      </div>

      <a class="nav__link" href="about.php">О компании</a>

      <div class="nav__actions">
        <!-- 🔑 ИКОНКА АККАУНТА -->
        <?php if ($isAuth): ?>
          <a class="iconBtn iconBtn--auth"
             href="../php/account.php"
             aria-label="Личный кабинет">
            <svg viewBox="0 0 24 24" aria-hidden="true" class="iconUser">
              <circle cx="12" cy="8" r="3.2" />
              <path d="M5 19c1.4-3 3.6-4.5 7-4.5s5.6 1.5 7 4.5" />
            </svg>
          </a>
        <?php else: ?>
          <button class="iconBtn"
                  type="button"
                  aria-label="Войти"
                  data-open-modal="authModal">
            <svg viewBox="0 0 24 24" aria-hidden="true" class="iconUser">
              <circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.7"/>
              <path d="M5 19c1.4-3 3.6-4.5 7-4.5s5.6 1.5 7 4.5"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"/>
            </svg>
          </button>
        <?php endif; ?>

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

        <a class="btn btn--dark btn--sm hide-sm" href="cart.php">Корзина</a>
      </div>
    </nav>
  </div>
</header>

  <!-- MAIN CONTENT -->
  <main class="container section" id="main-content" role="main" tabindex="-1">

    <!-- Хлебные крошки -->
    <nav class="breadcrumbs" aria-label="Хлебные крошки">
      <ol>
        <li><a href="../index.php">Главная</a></li>
        <li><span aria-current="page">Каталог</span></li>
      </ol>
    </nav>

    <!-- Заголовок страницы -->
    <header class="page-header">
      <h1 class="h1">Каталог сувениров ручной работы</h1>
      <p class="lead">Уникальные подарки и предметы интерьера для вашего уюта</p>
    </header>

    <section id="home" class="section section--sm" aria-labelledby="for-home-title">
      <div class="grid3 mb-14" role="list">
        <a class="tile reveal" href="#group-ceramics" role="listitem">
          <div class="tile__img" role="img" aria-label="Керамические изделия ручной работы" data-bg="../img/ceramic.png"></div>
          <div class="tile__overlay">
            <div class="tile__title">Керамика</div>
            <div class="tile__sub">кружки • тарелки • фигурки</div>
          </div>
        </a>

        <a class="tile reveal" href="#group-postcards" role="listitem">
          <div class="tile__img" role="img" aria-label="Открытки ручной работы с акварельными рисунками" data-bg="../img/letter.png"></div>
          <div class="tile__overlay">
            <div class="tile__title">Открытки</div>
            <div class="tile__sub">акварель • авторские</div>
          </div>
        </a>

        <a class="tile reveal" href="#group-candles" role="listitem">
          <div class="tile__img" role="img" aria-label="Ароматические свечи из соевого воска" data-bg="../img/candle.png"></div>
          <div class="tile__overlay">
            <div class="tile__title">Свечи</div>
            <div class="tile__sub">соевые • ароматные</div>
          </div>
        </a>

        <a class="tile reveal" href="#group-textile" role="listitem">
          <div class="tile__img" role="img" aria-label="Текстильные изделия и мягкие игрушки" data-bg="../img/textile.png"></div>
          <div class="tile__overlay">
            <div class="tile__title">Текстиль</div>
            <div class="tile__sub">игрушки • вышивка</div>
          </div>
        </a>

        <a class="tile reveal" href="#group-decor" role="listitem">
          <div class="tile__img" role="img" aria-label="Декор для интерьера и фигурки" data-bg="../img/decor.png"></div>
          <div class="tile__overlay">
            <div class="tile__title">Декор</div>
            <div class="tile__sub">фигурки • вазы</div>
          </div>
        </a>

        <a class="tile reveal" href="#group-sets" role="listitem">
          <div class="tile__img" role="img" aria-label="Подарочные наборы в красивой упаковке" data-bg="../img/box.png"></div>
          <div class="tile__overlay">
            <div class="tile__title">Подарочные наборы</div>
            <div class="tile__sub">свечи • керамика • открытки</div>
          </div>
        </a>
      </div>

      <!-- ХИТЫ ПРОДАЖ -->
      <section class="hits reveal" id="hits" aria-labelledby="hits-title" data-filter-exclude>
        <div class="catalog-head">
          <div>
            <h2 id="hits-title" class="h2">Хиты продаж</h2>
            <p class="lead">Самые популярные товары — чаще всего выбирают в подарок.</p>
          </div>
        </div>

        <!-- быстрый ряд товаров -->
        <div class="grid4" role="list">
          <div class="reveal" data-product data-category="candles" data-id="candle-1" data-name="Свеча Природа" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Свеча Природа с ароматом трав" data-bg="../img/candle2.png">
                <span class="pbadge pbadge--hit">Хит</span>
              </div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Свеча «Природа»</h3>
                    <div class="card__meta">аромат трав • спокойное настроение</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">1 199</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="candle-1"
                          data-product-name="Свеча «Природа»" 
                          data-product-price="1199"
                          data-product-img="../img/candle2.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="candle-1" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="candle-1">−</button>
                    <span class="qty__val" id="cardQty-candle-1">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="candle-1">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Свеча Природа в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="candle-1"
                          data-product-name="Свеча «Природа»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="reveal" data-product data-category="ceramics" data-id="ceramic-1" data-name="Фигурка Дом" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Керамическая фигурка Дом" data-bg="../img/ceramic4.png">
                <span class="pbadge pbadge--hit">Хит</span>
              </div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Фигурка «Домик»</h3>
                    <div class="card__meta">керамика • декоративный акцент</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">1 999</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="ceramic-1"
                          data-product-name="Фигурка «Домик»" 
                          data-product-price="1999"
                          data-product-img="../img/ceramic4.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="ceramic-1" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="ceramic-1">−</button>
                    <span class="qty__val" id="cardQty-ceramic-1">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="ceramic-1">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Фигурка Дом в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="ceramic-1"
                          data-product-name="Фигурка «Домик»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="reveal" data-product data-category="textile" data-id="textile-1" data-name="Игрушка Мишка" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Мягкая игрушка Мишка ручной работы" data-bg="../img/textile1.png">
                <span class="pbadge pbadge--hit">Хит</span>
              </div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Игрушка «Мишка»</h3>
                    <div class="card__meta">мягкая • ручная работа</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">1 699</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="textile-1"
                          data-product-name="Игрушка «Мишка»" 
                          data-product-price="1699"
                          data-product-img="../img/textile1.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="textile-1" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="textile-1">−</button>
                    <span class="qty__val" id="cardQty-textile-1">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="textile-1">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Игрушка Мишка в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="textile-1"
                          data-product-name="Игрушка «Мишка»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="reveal" data-product data-category="decor" data-id="decor-1" data-name="Ваза Спокойствие" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Ваза Спокойствие пастельного оттенка" data-bg="../img/decor2.png">
                <span class="pbadge pbadge--hit">Хит</span>
              </div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Ваза «Спокойствие»</h3>
                    <div class="card__meta">пастельный оттенок • для сухоцветов</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">1 999</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="decor-1"
                          data-product-name="Ваза «Спокойствие»" 
                          data-product-price="1999"
                          data-product-img="../img/decor2.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="decor-1" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="decor-1">−</button>
                    <span class="qty__val" id="cardQty-decor-1">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="decor-1">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Ваза Спокойствие в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="decor-1"
                          data-product-name="Ваза «Спокойствие»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </section>

    <!-- ПЕРСОНАЛЬНЫЕ ПОДАРКИ -->
    <section class="personal-gift reveal" id="personalGift" aria-labelledby="personal-gift-title">
      <div class="personal-gift__bg"
           role="img"
           aria-label="Персонализированный подарок с гравировкой имени"
           data-bg="../img/personal-gift.png"></div>

      <div class="personal-gift__veil" aria-hidden="true"></div>

      <div class="personal-gift__inner container">
        <!-- текст -->
        <div class="personal-gift__content">
          <p class="personal-gift__top">ПЕРСОНАЛИЗАЦИЯ • ИМЯ • ДАТА</p>

          <h2 id="personal-gift-title" class="personal-gift__title">
            Персональный подарок:<br />
            добавим имя или пожелание.
          </h2>

          <p class="personal-gift__text">
            Для некоторых изделий доступна гравировка или надпись.
            Срок изготовления — <strong>от 1 до 5 дней</strong>.
          </p>

          <ul class="personal-gift__bullets" aria-label="Что можно сделать">
            <li>Имя или короткая фраза</li>
            <li>Дата & инициалы</li>
            <li>Пожелание на открытке</li>
          </ul>
        </div>

        <!-- форма -->
        <div class="personal-gift__formCard" role="region" aria-label="Заявка на персонализацию">
          <div class="pgForm__head">
            <div>
              <div class="pgForm__kicker">Заявка за 1 минуту</div>
              <h3 class="pgForm__title">Хочу гравировку</h3>
            </div>
            <div class="pgForm__badge" aria-hidden="true">1–5 дней</div>
          </div>

          <form class="pgForm" id="engraveForm" action="#" method="post" novalidate>
            <div class="pgForm__grid">
              <label class="pgField">
                <span class="pgField__label">Текст гравировки</span>
                <input class="input" type="text" name="engraveText" maxlength="40"
                       placeholder="Например: "Дорогой Ане"" />
                <span class="pgField__hint">до 40 символов</span>
              </label>

              <label class="pgField">
                <span class="pgField__label">На каком изделии?</span>
                <div class="select-wrap">
                  <select class="input" name="engraveOn" required>
                    <option value="">Выберите изделие</option>
                    <option value="postcard">Открытка</option>
                    <option value="mug">Кружка</option>
                    <option value="plate">Тарелка</option>
                    <option value="cat">Фигурка «Кот»</option>
                    <option value="bear">Игрушка «Мишка»</option>
                  </select>
                </div>
              </label>

              <label class="pgField">
                <span class="pgField__label">Срок</span>
                <div class="select-wrap">
                  <select class="input" name="deadline">
                    <option>Не срочно (1–5 дней)</option>
                    <option>Как можно быстрее</option>
                    <option>К конкретной дате</option>
                  </select>
                </div>
              </label>

              <label class="pgField">
                <span class="pgField__label">Связаться со мной</span>
                <input class="input" type="tel" name="contact"
                       placeholder="+7 (999) 000-00-00" />
              </label>

              <label class="pgField pgField--full">
                <span class="pgField__label">Комментарий</span>
                <textarea class="input pgTextarea" name="comment" rows="3"
                          placeholder="Например: "Нужна надпись на донышке""></textarea>
              </label>
            </div>

            <div class="pgForm__actions">
              <button type="button" class="btn btn--dark btn--full">
                Отправить заявку
              </button>
              <p class="muted small pgForm__note">
                Нажимая кнопку, вы соглашаетесь на обработку данных.
              </p>
            </div>
          </form>
        </div>
      </div>
    </section>

    <section class="section section--sm" id="productGroups" aria-label="Группы товаров">
      <section class="filters-bar reveal" id="collectionsNav" aria-labelledby="filters-title">
        <div class="filters-left">
          <h2 id="filters-title" class="visually-hidden">Фильтры каталога</h2>
          <!-- фильтр по категориям -->
          <div class="filters-row" id="categoryFilters" role="tablist" aria-label="Категории товаров">
            <button class="chip chip--filter is-active" 
                    type="button" 
                    role="tab"
                    aria-selected="true"
                    aria-controls="all-products"
                    data-filter="all"
                    id="filter-all">Показать все</button>
            <button class="chip chip--filter" 
                    type="button" 
                    role="tab"
                    aria-selected="false"
                    aria-controls="candles-products"
                    data-filter="candles"
                    id="filter-candles">Свечи</button>
            <button class="chip chip--filter" 
                    type="button" 
                    role="tab"
                    aria-selected="false"
                    aria-controls="ceramics-products"
                    data-filter="ceramics"
                    id="filter-ceramics">Керамика</button>
            <button class="chip chip--filter" 
                    type="button" 
                    role="tab"
                    aria-selected="false"
                    aria-controls="decor-products"
                    data-filter="decor"
                    id="filter-decor">Декор</button>
            <button class="chip chip--filter" 
                    type="button" 
                    role="tab"
                    aria-selected="false"
                    aria-controls="textile-products"
                    data-filter="textile"
                    id="filter-textile">Текстиль</button>
            <button class="chip chip--filter" 
                    type="button" 
                    role="tab"
                    aria-selected="false"
                    aria-controls="postcards-products"
                    data-filter="postcards"
                    id="filter-postcards">Открытки</button>
            <button class="chip chip--filter" 
                    type="button" 
                    role="tab"
                    aria-selected="false"
                    aria-controls="sets-products"
                    data-filter="sets"
                    id="filter-sets">Наборы</button>
          </div>
        </div>

        <!-- поиск -->
        <div class="search-wrap">
          <label for="searchInput" class="visually-hidden">Поиск по каталогу</label>
          <svg class="search-ico" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.6"></circle>
            <path d="M16.2 16.2L21 21" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
          </svg>

          <input
            id="searchInput"
            class="input input--lg"
            type="search"
            placeholder="Поиск по названию…"
            aria-label="Поиск по названию товара"
            autocomplete="off"
            data-search-input
          />
          <button class="search-clear visually-hidden" type="button" aria-label="Очистить поиск" data-search-clear>✕</button>
        </div>
      </section>

      <span id="filtersAnchor"></span>

      <!-- Счётчики результатов -->
      <div class="results-info" aria-live="polite" aria-atomic="true" style="display: none;">
        <p id="results-count">Найдено <span id="results-number">0</span> товаров</p>
        <button class="btn btn--text btn--sm" id="clear-filters" style="display: none;">Сбросить фильтры</button>
      </div>

      <!-- ===== Керамика ===== -->
      <section class="group-block group-block--hero reveal" id="group-ceramics" data-group="ceramics" aria-labelledby="ceramics-title">
        <div class="group-head">
          <div>
            <h3 id="ceramics-title" class="group-title">Керамика</h3>
            <p class="group-desc">
              Ручная лепка, приятные формы и спокойные оттенки — для ежедневных маленьких ритуалов.
            </p>
          </div>
        </div>

        <div class="grid4" role="list">
          <div data-product data-category="ceramics" data-id="ceramic-2" data-name="Кружка Утро" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Кружка Утро ручной лепки с матовой глазурью" data-bg="../img/ceramic1.png"></div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Кружка «Утро»</h3>
                    <div class="card__meta">ручная лепка • матовая глазурь</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">1 499</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="ceramic-2"
                          data-product-name="Кружка «Утро»" 
                          data-product-price="1499"
                          data-product-img="../img/ceramic1.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="ceramic-2" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="ceramic-2">−</button>
                    <span class="qty__val" id="cardQty-ceramic-2">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="ceramic-2">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Кружка Утро в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="ceramic-2"
                          data-product-name="Кружка «Утро»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div data-product data-category="ceramics" data-id="ceramic-3" data-name="Тарелка Мини" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Тарелка Мини для украшений и мелочей" data-bg="../img/ceramic2.png"></div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Тарелка «Мини»</h3>
                    <div class="card__meta">для украшений, свечей и мелочей</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">999</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="ceramic-3"
                          data-product-name="Тарелка «Мини»" 
                          data-product-price="999"
                          data-product-img="../img/ceramic2.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="ceramic-3" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="ceramic-3">−</button>
                    <span class="qty__val" id="cardQty-ceramic-3">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="ceramic-3">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Тарелка Мини в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="ceramic-3"
                          data-product-name="Тарелка «Мини»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div data-product data-category="ceramics" data-id="ceramic-4" data-name="Миска Тепло" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Миска Тепло с натуральной текстурой" data-bg="../img/ceramic3.png"></div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Миска «Тепло»</h3>
                    <div class="card__meta">натуральная текстура • универсальная</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">1 299</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="ceramic-4"
                          data-product-name="Миска «Тепло»" 
                          data-product-price="1299"
                          data-product-img="../img/ceramic3.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="ceramic-4" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="ceramic-4">−</button>
                    <span class="qty__val" id="cardQty-ceramic-4">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="ceramic-4">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Миска Тепло в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="ceramic-4"
                          data-product-name="Миска «Тепло»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div data-product data-category="ceramics" data-id="ceramic-5" data-name="Фигурка Домик" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Фигурка Домик из керамики" data-bg="../img/ceramic4.png">
                <span class="pbadge pbadge--hit">Хит</span>
              </div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Фигурка «Домик»</h3>
                    <div class="card__meta">керамика • декоративный акцент</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">1 999</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="ceramic-1"
                          data-product-name="Фигурка «Домик»" 
                          data-product-price="1999"
                          data-product-img="../img/ceramic4.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="ceramic-1" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="ceramic-1">−</button>
                    <span class="qty__val" id="cardQty-ceramic-1">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="ceramic-1">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Фигурка Домик в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="ceramic-1"
                          data-product-name="Фигурка «Домик»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ===== Открытки ===== -->
      <section class="group-block group-block--hero reveal" id="group-postcards" data-group="postcards" aria-labelledby="postcards-title">
        <div class="group-head">
          <div>
            <h3 id="postcards-title" class="group-title">Открытки</h3>
            <p class="group-desc">
              Тёплые слова в красивой форме — идеально как самостоятельный подарок или дополнение к набору.
            </p>
          </div>
        </div>

        <div class="grid4" role="list">
          <div data-product data-category="postcards" data-id="postcard-1" data-name="Открытка Цветы" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Открытка Цветы с акварельным рисунком" data-bg="../img/letter1.png"></div>
              <span class="pbadge pbadge--new">Новинка</span>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Открытка «Цветы»</h3>
                    <div class="card__meta">акварель • авторская</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">249</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="postcard-1"
                          data-product-name="Открытка «Цветы»" 
                          data-product-price="249"
                          data-product-img="../img/letter1.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="postcard-1" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="postcard-1">−</button>
                    <span class="qty__val" id="cardQty-postcard-1">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="postcard-1">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Открытка Цветы в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="postcard-1"
                          data-product-name="Открытка «Цветы»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div data-product data-category="postcards" data-id="postcard-2" data-name="Открытка Дом" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Открытка Дом в минималистичном стиле" data-bg="../img/letter2.png"></div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Открытка «Дом»</h3>
                    <div class="card__meta">минимализм • тёплые пожелания</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">249</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="postcard-2"
                          data-product-name="Открытка «Дом»" 
                          data-product-price="249"
                          data-product-img="../img/letter2.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="postcard-2" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="postcard-2">−</button>
                    <span class="qty__val" id="cardQty-postcard-2">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="postcard-2">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Открытка Дом в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="postcard-2"
                          data-product-name="Открытка «Дом»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div data-product data-category="postcards" data-id="postcard-3" data-name="Открытка С любовью" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Открытка С любовью для подарков" data-bg="../img/letter3.png"></div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Открытка «С любовью»</h3>
                    <div class="card__meta">для подарков • ручная работа</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">299</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="postcard-3"
                          data-product-name="Открытка «С любовью»" 
                          data-product-price="299"
                          data-product-img="../img/letter3.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="postcard-3" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="postcard-3">−</button>
                    <span class="qty__val" id="cardQty-postcard-3">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="postcard-3">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Открытка С любовью в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="postcard-3"
                          data-product-name="Открытка «С любовью»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div data-product data-category="postcards" data-id="postcard-4" data-name="Открытка С новым годом" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Открытка С новым годом с тёплыми пожеланиями" data-bg="../img/letter4.png"></div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Открытка «С новым годом»</h3>
                    <div class="card__meta">тёплые пожелания • ручная работа</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">299</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="postcard-4"
                          data-product-name="Открытка «С новым годом»" 
                          data-product-price="299"
                          data-product-img="../img/letter4.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="postcard-4" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="postcard-4">−</button>
                    <span class="qty__val" id="cardQty-postcard-4">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="postcard-4">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Открытка С новым годом в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="postcard-4"
                          data-product-name="Открытка «С новым годом»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ===== Свечи ===== -->
      <section class="group-block group-block--hero reveal" id="group-candles" data-group="candles" aria-labelledby="candles-title">
        <div class="group-head">
          <div>
            <h3 id="candles-title" class="group-title">Свечи</h3>
            <p class="group-desc">
              Интерьерные и ароматные свечи — для настроения и уюта.
            </p>
          </div>
        </div>

        <div class="grid4" role="list">
          <div data-product data-category="candles" data-id="candle-2" data-name="Свеча необычная" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Свеча необычная жёлтая с необычной формой" data-bg="../img/candle1.png"></div>
              <span class="pbadge pbadge--new">Новинка</span>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Свеча «Необычная»</h3>
                    <div class="card__meta">жёлтая • необычная форма</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">999</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="candle-2"
                          data-product-name="Свеча «Необычная»" 
                          data-product-price="999"
                          data-product-img="../img/candle1.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="candle-2" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="candle-2">−</button>
                    <span class="qty__val" id="cardQty-candle-2">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="candle-2">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Свеча Необычная в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="candle-2"
                          data-product-name="Свеча «Необычная»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div data-product data-category="candles" data-id="candle-3" data-name="Свеча Природа зеленая" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Свеча Природа с ароматом ели" data-bg="../img/candle2.png">
                <span class="pbadge pbadge--hit">Хит</span>
              </div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Свеча «Природа»</h3>
                    <div class="card__meta">аромат ели • спокойствие</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">1 199</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="candle-1"
                          data-product-name="Свеча «Природа»" 
                          data-product-price="1199"
                          data-product-img="../img/candle2.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="candle-1" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="candle-1">−</button>
                    <span class="qty__val" id="cardQty-candle-1">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="candle-1">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Свеча Природа зеленая в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="candle-1"
                          data-product-name="Свеча «Природа»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div data-product data-category="candles" data-id="candle-4" data-name="Свеча Форма" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Свеча Форма декоративная для интерьера" data-bg="../img/candle3.png"></div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Свеча «Форма»</h3>
                    <div class="card__meta">декоративная • акцент в интерьере</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">899</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="candle-4"
                          data-product-name="Свеча «Форма»" 
                          data-product-price="899"
                          data-product-img="../img/candle3.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="candle-4" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="candle-4">−</button>
                    <span class="qty__val" id="cardQty-candle-4">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="candle-4">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Свеча Форма в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="candle-4"
                          data-product-name="Свеча «Форма»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div data-product data-category="candles" data-id="candle-5" data-name="Свеча Вечер" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Свеча Вечер компактная для уютных вечеров" data-bg="../img/candle4.png"></div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Свеча «Вечер»</h3>
                    <div class="card__meta">компактная • уютные вечера</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">999</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="candle-5"
                          data-product-name="Свеча «Вечер»" 
                          data-product-price="999"
                          data-product-img="../img/candle4.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="candle-5" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="candle-5">−</button>
                    <span class="qty__val" id="cardQty-candle-5">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="candle-5">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Свеча Вечер в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="candle-5"
                          data-product-name="Свеча «Вечер»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ===== Текстиль ===== -->
      <section class="group-block group-block--hero reveal" id="group-textile" data-group="textile" aria-labelledby="textile-title">
        <div class="group-head">
          <div>
            <h3 id="textile-title" class="group-title">Текстиль</h3>
            <p class="group-desc">
              Мягкие и тёплые вещи ручной работы: игрушки, мешочки, панно и шарфы.
            </p>
          </div>
        </div>

        <div class="grid4" role="list">
          <div data-product data-category="textile" data-id="textile-2" data-name="Игрушка Мишка большой" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Игрушка Мишка большая мягкая" data-bg="../img/textile1.png">
                <span class="pbadge pbadge--hit">Хит</span>
              </div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Игрушка «Мишка»</h3>
                    <div class="card__meta">мягкая • ручная работа</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">1 699</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="textile-1"
                          data-product-name="Игрушка «Мишка»" 
                          data-product-price="1699"
                          data-product-img="../img/textile1.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="textile-1" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="textile-1">−</button>
                    <span class="qty__val" id="cardQty-textile-1">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="textile-1">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Игрушка Мишка большой в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="textile-1"
                          data-product-name="Игрушка «Мишка»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div data-product data-category="textile" data-id="textile-3" data-name="Мешочек Лён" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Мешочек Лён для хранения и подарков" data-bg="../img/textile2.png"></div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Мешочек «Лён»</h3>
                    <div class="card__meta">для хранения и подарков • натурально</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">499</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="textile-3"
                          data-product-name="Мешочек «Лён»" 
                          data-product-price="499"
                          data-product-img="../img/textile2.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="textile-3" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="textile-3">−</button>
                    <span class="qty__val" id="cardQty-textile-3">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="textile-3">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Мешочек Лён в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="textile-3"
                          data-product-name="Мешочек «Лён»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div data-product data-category="textile" data-id="textile-4" data-name="Панно Цветок" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Панно Цветок с вышивкой" data-bg="../img/textile3.png"></div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Панно «Цветок»</h3>
                    <div class="card__meta">вышивка • декоративное панно</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">1 199</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="textile-4"
                          data-product-name="Панно «Цветок»" 
                          data-product-price="1199"
                          data-product-img="../img/textile3.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="textile-4" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="textile-4">−</button>
                    <span class="qty__val" id="cardQty-textile-4">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="textile-4">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Панно Цветок в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="textile-4"
                          data-product-name="Панно «Цветок»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div data-product data-category="textile" data-id="textile-5" data-name="Шарф Тепло" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Шарф Тепло из натуральных материалов" data-bg="../img/textile4.png"></div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Шарф «Тепло»</h3>
                    <div class="card__meta">лёгкий • натуральные материалы</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">2 999</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="textile-5"
                          data-product-name="Шарф «Тепло»" 
                          data-product-price="2999"
                          data-product-img="../img/textile4.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="textile-5" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="textile-5">−</button>
                    <span class="qty__val" id="cardQty-textile-5">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="textile-5">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Шарф Тепло в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="textile-5"
                          data-product-name="Шарф «Тепло»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ===== Декор ===== -->
      <section class="group-block group-block--hero reveal" id="group-decor" data-group="decor" aria-labelledby="decor-title">
        <div class="group-head">
          <div>
            <h3 id="decor-title" class="group-title">Декор</h3>
            <p class="group-desc">
              Небольшие элементы для полок и столов: фигурки, вазы и подсвечники.
            </p>
          </div>
        </div>

        <div class="grid4" role="list">
          <div data-product data-category="decor" data-id="decor-2" data-name="Фигурка Кот" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Фигурка Кот из дерева" data-bg="../img/decor1.png">
                <span class="pbadge pbadge--new">Новинка</span>
              </div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Фигурка «Кот»</h3>
                    <div class="card__meta">дерево • ручная работа</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">1 499</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="decor-2"
                          data-product-name="Фигурка «Кот»" 
                          data-product-price="1499"
                          data-product-img="../img/decor1.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="decor-2" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="decor-2">−</button>
                    <span class="qty__val" id="cardQty-decor-2">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="decor-2">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Фигурка Кот в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="decor-2"
                          data-product-name="Фигурка «Кот»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div data-product data-category="decor" data-id="decor-3" data-name="Ваза Спокойствие большая" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Ваза Спокойствие большая для сухоцветов" data-bg="../img/decor2.png">
                <span class="pbadge pbadge--hit">Хит</span>
              </div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Ваза «Спокойствие»</h3>
                    <div class="card__meta">пастельный оттенок • для сухоцветов</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">1 999</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="decor-1"
                          data-product-name="Ваза «Спокойствие»" 
                          data-product-price="1999"
                          data-product-img="../img/decor2.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="decor-1" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="decor-1">−</button>
                    <span class="qty__val" id="cardQty-decor-1">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="decor-1">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Ваза Спокойствие большая в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="decor-1"
                          data-product-name="Ваза «Спокойствие»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div data-product data-category="decor" data-id="decor-4" data-name="Подсвечник Домик" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Подсвечник Домик из керамики" data-bg="../img/decor3.png"></div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Подсвечник «Домик»</h3>
                    <div class="card__meta">керамика • для чайной свечи</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">1 499</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="decor-4"
                          data-product-name="Подсвечник «Домик»" 
                          data-product-price="1499"
                          data-product-img="../img/decor3.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="decor-4" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="decor-4">−</button>
                    <span class="qty__val" id="cardQty-decor-4">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="decor-4">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Подсвечник Домик в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="decor-4"
                          data-product-name="Подсвечник «Домик»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div data-product data-category="decor" data-id="decor-5" data-name="Мини-декор Сердце" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Мини-декор Сердце для полок" data-bg="../img/decor4.png"></div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Мини-декор «Сердце»</h3>
                    <div class="card__meta">небольшой акцент • для полок</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">799</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="decor-5"
                          data-product-name="Мини-декор «Сердце»" 
                          data-product-price="799"
                          data-product-img="../img/decor4.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="decor-5" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="decor-5">−</button>
                    <span class="qty__val" id="cardQty-decor-5">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="decor-5">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Мини-декор Сердце в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="decor-5"
                          data-product-name="Мини-декор «Сердце»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ===== Наборы ===== -->
      <section class="group-block group-block--hero reveal" id="group-sets" data-group="sets" aria-labelledby="sets-title">
        <div class="group-head">
          <div>
            <h3 id="sets-title" class="group-title">Подарочные наборы</h3>
            <p class="group-desc">
              Красиво упакованные боксы — можно дарить сразу, без лишних забот.
            </p>
          </div>
        </div>

        <div class="grid4" role="list">
          <div data-product data-category="sets" data-id="set-1" data-name="Набор Уют" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Набор Уют со свечой и тарелкой" data-bg="../img/box1.png">
                <span class="pbadge pbadge--new">Новинка</span>
              </div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Набор «Уют»</h3>
                    <div class="card__meta">свеча • тарелка</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">2 399</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="set-1"
                          data-product-name="Набор «Уют»" 
                          data-product-price="2399"
                          data-product-img="../img/box1.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="set-1" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="set-1">−</button>
                    <span class="qty__val" id="cardQty-set-1">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="set-1">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Набор Уют в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="set-1"
                          data-product-name="Набор «Уют»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div data-product data-category="sets" data-id="set-2" data-name="Набор Теплый дом" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Набор Тёплый дом со свечой, открыткой и фигуркой" data-bg="../img/box2.png"></div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Набор «Тёплый дом»</h3>
                    <div class="card__meta">свеча • открытка • фигурка</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">2 999</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="set-2"
                          data-product-name="Набор «Тёплый дом»" 
                          data-product-price="2999"
                          data-product-img="../img/box2.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="set-2" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="set-2">−</button>
                    <span class="qty__val" id="cardQty-set-2">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="set-2">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Набор Теплый дом в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="set-2"
                          data-product-name="Набор «Тёплый дом»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div data-product data-category="sets" data-id="set-3" data-name="Набор Нежность" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Набор Нежность с игрушкой и открыткой" data-bg="../img/box3.png"></div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Набор «Нежность»</h3>
                    <div class="card__meta">игрушка • открытка</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">2 199</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="set-3"
                          data-product-name="Набор «Нежность»" 
                          data-product-price="2199"
                          data-product-img="../img/box3.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="set-3" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="set-3">−</button>
                    <span class="qty__val" id="cardQty-set-3">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="set-3">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Набор Нежность в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="set-3"
                          data-product-name="Набор «Нежность»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div data-product data-category="sets" data-id="set-4" data-name="Набор Малый" class="reveal" role="listitem">
            <div class="card">
              <div class="card__img" role="img" aria-label="Набор Малый с фигуркой и открыткой" data-bg="../img/box4.png"></div>
              <div class="card__body">
                <div class="card__top">
                  <div>
                    <h3 class="card__title">Набор «Малый»</h3>
                    <div class="card__meta">фигурка • открытка</div>
                  </div>
                  <div class="card__price">
                    <span class="price-amount">1 899</span> ₽
                  </div>
                </div>
                <div class="card__actions">
                  <button class="btn btn--dark btn--full" 
                          data-add-to-cart 
                          data-product-id="set-4"
                          data-product-name="Набор «Малый»" 
                          data-product-price="1899"
                          data-product-img="../img/box4.png">
                    В корзину
                  </button>
                  <!-- ДОБАВЛЕН БЛОК СЧЕТЧИКА -->
                  <div class="qty qty--card" data-qty-wrap="set-4" style="display:none;">
                    <button class="qty__btn" type="button" aria-label="Уменьшить количество" data-qty-minus="set-4">−</button>
                    <span class="qty__val" id="cardQty-set-4">1</span>
                    <button class="qty__btn" type="button" aria-label="Увеличить количество" data-qty-plus="set-4">+</button>
                  </div>
                  <button class="iconBtn" 
                          aria-label="Добавить Набор Малый в избранное"
                          aria-pressed="false"
                          data-fav-btn
                          data-product-id="set-4"
                          data-product-name="Набор «Малый»">
                    <svg class="favorites-icon" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="1.6"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </section>
  </main>

<!-- FOOTER -->
<footer class="footer" role="contentinfo">
  <!-- Кнопка "Наверх" -->
  <button class="to-top" id="toTopBtn" aria-label="Вернуться наверх" style="display: none;">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <polyline points="18 15 12 9 6 15"></polyline>
    </svg>
  </button>

  <div class="container">
    <div class="footer__grid">
      <!-- Блок с логотипом -->
      <div>
        <a href="../index.php" class="footer__brand-link">
          <div class="footer__brand">
            <div class="brand__mark" aria-hidden="true">
              <img src="../img/placeholder.webp" alt="Логотип Лавка">
            </div>
            <div class="brand__name">Лавка</div>
          </div>
        </a>
        <p class="muted">Сувениры ручной работы и забота о деталях.</p>
        
        <!-- Соцсети с иконками -->
        <div class="footer__social-icons">
          <div class="social-icons">
            <a href="#" class="social-icon" aria-label="ВКонтакте" title="ВКонтакте">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M21.579 6.855c.14-.465 0-.806-.662-.806h-2.193c-.558 0-.815.295-.956.619 0 0-1.118 2.719-2.695 4.482-.51.513-.743.675-1.021.675-.139 0-.341-.162-.341-.627V6.855c0-.558-.161-.806-.626-.806H9.642c-.348 0-.558.258-.558.504 0 .528.79.65.87 2.138v3.228c0 .707-.127.836-.407.836-.743 0-2.551-2.729-3.624-5.853-.209-.607-.42-.853-.98-.853H2.752c-.627 0-.752.295-.752.619 0 .582.743 3.462 3.461 7.271 1.812 2.601 4.363 4.011 6.687 4.011 1.393 0 1.565-.313 1.565-.853v-1.966c0-.626.133-.752.57-.752.324 0 .882.164 2.183 1.417 1.486 1.486 1.732 2.153 2.567 2.153h2.192c.626 0 .939-.313.759-.931-.197-.615-.907-1.51-1.849-2.569-.512-.604-1.277-1.254-1.51-1.579-.325-.419-.231-.604 0-.976.001.001 2.672-3.761 2.95-5.04z"/>
              </svg>
            </a>
            <a href="#" class="social-icon" aria-label="Telegram" title="Telegram">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M20.665 3.717l-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l.002.001-.314 4.692c.46 0 .663-.211.921-.46l2.211-2.15 4.599 3.397c.848.467 1.457.227 1.668-.785l3.019-14.228c.309-1.239-.473-1.8-1.282-1.434z"/>
              </svg>
            </a>
            <a href="#" class="social-icon" aria-label="YouTube" title="YouTube">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
              </svg>
            </a>
          </div>
        </div>
      </div>

      <!-- Навигация -->
      <div>
        <h3 class="footer__title">Навигация</h3>
        <ul class="footer__list">
          <li><a class="footer__link" href="../index.php">Главная</a></li>
          <li><a class="footer__link" href="about.php">О компании</a></li>
          <li><a class="footer__link" href="catalog.php">Каталог</a></li>
          <li><a class="footer__link" href="registration.php">Регистрация</a></li>
        </ul>
      </div>

      <!-- Информация -->
      <div>
        <h3 class="footer__title">Информация</h3>
        <ul class="footer__list">
          <li><a class="footer__link" href="about.php#delivery">Доставка</a></li>
          <li><a class="footer__link" href="about.php#returns">Возврат</a></li>
          <li><a class="footer__link" href="about.php#materials">Материалы</a></li>
          <li><a class="footer__link" href="about.php#contacts">Контакты</a></li>
        </ul>
      </div>

      <!-- Рассылка -->
      <div>
        <h3 class="footer__title">Рассылка</h3>
        <p class="muted small">Новости и новые коллекции без спама. Первым узнавайте о скидках!</p>
        <form class="sub" data-newsletter-form>
          <label for="newsletter-email" class="visually-hidden">Email для рассылки</label>
          <input id="newsletter-email" class="input" type="email" placeholder="Ваш email" required />
          <button class="btn btn--dark" type="submit">Подписаться</button>
        </form>
      </div>
    </div>

    <div class="footer__bottom">
      <p class="muted small">&copy; 2026 «Лавка». Все права защищены.</p>
    </div>
  </div>
</footer>

<script>
  // Скрипт для кнопки "Наверх"
  document.addEventListener('DOMContentLoaded', function() {
    const toTopBtn = document.getElementById('toTopBtn');
  
    window.addEventListener('scroll', function() {
      if (window.pageYOffset > 300) {
        toTopBtn.style.display = 'flex';
      } else {
        toTopBtn.style.display = 'none';
      }
    });
    
    toTopBtn.addEventListener('click', function() {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
    
    const newsletterForm = document.querySelector('[data-newsletter-form]');
    if (newsletterForm) {
      newsletterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const emailInput = this.querySelector('#newsletter-email');
        const email = emailInput.value.trim();
        
        if (email && email.includes('@')) {
          console.log('Подписка на рассылку:', email);
          alert('Спасибо за подписку! На ' + email + ' отправлено письмо с подтверждением.');
          emailInput.value = '';
        }
      });
    }
  });
</script>

<div class="modal" id="authModal" aria-hidden="true"
     <?php if (!empty($_SESSION['auth_error'])) echo 'data-autoshow="1"'; ?>>
  <div class="modal__backdrop" data-close></div>

  <div class="modal__dialog" role="dialog" aria-modal="true" aria-label="Авторизация">
    <div class="modal__head">
      <div class="modal__title">Вход в аккаунт</div>
      <button class="iconBtn" type="button" data-close aria-label="Закрыть">✕</button>
    </div>

    <div class="modal__body">

      <?php if (!empty($_SESSION['auth_error'])): ?>
        <div class="alert alert--error" style="color:#b00020; margin-bottom:10px;">
          <?= htmlspecialchars($_SESSION['auth_error']) ?>
        </div>
        <?php unset($_SESSION['auth_error']); ?>
      <?php endif; ?>

      <form action="../php/auth.php" method="post" class="needs-validation" novalidate>
        <div class="mb-3">
          <label for="authLogin" class="small">Логин или email</label>
          <input id="authLogin" class="input input--lg" type="text" name="login" required>
        </div>

        <div class="mb-3">
          <label for="authPass" class="small">Пароль</label>
          <input id="authPass" class="input input--lg" type="password" name="password" required>
        </div>

        <button class="btn btn--dark btn--full" style="margin-top:20px;" type="submit">Войти</button>
      </form>

      <p class="muted small" style="margin-top:12px;">
        Нет аккаунта?
        <a href="registration.php">Зарегистрироваться</a>
      </p>
    </div>
  </div>
</div>

<!-- FAVORITES SHEET -->
<aside class="sheet" id="favoritesSheet" aria-hidden="true">
  <div class="sheet__backdrop" data-close></div>

  <div class="sheet__panel" role="dialog" aria-modal="true" aria-label="Избранное">
    <div class="sheet__head">
      <div class="sheet__title">Избранное</div>
      <button class="iconBtn" type="button" data-close aria-label="Закрыть">✕</button>
    </div>

    <div id="favorites-content"></div>

    <div class="favorites-actions">
      <button class="btn btn--dark btn--full" type="button" id="add-all-to-cart">Добавить всё в корзину</button>
      <button class="btn btn--full" type="button" id="clear-favorites">Очистить избранное</button>
    </div>
  </div>
</aside>

<script src="../js/script.js" defer></script>
<script src="../js/cart.js" defer></script>

</body>
</html>