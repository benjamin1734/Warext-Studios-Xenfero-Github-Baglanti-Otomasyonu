#!/usr/bin/env python3
from __future__ import annotations
import argparse, json
from pathlib import Path
from html import escape

EMPTY = [
    'activity_summary_definitions','advertising_positions','api_scopes','bb_code_media_sites','bb_codes','class_extensions',
    'code_events','content_type_fields','cron','help_pages','member_stats','navigation','option_groups','options',
    'permission_interface_groups','permissions','style_properties','style_property_groups','template_modifications',
    'widget_definitions','widget_positions'
]

def attr(name, value):
    if value is None or value == '': return ''
    if isinstance(value, bool): value = '1' if value else '0'
    return f' {name}="{escape(str(value), quote=True)}"'

def cdata(value): return str(value).replace(']]>', ']-]->')

def doc(root, rows):
    if not rows: return f'<?xml version="1.0" encoding="utf-8"?>\n<{root}/>\n'
    return '<?xml version="1.0" encoding="utf-8"?>\n<' + root + '>\n' + '\n'.join('  '+x for x in rows) + f'\n</{root}>\n'

def jsons(path):
    if not path.exists(): return []
    return [json.loads(p.read_text(encoding='utf-8')) for p in sorted(path.rglob('*.json')) if p.name != '_metadata.json']

def build(addon):
    addon=Path(addon); out=addon/'_output'; data=addon/'_data'; data.mkdir(parents=True,exist_ok=True)
    meta=json.loads((addon/'addon.json').read_text(encoding='utf-8')); vid=meta['version_id']; vs=meta['version_string']

    rows=[]
    for r in jsons(out/'admin_navigation'):
        s='<admin_navigation_entry'
        for k,v in [('navigation_id',r.get('navigation_id')),('parent_navigation_id',r.get('parent_navigation_id')),('display_order',r.get('display_order')),('link',r.get('link')),('icon',r.get('icon')),('admin_permission_id',r.get('admin_permission_id')),('debug_only',r.get('debug_only',False)),('development_only',r.get('development_only',False)),('hide_no_children',r.get('hide_no_children',False))]: s+=attr(k,v)
        rows.append(s+'/>')
    (data/'admin_navigation.xml').write_text(doc('admin_navigation',rows),encoding='utf-8')

    rows=['<admin_permission'+attr('admin_permission_id',r.get('admin_permission_id'))+attr('display_order',r.get('display_order'))+'/>' for r in jsons(out/'admin_permissions')]
    (data/'admin_permission.xml').write_text(doc('admin_permission',rows),encoding='utf-8')

    rows=[]
    for r in sorted(jsons(out/'routes'),key=lambda x:(x.get('route_type',''),x.get('route_prefix',''),x.get('sub_name',''))):
        s='<route'
        for k in ['route_type','route_prefix','sub_name','format','build_class','build_method','controller','context','action_prefix']: s+=attr(k,r.get(k,''))
        rows.append(s+'/>')
    (data/'routes.xml').write_text(doc('routes',rows),encoding='utf-8')

    rows=[]
    for r in sorted(jsons(out/'code_event_listeners'),key=lambda x:(x.get('event_id',''),x.get('callback_class',''),x.get('callback_method',''))):
        s='<listener'
        for k in ['event_id','execute_order','callback_class','callback_method','active','hint','description']: s+=attr(k,r.get(k,''))
        rows.append(s+'/>')
    (data/'code_event_listeners.xml').write_text(doc('code_event_listeners',rows),encoding='utf-8')

    rows=[]
    pdir=out/'phrases'
    if pdir.exists():
        # Only canonical XenForo dotted phrase identifiers are exported.
        for p in sorted(pdir.glob('*.txt')):
            if '.' not in p.stem: continue
            rows.append('<phrase'+attr('title',p.stem)+attr('version_id',vid)+attr('version_string',vs)+f'><![CDATA[{cdata(p.read_text(encoding="utf-8"))}]]></phrase>')
    (data/'phrases.xml').write_text(doc('phrases',rows),encoding='utf-8')

    rows=[]; tdir=out/'templates'
    if tdir.exists():
        for p in sorted(x for x in tdir.rglob('*') if x.is_file() and x.name!='_metadata.json'):
            rel=p.relative_to(tdir); parts=rel.parts
            if len(parts)<2: continue
            typ=parts[0]; title='/'.join(parts[1:]); title=title[:-5] if title.endswith('.html') else title
            rows.append('<template'+attr('type',typ)+attr('title',title)+attr('version_id',vid)+attr('version_string',vs)+f'><![CDATA[{cdata(p.read_text(encoding="utf-8"))}]]></template>')
    (data/'templates.xml').write_text(doc('templates',rows),encoding='utf-8')

    for root in EMPTY: (data/f'{root}.xml').write_text(doc(root,[]),encoding='utf-8')
    print(f'Generated {len(list(data.glob("*.xml")))} XenForo _data XML files')

if __name__=='__main__':
    ap=argparse.ArgumentParser(); ap.add_argument('addon_dir'); build(ap.parse_args().addon_dir)
