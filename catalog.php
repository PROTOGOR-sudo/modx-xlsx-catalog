<?php
/**
 * catalog.php — Каталог продукции (совместим с PHP 5.6 / 7.x / 8.x)
 * Разместить в корне сайта рядом с index.php
 */

// ─── Настройки ───────────────────────────────────────────────────────────────
define('CATALOG_JSON', dirname(__FILE__) . '/catalog/data/catalog.json');
define('CATALOG_XLSX', dirname(__FILE__) . '/catalog/data/catalog.xlsx');
define('SITE_NAME',    'Skynet Group');
define('BASE_URL',     '/');
define('PER_PAGE',     48);

// ─── Загрузка данных ─────────────────────────────────────────────────────────
if (!file_exists(CATALOG_JSON)) {
    die('<div style="font-family:sans-serif;padding:40px;max-width:600px;margin:auto">
        <h2 style="color:#c00">Файл каталога не найден</h2>
        <p>Поместите <code>catalog.json</code> в папку <code>catalog/data/</code></p>
    </div>');
}

$raw      = json_decode(file_get_contents(CATALOG_JSON), true);
$allItems = isset($raw['items'])  ? $raw['items']  : array();
$allCats  = isset($raw['cats'])   ? $raw['cats']   : array();
$allBrands= isset($raw['brands']) ? $raw['brands'] : array();

// ─── Параметры запроса ───────────────────────────────────────────────────────
$q      = isset($_GET['q'])     ? trim($_GET['q'])     : '';
$fCat   = isset($_GET['cat'])   ? trim($_GET['cat'])   : '';
$fBrand = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$fStock = !empty($_GET['stock']);
$page   = isset($_GET['page'])  ? max(1, (int)$_GET['page']) : 1;

// ─── Фильтрация ──────────────────────────────────────────────────────────────
$filtered = array();
foreach ($allItems as $item) {
    if ($fCat   && $item['cat']   !== $fCat)   continue;
    if ($fBrand && $item['brand'] !== $fBrand) continue;
    if ($fStock && $item['sc']    !== 'yes')   continue;
    if ($q !== '') {
        $ql = mb_strtolower($q);
        $inName = mb_strpos(mb_strtolower($item['name']), $ql) !== false;
        $inPn   = mb_strpos(mb_strtolower($item['pn']),   $ql) !== false;
        if (!$inName && !$inPn) continue;
    }
    $filtered[] = $item;
}

$total = count($filtered);
$pages = max(1, (int)ceil($total / PER_PAGE));
$page  = min($page, $pages);
$shown = array_slice($filtered, ($page - 1) * PER_PAGE, PER_PAGE);

// ─── Счётчики для сайдбара ───────────────────────────────────────────────────
function countFor($items, $key, $val, $fCat, $fBrand, $fStock, $q) {
    $cnt = 0;
    foreach ($items as $i) {
        if ($i[$key] !== $val) continue;
        if ($key !== 'cat'   && $fCat   && $i['cat']   !== $fCat)   continue;
        if ($key !== 'brand' && $fBrand && $i['brand'] !== $fBrand) continue;
        if ($fStock && $i['sc'] !== 'yes') continue;
        if ($q !== '') {
            $ql = mb_strtolower($q);
            $inName = mb_strpos(mb_strtolower($i['name']), $ql) !== false;
            $inPn   = mb_strpos(mb_strtolower($i['pn']),   $ql) !== false;
            if (!$inName && !$inPn) continue;
        }
        $cnt++;
    }
    return $cnt;
}

// ─── Helpers ─────────────────────────────────────────────────────────────────
function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function purl($over) {
    $cur = array();
    if (isset($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $cur);
    }
    $p = array_merge($cur, $over);
    foreach ($p as $k => $v) {
        if ($v === '' || $v === null || $v === false) unset($p[$k]);
    }
    unset($p['page']);
    return '?' . http_build_query($p);
}

function fmtPrice($p, $c) {
    if ($p === null) return 'По запросу';
    return number_format($p, 0, '.', '&#8201;') . '&nbsp;' . ($c === 'RUB' ? '&#8381;' : h($c));
}

function catIcon($cat) {
    $map = array(
        'Внешние аккумуляторы'   => 'fa-battery-three-quarters',
        'Зарядные устройства'    => 'fa-plug',
        'Кабели'                 => 'fa-exchange',
        'Наушники'               => 'fa-headphones',
        'Акустика'               => 'fa-volume-up',
        'Чехлы'                  => 'fa-mobile',
        'Держатели'              => 'fa-map-pin',
        'Адаптеры и конвертеры'  => 'fa-random',
        'Хабы и доки'            => 'fa-sitemap',
        'Периферия'              => 'fa-keyboard-o',
        'Носимые устройства'     => 'fa-clock-o',
        'Bluetooth аксессуары'   => 'fa-bluetooth-b',
        'Автоаксессуары'         => 'fa-car',
        'Сетевое оборудование'   => 'fa-wifi',
        'Прочие аксессуары'      => 'fa-th-large',
    );
    return isset($map[$cat]) ? $map[$cat] : 'fa-cube';
}

function stockQtyLabel($item) {
    $out = $item['sl'];
    if ($item['sc'] === 'yes' && $item['sq'] > 0) {
        $out .= ' (' . $item['sq'] . ')';
    }
    return $out;
}

?><!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Каталог товаров &mdash; <?php echo h(SITE_NAME); ?></title>
<link rel="stylesheet" href="/template/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="/template/vendor/font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="/template/css/theme.css">
<link rel="stylesheet" href="/template/css/theme-elements.css">
<link rel="stylesheet" href="/template/css/custom.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; }
body { font-family: 'Inter', 'Open Sans', sans-serif; background: #f4f6fb; color: #1e2230; margin: 0; }

.site-header { background: #0c2050; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 16px rgba(0,0,0,.35); }
.site-header .inner { display: -webkit-flex; display: flex; -webkit-align-items: center; align-items: center; -webkit-justify-content: space-between; justify-content: space-between; height: 60px; padding: 0 24px; max-width: 1320px; margin: auto; }
.site-header .logo { color: #fff; font-weight: 700; font-size: 1.2rem; text-decoration: none; }
.site-header nav a { color: rgba(255,255,255,.65); text-decoration: none; font-size: .9rem; margin-left: 28px; }
.site-header nav a:hover { color: #fff; }
.site-header nav a.active { color: #fff; font-weight: 600; }

.cat-hero { background: linear-gradient(130deg, #0c2050 0%, #1a4b9e 60%, #2660d0 100%); padding: 48px 24px 40px; position: relative; overflow: hidden; }
.cat-hero::after { content: ''; position: absolute; right: -60px; top: -60px; width: 380px; height: 380px; border-radius: 50%; background: rgba(255,255,255,.04); pointer-events: none; }
.cat-hero .inner { position: relative; max-width: 1320px; margin: auto; }
.cat-hero h1 { font-size: 2rem; font-weight: 700; color: #fff; margin: 0 0 4px; }
.cat-hero .sub { color: rgba(255,255,255,.65); font-size: .97rem; margin: 0; }

.hero-pills { display: -webkit-flex; display: flex; -webkit-flex-wrap: wrap; flex-wrap: wrap; gap: 12px; margin-top: 20px; }
.hero-pill { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2); border-radius: 50px; padding: 5px 16px; color: #fff; font-size: .82rem; font-weight: 500; display: -webkit-inline-flex; display: inline-flex; -webkit-align-items: center; align-items: center; gap: 7px; }
.hero-pill i { opacity: .75; }

.hero-search { margin-top: 24px; position: relative; max-width: 520px; }
.hero-search input { width: 100%; padding: 13px 52px 13px 18px; border-radius: 12px; border: none; font-size: .95rem; font-family: inherit; box-shadow: 0 4px 20px rgba(0,0,0,.25); outline: none; color: #1e2230; }
.hero-search button { position: absolute; right: 7px; top: 50%; -webkit-transform: translateY(-50%); transform: translateY(-50%); background: #1a4b9e; color: #fff; border: none; border-radius: 9px; width: 38px; height: 38px; display: -webkit-flex; display: flex; -webkit-align-items: center; align-items: center; -webkit-justify-content: center; justify-content: center; cursor: pointer; }
.hero-search button:hover { background: #0c2050; }

.cat-body { max-width: 1320px; margin: 0 auto; padding: 28px 20px 60px; display: -webkit-flex; display: flex; gap: 24px; -webkit-align-items: flex-start; align-items: flex-start; }
.cat-sidebar { width: 256px; -webkit-flex-shrink: 0; flex-shrink: 0; position: sticky; top: 76px; }
.cat-main { -webkit-flex: 1; flex: 1; min-width: 0; }
@media (max-width: 991px) { .cat-body { -webkit-flex-direction: column; flex-direction: column; } .cat-sidebar { width: 100%; position: static; } }

.sb-block { background: #fff; border-radius: 14px; border: 1.5px solid #e6eaf3; margin-bottom: 14px; box-shadow: 0 2px 8px rgba(0,0,0,.04); overflow: hidden; }
.sb-title { font-size: .71rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #8a96aa; padding: 12px 16px 10px; border-bottom: 1px solid #f0f3f9; }
.sb-pad { padding: 14px 16px; }

.tog-wrap { display: -webkit-flex; display: flex; -webkit-align-items: center; align-items: center; gap: 10px; cursor: pointer; }
.tog-wrap input { display: none; }
.tog-track { width: 42px; height: 22px; border-radius: 11px; background: #dde2ee; position: relative; -webkit-transition: background .2s; transition: background .2s; -webkit-flex-shrink: 0; flex-shrink: 0; }
.tog-thumb { position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.2); -webkit-transition: -webkit-transform .2s; transition: transform .2s; }
.tog-wrap input:checked ~ .tog-track { background: #1a4b9e; }
.tog-wrap input:checked ~ .tog-track .tog-thumb { -webkit-transform: translateX(20px); transform: translateX(20px); }
.tog-label { font-size: .87rem; color: #3a4155; font-weight: 500; -webkit-user-select: none; user-select: none; }

.cat-nav { list-style: none; padding: 6px 0; margin: 0; }
.cat-nav li a { display: -webkit-flex; display: flex; -webkit-align-items: center; align-items: center; gap: 9px; padding: 8px 16px; color: #3a4155; text-decoration: none; font-size: .88rem; border-left: 3px solid transparent; -webkit-transition: all .13s; transition: all .13s; }
.cat-nav li a:hover { background: #f3f6fb; color: #1a4b9e; }
.cat-nav li a.on { background: #eef3fd; color: #1a4b9e; font-weight: 600; border-left-color: #1a4b9e; }
.cat-nav li a .ico { width: 18px; text-align: center; opacity: .55; font-size: .85rem; -webkit-flex-shrink: 0; flex-shrink: 0; }
.cat-nav li a .lbl { -webkit-flex: 1; flex: 1; }
.cat-nav li a .cnt { background: #f0f3f9; color: #8a96aa; font-size: .72rem; font-weight: 600; border-radius: 20px; padding: 2px 8px; }
.cat-nav li a.on .cnt { background: #d6e4ff; color: #1a4b9e; }

.brand-cloud { display: -webkit-flex; display: flex; -webkit-flex-wrap: wrap; flex-wrap: wrap; gap: 7px; padding: 12px 14px; }
.brand-tag { display: -webkit-inline-flex; display: inline-flex; -webkit-align-items: center; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 20px; border: 1.5px solid #e2e8f0; background: #fff; font-size: .81rem; color: #4a5568; text-decoration: none; -webkit-transition: all .13s; transition: all .13s; }
.brand-tag:hover { border-color: #1a4b9e; color: #1a4b9e; background: #eef3fd; }
.brand-tag.on { background: #1a4b9e; color: #fff; border-color: #1a4b9e; }
.brand-tag .n { opacity: .7; font-size: .76rem; }

.dl-btn { display: -webkit-flex; display: flex; -webkit-align-items: center; align-items: center; -webkit-justify-content: center; justify-content: center; gap: 8px; background: #1a4b9e; color: #fff; text-decoration: none; padding: 11px 0; border-radius: 12px; font-size: .88rem; font-weight: 600; box-shadow: 0 3px 12px rgba(26,75,158,.3); -webkit-transition: all .2s; transition: all .2s; }
.dl-btn:hover { background: #0c2050; color: #fff; }

.toolbar { display: -webkit-flex; display: flex; -webkit-align-items: center; align-items: center; -webkit-justify-content: space-between; justify-content: space-between; -webkit-flex-wrap: wrap; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
.toolbar-info { font-size: .87rem; color: #6b7280; }
.toolbar-info strong { color: #1e2230; }
.view-btns { display: -webkit-flex; display: flex; gap: 5px; }
.view-btns button { width: 34px; height: 34px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: #fff; color: #8a96aa; cursor: pointer; display: -webkit-flex; display: flex; -webkit-align-items: center; align-items: center; -webkit-justify-content: center; justify-content: center; -webkit-transition: all .15s; transition: all .15s; }
.view-btns button:hover, .view-btns button.on { background: #1a4b9e; border-color: #1a4b9e; color: #fff; }

.chips { display: -webkit-flex; display: flex; gap: 7px; -webkit-flex-wrap: wrap; flex-wrap: wrap; margin-bottom: 14px; }
.chip { display: -webkit-inline-flex; display: inline-flex; -webkit-align-items: center; align-items: center; gap: 6px; background: #eef3fd; color: #1a4b9e; border-radius: 20px; padding: 4px 11px; font-size: .81rem; font-weight: 500; text-decoration: none; }
.chip .x { opacity: .65; font-size: .88rem; }
.reset-all { font-size: .81rem; color: #8a96aa; text-decoration: none; display: -webkit-inline-flex; display: inline-flex; -webkit-align-items: center; align-items: center; gap: 4px; }
.reset-all:hover { color: #1a4b9e; }

#gView { display: -webkit-flex; display: flex; -webkit-flex-wrap: wrap; flex-wrap: wrap; margin: -8px; }
#gView .pcard-wrap { width: 33.333%; padding: 8px; }
@media (max-width: 1200px) { #gView .pcard-wrap { width: 50%; } }
@media (max-width: 540px)  { #gView .pcard-wrap { width: 100%; } }

.pcard { background: #fff; border-radius: 14px; border: 1.5px solid #e6eaf3; display: -webkit-flex; display: flex; -webkit-flex-direction: column; flex-direction: column; height: 100%; -webkit-transition: box-shadow .18s, -webkit-transform .18s; transition: box-shadow .18s, transform .18s; overflow: hidden; }
.pcard:hover { box-shadow: 0 8px 30px rgba(26,75,158,.13); -webkit-transform: translateY(-3px); transform: translateY(-3px); border-color: #c2d4f8; }
.pcard-img { height: 130px; background: -webkit-linear-gradient(135deg, #eef3fb, #e2eaf8); background: linear-gradient(135deg, #eef3fb, #e2eaf8); display: -webkit-flex; display: flex; -webkit-align-items: center; align-items: center; -webkit-justify-content: center; justify-content: center; -webkit-flex-shrink: 0; flex-shrink: 0; }
.pcard-img i { font-size: 2.5rem; color: #b8c9e8; }
.pcard-body { padding: 13px 15px 15px; -webkit-flex: 1; flex: 1; display: -webkit-flex; display: flex; -webkit-flex-direction: column; flex-direction: column; }
.pcard-cat  { font-size: .68rem; font-weight: 700; color: #1a4b9e; text-transform: uppercase; letter-spacing: .07em; margin-bottom: 5px; }
.pcard-name { font-size: .87rem; font-weight: 600; color: #1e2230; line-height: 1.42; -webkit-flex: 1; flex: 1; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 8px; }
.pcard-pn   { font-size: .74rem; color: #a0aaba; font-family: 'Courier New', monospace; margin-bottom: 10px; }
.pcard-foot { display: -webkit-flex; display: flex; -webkit-align-items: flex-end; align-items: flex-end; -webkit-justify-content: space-between; justify-content: space-between; gap: 6px; margin-top: auto; }
.pcard-price { font-size: 1.02rem; font-weight: 700; color: #1a4b9e; }
.pcard-price.na { font-size: .83rem; color: #a0aaba; font-weight: 400; }

#lView { display: none; }
.ctable-wrap { background: #fff; border-radius: 14px; border: 1.5px solid #e6eaf3; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.04); }
.ctable { width: 100%; border-collapse: collapse; }
.ctable thead th { background: #0c2050; color: #fff; padding: 11px 15px; font-size: .76rem; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; white-space: nowrap; border: none; text-align: left; }
.ctable tbody tr:hover { background: #f3f6fb; }
.ctable tbody td { padding: 10px 15px; border-bottom: 1px solid #f0f3f9; font-size: .87rem; vertical-align: middle; }
.ctable tbody tr:last-child td { border-bottom: none; }
.td-name  { font-weight: 500; color: #1e2230; line-height: 1.4; }
.td-pn    { color: #a0aaba; font-family: 'Courier New', monospace; font-size: .76rem; white-space: nowrap; }
.td-price { font-weight: 700; color: #1a4b9e; white-space: nowrap; }
.td-brand { color: #6b7280; font-size: .83rem; white-space: nowrap; }

.sbadge { display: -webkit-inline-flex; display: inline-flex; -webkit-align-items: center; align-items: center; gap: 4px; font-size: .72rem; font-weight: 600; border-radius: 20px; padding: 3px 9px; white-space: nowrap; }
.sbadge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; -webkit-flex-shrink: 0; flex-shrink: 0; }
.s-yes     { background: #e7f8ee; color: #15803d; } .s-yes::before     { background: #15803d; }
.s-reserve { background: #fff8e6; color: #b45309; } .s-reserve::before { background: #b45309; }
.s-no      { background: #fef2f2; color: #dc2626; } .s-no::before      { background: #dc2626; }

.pag { display: -webkit-flex; display: flex; -webkit-justify-content: center; justify-content: center; gap: 5px; -webkit-flex-wrap: wrap; flex-wrap: wrap; margin-top: 30px; }
.pag a, .pag span { display: -webkit-inline-flex; display: inline-flex; -webkit-align-items: center; align-items: center; -webkit-justify-content: center; justify-content: center; min-width: 38px; height: 38px; padding: 0 10px; border-radius: 9px; font-size: .87rem; font-weight: 500; text-decoration: none; border: 1.5px solid #e2e8f0; color: #4a5568; background: #fff; -webkit-transition: all .13s; transition: all .13s; }
.pag a:hover { background: #eef3fd; border-color: #1a4b9e; color: #1a4b9e; }
.pag .cur  { background: #1a4b9e; border-color: #1a4b9e; color: #fff; }
.pag .dots { border-color: transparent; background: transparent; color: #9aa3b3; cursor: default; }

.empty { text-align: center; padding: 70px 20px; background: #fff; border-radius: 14px; border: 2px dashed #e2e8f0; }
.empty i  { font-size: 2.8rem; color: #c0cfe8; display: block; margin-bottom: 18px; }
.empty h4 { color: #1e2230; font-weight: 600; margin-bottom: 8px; }
.empty p  { color: #8a96aa; font-size: .9rem; }

.cat-foot { background: #0c2050; color: rgba(255,255,255,.45); padding: 22px 0; text-align: center; font-size: .81rem; margin-top: 50px; }
</style>
</head>
<body>

<header class="site-header">
    <div class="inner">
        <a href="<?php echo BASE_URL; ?>" class="logo"><?php echo h(SITE_NAME); ?></a>
        <nav>
            <a href="<?php echo BASE_URL; ?>">Главная</a>
            <a href="/catalog.php" class="active">Каталог</a>
        </nav>
    </div>
</header>

<section class="cat-hero">
    <div class="inner">
        <h1>Каталог товаров</h1>
        <p class="sub">Электроника и аксессуары &mdash; актуальные цены и наличие</p>
        <div class="hero-pills">
            <div class="hero-pill"><i class="fa fa-th"></i><?php echo number_format(count($allItems), 0, '.', '&#8201;'); ?>&nbsp;позиций</div>
            <div class="hero-pill"><i class="fa fa-list-ul"></i><?php echo count($allCats); ?>&nbsp;категорий</div>
            <div class="hero-pill"><i class="fa fa-tag"></i><?php echo count($allBrands); ?>&nbsp;брендов</div>
            <div class="hero-pill"><i class="fa fa-refresh"></i>Обновлено&nbsp;<?php echo date('d.m.Y', filemtime(CATALOG_JSON)); ?></div>
        </div>
        <form class="hero-search" method="get" action="">
            <?php if ($fCat !== ''):   ?><input type="hidden" name="cat"   value="<?php echo h($fCat); ?>"><?php endif; ?>
            <?php if ($fBrand !== ''): ?><input type="hidden" name="brand" value="<?php echo h($fBrand); ?>"><?php endif; ?>
            <?php if ($fStock): ?><input type="hidden" name="stock" value="1"><?php endif; ?>
            <input type="text" name="q" value="<?php echo h($q); ?>" placeholder="Поиск по названию или артикулу&hellip;" autocomplete="off">
            <button type="submit"><i class="fa fa-search"></i></button>
        </form>
    </div>
</section>

<div class="cat-body">

    <aside class="cat-sidebar">

        <div class="sb-block">
            <div class="sb-title">Наличие</div>
            <div class="sb-pad">
                <label class="tog-wrap" for="tgl">
                    <input type="checkbox" id="tgl" <?php echo $fStock ? 'checked' : ''; ?>
                           onchange="applyParam('stock', this.checked ? '1' : '')">
                    <div class="tog-track"><div class="tog-thumb"></div></div>
                    <span class="tog-label">Только в наличии</span>
                </label>
            </div>
        </div>

        <div class="sb-block">
            <div class="sb-title">Категория</div>
            <ul class="cat-nav">
                <li>
                    <a href="<?php echo h(purl(array('cat'=>''))); ?>" class="<?php echo $fCat === '' ? 'on' : ''; ?>">
                        <span class="ico"><i class="fa fa-th"></i></span>
                        <span class="lbl">Все категории</span>
                        <span class="cnt"><?php echo count($filtered); ?></span>
                    </a>
                </li>
                <?php foreach ($allCats as $cat):
                    $cnt = countFor($allItems, 'cat', $cat, '', $fBrand, $fStock, $q);
                    if (!$cnt) continue;
                ?>
                <li>
                    <a href="<?php echo h(purl(array('cat'=>$cat))); ?>" class="<?php echo $fCat === $cat ? 'on' : ''; ?>">
                        <span class="ico"><i class="fa <?php echo catIcon($cat); ?>"></i></span>
                        <span class="lbl"><?php echo h($cat); ?></span>
                        <span class="cnt"><?php echo $cnt; ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="sb-block">
            <div class="sb-title">Бренд</div>
            <div class="brand-cloud">
                <?php foreach ($allBrands as $brand):
                    $cnt = countFor($allItems, 'brand', $brand, $fCat, '', $fStock, $q);
                    if (!$cnt) continue;
                    $newBrand = ($fBrand === $brand) ? '' : $brand;
                ?>
                <a href="<?php echo h(purl(array('brand'=> $newBrand))); ?>"
                   class="brand-tag <?php echo $fBrand === $brand ? 'on' : ''; ?>">
                    <?php echo h($brand); ?><span class="n"><?php echo $cnt; ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <a href="/catalog/data/catalog.xlsx" class="dl-btn" download>
            <i class="fa fa-download"></i>Скачать прайс-лист
        </a>

    </aside>

    <main class="cat-main">

        <div class="toolbar">
            <div class="toolbar-info">
                Найдено: <strong><?php echo number_format($total, 0, '.', '&#8201;'); ?></strong> товаров
                <?php if ($pages > 1): ?>&nbsp;&middot; стр. <strong><?php echo $page; ?></strong> из <strong><?php echo $pages; ?></strong><?php endif; ?>
            </div>
            <div class="view-btns">
                <button id="bG" class="on" onclick="setView('g')" title="Карточки"><i class="fa fa-th"></i></button>
                <button id="bL" onclick="setView('l')" title="Таблица"><i class="fa fa-list"></i></button>
            </div>
        </div>

        <?php if ($fCat !== '' || $fBrand !== '' || $q !== '' || $fStock): ?>
        <div class="chips">
            <?php if ($fCat !== ''): ?><a href="<?php echo h(purl(array('cat'=>''))); ?>" class="chip"><i class="fa <?php echo catIcon($fCat); ?>"></i><?php echo h($fCat); ?><span class="x">&times;</span></a><?php endif; ?>
            <?php if ($fBrand !== ''): ?><a href="<?php echo h(purl(array('brand'=>''))); ?>" class="chip"><i class="fa fa-tag"></i><?php echo h($fBrand); ?><span class="x">&times;</span></a><?php endif; ?>
            <?php if ($q !== ''): ?><a href="<?php echo h(purl(array('q'=>''))); ?>" class="chip"><i class="fa fa-search"></i>&laquo;<?php echo h($q); ?>&raquo;<span class="x">&times;</span></a><?php endif; ?>
            <?php if ($fStock): ?><a href="<?php echo h(purl(array('stock'=>''))); ?>" class="chip"><i class="fa fa-check"></i>В наличии<span class="x">&times;</span></a><?php endif; ?>
            <a href="catalog.php" class="reset-all"><i class="fa fa-times-circle"></i>Сбросить всё</a>
        </div>
        <?php endif; ?>

        <?php if (empty($shown)): ?>
        <div class="empty">
            <i class="fa fa-search-minus"></i>
            <h4>Ничего не найдено</h4>
            <p>Измените параметры поиска или <a href="catalog.php" style="color:#1a4b9e">сбросьте фильтры</a></p>
        </div>

        <?php else: ?>

        <div id="gView">
            <?php foreach ($shown as $i): ?>
            <div class="pcard-wrap">
            <div class="pcard">
                <div class="pcard-img">
                    <i class="fa <?php echo catIcon($i['cat']); ?>"></i>
                </div>
                <div class="pcard-body">
                    <div class="pcard-cat"><?php echo h($i['cat']); ?></div>
                    <div class="pcard-name" title="<?php echo h($i['name']); ?>"><?php echo h($i['name']); ?></div>
                    <?php if ($i['pn'] !== ''): ?><div class="pcard-pn"><?php echo h($i['pn']); ?></div><?php endif; ?>
                    <div class="pcard-foot">
                        <span class="pcard-price <?php echo $i['price'] !== null ? '' : 'na'; ?>"><?php echo fmtPrice($i['price'], $i['cur']); ?></span>
                        <span class="sbadge s-<?php echo $i['sc']; ?>"><?php echo stockQtyLabel($i); ?></span>
                    </div>
                </div>
            </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div id="lView">
            <div class="ctable-wrap">
                <table class="ctable">
                    <thead>
                        <tr>
                            <th>Артикул</th>
                            <th>Наименование</th>
                            <th>Категория</th>
                            <th>Бренд</th>
                            <th>Цена</th>
                            <th>Наличие</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shown as $i): ?>
                        <tr>
                            <td class="td-pn"><?php echo h($i['pn']); ?></td>
                            <td class="td-name"><?php echo h($i['name']); ?></td>
                            <td><small style="color:#6b7280"><?php echo h($i['cat']); ?></small></td>
                            <td class="td-brand"><?php echo h($i['brand']); ?></td>
                            <td class="td-price"><?php echo fmtPrice($i['price'], $i['cur']); ?></td>
                            <td><span class="sbadge s-<?php echo $i['sc']; ?>"><?php echo stockQtyLabel($i); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($pages > 1):
            $base = purl(array());
        ?>
        <nav class="pag">
            <?php if ($page > 1): ?><a href="<?php echo h($base); ?>&amp;page=<?php echo $page - 1; ?>">&larr;</a><?php endif; ?>
            <?php
            $from = max(1, $page - 2);
            $to   = min($pages, $page + 2);
            if ($from > 1) {
                echo '<a href="' . h($base) . '&amp;page=1">1</a>';
                if ($from > 2) echo '<span class="dots">&hellip;</span>';
            }
            for ($p = $from; $p <= $to; $p++):
            ?>
                <?php if ($p === $page): ?>
                    <span class="cur"><?php echo $p; ?></span>
                <?php else: ?>
                    <a href="<?php echo h($base); ?>&amp;page=<?php echo $p; ?>"><?php echo $p; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php
            if ($to < $pages) {
                if ($to < $pages - 1) echo '<span class="dots">&hellip;</span>';
                echo '<a href="' . h($base) . '&amp;page=' . $pages . '">' . $pages . '</a>';
            }
            ?>
            <?php if ($page < $pages): ?><a href="<?php echo h($base); ?>&amp;page=<?php echo $page + 1; ?>">&rarr;</a><?php endif; ?>
        </nav>
        <?php endif; ?>

        <?php endif; ?>
    </main>
</div>

<footer class="cat-foot">
    <div style="max-width:1320px;margin:auto;padding:0 20px">
        &copy; <?php echo date('Y'); ?> <?php echo h(SITE_NAME); ?> &nbsp;&middot;&nbsp; Все права защищены
    </div>
</footer>

<script src="/template/vendor/jquery/jquery.min.js"></script>
<script src="/template/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function setView(v) {
    localStorage.setItem('cv', v);
    document.getElementById('gView').style.display = (v === 'g') ? '' : 'none';
    document.getElementById('lView').style.display = (v === 'l') ? '' : 'none';
    document.getElementById('bG').className = (v === 'g') ? 'on' : '';
    document.getElementById('bL').className = (v === 'l') ? 'on' : '';
}
(function(){ setView(localStorage.getItem('cv') || 'g'); })();

function applyParam(k, v) {
    var u = new URL(location.href);
    if (v) { u.searchParams.set(k, v); } else { u.searchParams.delete(k); }
    u.searchParams.delete('page');
    location.href = u.toString();
}
</script>
</body>
</html>
