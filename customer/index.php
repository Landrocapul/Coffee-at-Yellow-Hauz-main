<?php
require_once __DIR__ . '/../db.php';

$tableNumber = max(0, (int)($_GET['table'] ?? 0));
$taxRate = (float)(getSetting('tax_rate') ?: 12);

$items = $pdo->query("SELECT mi.id, mi.name, mi.description, mi.price, mi.image_url, mi.is_best_seller, c.name AS category_name FROM menu_items mi JOIN categories c ON c.id = mi.category_id WHERE mi.is_available = 1 AND c.status = 'active' ORDER BY c.sort_order, mi.sort_order, mi.name")->fetchAll();

/**
 * Photos shot in-house and shipped with the app. These are preferred over the
 * remote stock URLs stored in menu_items, which are slow and frequently fail.
 */
function localPhotos(): array
{
    static $map = [
        'longganisa' => 'porklonganisa.webp',
        'pork adobo flakes' => 'porkadoboflakes.webp',
        'chicken sandwich' => 'chickensandwich.webp',
        'grilled garlic cheese' => 'grilledgarliccheese.webp',
        'tuna & garlic pasta' => 'tunagarlic.webp',
        'blueberry cheesecake cake' => 'blueberrycheesecake.webp',
        'cheesecake' => 'cheesecakeduo.webp',
        'tiramisu' => 'tiramisu.webp',
        'latte' => 'latte.webp',
        'iced latte' => 'icelatte.webp',
    ];
    return $map;
}

/** Resolves the best available photo for an item, or null for a text-only row. */
function itemPhoto(array $item): ?string
{
    $key = strtolower(trim((string)$item['name']));
    $local = localPhotos();
    if (isset($local[$key])) {
        return '../images/' . $local[$key];
    }
    $remote = trim((string)($item['image_url'] ?? ''));
    return $remote !== '' ? $remote : null;
}

function slugify(string $value): string
{
    return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($value)), '-');
}

$groups = [];
foreach ($items as $item) {
    $groups[$item['category_name']][] = $item;
}
$categories = array_keys($groups);
$itemCount = count($items);
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#F7F5F0">
<title>Order Online | Coffee at Yellow Hauz</title>
<link rel="icon" href="../images/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Alegreya:wght@500;600;700;800&family=Nunito+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        shell: '#E7E3DA',
        paper: '#F7F5F0',
        line: { DEFAULT: '#E4DED2', strong: '#D3C9B8' },
        ink: { DEFAULT: '#1A1613', soft: '#544B42', mute: '#857A6D' },
        brand: { DEFAULT: '#FBBF24', soft: '#FEF3C7', deep: '#B45309', ink: '#432C06' }
      },
      fontFamily: {
        display: ['Alegreya', 'Georgia', 'serif'],
        body: ['"Nunito Sans"', 'system-ui', 'sans-serif']
      },
      boxShadow: {
        card: '0 1px 2px rgba(26,22,19,.05)',
        shell: '0 30px 70px -40px rgba(26,22,19,.55)',
        panel: '0 -10px 40px -12px rgba(26,22,19,.28)'
      }
    }
  }
};
</script>
<style>
  ::-webkit-scrollbar { width: 8px; height: 8px }
  ::-webkit-scrollbar-thumb { background: #D3C9B8; border-radius: 10px }
  .no-scrollbar { scrollbar-width: none }
  .no-scrollbar::-webkit-scrollbar { display: none }
  :focus-visible { outline: 2px solid #B45309; outline-offset: 2px }
  .chip[aria-current="true"] { background: #1A1613; color: #FBBF24; border-color: #1A1613 }
  .jump[aria-current="true"] { background: #FEF3C7; color: #432C06 }
  .jump[aria-current="true"] .jump-count { color: #B45309 }
</style>
</head>
<body class="min-h-screen bg-shell font-body text-ink antialiased">
<a href="#menu" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-ink focus:px-4 focus:py-2 focus:font-bold focus:text-brand">Skip to menu</a>

<div class="mx-auto w-full max-w-[1440px] lg:p-4">
<div class="flex w-full overflow-hidden bg-paper lg:min-h-[calc(100vh-32px)] lg:rounded-[28px] lg:border lg:border-line-strong lg:shadow-shell">

  <!-- Desktop rail: brand, primary nav and a jump index for the whole menu -->
  <aside class="hidden w-[260px] shrink-0 flex-col border-r border-line bg-white lg:flex">
    <div class="px-6 pb-5 pt-7">
      <span class="block font-display text-sm italic text-ink-mute">Coffee at</span>
      <h1 class="font-display text-[26px] font-extrabold uppercase leading-none tracking-tight">Yellow Hauz</h1>
      <div class="mt-2.5 flex items-center gap-2">
        <span class="h-px w-5 bg-brand"></span>
        <span class="text-[10px] font-bold tracking-[.22em] text-ink-mute">SINCE 2007</span>
      </div>
    </div>
    <nav aria-label="Guest sections" class="space-y-1 px-3">
      <a href="./" aria-current="page" class="flex items-center gap-3 rounded-xl bg-ink px-3 py-2.5 text-sm font-bold text-brand">
        <i class="fa-solid fa-mug-hot w-4 text-center" aria-hidden="true"></i>Order menu
      </a>
      <a href="reservation/" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-ink-soft transition hover:bg-paper hover:text-ink">
        <i class="fa-solid fa-calendar-check w-4 text-center" aria-hidden="true"></i>Reserve a table
      </a>
    </nav>
    <div class="mt-6 flex min-h-0 flex-1 flex-col border-t border-line pt-4">
      <p class="px-6 pb-2 text-[10px] font-bold uppercase tracking-[.18em] text-ink-mute">Jump to</p>
      <div id="jump-list" class="min-h-0 flex-1 space-y-0.5 overflow-y-auto px-3 pb-4">
        <?php foreach ($groups as $category => $categoryItems): ?>
          <a href="#cat-<?= slugify($category) ?>" data-jump="cat-<?= slugify($category) ?>" class="jump flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-[13px] font-semibold text-ink-soft transition hover:bg-paper">
            <span class="truncate"><?= escape($category) ?></span>
            <span class="jump-count shrink-0 text-[11px] font-bold text-ink-mute"><?= count($categoryItems) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="border-t border-line px-6 py-4">
      <a href="../" class="flex items-center gap-2 text-[13px] font-semibold text-ink-mute transition hover:text-ink">
        <i class="fa-solid fa-user-lock" aria-hidden="true"></i>Staff login
      </a>
    </div>
  </aside>

  <main class="flex min-w-0 flex-1 flex-col">
    <header class="sticky top-0 z-30 border-b border-line bg-paper/95 backdrop-blur">
      <div class="flex items-center gap-3 px-4 pb-3 pt-4 lg:px-8 lg:pt-6">
        <div class="min-w-0 flex-1">
          <h1 class="font-display text-xl font-extrabold uppercase leading-none tracking-tight lg:hidden">Yellow Hauz</h1>
          <p class="hidden font-display text-[26px] font-bold leading-none lg:block">Order menu</p>
          <p class="mt-1.5 truncate text-[12px] text-ink-soft lg:mt-2 lg:text-[13px]">
            <?php if ($tableNumber): ?>
              Dine-in at <strong class="font-bold text-ink">Table <?= $tableNumber ?></strong> &middot; <?= $itemCount ?> items available
            <?php else: ?>
              <?= $itemCount ?> items &middot; choose dine-in or take away at checkout
            <?php endif; ?>
          </p>
        </div>
        <a href="reservation/" class="hidden h-11 items-center gap-2 rounded-xl border border-line-strong bg-white px-4 text-sm font-bold text-ink-soft transition hover:text-ink sm:flex lg:hidden">
          <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>Reserve
        </a>
        <button type="button" data-open-cart class="flex h-11 items-center gap-2 rounded-xl bg-ink px-4 text-sm font-bold text-brand transition hover:bg-black">
          <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
          <span class="hidden sm:inline">Cart</span>
          <span class="grid h-6 min-w-6 place-items-center rounded-full bg-brand px-1.5 text-[12px] font-extrabold text-brand-ink" data-cart-count aria-hidden="true">0</span>
          <span class="sr-only" data-cart-label aria-live="polite">Cart is empty</span>
        </button>
      </div>

      <div class="px-4 pb-3 lg:px-8">
        <div class="relative">
          <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm text-ink-mute" aria-hidden="true"></i>
          <label for="search" class="sr-only">Search the menu</label>
          <input id="search" type="search" autocomplete="off" placeholder="Search coffee, pizza, cheesecake&hellip;"
            class="h-11 w-full rounded-xl border border-line-strong bg-white pl-11 pr-11 text-[15px] placeholder:text-ink-mute focus:border-brand-deep focus:outline-none">
          <button type="button" data-clear-search class="absolute right-2 top-1/2 hidden h-8 w-8 -translate-y-1/2 rounded-lg text-ink-mute hover:bg-paper hover:text-ink" aria-label="Clear search">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
          </button>
        </div>
      </div>

      <div id="chip-rail" class="no-scrollbar flex gap-2 overflow-x-auto px-4 pb-3 lg:hidden" aria-label="Menu categories">
        <?php foreach ($categories as $category): ?>
          <button type="button" class="chip shrink-0 whitespace-nowrap rounded-full border border-line-strong bg-white px-3.5 py-2 text-[13px] font-bold text-ink-soft transition" data-chip="cat-<?= slugify($category) ?>"><?= escape($category) ?></button>
        <?php endforeach; ?>
      </div>
    </header>

    <div id="menu" class="flex-1 px-4 pb-40 lg:px-8 lg:pb-16">
      <?php foreach ($groups as $category => $categoryItems): ?>
        <section id="cat-<?= slugify($category) ?>" data-section class="scroll-mt-[188px] pt-7 lg:scroll-mt-[132px]">
          <div class="flex items-baseline justify-between gap-3 border-b-2 border-ink/10 pb-2">
            <h2 class="font-display text-[22px] font-extrabold tracking-tight lg:text-2xl"><?= escape($category) ?></h2>
            <span class="shrink-0 text-[11px] font-bold uppercase tracking-[.14em] text-ink-mute"><?= count($categoryItems) ?> items</span>
          </div>
          <ul class="lg:grid lg:grid-cols-2 lg:gap-x-10">
            <?php foreach ($categoryItems as $item):
              $photo = itemPhoto($item); ?>
              <li class="menu-item flex items-start gap-3.5 border-b border-line py-4"
                data-id="<?= (int)$item['id'] ?>"
                data-name="<?= escape($item['name']) ?>"
                data-price="<?= (float)$item['price'] ?>"
                data-search="<?= escape(strtolower($item['name'] . ' ' . $item['description'] . ' ' . $category)) ?>">
                <?php if ($photo): ?>
                  <div class="thumb h-[68px] w-[68px] shrink-0 overflow-hidden rounded-xl bg-brand-soft">
                    <img src="<?= escape($photo) ?>" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover"
                      onerror="this.closest('.thumb').remove()">
                  </div>
                <?php endif; ?>
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <h3 class="font-display text-[17px] font-bold leading-tight"><?= escape($item['name']) ?></h3>
                    <?php if ($item['is_best_seller']): ?>
                      <span class="rounded-full bg-brand px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-brand-ink">Popular</span>
                    <?php endif; ?>
                  </div>
                  <p class="mt-1 line-clamp-2 text-[13px] leading-relaxed text-ink-soft"><?= escape($item['description']) ?></p>
                </div>
                <div class="flex shrink-0 flex-col items-end gap-2">
                  <span class="font-display text-[17px] font-bold tabular-nums">&#8369;<?= number_format($item['price'], 2) ?></span>
                  <div data-qty-control>
                    <button type="button" data-add class="h-11 rounded-xl bg-ink px-4 text-[13px] font-bold text-brand transition hover:bg-black">
                      Add<span class="sr-only"> <?= escape($item['name']) ?> to cart</span>
                    </button>
                    <div data-stepper class="hidden items-center rounded-xl bg-ink p-0.5">
                      <button type="button" data-step="-1" class="h-10 w-10 rounded-lg text-lg font-bold text-brand transition hover:bg-white/10" aria-label="Remove one <?= escape($item['name']) ?>">&minus;</button>
                      <span data-qty class="w-7 text-center text-sm font-extrabold tabular-nums text-white">0</span>
                      <button type="button" data-step="1" class="h-10 w-10 rounded-lg text-lg font-bold text-brand transition hover:bg-white/10" aria-label="Add one <?= escape($item['name']) ?>">+</button>
                    </div>
                  </div>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endforeach; ?>

      <p id="no-results" class="hidden py-16 text-center text-[15px] text-ink-soft">
        <i class="fa-solid fa-mug-saucer mb-3 block text-2xl text-line-strong" aria-hidden="true"></i>
        Nothing matches &ldquo;<span data-query class="font-bold text-ink"></span>&rdquo;. Try a shorter word.
      </p>

      <div class="mt-8 border-t border-line pt-5 text-[13px] text-ink-mute lg:hidden">
        <a href="reservation/" class="font-bold text-brand-deep">Reserve a table</a>
        <span class="px-2">&middot;</span>
        <a href="../" class="font-semibold">Staff login</a>
      </div>
    </div>
  </main>
</div>
</div>

<!-- Sticky order summary for phones -->
<div id="cart-bar" class="fixed inset-x-0 bottom-0 z-30 hidden p-3 lg:hidden">
  <button type="button" data-open-cart class="flex w-full items-center gap-3 rounded-2xl bg-ink px-4 py-3 text-left shadow-panel">
    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-brand text-sm font-extrabold text-brand-ink" data-cart-count>0</span>
    <span class="min-w-0 flex-1">
      <span class="block text-[13px] font-bold text-white">View order</span>
      <span class="block text-[12px] text-white/70"><span data-cart-count></span> item(s) &middot; &#8369;<span data-cart-subtotal>0.00</span></span>
    </span>
    <i class="fa-solid fa-arrow-right text-brand" aria-hidden="true"></i>
  </button>
</div>

<!-- Cart / checkout sheet -->
<div id="cart-panel" class="fixed inset-0 z-40 hidden">
  <div class="absolute inset-0 bg-ink/40 backdrop-blur-sm" data-close-cart></div>
  <section role="dialog" aria-modal="true" aria-labelledby="cart-heading"
    class="absolute inset-x-0 bottom-0 flex max-h-[92vh] flex-col rounded-t-3xl bg-white shadow-panel sm:inset-y-0 sm:left-auto sm:right-0 sm:max-h-none sm:w-[420px] sm:rounded-none">
    <header class="flex items-start justify-between gap-3 border-b border-line px-5 py-4">
      <div>
        <h2 id="cart-heading" class="font-display text-2xl font-extrabold">Your order</h2>
        <p class="mt-0.5 text-[13px] text-ink-soft">Sent straight to the counter.</p>
      </div>
      <button type="button" data-close-cart class="h-11 w-11 rounded-xl bg-paper text-ink-soft transition hover:text-ink" aria-label="Close order panel">
        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
      </button>
    </header>

    <div id="cart-items" class="min-h-0 flex-1 space-y-2 overflow-y-auto px-5 py-4"></div>

    <div class="border-t border-line px-5 py-4">
      <dl class="space-y-1.5 text-[13px]">
        <div class="flex justify-between text-ink-soft"><dt>Subtotal</dt><dd class="tabular-nums">&#8369;<span data-cart-subtotal>0.00</span></dd></div>
        <div class="flex justify-between text-ink-soft"><dt>Tax (<?= rtrim(rtrim(number_format($taxRate, 2), '0'), '.') ?>%)</dt><dd class="tabular-nums">&#8369;<span data-cart-tax>0.00</span></dd></div>
        <div class="flex justify-between border-t border-line pt-2 font-display text-lg font-extrabold text-ink"><dt>Total</dt><dd class="tabular-nums">&#8369;<span data-cart-total>0.00</span></dd></div>
      </dl>

      <form id="checkout" class="mt-4 space-y-3" novalidate>
        <div>
          <label for="customer_name" class="mb-1 block text-[12px] font-bold uppercase tracking-wider text-ink-soft">Your name</label>
          <input id="customer_name" name="customer_name" required maxlength="100" autocomplete="name"
            class="h-11 w-full rounded-xl border border-line-strong px-3 text-[15px] focus:border-brand-deep focus:outline-none">
        </div>
        <div>
          <span class="mb-1 block text-[12px] font-bold uppercase tracking-wider text-ink-soft">Order type</span>
          <div class="grid grid-cols-2 gap-1 rounded-xl bg-paper p-1">
            <label class="cursor-pointer">
              <input type="radio" name="order_type" value="dine_in" class="peer sr-only" checked>
              <span class="block rounded-lg py-2.5 text-center text-[13px] font-bold text-ink-soft peer-checked:bg-ink peer-checked:text-brand">Dine in</span>
            </label>
            <label class="cursor-pointer">
              <input type="radio" name="order_type" value="take_away" class="peer sr-only">
              <span class="block rounded-lg py-2.5 text-center text-[13px] font-bold text-ink-soft peer-checked:bg-ink peer-checked:text-brand">Take away</span>
            </label>
          </div>
        </div>
        <div id="table-field">
          <label for="table_number" class="mb-1 block text-[12px] font-bold uppercase tracking-wider text-ink-soft">Table number</label>
          <input id="table_number" name="table_number" type="number" inputmode="numeric" min="1" value="<?= $tableNumber ?: '' ?>"
            class="h-11 w-full rounded-xl border border-line-strong px-3 text-[15px] focus:border-brand-deep focus:outline-none">
        </div>
        <p id="checkout-error" role="alert" class="hidden rounded-xl bg-red-50 px-3 py-2 text-[13px] font-semibold text-red-700"></p>
        <button type="submit" data-submit class="h-12 w-full rounded-xl bg-brand font-extrabold text-brand-ink transition hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-50">
          <span data-submit-label>Send order</span>
          <i class="fa-solid fa-arrow-right ml-1" aria-hidden="true"></i>
        </button>
        <p class="text-center text-[12px] text-ink-mute">Pay at the counter when your order is called.</p>
      </form>
    </div>
  </section>
</div>

<!-- Confirmation -->
<div id="success" class="fixed inset-0 z-50 hidden place-items-center bg-ink/50 p-4">
  <div role="dialog" aria-modal="true" aria-labelledby="success-heading" class="w-full max-w-sm rounded-3xl bg-white p-7 text-center shadow-shell">
    <i class="fa-solid fa-circle-check text-4xl text-green-600" aria-hidden="true"></i>
    <h2 id="success-heading" class="mt-3 font-display text-2xl font-extrabold">Order received</h2>
    <p class="mt-1.5 text-[13px] text-ink-soft">Show this reference at the counter.</p>
    <p id="order-number" class="mt-4 rounded-xl bg-brand-soft px-3 py-3 font-display text-lg font-extrabold tracking-wide text-brand-ink"></p>
    <p class="mt-2 text-[13px] text-ink-soft">Total due <strong class="font-bold text-ink">&#8369;<span id="order-total">0.00</span></strong></p>
    <a href="./" class="mt-6 inline-flex h-12 w-full items-center justify-center rounded-xl bg-ink font-extrabold text-brand">Start a new order</a>
  </div>
</div>

<script>
(function () {
  'use strict';

  var CSRF_TOKEN = <?= json_encode(csrfToken()) ?>;
  var TAX_RATE = <?= json_encode($taxRate) ?>;
  var STORAGE_KEY = 'yh_customer_cart';

  var cart = [];
  try { cart = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]') || []; } catch (e) { cart = []; }
  cart = cart.filter(function (line) { return line && line.id && line.quantity > 0; });

  var rows = Array.prototype.slice.call(document.querySelectorAll('.menu-item'));
  var sections = Array.prototype.slice.call(document.querySelectorAll('[data-section]'));
  var cartPanel = document.getElementById('cart-panel');
  var cartBar = document.getElementById('cart-bar');
  var cartItems = document.getElementById('cart-items');
  var searchInput = document.getElementById('search');
  var clearSearch = document.querySelector('[data-clear-search]');
  var noResults = document.getElementById('no-results');
  var checkout = document.getElementById('checkout');
  var checkoutError = document.getElementById('checkout-error');
  var tableField = document.getElementById('table-field');
  var lastFocused = null;

  function money(value) { return value.toFixed(2); }
  function setAll(selector, value) {
    document.querySelectorAll(selector).forEach(function (node) { node.textContent = value; });
  }
  function findLine(id) {
    return cart.find(function (line) { return line.id === id; });
  }
  function persist() {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(cart)); } catch (e) { /* private mode */ }
    render();
  }
  function changeQty(id, delta, meta) {
    var line = findLine(id);
    if (!line && delta > 0 && meta) {
      cart.push({ id: id, name: meta.name, price: meta.price, quantity: 0 });
      line = cart[cart.length - 1];
    }
    if (!line) return;
    line.quantity = Math.min(20, line.quantity + delta);
    if (line.quantity < 1) cart = cart.filter(function (other) { return other.id !== id; });
    persist();
  }

  function totals() {
    var subtotal = cart.reduce(function (sum, line) { return sum + line.price * line.quantity; }, 0);
    var tax = Math.round(subtotal * (TAX_RATE / 100) * 100) / 100;
    var count = cart.reduce(function (sum, line) { return sum + line.quantity; }, 0);
    return { subtotal: subtotal, tax: tax, total: subtotal + tax, count: count };
  }

  function renderRows() {
    rows.forEach(function (row) {
      var line = findLine(Number(row.dataset.id));
      var stepper = row.querySelector('[data-stepper]');
      var addButton = row.querySelector('[data-add]');
      if (line) {
        row.querySelector('[data-qty]').textContent = line.quantity;
        stepper.classList.remove('hidden');
        stepper.classList.add('flex');
        addButton.classList.add('hidden');
      } else {
        stepper.classList.add('hidden');
        stepper.classList.remove('flex');
        addButton.classList.remove('hidden');
      }
    });
  }

  function renderCartList() {
    if (!cart.length) {
      cartItems.innerHTML = '<p class="py-14 text-center text-[14px] text-ink-mute"><i class="fa-regular fa-clipboard mb-3 block text-2xl text-line-strong"></i>Nothing here yet. Add something from the menu.</p>';
      return;
    }
    cartItems.textContent = '';
    cart.forEach(function (line) {
      var wrapper = document.createElement('div');
      wrapper.className = 'rounded-xl bg-paper p-3';
      var head = document.createElement('div');
      head.className = 'flex items-start justify-between gap-3';
      var name = document.createElement('strong');
      name.className = 'font-display text-[15px] font-bold leading-snug';
      name.textContent = line.name;
      var amount = document.createElement('span');
      amount.className = 'shrink-0 text-[14px] font-bold tabular-nums';
      amount.textContent = '\u20B1' + money(line.price * line.quantity);
      head.appendChild(name);
      head.appendChild(amount);

      var controls = document.createElement('div');
      controls.className = 'mt-2 flex items-center justify-between';
      var stepper = document.createElement('div');
      stepper.className = 'flex items-center rounded-xl border border-line-strong bg-white p-0.5';
      [['-1', '\u2212', 'Remove one '], ['1', '+', 'Add one ']].forEach(function (config, index) {
        if (index === 1) {
          var count = document.createElement('span');
          count.className = 'w-8 text-center text-sm font-extrabold tabular-nums';
          count.textContent = line.quantity;
          stepper.appendChild(count);
        }
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'h-10 w-10 rounded-lg text-lg font-bold text-ink transition hover:bg-paper';
        button.dataset.cartStep = config[0];
        button.dataset.cartId = line.id;
        button.textContent = config[1];
        button.setAttribute('aria-label', config[2] + line.name);
        stepper.appendChild(button);
      });
      var remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'h-10 rounded-lg px-2 text-[13px] font-bold text-ink-mute transition hover:text-red-700';
      remove.dataset.cartRemove = line.id;
      remove.textContent = 'Remove';
      controls.appendChild(stepper);
      controls.appendChild(remove);

      wrapper.appendChild(head);
      wrapper.appendChild(controls);
      cartItems.appendChild(wrapper);
    });
  }

  function render() {
    var sums = totals();
    setAll('[data-cart-count]', String(sums.count));
    setAll('[data-cart-subtotal]', money(sums.subtotal));
    setAll('[data-cart-tax]', money(sums.tax));
    setAll('[data-cart-total]', money(sums.total));
    setAll('[data-cart-label]', sums.count ? sums.count + ' item(s) in your order' : 'Cart is empty');
    cartBar.classList.toggle('hidden', sums.count === 0);
    document.querySelector('[data-submit]').disabled = sums.count === 0;
    renderRows();
    renderCartList();
  }

  /* Menu interactions -------------------------------------------------- */

  document.addEventListener('click', function (event) {
    var row = event.target.closest('.menu-item');
    if (row) {
      var meta = { name: row.dataset.name, price: parseFloat(row.dataset.price) };
      var id = Number(row.dataset.id);
      if (event.target.closest('[data-add]')) return changeQty(id, 1, meta);
      var step = event.target.closest('[data-step]');
      if (step) return changeQty(id, Number(step.dataset.step), meta);
    }
    var cartStep = event.target.closest('[data-cart-step]');
    if (cartStep) return changeQty(Number(cartStep.dataset.cartId), Number(cartStep.dataset.cartStep));
    var cartRemove = event.target.closest('[data-cart-remove]');
    if (cartRemove) {
      cart = cart.filter(function (line) { return line.id !== Number(cartRemove.dataset.cartRemove); });
      return persist();
    }
    if (event.target.closest('[data-open-cart]')) return openCart();
    if (event.target.closest('[data-close-cart]')) return closeCart();
    var jump = event.target.closest('[data-jump], [data-chip]');
    if (jump) {
      event.preventDefault();
      var target = document.getElementById(jump.dataset.jump || jump.dataset.chip);
      if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });

  /* Search ------------------------------------------------------------- */

  function applySearch() {
    var query = searchInput.value.trim().toLowerCase();
    clearSearch.classList.toggle('hidden', query === '');
    rows.forEach(function (row) {
      row.hidden = query !== '' && row.dataset.search.indexOf(query) === -1;
    });
    var visible = 0;
    sections.forEach(function (section) {
      var matches = section.querySelectorAll('.menu-item:not([hidden])').length;
      section.hidden = matches === 0;
      visible += matches;
    });
    noResults.querySelector('[data-query]').textContent = searchInput.value.trim();
    noResults.classList.toggle('hidden', visible > 0);
  }
  searchInput.addEventListener('input', applySearch);
  clearSearch.addEventListener('click', function () {
    searchInput.value = '';
    applySearch();
    searchInput.focus();
  });

  /* Active category tracking ------------------------------------------- */

  if ('IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var id = entry.target.id;
        document.querySelectorAll('[data-jump], [data-chip]').forEach(function (node) {
          var active = (node.dataset.jump || node.dataset.chip) === id;
          node.setAttribute('aria-current', active ? 'true' : 'false');
          if (active && node.classList.contains('chip')) {
            // Keep the rail centred on the active chip without moving the page.
            var rail = document.getElementById('chip-rail');
            rail.scrollTo({ left: node.offsetLeft - (rail.clientWidth - node.offsetWidth) / 2, behavior: 'smooth' });
          }
        });
      });
    }, { rootMargin: '-190px 0px -70% 0px' });
    sections.forEach(function (section) { observer.observe(section); });
  }

  /* Cart panel --------------------------------------------------------- */

  function openCart() {
    lastFocused = document.activeElement;
    cartPanel.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    document.getElementById('customer_name').focus();
  }
  function closeCart() {
    cartPanel.classList.add('hidden');
    document.body.style.overflow = '';
    if (lastFocused) lastFocused.focus();
  }
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !cartPanel.classList.contains('hidden')) closeCart();
  });

  function syncTableField() {
    var dineIn = checkout.querySelector('input[name="order_type"]:checked').value === 'dine_in';
    tableField.classList.toggle('hidden', !dineIn);
  }
  checkout.querySelectorAll('input[name="order_type"]').forEach(function (input) {
    input.addEventListener('change', syncTableField);
  });
  syncTableField();

  /* Checkout ----------------------------------------------------------- */

  function showError(message) {
    checkoutError.textContent = message;
    checkoutError.classList.remove('hidden');
  }

  checkout.addEventListener('submit', async function (event) {
    event.preventDefault();
    checkoutError.classList.add('hidden');
    var submit = checkout.querySelector('[data-submit]');
    var label = checkout.querySelector('[data-submit-label]');
    var payload = Object.fromEntries(new FormData(checkout));

    if (!payload.customer_name || !payload.customer_name.trim()) {
      document.getElementById('customer_name').focus();
      return showError('Please enter your name so staff can call your order.');
    }
    if (payload.order_type === 'dine_in' && !(Number(payload.table_number) > 0)) {
      document.getElementById('table_number').focus();
      return showError('Enter your table number, or switch to take away.');
    }

    submit.disabled = true;
    label.textContent = 'Sending\u2026';
    try {
      var response = await fetch('../customer-order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(Object.assign({}, payload, { cart: cart, csrf_token: CSRF_TOKEN }))
      });
      var result = await response.json();
      if (!result.success) throw new Error(result.error);
      cart = [];
      persist();
      closeCart();
      document.getElementById('order-number').textContent = result.order_number;
      document.getElementById('order-total').textContent = money(Number(result.total));
      var success = document.getElementById('success');
      success.classList.remove('hidden');
      success.classList.add('grid');
    } catch (error) {
      showError(error.message || 'Could not place the order. Please ask a staff member.');
    } finally {
      submit.disabled = false;
      label.textContent = 'Send order';
    }
  });

  render();
})();
</script>
</body>
</html>
