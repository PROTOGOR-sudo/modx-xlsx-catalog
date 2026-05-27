#!/usr/bin/env python3
"""
xlsx_to_json.py — Конвертер каталога XLSX → JSON
Установка: pip install openpyxl
Использование: python3 xlsx_to_json.py input.xlsx output.json
"""

import sys, json, re
import openpyxl

BRANDS = ['Apple','Samsung','Xiaomi','JBL','Jabra','Satechi','Belkin','UGREEN','TTEC','Hoto','Anker','Baseus','360']

def detect_category(name):
    n = name.lower()
    if re.search(r'внешний.{0,10}(аккумулятор|battery)|power\s*bank|powerbank', n): return 'Внешние аккумуляторы'
    if re.search(r'сетевое|зарядн|charger|зарядка|зарядное', n): return 'Зарядные устройства'
    if re.search(r'кабел|cable', n): return 'Кабели'
    if re.search(r'наушник|earphone|headphone|airpod|гарнитур|tws|вкладыш', n): return 'Наушники'
    if re.search(r'колонк|speaker', n): return 'Акустика'
    if re.search(r'чехол|bumper|бампер', n): return 'Чехлы'
    if re.search(r'держател|holder|mount|крепл', n): return 'Держатели'
    if re.search(r'хаб|hub\b|dock|станция', n): return 'Хабы и доки'
    if re.search(r'мышь|mouse\b|клавиатур|keyboard', n): return 'Периферия'
    if re.search(r'часы|watch|трекер|браслет', n): return 'Носимые устройства'
    if re.search(r'wifi|wi-fi|ethernet\s*адаптер', n): return 'Сетевое оборудование'
    if re.search(r'адаптер|конвертер|adapter|converter|сплиттер', n): return 'Адаптеры и конвертеры'
    if re.search(r'автомобил|car\b|регистратор|насос|inflat', n): return 'Автоаксессуары'
    return 'Прочие аксессуары'

def detect_brand(name):
    for b in BRANDS:
        if b.lower() in name.lower():
            return b
    return 'Другое'

def detect_stock(val):
    if val is None: return {'label':'Нет','qty':0,'cls':'no'}
    s = str(val).strip()
    if s.lower() == 'в резерве': return {'label':'В резерве','qty':0,'cls':'reserve'}
    try:
        n = int(float(s))
        if n > 0: return {'label':'В наличии','qty':n,'cls':'yes'}
    except: pass
    return {'label':'Нет','qty':0,'cls':'no'}

def convert(xlsx_path, json_path):
    print(f"Читаю: {xlsx_path}")
    wb = openpyxl.load_workbook(xlsx_path, read_only=True)
    ws = wb.active
    items, cats, brands = [], [], []
    for i, row in enumerate(ws.iter_rows(values_only=True)):
        if i == 0: continue
        name = str(row[3]).strip() if row[3] else ''
        if not name or name == 'None': continue
        pn = str(row[2]).strip() if row[2] else ''
        try: price = round(float(row[7]), 2) if row[7] is not None else None
        except: price = None
        cur = str(row[8]).strip() if row[8] else 'RUB'
        st = detect_stock(row[4])
        cat = detect_category(name)
        brand = detect_brand(name)
        items.append({'pn':pn,'name':name,'cat':cat,'brand':brand,'price':price,'cur':cur,'sl':st['label'],'sq':st['qty'],'sc':st['cls']})
        if cat not in cats: cats.append(cat)
        if brand not in brands: brands.append(brand)
    wb.close()
    cats.sort(); brands.sort()
    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump({'items':items,'cats':cats,'brands':brands}, f, ensure_ascii=False, separators=(',',':'))
    print(f"Готово! {len(items)} товаров → {json_path}")

if __name__ == '__main__':
    if len(sys.argv) != 3:
        print("Использование: python3 xlsx_to_json.py input.xlsx output.json")
        sys.exit(1)
    convert(sys.argv[1], sys.argv[2])
