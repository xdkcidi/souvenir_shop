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
  <title>О нас — Лавка</title>
  <meta name="description" content="О компании Лавка: мастерская, материалы, доставка, возвраты и гарантия. Контакты." />
  <link rel="stylesheet" href="../css/cart.css"/>
  <link rel="stylesheet" href="../css/main.css"/>
  <link rel="stylesheet" href="../css/about.css"/>
  <link rel="stylesheet" href="../css/style.css"/>
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
                   data-bg="../img/mega-preview.webp">
              </div>

              <div class="mega__note">Быстрая навигация и фильтры — сверху каталога.</div>
            </div>
          </div>
        </div>
      </div>

      <a class="nav__link" href="about.php">О компании</a>

      <div class="nav__actions">
        <?php if ($isAuth): ?>
          <a class="iconBtn iconBtn--auth"
             href="account.php"
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

<main id="main-content" role="main" tabindex="-1">
  <!-- Хлебные крошки -->
  <div class="container">
    <nav class="breadcrumbs" aria-label="Хлебные крошки">
      <ol>
        <li><a href="../index.php">Главная</a></li>
        <li><span aria-current="page">О компании</span></li>
      </ol>
    </nav>
  </div>

  <!-- HERO -->
  <section class="aboutHero" aria-labelledby="about-hero-title">
    <div class="aboutHero__bg" aria-hidden="true" data-bg="../img/about-hero.webp"></div>

    <div class="container aboutHero__inner">
      <div class="aboutHero__card reveal">
        <p class="kicker">Лавка • о нас</p>
        <h1 id="about-hero-title" class="h1">Сувениры, которые хочется дарить</h1>
        <p class="about-intro__text">
          Мы — мастерская авторских подарков, где каждая вещь создаётся вручную,
          небольшими партиями с вниманием к деталям и любовью.
        </p>
        <p class="about-intro__text">
          Тут вы узнаете, как устроена наша мастерская, из каких материалов
          мы работаем, как проходит доставка, какие гарантии мы даём и как с нами связаться.
        </p>

        <nav class="about-links" aria-label="Разделы о компании">
          <a class="about-link" href="#company"><span class="about-link__emoji">🏷️</span>О компании</a>
          <a class="about-link" href="#workshop"><span class="about-link__emoji">🛠️</span>Мастерская</a>
          <a class="about-link" href="#materials"><span class="about-link__emoji">🧵</span>Материалы</a>
          <a class="about-link" href="#delivery"><span class="about-link__emoji">🚚</span>Доставка</a>
          <a class="about-link" href="#returns"><span class="about-link__emoji">↩️</span>Возврат</a>
          <a class="about-link" href="#contacts"><span class="about-link__emoji">💬</span>Контакты</a>
        </nav>
      </div>
    </div>
  </section>

  <!-- ABOUT COMPANY -->
  <section id="company" class="container section" aria-labelledby="company-title">
    <div class="aboutGrid reveal">
      <div class="aboutCard">
        <h2 id="company-title" class="h2">О компании</h2>

        <p class="aboutText">
          Лавка — семейная мастерская подарков и домашнего декора. Мы проектируем изделия сами,
          делаем небольшие партии и доводим каждую вещь до "того самого" ощущения — когда подарок
          хочется вручить сразу, без лишних слов.
        </p>

        <p class="aboutText aboutText--meta">
          Работаем с <strong>2018</strong> года в <strong>Москве</strong>: собираем, упаковываем и отправляем заказы по России.
          Для многих позиций доступна персонализация — имя, дата или короткая фраза.
        </p>

        <div class="aboutStats" role="list" aria-label="Факты о нас">
          <div class="aboutStat" role="listitem">
            <div class="aboutStat__n">20+</div>
            <div class="aboutStat__t">готовых товаров</div>
          </div>
          <div class="aboutStat" role="listitem">
            <div class="aboutStat__n">1–5</div>
            <div class="aboutStat__t">дней на персонализацию</div>
          </div>
          <div class="aboutStat" role="listitem">
            <div class="aboutStat__n">100%</div>
            <div class="aboutStat__t">надежная упаковка</div>
          </div>
        </div>
      </div>

      <div class="aboutSide">
        <div class="aboutSide__img" role="img" aria-label="Предметы ручной работы" data-bg="../img/slide2.webp"></div>
      </div>
    </div>

    <!-- facts -->
    <div class="aboutQuote" aria-label="Интересные факты">
      <div class="aboutQuote__box">
        <p id="randomFact" class="aboutQuote__text" aria-live="polite"></p>

        <div class="aboutQuote__actions">
          <button id="factBtn" type="button" class="factBtn">
            Ещё факт
            <span class="factBtn__arrow" aria-hidden="true">→</span>
          </button>

          <div class="aboutQuote__count" aria-hidden="true">
            <span id="factIndex">1</span>/<span id="factTotal">1</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- WORKSHOP -->
  <section id="workshop" class="container section" aria-labelledby="workshop-title">
    <div class="workshop reveal">
      <div class="workshop__left">
        <h2 id="workshop-title" class="h2">Мастерская</h2>
        <p class="aboutText">
          Мы работаем небольшими сериями: шлифуем дерево, расписываем керамику,
          подбираем композиции для свечей и декора. Каждая вещь проходит ручной контроль.
        </p>

        <ol class="steps" aria-label="Как рождается изделие">
          <li class="step">
            <div class="step__n">1</div>
            <div class="step__c">
              <div class="step__t">Идея и эскиз</div>
              <div class="step__d">
                Подбираем форму, оттенок и смысл подарка.
              </div>
            </div>
          </li>

          <li class="step">
            <div class="step__n">2</div>
            <div class="step__c">
              <div class="step__t">Изготовление</div>
              <div class="step__d">
                Работаем руками — без массового производства.
              </div>
            </div>
          </li>

          <li class="step">
            <div class="step__n">3</div>
            <div class="step__c">
              <div class="step__t">Упаковка</div>
              <div class="step__d">
                Крафт, ленты и открытка — аккуратно и красиво.
              </div>
            </div>
          </li>

          <li class="step">
            <div class="step__n">4</div>
            <div class="step__c">
              <div class="step__t">Проверка и отправка</div>
              <div class="step__d">
                Проверяем изделие и аккуратно отправляем заказ.
              </div>
            </div>
          </li>
        </ol>
      </div>

      <div class="workshop__right">
        <div class="workshop__img" role="img" aria-label="Мастерская" data-bg="../img/slide1.webp"></div>
        <div class="workshop__mini">
          <div class="miniItem">
            <div class="miniItem__t">Тёплая эстетика</div>
            <div class="miniItem__d muted">натуральные оттенки и материалы</div>
          </div>
          <div class="miniItem">
            <div class="miniItem__t">Подарок без суеты</div>
            <div class="miniItem__d muted">подскажем и соберём набор</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- MATERIALS -->
  <section id="materials" class="container section" aria-labelledby="materials-title">
    <div class="reveal">
      <h2 id="materials-title" class="h2">Материалы</h2>
      <p class="aboutText">
        Мы выбираем материалы так, чтобы подарок был приятным на ощупь и служил долго.
        Ниже — что чаще всего используем в работе.
      </p>

      <div class="materialsGrid" role="list">
        <article class="mat" role="listitem">
          <div class="mat__icon" aria-hidden="true">🪵</div>
          <h3 class="mat__t">Дерево</h3>
          <p class="mat__d">Шлифуем вручную, используем безопасные покрытия.</p>
        </article>

        <article class="mat" role="listitem">
          <div class="mat__icon" aria-hidden="true">🏺</div>
          <h3 class="mat__t">Керамика</h3>
          <p class="mat__d">Небольшие партии, спокойные оттенки, приятная фактура.</p>
        </article>

        <article class="mat" role="listitem">
          <div class="mat__icon" aria-hidden="true">🕯️</div>
          <h3 class="mat__t">Воск и ароматы</h3>
          <p class="mat__d">Композиции под настроение: уют, свежесть, спокойствие.</p>
        </article>

        <article class="mat" role="listitem">
          <div class="mat__icon" aria-hidden="true">🧵</div>
          <h3 class="mat__t">Текстиль</h3>
          <p class="mat__d">Натуральные ткани, мягкость и аккуратные швы.</p>
        </article>
      </div>

      <div class="noteCard">
        <strong>Важно:</strong>
        <span class="muted">оттенки и фактура могут немного отличаться — это особенность ручной работы.</span>
      </div>
    </div>
  </section>

  <!-- DELIVERY -->
  <section id="delivery" class="container section" aria-labelledby="delivery-title">
    <div class="deliverySplit reveal">

      <!-- LEFT: image + small hint -->
      <aside class="deliverySplit__left" aria-label="Иллюстрация и подсказка">
        <div class="deliveryMedia">
          <div class="deliveryMedia__img"
               role="img"
               aria-label="Упаковка и отправка подарка"
               data-bg="../img/delivery.webp"></div>
        </div>

        <div class="deliveryHint">
          <div class="deliveryHint__t">Подсказка</div>
          <div class="deliveryHint__d muted">
            Нужен подарок "на завтра"? Напишите — подскажем, что есть в наличии.
          </div>
        </div>
      </aside>

      <!-- RIGHT: text -->
      <div class="deliverySplit__right">
        <h2 id="delivery-title" class="h2">Доставка</h2>
        <p class="aboutText">
          Доставляем по городу и отправляем в другие регионы. Срок и стоимость зависят от адреса и службы доставки.
        </p>

        <div class="infoList" aria-label="Условия доставки">
          <div class="infoItem">
            <div class="infoItem__ico" aria-hidden="true">🚚</div>
            <div class="infoItem__content">
              <div class="infoItem__t">По городу</div>
              <div class="infoItem__d muted">Обычно 1–2 дня. Возможен самовывоз.</div>
            </div>
          </div>

          <div class="infoItem">
            <div class="infoItem__ico" aria-hidden="true">📦</div>
            <div class="infoItem__content">
              <div class="infoItem__t">В регионы</div>
              <div class="infoItem__d muted">От 2 дней, отправляем в надёжной упаковке.</div>
            </div>
          </div>

          <div class="infoItem">
            <div class="infoItem__ico" aria-hidden="true">🎁</div>
            <div class="infoItem__content">
              <div class="infoItem__t">Упаковка</div>
              <div class="infoItem__d muted">Крафт, ленты и открытка — бесплатно.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- RETURNS -->
  <section id="returns" class="container section" aria-labelledby="returns-title">
    <div class="returnsBg"></div>
    <div class="returnsWrap reveal">
      <div class="kicker" aria-hidden="true">быстро решаем • быстрый ответ</div>
      <h2 id="returns-title" class="h2 returnsTitle">Возвраты</h2>
      <p class="aboutText returnsText">
        Если товар не подошёл — напишите нам, и мы поможем решить вопрос.
        Персонализированные изделия могут иметь особые условия возврата.
      </p>
      <div class="faq" aria-label="Вопросы и ответы о возвратах">
        <details class="faq__item">
          <summary class="faq__q">В какие сроки можно оформить возврат?</summary>
          <div class="faq__a muted">
            Обычно в течение 7–14 дней после получения (если товар не использовался и сохранён товарный вид).
            Точные условия уточним по вашему заказу.
          </div>
        </details>
        <details class="faq__item">
          <summary class="faq__q">Какие товары не подлежат возврату?</summary>
          <div class="faq__a muted">
            Персонализированные изделия (с именем/датой/надписью) обычно изготавливаются под заказ,
            поэтому возврат возможен только при проблеме с качеством.
          </div>
        </details>
        <details class="faq__item">
          <summary class="faq__q">Можно ли обменять товар на другой?</summary>
          <div class="faq__a muted">
            Да, если товар в сохранности и доступен нужный вариант. Подскажем по наличию и оформим обмен.
          </div>
        </details>
        <details class="faq__item">
          <summary class="faq__q">Как быстро вернутся деньги?</summary>
          <div class="faq__a muted">
            После получения и проверки возврата обычно 1–5 рабочих дней (зависит от банка и способа оплаты).
          </div>
        </details>
      </div>
    </div>
  </section>
  
  <!-- CONTACTS -->
  <section id="contacts" class="container section" aria-labelledby="contacts-title">
    <div class="contacts2 reveal">
      <!-- LEFT -->
      <div class="contacts2__left">
        <h2 id="contacts-title" class="h2">Контакты</h2>
        <p class="aboutText">
          Напишите нам — поможем выбрать подарок, подскажем наличие и сроки персонализации.
        </p>

        <div class="contacts2__grid">
          <!-- Телефон -->
          <div class="c2Item c2Item--red">
            <div class="c2Item__t">Телефон</div>
            <div class="c2Item__v">+7 (999) 000-00-00</div>
            <div class="muted small">ежедневно 10:00–20:00</div>
          </div>

          <!-- Почта -->
          <div class="c2Item c2Item--red">
            <div class="c2Item__t">Почта</div>
            <div class="c2Item__v">hello@lavka.ru</div>
            <div class="muted small">ответим в течение дня</div>
          </div>

          <!-- Адрес -->
          <div class="c2Item c2Item--green c2Item--wide">
            <div class="c2Item__t">Адрес</div>
            <div class="c2Item__v">Москва, ул. Примерная, 10</div>
            <div class="muted small">самовывоз по договорённости</div>
          </div>

          <!-- Написать нам -->
          <div class="c2Chats">
            <a class="c2Chat" href="#" aria-label="WhatsApp">
              <img class="c2Chat__img" src="../img/whatsapp.webp" alt="WhatsApp">
            </a>

            <a class="c2Chat" href="#" aria-label="Telegram">
              <img class="c2Chat__img" src="../img/telegram.webp" alt="Telegram">
            </a>

            <a class="c2Chat" href="#" aria-label="VK">
              <img class="c2Chat__img" src="../img/vk.webp" alt="VK">
            </a>
          </div>
        </div>
      </div>

      <!-- RIGHT -->
      <aside class="contacts2__right" aria-label="Карта">
        <div class="c2Map">
          <iframe
            src="https://yandex.ru/map-widget/v1/?um=constructor%3A9f6c2b8d2c6b8e9b8f0e5f7e1b6b0c8f2e3a4b5c6d7e8f9a0b1c2d3e4&amp;source=constructor"
            frameborder="0"
            aria-label="Мы на карте"
            loading="lazy">
          </iframe>
        </div>
      </aside>
    </div>
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
    
    // Показываем кнопку при прокрутке
    window.addEventListener('scroll', function() {
      if (window.pageYOffset > 300) {
        toTopBtn.style.display = 'flex';
      } else {
        toTopBtn.style.display = 'none';
      }
    });
    
    // Плавная прокрутка наверх
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