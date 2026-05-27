<?php
/**
 * xlsx_to_json.php — Конвертер каталога XLSX → JSON
 * Запускать при обновлении прайс-листа:
 *   php catalog/tools/xlsx_to_json.php
 *
 * Требует библиотеку SimpleXLSX:
 *   https://github.com/shuchkin/simplexlsx/raw/master/src/SimpleXLSX.php
 *   → сохранить как catalog/libs/SimpleXLSX.php
 */

$root = dirname(__DIR__);
$lib  = $root . '/libs/SimpleXLSX.php';
$xlsx = $root . '/data/catalog.xlsx';
$out  = $root . '/data/catalog.json';

if (!file_exists($lib)) {
    die("ОШИБКА: Не найден файл $lib\n"
      . "Скачайте: https://github.com/shuchkin/simplexlsx/raw/master/src/SimpleXLSX.php\n");
}
if (!file_exists($xlsx)) {
    die("ОШИБКА: Не найден файл $xlsx\n");
}

require_once $lib;

// ── Классификаторы ────────────────────────────────────────────────────────
$BRANDS = ['Apple','Samsung','Xiaomi','JBL','Jabra','Satechi','Belkin','UGREEN','TTEC','Hoto','Anker','Baseus','360'];

function detectCat(string $n): string {
    $n = mb_strtolower($n);
    if (preg_match('/внешний.{0,10}(аккумулятор|battery)|power\s*bank|powerbank/u', $n)) return 'Внешние аккумуляторы';
    if (preg_match('/сетевое|зарядн|charger|зарядка|зарядное/u', $n)) return 'Зарядные устройства';
    if (preg_match('/кабел|cable/u', $n)) return 'Кабели';
    if (preg_match('/наушник|earphone|headphone|airpod|гарнитур|tws|вкладыш/u', $n)) return 'Наушники';
    if (preg_match('/колонк|speaker|аудио.{0,5}систем/u', $n)) return 'Акустика';
    if (preg_match('/чехол|bumper|бампер/u', $n)) return 'Чехлы';
    if (preg_match('/держател|holder|mount|крепл/u', $n)) return 'Держатели';
    if (preg_match('/хаб|hub\b|dock|станция/u', $n)) return 'Хабы и доки';
    if (preg_match('/мышь|мыши|mouse\b|клавиатур|keyboard/u', $n)) return 'Периферия';
    if (preg_match('/часы|watch|трекер|tracker|браслет/u', $n)) return 'Носимые устройства';
    if (preg_match('/wifi|wi-fi|ethernet\s*адаптер|коммутатор/u', $n)) return 'Сетевое оборудование';
    if (preg_match('/адаптер|конвертер|adapter|converter|сплиттер/u', $n)) return 'Адаптеры и конвертеры';
    if (preg_match('/автомобил|car\b|регистратор|насос|inflat/u', $n)) return 'Автоаксессуары';
    return 'Прочие аксессуары';
}

function detectBrand(string $name, array $brands): string {
    foreach ($brands as $b) if (stripos($name, $b) !== false) return $b;
    return 'Другое';
}

function detectStock(mixed $val): array {
    if ($val === null) return ['label'=>'Нет','qty'=>0,'cls'=>'no'];
    $s = trim((string)$val);
    if (mb_strtolower($s) === 'в резерве') return ['label'=>'В резерве','qty'=>0,'cls'=>'reserve'];
    if (is_numeric($s) && (int)$s > 0) return ['label'=>'В наличии','qty'=>(int)$s,'cls'=>'yes'];
    return ['label'=>'Нет','qty'=>0,'cls'=>'no'];
}

// ── Чтение XLSX ───────────────────────────────────────────────────────────
echo "Чтение $xlsx ...\n";
$xlsx_obj = SimpleXLSX::parse($xlsx);
if (!$xlsx_obj) die("Ошибка: " . SimpleXLSX::parseError() . "\n");

$rows = $xlsx_obj->rows(0);
$items = $cats = $brands = [];

foreach ($rows as $idx => $row) {
    if ($idx === 0) continue;
    $name = isset($row[3]) ? trim((string)$row[3]) : '';
    if (!$name || $name === 'None') continue;

    $pn    = isset($row[2]) ? trim((string)$row[2]) : '';
    $price = isset($row[7]) && is_numeric($row[7]) ? round((float)$row[7], 2) : null;
    $cur   = isset($row[8]) ? trim((string)$row[8]) : 'RUB';
    $st    = detectStock($row[4] ?? null);
    $cat   = detectCat($name);
    $brand = detectBrand($name, $BRANDS);

    $items[] = ['pn'=>$pn,'name'=>$name,'cat'=>$cat,'brand'=>$brand,'price'=>$price,'cur'=>$cur,'sl'=>$st['label'],'sq'=>$st['qty'],'sc'=>$st['cls']];
    if (!in_array($cat,   $cats))   $cats[]   = $cat;
    if (!in_array($brand, $brands)) $brands[] = $brand;
}

sort($cats); sort($brands);

$result = ['items'=>$items,'cats'=>$cats,'brands'=>$brands];
file_put_contents($out, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "Готово! Записано " . count($items) . " товаров → $out\n";
echo "Категорий: " . count($cats) . ", Брендов: " . count($brands) . "\n";
