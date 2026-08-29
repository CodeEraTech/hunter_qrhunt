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
