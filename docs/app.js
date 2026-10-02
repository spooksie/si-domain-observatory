'use strict';
const $ = s => document.querySelector(s);
let rows=[], filter='available',page=1,checking=false,stop=false;
const perPage=60;
const selectedThemes=new Set();
const shortTheme='2–3 letter names';
const isShortName=r=>/^[a-z]{2,3}$/.test(r.word);
const inTheme=(r,c)=>c===shortTheme?isShortName(r):r.category===c;
let themesInitialized=false;
let initialCategory=new URLSearchParams(window.location.search).get('category')||'';
if(initialCategory==='AI & intelligence')initialCategory='SI & super intelligence';
const escape=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const hosted=document.documentElement.dataset.storage==='browser';
const csrf=$('meta[name="csrf-token"]')?.content;
const storageKey='qquantum-domain-observatory-v1';
function personal(){try{return JSON.parse(localStorage.getItem(storageKey)||'{"flags":{},"added":[]}');}catch{throw new Error('Browser storage could not be read.');}}
function persist(data){try{localStorage.setItem(storageKey,JSON.stringify(data));}catch{throw new Error('Browser storage is unavailable; your change was not saved.');}}

function toast(message){$('#toast').textContent=message;$('#toast').hidden=false;clearTimeout(toast.timer);toast.timer=setTimeout(()=>$('#toast').hidden=true,6000);}
async function post(body){if(hosted){const state=personal();if(body.action==='save'){state.flags[body.domain]={...(state.flags[body.domain]||{})};for(const key of ['bought','favorite'])if(typeof body[key]==='boolean')state.flags[body.domain][key]=body[key];persist(state);return {saved:true};}if(body.action==='add'){const word=body.word.trim().toLowerCase().replace(/\.si$/,'');if(!/^[a-z][a-z0-9-]{0,61}[a-z0-9]$/.test(word))throw new Error('Use a domain name of 2–63 letters, numbers or hyphens.');const domain=word+'.si';if(!rows.some(r=>r.domain===domain)){state.added.push({word,domain,category:'Your names',dictionary:'Personal suggestion',status:'unchecked',ai:false,bought:false,favorite:false,idea:'Open a registrar to check this name.'});persist(state);}return {domain};}throw new Error('Open a registrar link for a fresh check.');}const response=await fetch('api.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},body:JSON.stringify(body)});const json=await response.json();if(!response.ok)throw new Error(json.error||'Request failed');return json;}
async function load(){try{const r=await fetch(hosted?'domains.json?updated='+Date.now():'api.php',{cache:'no-store'});if(!r.ok)throw new Error('Could not load domains');const data=await r.json();rows=hosted?data:data.domains;if(hosted){const state=personal();rows=rows.concat(state.added.filter(a=>!rows.some(r=>r.domain===a.domain))).map(r=>({...r,...state.flags[r.domain]}));}render();}catch(e){toast(e.message);}}
function filtered(){let data=rows.filter(r=>{
 const q=$('#search').value.trim().toLowerCase();if(q&&!`${r.domain} ${r.idea||''} ${r.category}`.toLowerCase().includes(q))return false;
 if(selectedThemes.size&&![...selectedThemes].some(c=>inTheme(r,c)))return false;
 if($('#hide-bought').checked&&r.bought)return false;
 return filter==='all'||(filter==='ai'?r.ai&&r.status==='available':filter==='favorites'?r.favorite:filter==='bought'?r.bought:r.status===filter);
});
 const sort=$('#sort').value;data.sort((a,b)=>sort==='shortest'?a.word.length-b.word.length||a.domain.localeCompare(b.domain):sort==='alphabetical'?a.domain.localeCompare(b.domain):sort==='recent'?(b.checked_at||'').localeCompare(a.checked_at||''):(b.rank||0)-(a.rank||0)||Number(!!b.recommended)-Number(!!a.recommended)||Number(b.ai)-Number(a.ai)||a.domain.localeCompare(b.domain));return data;}
function date(value){return value?new Date(value).toLocaleString(undefined,{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit',second:'2-digit'}):'Not checked yet';}
function rowHTML(r){const d=escape(r.domain),status=escape(r.status),label=({available:'Available',taken:'Unavailable',reserved:'Reserved',unknown:'Could not confirm',candidate:'Needs confirmation',unchecked:'Unchecked'})[r.status]||'Unknown';return `<tr data-domain="${d}"><td><div class="name-row"><button class="favorite ${r.favorite?'on':''}" data-action="favorite" aria-label="${r.favorite?'Unsave':'Save'} ${d}" aria-pressed="${!!r.favorite}">${r.favorite?'★':'☆'}</button><span class="domain">${escape(r.word)}<span class="suffix">.si</span></span>${r.recommended?'<span class="recommended">PICK</span>':''}</div><span class="idea">${escape(r.idea|| (r.ai?'A one-word name with potential for an SI company.':'A dictionary word for a distinctive brand.'))}</span></td><td><span class="theme">${escape(r.category)}</span><span class="dictionary">${escape(r.dictionary)}</span></td><td><span class="status ${status}">${label}</span><time class="checked" title="${escape(r.checked_at||'')}" datetime="${escape(r.checked_at||'')}">${escape(date(r.checked_at))}</time><span class="dictionary">${escape(r.source||'')}</span>${hosted?`<a class="check" href="https://www.domenca.com/portal/en_US/shoppingcart-domainsearchengine/goal/domain/?initialDomain=${encodeURIComponent(r.domain)}" target="_blank" rel="noopener">Check at registrar ↗</a>`:'<button class="check" data-action="check">Recheck live ↗</button>'}</td><td><span class="price">${r.price_eur!=null?'€'+Number(r.price_eur).toFixed(2):'—'}</span>${r.price_eur!=null?`<span class="duration">${escape(r.duration||1)} year(s) · incl. VAT</span>`:''}</td><td><div class="purchase"><a href="https://www.neoserv.si/domene?domena=${encodeURIComponent(r.domain)}" target="_blank" rel="noopener">Neoserv ↗</a><a href="https://www.domenca.com/portal/en_US/shoppingcart-domainsearchengine/goal/domain/?initialDomain=${encodeURIComponent(r.domain)}" target="_blank" rel="noopener" >Domenca ↗</a></div></td><td><input class="bought" type="checkbox" aria-label="Mark ${d} bought" data-action="bought" ${r.bought?'checked':''}></td></tr>`;}
function render(){
 const available=rows.filter(r=>r.status==='available');$('#count-all').textContent=rows.length.toLocaleString();$('#count-available').textContent=available.length.toLocaleString();$('#tab-available').textContent=available.length;$('#count-ai').textContent=available.filter(r=>r.ai).length;$('#count-bought').textContent=rows.filter(r=>r.bought).length;
 const checked=rows.filter(r=>r.status!=='unchecked').length;$('#progress').value=100*checked/Math.max(rows.length,1);if(!checking)$('#progress-text').textContent=`${checked.toLocaleString()} / ${rows.length.toLocaleString()} explored · ${hosted?'dated snapshot · updates refresh every minute':'saved results refresh every 3 seconds'}`;
 renderThemes(available);

 const data=filtered();const pages=Math.max(1,Math.ceil(data.length/perPage));page=Math.min(page,pages);const start=(page-1)*perPage;$('#domains').innerHTML=data.slice(start,start+perPage).map(rowHTML).join('');$('#empty').hidden=!!data.length;$('#result-count').textContent=`${data.length.toLocaleString()} matching names${data.length?` · showing ${start+1}–${Math.min(start+perPage,data.length)}`:''}`;$('#page-label').textContent=`Page ${page} of ${pages}`;$('#prev').disabled=page<=1;$('#next').disabled=page>=pages;
}
function renderThemes(available){
 const categories=[...new Set(rows.flatMap(r=>isShortName(r)?[r.category,shortTheme]:[r.category]))];
 if(!themesInitialized){if(categories.includes(initialCategory))selectedThemes.add(initialCategory);themesInitialized=true;}
 const counts=new Map(categories.map(c=>[c,available.filter(r=>inTheme(r,c)).length]));
 const showCounts=$('#show-theme-counts').checked;
 const icons={'2–3 letter names':'↔','SI & super intelligence':'✦','Science & space':'✧','Brandable dictionary words':'Aa','Build & infrastructure':'▦','Rare dictionary words':'◇','Modern service names':'⚡','Names & nicknames':'☺','Domain hacks':'↗','Your names':'★','Quiet intelligence':'☾','Agent crews':'⋈','Memory & continuity':'∞','Seeing & sensing':'◉','Motion & momentum':'➜','Playful sound':'♪','Tiny utilities':'⌁','Crafted & tactile':'▧'};
 $('#theme-buttons').innerHTML=`<button type="button" class="theme-chip all-themes ${selectedThemes.size?'':'selected'}" data-theme="" aria-pressed="${!selectedThemes.size}">All themes${showCounts?`<span class="theme-count">${available.length}</span>`:''}</button>`+categories.map(c=>`<button type="button" class="theme-chip ${selectedThemes.has(c)?'selected':''}" data-theme="${escape(c)}" aria-pressed="${selectedThemes.has(c)}"><span class="theme-icon" aria-hidden="true">${icons[c]||'◇'}</span>${escape(c)}${showCounts?`<span class="theme-count">${counts.get(c)}</span>`:''}</button>`).join('');
 $('#category').innerHTML='<option value="">All themes'+(showCounts?` · ${available.length} available`:'')+'</option>'+categories.map(c=>`<option value="${escape(c)}">${escape(c)}${showCounts?` · ${counts.get(c)} available`:''}</option>`).join('')+(selectedThemes.size>1?`<option value="__multiple">${selectedThemes.size} themes selected</option>`:'');
 $('#category').value=selectedThemes.size>1?'__multiple':([...selectedThemes][0]||'');
 $('#theme-selection').textContent=selectedThemes.size?`${selectedThemes.size} theme${selectedThemes.size===1?'':'s'} selected · tap again to remove`:'All themes included · tap buttons to mix your favourites';
}
$('#theme-buttons').addEventListener('click',e=>{const button=e.target.closest('[data-theme]');if(!button)return;const theme=button.dataset.theme;if(!theme)selectedThemes.clear();else if(selectedThemes.has(theme))selectedThemes.delete(theme);else selectedThemes.add(theme);page=1;render();});
$('#show-theme-counts').addEventListener('change',render);
$('#category').addEventListener('change',()=>{const value=$('#category').value;if(value==='__multiple')return;selectedThemes.clear();if(value)selectedThemes.add(value);page=1;render();});
async function check(domains){if(hosted){toast('Open Neoserv or Domenca for a fresh availability check.');return;}if(checking)return;checking=true;stop=false;$('#stop-check').hidden=false;$('#check-visible').disabled=true;let done=0;try{for(let i=0;i<domains.length&&!stop;i+=3){$('#progress-text').textContent=`Rechecking ${done} / ${domains.length}…`;let result;let retries=0;while(true){try{result=await post({action:'check',domains:domains.slice(i,i+3)});break;}catch(e){if(e.message.includes('already running')&&retries++<10){await new Promise(r=>setTimeout(r,2000));if(stop)break;continue;}throw e;}}if(!result)break;for(const r of result.results){const row=rows.find(x=>x.domain===r.domain);if(row)Object.assign(row,r);}done+=result.results.length;render();if(result.results.some(r=>r.http_status===429))throw new Error('Registrar rate limit reached. Wait before trying again.');await new Promise(r=>setTimeout(r,700));}toast(`Updated ${done} domain${done===1?'':'s'}.`);}catch(e){toast(e.message);}finally{checking=false;$('#stop-check').hidden=true;$('#check-visible').disabled=false;await load();}}
$('#domains').addEventListener('click',async e=>{const el=e.target.closest('[data-action]');if(!el)return;const domain=el.closest('tr').dataset.domain;const row=rows.find(r=>r.domain===domain);const action=el.dataset.action;
 if(action==='check'){await check([domain]);return;}
 if(action==='favorite'||action==='bought'){const key=action==='favorite'?'favorite':'bought',value=action==='favorite'?!row.favorite:el.checked;el.disabled=true;try{await post({action:'save',domain,[key]:value});row[key]=value;$('#save-message').textContent=`Saved ${domain} ${hosted?'in this browser':'to this folder'}`;render();}catch(e){toast(e.message);render();}}
});
for(const id of ['search','sort','hide-bought'])$('#'+id).addEventListener(id==='search'?'input':'change',()=>{page=1;render();});
$('.filters').addEventListener('click',e=>{const button=e.target.closest('[data-filter]');if(!button)return;filter=button.dataset.filter;page=1;document.querySelectorAll('[data-filter]').forEach(b=>b.classList.toggle('active',b===button));render();});
$('#prev').onclick=()=>{page--;render();};$('#next').onclick=()=>{page++;render();};
$('#check-visible').onclick=()=>check(filtered().slice((page-1)*perPage,page*perPage).map(r=>r.domain));$('#stop-check').onclick=()=>{stop=true;};
$('#add-toggle').onclick=()=>{$('#add-form').hidden=!$('#add-form').hidden;if(!$('#add-form').hidden)$('#new-name').focus();};
$('#add-form').onsubmit=async e=>{e.preventDefault();try{const result=await post({action:'add',word:$('#new-name').value});$('#new-name').value='';await load();await check([result.domain]);}catch(e){toast(e.message);}};
if(hosted)$('#check-visible').hidden=true;
$('.download').addEventListener('click',e=>{
 e.preventDefault();
 const available=rows.filter(r=>r.status==='available');
 if(!available.length){toast('No available domains to download. Wait for the catalogue to load or check names first.');return;}
 const lines=['# QQuantum.ai — available .si domains','',`${available.length} domains available at their latest registrar check, across all themes.`,`Availability can change; check dates are recorded below.`, '', '| Domain | Category | Availability | Checked UTC | Bought | Saved |','|---|---|---|---|---|---|',...available.map(r=>`| ${r.domain} | ${r.category} | ${r.status} | ${r.checked_at||'Not recorded'} | ${r.bought?'Yes':'No'} | ${r.favorite?'Yes':'No'} |`)];
 const url=URL.createObjectURL(new Blob([lines.join('\n')],{type:'text/markdown'}));
 const link=document.createElement('a');link.href=url;link.download='AVAILABLE-DOMAINS.md';link.click();setTimeout(()=>URL.revokeObjectURL(url),1000);
 toast(`Downloaded ${available.length.toLocaleString()} available domains.`);
});
load();setInterval(()=>{if(!checking&&!document.hidden)load();},hosted?60000:3000);
