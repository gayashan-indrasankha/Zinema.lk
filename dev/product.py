"""Full product HTTP journeys against the owned fixture; no live integrations."""
import concurrent.futures
import json
from pathlib import Path
import re
import sys
import time
from integration import Client, PASSWORD, control, png

FIXTURE=json.loads(Path('/results/product-fixture.json').read_text())
ROWS=[]

def check(name,passed):
    ROWS.append({'name':name,'passed':bool(passed),'evidence':'runtime verified locally'})
    print(('PASS ' if passed else 'FAIL ')+name,flush=True)

def post_json(client,path,payload):
    return client.request(path,raw=json.dumps(payload).encode(),headers={'Content-Type':'application/json','X-CSRF-Token':client.csrf()})

def run():
    guest=Client();admin=Client();user=Client();f=FIXTURE
    check('Fictional product seed available',f.get('success') is True)
    response=admin.post('/admin/login.php',{'username':'demo_admin','password':PASSWORD})
    check('Administrator password sign-in redirects to dashboard',response[0]==302)
    check('Trailer administration is reachable from the shared sidebar',b'href="trailers.php"' in admin.request('/admin/index.php')[1])
    for page in ['index','movies','series','episodes','collections','manage_shots','trailers','analytics','settings','whatsapp-bot-tracking']:
        path='/admin/'+page+'.php'
        check('Administrator page renders '+page,admin.request(path)[0]==200)
        check('Guest denied administrator page '+page,guest.request(path)[0]==302)
    for path in ['/', '/movies.php','/tv-series.php','/collections.php','/trailers.php','/pages/trailers.php','/profile.php','/sitemap.xml',
        '/movie/'+str(f['movie'])+'/fictional','/series/'+str(f['series'])+'/fictional','/collection/'+str(f['collection'])+'/fictional']:
        check('Public product route '+path.split('?')[0],guest.request(path)[0]==200)
    for path,key in [('/api/movies.php',None),('/api/series.php',None),('/api/collections.php',None),('/api/trailers.php','trailers')]:
        response=guest.request(path);parsed=json.loads(response[1]);items=parsed[key] if key else parsed
        check('Catalogue API includes fictional data '+path,response[0]==200 and any(item['title']==f['title'] for item in items))
    for path,key in [('/api/series.php?id='+str(f['series']),'seasons'),('/api/collections.php?id='+str(f['collection']),'movies'),('/api/trailers.php?id='+str(f['trailer']),'trailer')]:
        response=guest.request(path);check('Detail API supplies related data '+path.split('?')[0],response[0]==200 and bool(response[2].get(key)))
    for path in ['/api/live_search.php?q='+f['run'],'/api/search_content.php?q='+f['run']+'&type=movie']:
        response=guest.request(path);check('Search locates fictional catalogue '+path.split('?')[0],response[0]==200 and f['title'].encode() in response[1])
    movie_body=guest.request('/movie/'+str(f['movie'])+'/fictional')[1]
    check('Full movie CTA uses WhatsApp-only delivery',('/whatsapp-watch.php?id='+str(f['movie'])).encode() in movie_body and b'onclick="playMovie(' not in movie_body)
    series_body=guest.request('/series/'+str(f['series'])+'/fictional')[1]
    check('Episodes route to WhatsApp without exposing legacy streaming references',b'/whatsapp-watch.php?id=' in series_body and b'&type=episode' in series_body and b'data-video-url=' not in series_body)
    check('Original test media served byte-for-byte',guest.request('/'+f['clip'])[1]==__import__('base64').b64decode(Path('dev/fixtures/motion-demo.mp4.b64').read_text()))
    check('Member password sign-in succeeds',user.post('/ajax_login.php',{'email':'verified@example.invalid','password':PASSWORD})[2].get('success') is True)
    for action in ['like','favorite','view','share']:
        response=post_json(user,'/api/trailers.php?action='+action,{'trailer_id':f['trailer'],'platform':'fixture'})
        check('Trailer action '+action,response[0]==200 and 'error' not in response[2])
    for path,values in [('/api/like_shot.php',{'shot_id':f['shot']}),('/api/favorite_shot.php',{'shot_id':f['shot']}),('/api/record_view.php',{'shot_id':f['shot']}),('/api/add_comment.php',{'shot_id':f['shot'],'comment_text':'Fictional product comment'})]:
        response=user.post(path,values);check('Member interaction '+path,response[2].get('status')=='success')
        if path.endswith('favorite_shot.php') and response[2].get('action')=='unfavorited':user.post(path,values)
    check('Saved shot appears on member profile',f['title'].encode() in user.request('/profile.php')[1])
    comments=guest.request('/api/get_comments.php?shot_id='+str(f['shot']))[2]
    check('Shot comments can be read',any(c.get('comment_text')=='Fictional product comment' for c in comments.get('comments',[])))
    for path in ['/api/trailers.php?page=-1','/api/trailers.php?limit=-1','/api/trailers.php?limit=1000000']:
        response=guest.request(path);check('Trailer pagination bounded '+path.split('?')[1],response[0]==400)
    invalid=user.post('/api/record_view.php',{'shot_id':2147483647})
    check('Views reject nonexistent shots',invalid[0] in {400,404})
    for path in ['/api/live_search.php?q[]=invalid','/api/search_content.php?q[]=invalid','/api/live_search.php?q='+'x'*201]:
        check('Malformed search is a controlled client error '+path.split('?')[0],guest.request(path)[0]==400)
    check('Oversized shot comments are rejected',user.post('/api/add_comment.php',{'shot_id':f['shot'],'comment_text':'x'*1001})[0]==400)
    check('Comments reject nonexistent shots',user.post('/api/add_comment.php',{'shot_id':2147483647,'comment_text':'Fictional missing target'})[0]==404)
    bad=admin.post('/admin/manage_shots.php',{'submit_shot':1,'title':'Local Test Invalid '+f['run'],'description':'Fictional','content_type':'movie','linked_content_id':f['movie'],'fb_share_url':'https://facebook.com.example.invalid/clip'})
    check('Shot form rejects deceptive Facebook host',bad[0] in {200,400,422} and b'valid Facebook URL' in bad[1])
    collection_shot=admin.post('/admin/manage_shots.php',{'submit_shot':1,'title':'Local Test Collection '+f['run'],'description':'Fictional','content_type':'collection','linked_content_id':f['collection'],'fb_share_url':'https://www.facebook.com/watch/?v=123'})
    check('Shot form supports its advertised collection content type',collection_shot[0]==200 and b'Shot added successfully' in collection_shot[1])
    shot_title='Local Test Collection '+f['run']
    shot=control('catalogue',table='shots',title=shot_title)
    if shot:
        response=admin.post('/admin/manage_shots.php',{'submit_shot':1,'shot_id':shot['id'],'title':shot_title+' Updated','description':'Fictional edited','content_type':'collection','linked_content_id':f['collection'],'fb_share_url':'https://www.facebook.com/watch/?v=123'})
        check('Administrator edits a fictional collection shot',response[0]==302 and bool(control('catalogue',table='shots',title=shot_title+' Updated')))
        response=admin.post('/admin/manage_shots.php',{'delete':shot['id']})
        check('Administrator deletes only the created fictional shot',response[0]==200 and not control('catalogue',table='shots',title=shot_title+' Updated'))
    # A private sample setting is scoped to this fictional run; existing settings remain untouched.
    setting='fixture_product_'+f['run']
    response=admin.post('/admin/settings.php',{'action':'add_setting','new_key':setting,'new_value':'fictional','new_description':'Product test only'})
    check('Administrator can create a fictional setting',response[0]==200 and b'added successfully' in response[1])
    response=admin.post('/admin/settings.php',{'action':'save_settings','setting_'+setting:'fictional-updated'})
    check('Administrator can update a fictional setting',response[0]==200 and b'Successfully updated' in response[1])
    common={'release_date':'2026-01-01','genre':'Demo','description':'Original fictional lifecycle'}
    cases=[('movies',dict(common,add_movie='1',rating='5',video_url=''),'cover_image','add_movie','edit_movie','movie_id','delete'),
           ('series',dict(common,add_series='1'),'cover_image','add_series','edit_series','series_id','delete'),
           ('collections',dict(common),'cover_image',None,None,'id','delete'),
           ('episodes',dict(common,add_episode='1',series_id=f['series'],season_number=2,episode_number=1,video_url=''),'thumb_image','add_episode','edit_episode','episode_id','delete_episode')]
    for table,values,field,add,edit,id_key,delete_key in cases:
        title=f['title']+' Lifecycle '+table
        if table in {'movies','series','collections'}:title+=' </script><script>/*fixture-json-boundary*/</script>'
        values=dict(values,title=title)
        response=admin.upload('/admin/'+table+'.php',values,field,'original.png',png())
        item=control('catalogue',table=table,title=title)
        check('Administrator create '+table,response[0] in {200,302} and bool(item.get('id')))
        if not item:continue
        if table in {'movies','series','collections'}:
            prefix={'movies':'movie','series':'series','collections':'collection'}[table]
            detail=guest.request('/'+prefix+'/'+str(item['id'])+'/fictional')[1]
            check('Catalogue JSON-LD cannot break out of its script '+table,b'<script>/*fixture-json-boundary*/</script>' not in detail)
            try:
                blocks=re.findall(rb'<script type="application/ld\+json">\s*(.*?)</script>',detail,re.S)
                decoded=[json.loads(block) for block in blocks]
                metadata=any(item.get('name')==title for item in decoded)
            except (ValueError,TypeError):metadata=False
            check('Catalogue JSON-LD retains valid decoded metadata '+table,metadata)
        updated=dict(values,title=title+' Updated',**{id_key:item['id']})
        if add:updated.pop(add)
        if edit:updated[edit]='1'
        response=admin.post('/admin/'+table+'.php',updated)
        changed=control('catalogue',table=table,title=updated['title'])
        check('Administrator edit '+table,response[0] in {200,302} and changed.get('id')==item['id'])
        response=admin.post('/admin/'+table+'.php',{delete_key:item['id']})
        check('Administrator delete only the created fictional '+table,response[0] in {200,302} and not control('catalogue',table=table,title=updated['title']))
        check('Catalogue deletion retains recoverable media '+table,guest.request('/'+item['path'])[0]==200)
    trailer_title=f['title']+' Lifecycle trailer'
    response=admin.upload('/admin/trailers.php',{'action':'add','movie_id':f['movie'],'title':trailer_title,'description':'Original fictional trailer'},'trailer_video','original.mp4',__import__('base64').b64decode(Path('dev/fixtures/red-frame.mp4.b64').read_text()),'video/mp4')
    trailer=control('catalogue',table='trailers',title=trailer_title)
    check('Administrator create trailer',response[0]==302 and bool(trailer.get('id')))
    if trailer:
        response=admin.post('/admin/trailers.php',{'action':'update','id':trailer['id'],'title':trailer_title+' Updated','description':'Fictional updated','status':'inactive'})
        check('Administrator edit trailer',response[0]==302 and bool(control('catalogue',table='trailers',title=trailer_title+' Updated')))
        check('Inactive trailer is hidden from public detail',guest.request('/api/trailers.php?id='+str(trailer['id']))[0]==404)
        check('Administrator can preview an inactive trailer',admin.request('/api/trailers.php?id='+str(trailer['id']))[0]==200)
        check('Members cannot interact with an inactive trailer',post_json(user,'/api/trailers.php?action=like',{'trailer_id':trailer['id']})[0]==404)
        response=admin.post('/admin/trailers.php',{'action':'delete','id':trailer['id']})
        check('Administrator delete only the created fictional trailer',response[0]==302 and not control('catalogue',table='trailers',title=trailer_title+' Updated'))
    for path in ['/api/subscribe.php','/api/payment-notify.php','/download.php','/find_movies.php','/api/server-check.php','/admin/run_migration.php','/admin/cron/cleanup-tokens.php','/admin/cron/reset-daily-stats.php']:
        check('Quarantined feature stays disabled '+path,guest.request(path)[0]==403)
    check('Normal WhatsApp delivery page stays disabled locally',guest.request('/whatsapp-watch.php?id='+str(f['movie']))[0]==503)
    # Read-only load sample, bounded concurrency. This is not production capacity evidence.
    start=time.monotonic()
    with concurrent.futures.ThreadPoolExecutor(max_workers=5) as pool:
        statuses=list(pool.map(lambda _:Client().request('/api/trailers.php?limit=5')[0],range(25)))
    check('Bounded five-client read-only smoke returns no HTTP errors',all(s==200 for s in statuses))
    print('Read-only smoke elapsed_ms='+str(round((time.monotonic()-start)*1000)),flush=True)
    check('Member sign-out succeeds',user.post('/logout.php',{})[0] in {302,303} and not user.session())
    check('Administrator sign-out removes access',admin.post('/admin/logout.php',{})[0] in {302,303} and admin.request('/admin/index.php')[0]==302)

if __name__=='__main__':
    try:run()
    except Exception as error:check('Product harness completed ('+type(error).__name__+')',False)
    result={'scope':'Owned isolated Docker; original media and fictional product records; live integrations disabled','tests':ROWS,'summary':{'total':len(ROWS),'passed':sum(r['passed'] for r in ROWS),'failed':sum(not r['passed'] for r in ROWS)}}
    Path('/results/product.json').write_text(json.dumps(result,indent=2)+'\n')
    print(json.dumps(result['summary']));sys.exit(1 if result['summary']['failed'] else 0)
