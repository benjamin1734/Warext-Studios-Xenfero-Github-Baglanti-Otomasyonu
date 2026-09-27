#!/usr/bin/env python3
from __future__ import annotations
import argparse, json
from pathlib import Path
import xml.etree.ElementTree as ET

ROOT=Path(__file__).resolve().parents[1]
ADDON=ROOT/'src/addons/Warext/GitHubSync'
LANG=ROOT/'languages'
ADDON_ID='Warext/GitHubSync'

def cdata(value:str)->str:
    return value.replace(']]>', ']]]]><![CDATA[>')

def source_map():
    pdir=ADDON/'_output/phrases'
    return {p.stem:p.read_text(encoding='utf-8').rstrip('\n') for p in sorted(pdir.glob('*.txt'))}

def translations():
    merged={}
    for p in sorted((LANG/'translations/tr-TR').glob('part-*.json')):
        data=json.loads(p.read_text(encoding='utf-8'))
        overlap=set(merged)&set(data)
        if overlap:
            raise SystemExit(f'Duplicate Turkish translation keys in {p}: {sorted(overlap)[:5]}')
        merged.update(data)
    return merged

def xml_text(title, code, date_format, time_format, decimal, thousands, values, version_id, version_string):
    lines=[f'<?xml version="1.0" encoding="utf-8"?>',
           f'<language title="{title}" date_format="{date_format}" time_format="{time_format}" decimal_point="{decimal}" thousands_separator="{thousands}" language_code="{code}" text_direction="LTR">']
    for key in sorted(values):
        lines.append(f'  <phrase title="{key}" addon_id="{ADDON_ID}" global_cache="0" version_id="{version_id}" version_string="{version_string}"><![CDATA[{cdata(values[key])}]]></phrase>')
    lines.append('</language>')
    return '\n'.join(lines)+'\n'

def main(check=False):
    meta=json.loads((ADDON/'addon.json').read_text(encoding='utf-8'))
    source=source_map()
    declared=json.loads((LANG/'source.en-US.json').read_text(encoding='utf-8'))
    tr=translations()

    if source != declared:
        missing=sorted(set(source)-set(declared)); extra=sorted(set(declared)-set(source))
        changed=sorted(k for k in set(source)&set(declared) if source[k]!=declared[k])
        raise SystemExit(f'English source map mismatch: missing={missing[:5]} extra={extra[:5]} changed={changed[:5]}')
    if set(source)!=set(tr):
        missing=sorted(set(source)-set(tr)); extra=sorted(set(tr)-set(source))
        raise SystemExit(f'Turkish translation coverage mismatch: missing={missing[:10]} extra={extra[:10]}')

    english=xml_text('Warext GitHub Sync English (US)','en-US','M j, Y','g:i A','.',',',source,meta['version_id'],meta['version_string'])
    turkish=xml_text('Warext GitHub Sync Türkçe (TR)','tr-TR','j F Y','H:i',',','.',tr,meta['version_id'],meta['version_string'])

    outputs={LANG/'English.xml':english, LANG/'Turkish.xml':turkish}
    if check:
        for path, expected in outputs.items():
            if not path.exists() or path.read_text(encoding='utf-8')!=expected:
                raise SystemExit(f'{path} is out of date; run tools/build-language-packs.py')
    else:
        for path, text in outputs.items(): path.write_text(text,encoding='utf-8')

    for path in outputs: ET.parse(path)
    print(f'Language pack validation: OK ({len(source)} phrases, exact EN/TR parity)')

if __name__=='__main__':
    ap=argparse.ArgumentParser(); ap.add_argument('--check',action='store_true'); args=ap.parse_args(); main(args.check)
