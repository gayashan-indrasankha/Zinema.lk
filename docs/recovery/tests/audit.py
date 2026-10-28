"""Isolated local assessment; no application repair and no legacy data import.

Run: python docs/recovery/tests/audit.py prepare
     python docs/recovery/tests/audit.py check <runtime-directory>
     python docs/recovery/tests/audit.py stop <runtime-directory>
Requires the existing Windows XAMPP installation, Node and optional ffmpeg.
Services bind to loopback only. Refuses occupied ports and existing data dirs.
Private generated credentials, sessions and logs stay under ignored .runtime/.
"""
import hashlib
from html.parser import HTMLParser
import http.cookiejar
import json
import os
from pathlib import Path
import re
import secrets
import shutil
import socket
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[3]
PHP = Path('C:/xampp/php/php.exe')
MYSQL = Path('C:/xampp/mysql/bin')
HTTPD = Path('C:/xampp/apache/bin/httpd.exe')
PORT = 8088
DB_PORT = 3307
BASE = f'http://127.0.0.1:{PORT}'
SAMPLE_PASSWORD = 'Fictional-local-test-42!'
SKIP = {'node_modules', '.git', 'whatsapp-session', '.wwebjs_auth', '.runtime', '.recovery-private', 'uploads'}
NO_WINDOW = getattr(subprocess, 'CREATE_NO_WINDOW', 0)
RESULT_PREFIX = os.environ.get('ZINEMA_TEST_PHASE', 'REPAIR')
if RESULT_PREFIX not in {'REPAIR', 'LOCAL_DEV', 'OPERATIONS', 'QUARANTINE', 'PRODUCTION'}: raise RuntimeError('Unknown sanitized result prefix')



def source_files(extension=None):
    # Prune excluded trees before traversal; preserved sessions and dependency
    # trees are not assessment input and can contain many thousands of files.
    for base, directories, names in os.walk(ROOT):
        directories[:] = sorted(name for name in directories if name not in SKIP)
        for name in sorted(names):
            path = Path(base) / name
            if extension is None or path.suffix == extension:
                yield path


def run(args, **kw):
    return subprocess.run([str(x) for x in args], capture_output=True, text=True,
                          encoding='utf-8', errors='replace', creationflags=NO_WINDOW, **kw)


def port_open(port):
    with socket.socket() as s:
        s.settimeout(.2)
        return s.connect_ex(('127.0.0.1', port)) == 0


def wait_port(port, child):
    for _ in range(150):
        if child.poll() is not None:
            raise RuntimeError(f'Service on port {port} exited; inspect private runtime logs')
        if port_open(port):
            return
        time.sleep(.1)
    raise RuntimeError(f'Service on port {port} did not start')


def checked_runtime(value):
    folder = Path(value).resolve()
    assert folder.parent == (ROOT / '.runtime').resolve() and folder.name.startswith('audit-')
    assert (folder / 'ownership.json').is_file(), 'Not a generated audit runtime'
    return folder


def sql(folder, statement, database=False):
    # Verify our fresh server identity BEFORE every SQL operation.
    args = [MYSQL / 'mysql.exe', f'--defaults-file={folder / "client.ini"}', '--batch', '--raw', '--skip-column-names']
    identity = run(args, input='SELECT @@datadir, @@port;')
    expected = str((folder / 'db').resolve()).replace('\\', '/').lower().rstrip('/')
    fields = identity.stdout.strip().split('\t')
    assert identity.returncode == 0 and len(fields) == 2
    assert fields[0].replace('\\', '/').lower().rstrip('/') == expected and fields[1] == str(DB_PORT), 'Database target mismatch'
    if database:
        args.append('zinema_recovery_test')
    result = run(args, input=statement)
    if result.returncode:
        # SQL input and stderr may contain data; do not echo either.
        (folder / 'fixture-sql-error.log').write_text(result.stderr)
        raise RuntimeError('Isolated fixture SQL failed; diagnostic retained privately')
    return result.stdout.strip()


def syntax():
    result = {}
    for ext, command in [('.php', [PHP, '-n', '-l']), ('.js', ['node', '--check'])]:
        files = list(source_files(ext))
        failures = []
        for path in files:
            check = run(command + [path])
            if check.returncode:
                diagnostic = re.findall(r'(?:SyntaxError|Parse error):[^\n]+', check.stdout + check.stderr)
                failures.append({'file': path.relative_to(ROOT).as_posix(), 'diagnostic': diagnostic})
        result[ext] = {'total': len(files), 'passed': len(files) - len(failures), 'failures': failures}
    return result


def prepare():
    assert not port_open(DB_PORT) and not port_open(PORT), 'Required loopback ports already occupied; do not reuse an unknown service'
    folder = ROOT / '.runtime' / ('audit-' + time.strftime('%Y%m%d-%H%M%S'))
    folder.mkdir(parents=True, exist_ok=False)
    # Extra containment if the existing XAMPP docroot includes this workspace.
    # Our dedicated Apache config skips ancestor overrides and serves only web/.
    denial = folder.parent / '.htaccess'
    if not denial.exists():
        denial.write_text('Require all denied\n', encoding='ascii')
    web = folder / 'web'
    web.mkdir()
    (folder / 'sessions').mkdir()
    ownership = {'purpose': 'owner-authorized isolated Zinema assessment', 'base': BASE, 'pids': {}}
    (folder / 'ownership.json').write_text(json.dumps(ownership, indent=2))
    manifest = {}
    # Mirror code and style only. No environment, SQL rows, logs, sessions or private media.
    for path in source_files():
        rel = path.relative_to(ROOT)
        if not path.is_file() or any(x in SKIP for x in rel.parts):
            continue
        if rel.parts[0] in {'docs', 'whatsapp-bot-deploy', 'fb-video-api', 'migrations'}:
            continue
        if path.suffix.lower() not in {'.php', '.js', '.css'} and rel.as_posix() != '.htaccess':
            continue
        dest = web / rel
        dest.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(path, dest)
        manifest[rel.as_posix()] = hashlib.sha256(path.read_bytes()).hexdigest()
    (folder / 'source-hashes.json').write_text(json.dumps(manifest, indent=2))
    (web / 'demo.svg').write_text('<svg xmlns="http://www.w3.org/2000/svg" width="480" height="720"><rect width="480" height="720" fill="#17223b"/><text x="40" y="350" fill="white" font-size="30">Fictional Audit Movie</text></svg>')
    ffmpeg = shutil.which('ffmpeg')
    if ffmpeg:
        video = run([ffmpeg, '-v', 'error', '-f', 'lavfi', '-i', 'testsrc2=size=320x240:rate=15', '-t', '4', '-an', '-c:v', 'libx264', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', web / 'demo.mp4'])
        assert video.returncode == 0, 'Generated test-pattern video failed'
    # No existing MySQL config, service registration, database or credentials are reused.
    root_password = secrets.token_hex(24)
    install = run([MYSQL / 'mysql_install_db.exe', f'--datadir={folder / "db"}', f'--port={DB_PORT}', f'--password={root_password}', '--silent'])
    (folder / 'db-install.log').write_text(install.stdout + install.stderr)
    assert install.returncode == 0, 'Fresh isolated MariaDB initialization failed'
    (folder / 'client.ini').write_text(f'[client]\nhost=127.0.0.1\nport={DB_PORT}\nuser=root\npassword={root_password}\nprotocol=tcp\n')
    db_log = open(folder / 'db-console.log', 'w')
    db = subprocess.Popen([str(MYSQL / 'mysqld.exe'), '--no-defaults', f'--basedir={MYSQL.parent}', f'--datadir={folder / "db"}', '--bind-address=127.0.0.1', f'--port={DB_PORT}', '--skip-name-resolve', f'--pid-file={folder / "mysql.pid"}', f'--log-error={folder / "mysql.log"}', '--console'], stdout=db_log, stderr=db_log, creationflags=NO_WINDOW)
    ownership['pids']['mysql'] = db.pid
    (folder / 'ownership.json').write_text(json.dumps(ownership, indent=2))
    wait_port(DB_PORT, db)
    app_password = secrets.token_hex(24)
    sql(folder, f"CREATE DATABASE zinema_recovery_test CHARACTER SET utf8mb4; CREATE USER 'zinema_recovery_test'@'127.0.0.1' IDENTIFIED BY '{app_password}'; GRANT ALL ON zinema_recovery_test.* TO 'zinema_recovery_test'@'127.0.0.1';")
    ddl = (ROOT / 'database/schema.sql').read_text(encoding='utf-8', errors='strict')
    # The reviewed canonical schema contains no legacy data or database selection.
    assert not re.search(r'\b(?:INSERT|REPLACE|LOAD DATA|OUTFILE|DEFINER|CREATE DATABASE|USE)\b', ddl, re.I)
    creates = re.findall(r'^CREATE TABLE', ddl, re.M)
    alters = re.findall(r'^ALTER TABLE', ddl, re.M)
    sql(folder, ddl, True)
    hashed = run([PHP, '-r', 'echo password_hash("' + SAMPLE_PASSWORD + '", PASSWORD_DEFAULT);']).stdout
    assert hashed.startswith('$2'), 'Sample password hashing failed'
    seed = f"""
INSERT INTO admins(id,username,password_hash) VALUES(1,'audit_admin','{hashed}');
INSERT INTO users(id,username,email,password_hash,is_verified) VALUES(1,'audit_verified','verified@example.invalid','{hashed}',1),(2,'audit_unverified','unverified@example.invalid','{hashed}',0);
INSERT INTO collections(id,title,description,cover_image,genre) VALUES(1,'Fictional Audit Collection','Generated test collection','/demo.svg','Drama');
INSERT INTO movies(id,title,release_date,genre,rating,cover_image,video_url,collection_id,views,language_type) VALUES(1,'Fictional Audit Movie','2026-01-01','Drama','7.0','/demo.svg','{BASE}/demo.mp4',1,0,'dubbed'),(2,'Fictional Other Movie','2025-01-01','Comedy','6.0','/demo.svg','{BASE}/demo.mp4',NULL,0,'subtitled');
INSERT INTO series(id,title,release_date,genre,description,cover_image,language_type) VALUES(1,'Fictional Audit Series','2026-01-01','Drama','Sample series','/demo.svg','subtitled');
INSERT INTO episodes(id,series_id,season_number,episode_number,title,description,video_url,thumb_image,duration) VALUES(1,1,1,1,'Fictional Episode One','Sample episode','{BASE}/demo.mp4','/demo.svg',1),(2,1,1,2,'Fictional Episode Two','Sample episode','{BASE}/demo.mp4','/demo.svg',1);
INSERT INTO shots(id,title,description,fb_share_url,shot_video_file,linked_content_type,linked_content_id,admin_id) VALUES(1,'Fictional Audit Shot','Generated colour test-pattern clip','','{BASE}/demo.mp4','movie',1,1);
INSERT INTO trailers(id,movie_id,title,description,video_url,thumbnail_url) VALUES(1,1,'Fictional Audit Trailer','Generated sample trailer','{BASE}/demo.mp4','/demo.svg');
"""
    sql(folder, seed, True)
    env = dict(os.environ)
    env.update(APP_ENV='test', BASE_URL=BASE, DB_HOST='127.0.0.1', DB_PORT=str(DB_PORT), DB_NAME='zinema_recovery_test', DB_USER='zinema_recovery_test', DB_PASS=app_password, SESSION_SAVE_PATH=str(folder / 'sessions'), ALLOW_LIVE_INTEGRATIONS='false')
    for adapter in ['MAIL', 'OAUTH', 'WHATSAPP', 'MEDIA', 'EXTRACTOR', 'PAYMENT']:
        env[adapter + '_MODE'] = 'disabled'
    # Apache's own config and actual project .htaccess run against the sanitized mirror.
    path = lambda p: str(p).replace('\\', '/')
    conf = [f'ServerRoot "C:/xampp/apache"', f'Listen 127.0.0.1:{PORT}', 'ServerName 127.0.0.1', f'PidFile "{path(folder / "httpd.pid")}"', f'ErrorLog "{path(folder / "apache.log")}"', 'LogLevel warn']
    for module in ['authz_core', 'authz_host', 'dir', 'mime', 'rewrite', 'headers', 'env']:
        conf.append(f'LoadModule {module}_module modules/mod_{module}.so')
    conf += ['LoadFile "C:/xampp/php/php8ts.dll"', 'LoadFile "C:/xampp/php/libpq.dll"', 'LoadFile "C:/xampp/php/libsqlite3.dll"', 'LoadModule php_module "C:/xampp/php/php8apache2_4.dll"', 'PHPIniDir "C:/xampp/php"', 'TypesConfig "C:/xampp/apache/conf/mime.types"', f'DocumentRoot "{path(web)}"', 'DirectoryIndex index.php', '<Directory />', 'Require all denied', 'AllowOverride None', '</Directory>', f'<Directory "{path(web)}">', 'Require local', 'AllowOverride All', 'Options -Indexes +FollowSymLinks', '</Directory>', '<FilesMatch "\\.php$">', 'SetHandler application/x-httpd-php', '</FilesMatch>', 'php_admin_flag display_errors Off', f'php_admin_value error_log "{path(folder / "php.log")}"', f'php_admin_value session.save_path "{path(folder / "sessions")}"', 'Header always set Content-Security-Policy "default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data:; media-src \'self\'; connect-src \'self\'; font-src \'self\'; frame-src \'self\'; object-src \'none\'; base-uri \'self\'; form-action \'self\'"']
    conf += ['PassEnv ' + key for key in ['APP_ENV','BASE_URL','DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS','SESSION_SAVE_PATH','ALLOW_LIVE_INTEGRATIONS'] + [x + '_MODE' for x in ['MAIL','OAUTH','WHATSAPP','MEDIA','EXTRACTOR','PAYMENT']]]
    conf.append(f'php_admin_value mysqli.default_port {DB_PORT}')
    (folder / 'httpd.conf').write_text('\n'.join(conf) + '\n')
    config_check = run([HTTPD, '-t', '-f', folder / 'httpd.conf'], env=env)
    (folder / 'apache-config-check.log').write_text(config_check.stdout + config_check.stderr)
    assert config_check.returncode == 0, 'Isolated Apache configuration failed validation'
    log = open(folder / 'apache-console.log', 'w')
    apache = subprocess.Popen([str(HTTPD), '-f', str(folder / 'httpd.conf')], env=env, stdout=log, stderr=log, creationflags=NO_WINDOW)
    ownership['pids']['apache'] = apache.pid
    ownership['schema'] = {'create_tables': len(creates), 'alter_statements': len(alters), 'legacy_data_rows_imported': 0}
    ownership['schema']['views'] = len(re.findall(r'^CREATE(?: SQL SECURITY INVOKER)? VIEW', ddl, re.M))
    ownership['schema']['sha256'] = hashlib.sha256((ROOT/'database/schema.sql').read_bytes()).hexdigest()
    ownership['generated_video'] = bool(ffmpeg)
    (folder / 'ownership.json').write_text(json.dumps(ownership, indent=2))
    wait_port(PORT, apache)
    print(json.dumps({'runtime': str(folder), 'base': BASE, 'schema': ownership['schema'], 'generated_video': bool(ffmpeg)}), flush=True)
    if '--hold' in sys.argv:
        # Keep the launching job alive on hosts that reap child processes when
        # their launcher exits. The separate stop command ends this loop too.
        while port_open(PORT) or port_open(DB_PORT):
            time.sleep(1)


class Client:
    def __init__(self):
        class LocalRedirect(urllib.request.HTTPRedirectHandler):
            def redirect_request(self, req, fp, code, msg, headers, newurl):
                assert newurl.startswith(BASE + '/'), 'Refusing redirect outside isolated fixture'
                return super().redirect_request(req, fp, code, msg, headers, newurl)
        self.jar = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(LocalRedirect(), urllib.request.HTTPCookieProcessor(self.jar))

    def request(self, route, data=None, is_json=False, extra_headers=None, method=None):
        assert route.startswith('/') and not route.startswith('//')
        headers = dict(extra_headers or {})
        if data is not None:
            headers['Content-Type'] = 'application/json' if is_json else 'application/x-www-form-urlencoded'
            data = (json.dumps(data) if is_json else urllib.parse.urlencode(data)).encode()
        request = urllib.request.Request(BASE + route, data=data, headers=headers, method=method)
        try:
            response = self.opener.open(request, timeout=10)
        except urllib.error.HTTPError as e:
            response = e
        # Redirects must stay in this fixture (the code currently uses local paths).
        assert response.url.startswith(BASE + '/')
        body = response.read().decode('utf-8', errors='replace')
        try:
            parsed = json.loads(body)
        except ValueError:
            parsed = None
        return response.status, body, parsed, dict(response.headers), response.url

    def csrf(self):
        body = self.request('/login.php')[1]
        match = re.search(r'name="csrf_token" value="([a-f0-9]+)"', body)
        assert match, 'No CSRF form field in rendered login page'
        return match[1]


def movie_cards(body):
    class Cards(HTMLParser):
        links = []
        def handle_starttag(self, tag, attrs):
            attrs = dict(attrs)
            if tag == 'a' and 'simple-movie-card' in attrs.get('class','').split():
                self.links.append(attrs.get('href',''))
    parser = Cards()
    parser.feed(body)
    return sorted(int(re.search(r'movie/(\d+)/',link)[1]) for link in parser.links)


def check(folder):
    folder = checked_runtime(folder)
    php_log = folder / 'php.log'
    log_offset = len(php_log.read_bytes()) if php_log.exists() else 0
    rows = []
    def record(name, passed, category='feature', evidence='runtime verified', detail=None):
        row = {'name': name, 'passed': bool(passed), 'category': category, 'evidence': evidence}
        if detail is not None:
            row['detail'] = detail
        rows.append(row)
        print(('PASS ' if passed else 'FAIL ') + name, flush=True)
    client = Client()
    routes = ['/', '/movies', '/tv-series', '/collections', '/movie/1/fictional-audit-movie', '/series/1/fictional-audit-series', '/collection/1/fictional-audit-collection', '/login.php', '/signup.php', '/reset-password.php', '/trailers.php', '/pages/movie-detail.php', '/pages/trailers.php', '/pages/download.php', '/sitemap.xml']
    for route in routes:
        status, body, _, _, _ = client.request(route)
        record('Page renders: ' + route, status == 200 and not re.search(r'Fatal error|Warning:|Deprecated:', body), detail={'http': status, 'bytes': len(body)})
    _, body, _, _, _ = client.request('/movies?search=Fictional+Audit+Movie')
    record('Movie search matches fixture', movie_cards(body) == [1])
    for route, expected in [('/movies?genre=Comedy', [2]), ('/movies?filter=dubbed',[1]), ('/movies?page=999',[])]:
        body = client.request(route)[1]
        record('Catalogue filter: ' + route, movie_cards(body) == expected, detail={'grid_movie_ids':movie_cards(body)})
    for route in ['/api/movies.php','/api/series.php','/api/collections.php','/api/collections.php?id=1','/api/live_search.php?q=Fictional','/api/search_content.php?q=Fictional']:
        status, _, parsed, _, _ = client.request(route)
        record('JSON endpoint: ' + route, status == 200 and parsed is not None, detail={'http':status})
    for route in ['/api/trailers.php','/api/comments.php?movie_id=1']:
        status, _, parsed, _, _ = client.request(route)
        record('Trailer/comment subsystem: ' + route, status == 200 and parsed is not None, detail={'http':status})
    for route, expected in [('/download.php?id=1',403),('/api/subscribe.php',403),('/api/payment-notify.php',403),('/api/server-check.php',403),('/admin/run_migration.php',403),('/admin/cron/cleanup-tokens.php',403),('/admin/cron/reset-daily-stats.php',403),('/whatsapp-watch.php?id=1',503),('/api/whatsapp/validate-token.php',503),('/google-callback.php',503)]:
        status = client.request(route)[0]
        record('Recovery gate: ' + route, status == expected, 'containment', detail={'http':status,'feature_available':False})
    # Fixtures are not copies of sensitive files; HTTP deny checks use synthetic sentinels.
    for name in ['.env.audit','Database.sql','error_log']:
        (folder / 'web' / name).write_text('AUDIT_SENTINEL_NO_PRIVATE_DATA')
        status, body, _, _, _ = client.request('/' + name)
        record('Apache blocks synthetic private path: /' + name, status == 403 and 'AUDIT_SENTINEL' not in body, 'containment')
    user = Client()
    bad = user.request('/ajax_login.php', {'email':'verified@example.invalid','password':SAMPLE_PASSWORD})[2]
    record('AJAX login rejects missing CSRF', bad is not None and bad.get('success') is False and 'security token' in bad.get('message','').lower(), 'security')
    token = user.csrf()
    session_before = next((c.value for c in user.jar if c.name == 'PHPSESSID'), None)
    good = user.request('/ajax_login.php', {'email':'verified@example.invalid','password':SAMPLE_PASSWORD,'csrf_token':token})[2]
    record('Verified AJAX login', good is not None and good.get('success') is True)
    session_after = next((c.value for c in user.jar if c.name == 'PHPSESSID'), None)
    record('Successful login regenerates the session ID', bool(session_before and session_after and session_before != session_after),'security')
    record('Authenticated session endpoint', user.request('/api/check-session.php')[2].get('logged_in') is True)
    record('Profile renders after login', user.request('/profile.php')[0] == 200)
    for route, data in [('/api/like_shot.php',{'shot_id':1}),('/api/favorite_shot.php',{'shot_id':1}),('/api/add_comment.php',{'shot_id':1,'comment_text':'Fictional audit comment'}),('/api/record_view.php',{'shot_id':1})]:
        data['csrf_token'] = user.csrf()
        status, _, parsed, _, _ = user.request(route,data)
        record('Authenticated interaction: ' + route, status == 200 and parsed is not None and parsed.get('status') == 'success')
    comments = user.request('/api/get_comments.php?shot_id=1')[2]
    record('Stored Shot comment can be retrieved', comments is not None and any(c.get('comment_text') == 'Fictional audit comment' for c in comments.get('comments',[])))
    seen = user.request('/api/record_view.php',{'shot_id':1,'csrf_token':user.csrf()})[2]
    record('Duplicate view is idempotent', seen is not None and seen.get('status') == 'success' and seen.get('inserted') is False, detail={'matching_rows':sql(folder,'SELECT COUNT(*) FROM user_views WHERE user_id=1 AND shot_id=1;',True)})
    record('GET logout cannot change session state', user.request('/logout.php')[0] == 405 and user.request('/api/check-session.php')[2].get('logged_in') is True, 'security')
    user.request('/logout.php',{'csrf_token':user.csrf()})
    record('Logout clears authenticated session', user.request('/api/check-session.php')[2].get('logged_in') is False)
    for route, data, json_mode in [('/ajax_login.php',{},False),('/login.php',{},False),('/api/auth-handler.php',{'action':'login'},True)]:
        unverified = Client()
        payload = {'email':'unverified@example.invalid','password':SAMPLE_PASSWORD,**data}
        payload['csrf_token'] = unverified.csrf()
        unverified.request(route,payload,json_mode)
        logged_in = unverified.request('/api/check-session.php')[2].get('logged_in')
        record('Unverified account denied: ' + route, logged_in is False, 'security', detail={'logged_in':logged_in})
    injection = Client()
    injection.request('/ajax_login.php',{'email':"' OR 1=1 -- @example.invalid",'password':SAMPLE_PASSWORD,'csrf_token':injection.csrf()})
    record('Login injection string does not authenticate', injection.request('/api/check-session.php')[2].get('logged_in') is False, 'security')
    bypass = Client()
    bypass.request('/api/auth-handler.php', {'action':'login','email':'verified@example.invalid','password':SAMPLE_PASSWORD},True)
    record('Alternate JSON login rejects missing CSRF', bypass.request('/api/check-session.php')[2].get('logged_in') is False,'security')
    bypass.request('/api/auth-handler.php', {'action':'login','email':'verified@example.invalid','password':SAMPLE_PASSWORD,'csrf_token':bypass.csrf()},True)
    record('Alternate JSON login accepts verified account with CSRF', bypass.request('/api/check-session.php')[2].get('logged_in') is True)
    # These writes use the authenticated fictional user; no CSRF token is supplied.
    response = bypass.request('/api/add_comment.php',{'shot_id':1,'comment_text':'Fictional missing-CSRF probe'})[2]
    record('Shot comment mutation rejects missing CSRF', response is not None and response.get('status') != 'success','security')
    # The same verified session exercises negative and positive PDO/schema paths.
    csrf = bypass.csrf()
    record('Wrong CSRF token is rejected', bypass.request('/api/add_comment.php',{'shot_id':1,'comment_text':'Must not be stored','csrf_token':'0'*64})[0] == 403,'security')
    record('Array CSRF token returns 403 rather than crashing', bypass.request('/api/auth-handler.php',{'action':'login','csrf_token':[]},True)[0] == 403,'security')
    stranger = Client()
    record('A token from another session is rejected', stranger.request('/api/add_comment.php',{'shot_id':1,'comment_text':'Must not be stored','csrf_token':csrf})[0] == 403,'security')
    record('Header CSRF works for form writes', bypass.request('/api/add_comment.php',{'shot_id':1,'comment_text':'Fictional header-CSRF comment'},extra_headers={'X-CSRF-Token':csrf})[2].get('status') == 'success')
    trailers = bypass.request('/api/trailers.php')[2]
    record('Trailer query returns the populated canonical fixture', trailers is not None and any(t.get('id') == 1 and t.get('trailer_url') == BASE+'/demo.mp4' for t in trailers.get('trailers',[])))
    like = bypass.request('/api/trailers.php?action=like',{'trailer_id':1},True,{'X-CSRF-Token':csrf})[2]
    record('Authenticated trailer like writes through PDO', like is not None and 'liked' in like)
    added = bypass.request('/api/comments.php',{'movie_id':1,'content':'Fictional canonical movie comment'},True,{'X-CSRF-Token':csrf})[2]
    comment_id = (added or {}).get('comment',{}).get('id')
    record('Movie comment writes through the canonical movie_comments table', comment_id is not None and added['comment'].get('content') == 'Fictional canonical movie comment')
    if comment_id:
        reply = bypass.request('/api/comments.php',{'movie_id':1,'parent_id':comment_id,'content':'Fictional reply'},True,{'X-CSRF-Token':csrf})[2]
        record('Movie comment reply uses canonical parent linkage', reply is not None and reply.get('comment',{}).get('parent_id') == comment_id)
        sql(folder,"INSERT INTO users(username,email,password_hash,is_verified) SELECT 'audit_other','other@example.invalid',password_hash,1 FROM users WHERE id=1 ON DUPLICATE KEY UPDATE is_verified=1;",True)
        other = Client()
        other.request('/ajax_login.php',{'email':'other@example.invalid','password':SAMPLE_PASSWORD,'csrf_token':other.csrf()})
        record('Another user cannot delete a movie comment',other.request('/api/comments.php?id='+str(comment_id),extra_headers={'X-CSRF-Token':other.csrf()},method='DELETE')[0] == 403,'security')
        deleted = bypass.request('/api/comments.php?id='+str(comment_id),extra_headers={'X-CSRF-Token':csrf},method='DELETE')
        record('Owner can delete its movie comment and reply', deleted[0] == 200 and sql(folder,f'SELECT COUNT(*) FROM movie_comments WHERE id={int(comment_id)} OR parent_id={int(comment_id)};',True) == '0')
    remembered = Client()
    remembered.request('/ajax_login.php',{'email':'verified@example.invalid','password':SAMPLE_PASSWORD,'remember':'1','csrf_token':remembered.csrf()})
    remember_token = next((c.value for c in remembered.jar if c.name == 'remember_token'),None)
    stored_hash = sql(folder,'SELECT remember_token FROM users WHERE id=1;',True)
    record('Remember-me stores a digest rather than the cookie credential', bool(remember_token and stored_hash == hashlib.sha256(remember_token.encode()).hexdigest() and stored_hash != remember_token),'security')
    replay = Client()
    for cookie in list(remembered.jar):
        if cookie.name in {'remember_token','remember_user'}: replay.jar.set_cookie(cookie)
        if cookie.name == 'PHPSESSID': remembered.jar.clear(cookie.domain,cookie.path,cookie.name)
    record('Verified remember-me cookie restores a session', remembered.request('/api/check-session.php')[2].get('logged_in') is True)
    remembered.request('/logout.php',{'csrf_token':remembered.csrf()})
    record('Logout revokes captured remember-me cookies', replay.request('/api/check-session.php')[2].get('logged_in') is False,'security')
    # Distinct fictional identities; no real mail. Deliberately exercise disabled-mail signup semantics.
    signup = Client()
    suffix = str(time.time_ns())[-12:]
    email = 'audit' + suffix + '@example.invalid'
    response = signup.request('/ajax_signup.php',{'username':'audit'+suffix,'email':email,'password':SAMPLE_PASSWORD,'csrf_token':signup.csrf()})[2]
    count = sql(folder,f"SELECT COUNT(*) FROM users WHERE email='{email}';",True)
    record('AJAX signup reports disabled delivery honestly', response is not None and response.get('success') is False, detail={'success':None if response is None else response.get('success'),'account_rows':count})
    record('Disabled-mail signup grants neither account nor session', count == '0' and signup.request('/api/check-session.php')[2].get('logged_in') is False,'security')
    admin = Client()
    record('Admin requires authentication', admin.request('/admin/index.php')[4].endswith('/admin/login.php'), 'security')
    admin.request('/admin/login.php',{'username':'audit_admin','password':SAMPLE_PASSWORD,'csrf_token':admin.csrf()})
    for route in ['/admin/index.php','/admin/movies.php','/admin/series.php','/admin/episodes.php','/admin/collections.php','/admin/manage_shots.php','/admin/analytics.php','/admin/settings.php','/admin/trailers.php','/admin/whatsapp-bot-tracking.php']:
        status, body, _, _, final = admin.request(route)
        record('Admin page renders: ' + route, status == 200 and not final.endswith('/admin/login.php') and not re.search(r'Fatal error|Warning:|Deprecated:',body), detail={'http':status,'visible_php_diagnostic':bool(re.search(r'Fatal error|Warning:|Deprecated:',body))})
    movie = {'add_movie':'1','title':'Fictional CRUD Movie','release_date':'2026-01-01','genre':'Drama','rating':'7','video_url':BASE+'/demo.mp4','language_type':'dubbed','collection_id':'1'}
    admin.request('/admin/movies.php',movie)
    new_id = sql(folder,"SELECT id FROM movies WHERE title='Fictional CRUD Movie' ORDER BY id DESC LIMIT 1;",True)
    record('Admin movie creation rejects missing CSRF',not new_id.isdigit(),'security')
    movie['csrf_token'] = admin.csrf()
    admin.request('/admin/movies.php',movie)
    new_id = sql(folder,"SELECT id FROM movies WHERE title='Fictional CRUD Movie' ORDER BY id DESC LIMIT 1;",True)
    record('Admin can create fictional movie with CSRF',new_id.isdigit())
    if new_id.isdigit():
        movie.pop('add_movie'); movie.update(edit_movie='1',movie_id=new_id,title='Fictional CRUD Movie Edited')
        admin.request('/admin/movies.php',movie)
        record('Admin can edit fictional movie',sql(folder,f"SELECT title FROM movies WHERE id={int(new_id)};",True)=='Fictional CRUD Movie Edited')
        # Delete only the exact synthetic movie just created, with no image file.
        admin.request('/admin/movies.php?delete='+new_id)
        remains = sql(folder,f'SELECT COUNT(*) FROM movies WHERE id={int(new_id)};',True)
        record('Admin refuses state-changing GET deletion',remains=='1','security')
        admin.request('/admin/movies.php',{'delete':new_id,'csrf_token':admin.csrf()})
        record('Admin can delete its fictional test movie',sql(folder,f'SELECT COUNT(*) FROM movies WHERE id={int(new_id)};',True)=='0')
    manifest=json.loads((folder/'source-hashes.json').read_text())
    record('Tested mirror matches current repaired source', all(hashlib.sha256((ROOT/rel).read_bytes()).hexdigest()==digest for rel,digest in manifest.items()),'integrity')
    mail_source = (ROOT/'includes/mailer.php').read_text(encoding='utf-8')
    record('Email templates contain no browser CSRF markup', 'csrf_meta_tags' not in mail_source and 'name="csrf-token"' not in mail_source,'static security')
    new_log = php_log.read_bytes()[log_offset:].decode('utf-8', errors='replace') if php_log.exists() else ''
    diagnostics = {name:len(re.findall('PHP '+name,new_log)) for name in ['Warning','Notice','Fatal error','Deprecated']}
    record('No new PHP diagnostics during the final request suite', not any(diagnostics.values()),'runtime diagnostics',detail=diagnostics)
    results = {'date':'2026-10-25','base':BASE,'schema':'database/schema.sql canonical local schema; zero legacy data rows','tests':rows,'summary':{'total':len(rows),'passed':sum(r['passed'] for r in rows),'failed':sum(not r['passed'] for r in rows)},'syntax':syntax(),'new_php_diagnostics':diagnostics}
    (ROOT/f'docs/recovery/{RESULT_PREFIX}_RETAINED_HTTP.json' if RESULT_PREFIX != 'REPAIR' else ROOT/'docs/recovery/REPAIR_RESULTS.json').write_text(json.dumps(results,indent=2)+'\n')
    print(json.dumps(results['summary']))
    raise SystemExit(1 if results['summary']['failed'] else 0)


def stop(folder):
    folder = checked_runtime(folder)
    ownership = json.loads((folder / 'ownership.json').read_text())
    # Windows Apache -k addresses registered services, so verify the standalone
    # process command line and terminate only the exact owned process tree.
    apache = None
    if 'apache' in ownership['pids']:
        pid = int(ownership['pids']['apache'])
        probe = run(['powershell', '-NoProfile', '-Command', f'Get-CimInstance Win32_Process -Filter "ProcessId={pid}" | Select-Object ExecutablePath,CommandLine | ConvertTo-Json -Compress'])
        process = json.loads(probe.stdout) if probe.stdout.strip() else None
        if process:
            assert process['ExecutablePath'].lower() == str(HTTPD).replace('/', '\\').lower()
            assert str(folder / 'httpd.conf').lower() in process['CommandLine'].lower()
            apache = run(['taskkill', '/PID', str(pid), '/T', '/F'])
    db = None
    if port_open(DB_PORT):
        sql(folder, 'SELECT 1;')
        db = run([MYSQL/'mysqladmin.exe', f'--defaults-file={folder / "client.ini"}', 'shutdown'])
    for _ in range(100):
        if not port_open(PORT) and not port_open(DB_PORT):
            break
        time.sleep(.1)
    print(json.dumps({'apache_command_exit':apache.returncode if apache else None,'mysql_shutdown_exit':db.returncode if db else None,'http_port_closed':not port_open(PORT),'mysql_port_closed':not port_open(DB_PORT)}))


def refresh(folder):
    folder = checked_runtime(folder)
    manifest = {}
    for path in source_files():
        rel = path.relative_to(ROOT)
        if not path.is_file() or any(x in SKIP for x in rel.parts): continue
        if rel.parts[0] in {'docs','whatsapp-bot-deploy','fb-video-api','migrations','database'}: continue
        if path.suffix.lower() not in {'.php','.js','.css'} and rel.as_posix() != '.htaccess': continue
        dest = folder / 'web' / rel
        dest.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(path, dest)
        manifest[rel.as_posix()] = hashlib.sha256(path.read_bytes()).hexdigest()
    (folder/'source-hashes.json').write_text(json.dumps(manifest,indent=2))
    print(json.dumps({'refreshed_source_files':len(manifest)}))


if __name__ == '__main__':
    assert Path.cwd().resolve() == ROOT, 'Run from workspace root'
    if sys.argv[1] == 'prepare':
        prepare()
    elif sys.argv[1] == 'check':
        check(sys.argv[2])
    elif sys.argv[1] == 'stop':
        stop(sys.argv[2])
    elif sys.argv[1] == 'refresh':
        refresh(sys.argv[2])
    else:
        raise SystemExit('Use prepare, check <runtime>, or stop <runtime>')
