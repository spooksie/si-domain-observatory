#!/usr/bin/env python3
"""Export a safe, database-free GitHub Pages version of the local viewer."""
import json,pathlib,re,shutil
root=pathlib.Path(__file__).resolve().parent.parent
out=root/'docs';out.mkdir(exist_ok=True)
source=(root/'index.php').read_text()
source=re.sub(r'^<\?php.*?\?>\s*','',source,flags=re.S)
source=source.replace('<html lang="en">','<html lang="en" data-storage="browser">')
source=re.sub(r'<meta name="csrf-token".*?">','',source)
assert '<?' not in source, 'Unrendered PHP in static export' 
source=source.replace('<b>Live registrar checks</b>','<b>Dated availability snapshot</b>')
source=source.replace('Availability from Neoserv and Domenca, with a timestamp on every result. Compare and purchase with Neoserv or Domenca.','Browse our registrar checks, with a timestamp on every result. Open Neoserv or Domenca for a fresh check before buying.')
source=source.replace('Purchase flags save to this folder','Your flags save in this browser')
source=source.replace('Add & check','Add name')
source=source.replace('<a href="README.md" target="_blank">How to use this viewer</a>','<a href="https://github.com/spooksie/si-domain-observatory" target="_blank" rel="noopener">GitHub ↗</a>')
source=source.replace('Marking “bought” only updates your tracker.','Marking “bought” only updates your tracker in this browser. Your flags are personal and are not shared with other visitors.')
(out/'index.html').write_text(source)
rows=json.loads((root/'data/domains.json').read_text())
for r in rows:
 r['bought']=False;r['favorite']=False
 for key in ['error','source_url','http_status']:r.pop(key,None)
(out/'domains.json').write_text(json.dumps(rows,ensure_ascii=False,separators=(',',':'))+'\n')
lines=[]
for line in (root/'DOMAINS.md').read_text().splitlines():
 if line.startswith('| ') and '.si |' in line:
  cells=line.split('|');cells[-3]=' No ';line='|'.join(cells)
 lines.append(line)
(out/'DOMAINS.md').write_text('\n'.join(lines)+'\n')
if (root/'CREATIVE-NAMING.md').exists():shutil.copyfile(root/'CREATIVE-NAMING.md',out/'CREATIVE-NAMING.md')
for name in ['style.css','app.js']:shutil.copyfile(root/name,out/name)
shutil.copytree(root/'assets',out/'assets',dirs_exist_ok=True)
(out/'.nojekyll').write_text('')
print(f'Exported {len(rows)} domains to docs/')
