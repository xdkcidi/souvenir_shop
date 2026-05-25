<?php
session_start();

$basePath = '..';

require_once __DIR__ . '/../includes/layout.php';

renderHead(
    'О нас — Лавка',
    'О компании Лавка: мастерская, материалы, доставка, возвраты и гарантия. Контакты.',
    [
      'css/style.css',
      'css/main.css',
      'css/cart.css',
      'css/about.css'
    ]
);

renderHeader();
?>

<main id="main-content" role="main" tabindex="-1">
  <div class="container">
    <nav class="breadcrumbs" aria-label="Хлебные крошки">
      <ol>
        <li><a href="../index.php">Главная</a></li>
        <li><span aria-current="page">О компании</span></li>
      </ol>
    </nav>
  </div>

  <!-- Начальный блок -->
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

  <!-- О компании -->
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
          Работаем с <strong>2018</strong> года в <strong>Москве</strong>: собираем, упаковываем и отправляем заказы по
          России.
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
  </section>

  <!-- Материалы -->
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

  <!-- Доставка -->
  <section id="delivery" class="container section" aria-labelledby="delivery-title">
    <div class="deliverySplit reveal">

      <aside class="deliverySplit__left" aria-label="Иллюстрация и подсказка">
        <div class="deliveryMedia">
          <div class="deliveryMedia__img" role="img" aria-label="Упаковка и отправка подарка"
            data-bg="../img/delivery.webp"></div>
        </div>

        <div class="deliveryHint">
          <div class="deliveryHint__t">Подсказка</div>
          <div class="deliveryHint__d muted">
            Нужен подарок "на завтра"? Напишите — подскажем, что есть в наличии.
          </div>
        </div>
      </aside>

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

  <!-- Возвраты -->
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

  <!-- Контакты -->
  <section id="contacts" class="container section" aria-labelledby="contacts-title">
    <div class="contacts2 reveal">
      <div class="contacts2__left">
        <h2 id="contacts-title" class="h2">Контакты</h2>
        <p class="aboutText">
          Напишите нам — поможем выбрать подарок, подскажем наличие и сроки персонализации.
        </p>

        <div class="contacts2__grid">
          <div class="c2Item c2Item--red">
            <div class="c2Item__t">Телефон</div>
            <div class="c2Item__v">+7 (999) 000-00-00</div>
            <div class="muted small">ежедневно 10:00–20:00</div>
          </div>

          <div class="c2Item c2Item--red">
            <div class="c2Item__t">Почта</div>
            <div class="c2Item__v">hello@lavka.ru</div>
            <div class="muted small">ответим в течение дня</div>
          </div>

          <div class="c2Item c2Item--green c2Item--wide">
            <div class="c2Item__t">Адрес</div>
            <div class="c2Item__v">Москва, ул. Примерная, 10</div>
            <div class="muted small">самовывоз по договорённости</div>
          </div>

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

      <!-- Карта -->
      <aside class="contacts2__right" aria-label="Карта">
        <div class="c2Map">
          <iframe
            src="https://yandex.ru/map-widget/v1/?um=constructor%3A9f6c2b8d2c6b8e9b8f0e5f7e1b6b0c8f2e3a4b5c6d7e8f9a0b1c2d3e4&amp;source=constructor"
            frameborder="0" aria-label="Мы на карте" loading="lazy">
          </iframe>
        </div>
      </aside>
    </div>
  </section>
</main>

<?php
renderFooter();
renderAuthModal();
renderFavoritesSheet();

renderScripts([
    'js/script.js',
    'js/cart.js',
    'js/favorites.js',
    'js/about.js'
]);
?>