const loader=document.createElement('div');loader.className='page-loader';loader.innerHTML='<div class="loader-spinner" role="status" aria-label="Loading"></div><span>Processing securely…</span>';document.body.append(loader);
const sidebar=document.querySelector('#sidebar');
document.querySelector('.menu-toggle')?.addEventListener('click',event=>{const open=sidebar?.classList.toggle('open')??false;event.currentTarget.setAttribute('aria-expanded',String(open))});

const toast=document.querySelector('#toast');
document.querySelectorAll('.alert').forEach((notice,index)=>setTimeout(()=>{if(toast){toast.textContent=notice.textContent?.trim()??'';toast.classList.add('show');setTimeout(()=>toast.classList.remove('show'),3500)}notice.remove()},index?6500:4500));

const dialog=document.createElement('dialog');
dialog.className='confirm-dialog';
dialog.innerHTML='<form class="dialog-form" method="dialog"><h2>Confirm action</h2><p data-message></p><div class="actions"><button class="btn secondary" value="cancel">Cancel</button><button class="btn danger" value="confirm">Confirm</button></div></form>';
document.body.append(dialog);
let pendingForm=null;
dialog.addEventListener('close',()=>{if(dialog.returnValue==='confirm'&&pendingForm){pendingForm.dataset.confirmed='true';pendingForm.requestSubmit()}pendingForm=null});

document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{
    if(form.dataset.confirmed==='true'){delete form.dataset.confirmed;const button=form.querySelector('button[type="submit"],button:not([type])');if(button){button.disabled=true;button.textContent='Processing…'}return}
    event.preventDefault();pendingForm=form;const message=dialog.querySelector('[data-message]');if(message)message.textContent=form.dataset.confirm??'Continue with this action?';dialog.showModal();
}));

document.querySelectorAll('form:not(.dialog-form):not([data-confirm])').forEach(form=>form.addEventListener('submit',()=>{const button=form.querySelector('button[type="submit"],button:not([type])');if(button){button.disabled=true;button.dataset.originalText=button.textContent;button.innerHTML='<span class="button-spinner"></span> Processing…'}loader.classList.add('show')}));

document.querySelectorAll('form.form-grid').forEach(form=>{if(!form.querySelector('[name="spot_name"]')) return;
  const hidden=document.createElement('input');hidden.type='hidden';hidden.name='spot_id';form.append(hidden);form.addEventListener('submit',()=>{const raw=(form.querySelector('[name="spot_name"]').value||'CAMPAIGN').toUpperCase().replace(/[^A-Z0-9]+/g,'_').replace(/^_+|_+$/g,'');hidden.value=('CAT_'+raw+'_'+Math.random().toString(16).slice(2,8)).slice(0,64);});
  form.classList.add('campaign-sections');
  const fields=[...form.querySelectorAll(':scope > .field')];
  const categoryField=form.querySelector('[name="category_id"]')?.closest('.field');
  if(categoryField&&!form.querySelector('[data-question-config]')){const box=document.createElement('div');box.className='field full';box.dataset.questionConfig='1';box.innerHTML='<label>Question mode</label><select name="question_mode"><option value="SINGLE">Single Question</option><option value="COLLECTION">Question Collection</option></select><label>Select Question</label><select name="question_id"><option value="">Select active question</option></select><label>Select Question Collection</label><select name="collection_id"><option value="">Select active collection</option></select><div class="question-preview help"></div><div class="actions"><a class="btn secondary" href="../hunts/questions.php">Add / manage single question</a><a class="btn secondary" href="../hunts/questions_bulk.php">Bulk upload questions</a><a class="btn secondary" href="../hunts/collections.php">Manage question collections</a></div>';fields[11]?.before(box);const mode=box.querySelector('[name="question_mode"]'),q=box.querySelector('[name="question_id"]'),col=box.querySelector('[name="collection_id"]'),preview=box.querySelector('.question-preview');const qs=window.hunterQuestions||[],cs=window.hunterCollections||[];qs.forEach(x=>{const o=new Option(x.riddle_text.slice(0,80),x.riddle_id);o.dataset.preview=JSON.stringify(x);q.add(o)});cs.forEach(x=>{const o=new Option(x.collection_name+' · '+x.total_questions+' questions',x.collection_id);o.dataset.preview=JSON.stringify(x);col.add(o)});['riddle_text','primary_answer','synonyms','yt_video_id','channel_url','answer_time_seconds'].forEach(n=>form.querySelector('[name="'+n+'"]')?.closest('.field')?.setAttribute('hidden','hidden'));const sync=()=>{const active=mode.value==='SINGLE'?q:col;q.hidden=mode.value!=='SINGLE';col.hidden=mode.value!=='COLLECTION';const d=active.selectedOptions[0]?.dataset.preview;if(d){const x=JSON.parse(d);preview.textContent=mode.value==='SINGLE'?('Riddle: '+x.riddle_text+' | Answer: '+x.primary_answer+' | Synonyms: '+x.synonyms+' | Video: '+(x.yt_video_id||'—')+' | Time: '+x.answer_time_seconds+'s'):('Collection: '+x.collection_name+' | Questions: '+x.total_questions+' | Rotation: '+x.rotation_method+' | Per session: '+x.questions_per_session+' | Status: '+x.status)}else preview.textContent=''};mode.onchange=sync;q.onchange=sync;col.onchange=sync;sync();}
  const save=form.querySelector('button.btn:not(.secondary)');
  // Section 3 owns the question-management toolbar; do not add a second legacy toolbar.
  form.querySelectorAll('.actions').forEach(actions=>{if(actions.querySelector('a[href*="download_template"]')) actions.remove();});
  [[18,'4. Reward settlement, min-cart & anti-fraud security rules'],[11,'3. Multi-chain dynamic riddle pool (rotation engine)'],[4,'2. Geo-navigation engine, proximity radar & indoor micro-spotting'],[0,'1. Campaign identification, target scope & master category']].forEach(([i,title])=>{if(fields[i]){const h=document.createElement('h3');h.className='form-section-title';h.textContent=title;const anchor=i===11?(form.querySelector('[data-question-config]')||fields[i]):fields[i];anchor.before(h)}});
});

document.querySelector('#load-question-sample')?.addEventListener('click',()=>{const v={riddle_text:'Mujhe pehno toh waqt rukta nahi, batao main kaun?',instruction_text:'Submit one answer within the time limit.',primary_answer:'Watch',synonyms:'ghadi, clock, samay',yt_video_id:'dQw4w9WgXcQ',channel_url:'https://youtube.com/@example',answer_time_seconds:'60'};Object.entries(v).forEach(([k,x])=>{const el=document.querySelector('[name=\"'+k+'\"]');if(el)el.value=x;});});

// Preserve the sidebar position across normal menu navigations.
if(sidebar){const key='hunter_admin_sidebar_scroll';const restore=()=>{const saved=sessionStorage.getItem(key);if(saved!==null)sidebar.scrollTop=parseInt(saved,10)||0;};restore();sidebar.addEventListener('scroll',()=>sessionStorage.setItem(key,String(sidebar.scrollTop)),{passive:true});window.addEventListener('pagehide',()=>sessionStorage.setItem(key,String(sidebar.scrollTop)));}
