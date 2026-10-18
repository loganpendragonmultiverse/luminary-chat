from pathlib import Path
import hashlib,subprocess,zipfile
root=Path(__file__).resolve().parents[1]
version=(root/'VERSION').read_text().strip()
slug=subprocess.check_output(['git','config','--get','remote.origin.url'],cwd=root,text=True).strip().rstrip('/').split('/')[-1].removesuffix('.git')
files=subprocess.check_output(['git','ls-files','-z'],cwd=root).decode().split('\0')
paths=[root/x for x in files if x and (root/x).is_file() and not (root/x).is_symlink()]
if (root/'public/build').exists():paths += [p for p in (root/'public/build').rglob('*') if p.is_file()]
dist=root/'dist';dist.mkdir(exist_ok=True);archive=dist/f'{slug}-{version}.zip'
with zipfile.ZipFile(archive,'w',zipfile.ZIP_DEFLATED) as z:
 for p in sorted(set(paths)):
  rel=p.relative_to(root).as_posix()
  assert rel!='.env' and not rel.endswith('.sqlite') and not rel.startswith(('vendor/','node_modules/')) and (not rel.startswith('storage/logs/') or p.name=='.gitignore')
  info=zipfile.ZipInfo(f'{slug}-{version}/'+rel,date_time=(2026,10,17,0,0,0));info.compress_type=zipfile.ZIP_DEFLATED
  z.writestr(info,p.read_bytes())
digest=hashlib.sha256(archive.read_bytes()).hexdigest()
(dist/'SHA256SUMS.txt').write_text(digest+'  '+archive.name+'\n')
print(archive.name,digest)
