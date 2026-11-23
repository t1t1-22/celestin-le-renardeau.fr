const $ = id => document.getElementById(id);
let items = [], settings = {}, filter = 'all', editing = null, cover = '', download = '', hero = '';
const labels = { all: 'Tableau de bord', livre:'Les livres', histoire:'Mini-histoires', coloriage:'Coloriages', settings:"Image d'accueil" };
const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function api(url, method = 'GET', data) {
  const result = await fetch(url, {
    method, credentials:'same-origin',
    headers: { 'Content-Type':'application/json', 'X-Celestin-Admin':'1' },
    body: data === undefined ? undefined : JSON.stringify(data)
  });
  const response = await result.json().catch(() => ({}));
  if (!result.ok) throw new Error(response.error || 'Une erreur est survenue');
  return response;
}
function showNotice(message, error = false) {
  const notice = $('notice');
  notice.textContent = message;
  notice.hidden = false;
  notice.classList.toggle('error', error);
  notice.scrollIntoView({behavior:'smooth',block:'nearest'});
}
function showApp() {
  $('login-screen').hidden = true;
  $('dashboard').hidden = false;
  refresh();
}
async function refresh() {
  try {
    const data = await api('/api/admin/items');
    items = data.items || []; settings = data.settings || {};
    $('count-livre').textContent = items.filter(x => x.type === 'livre').length;
    $('count-histoire').textContent = items.filter(x => x.type === 'histoire').length;
    $('count-coloriage').textContent = items.filter(x => x.type === 'coloriage').length;
    $('count-draft').textContent = items.filter(x => !x.published).length;
    hero = settings.heroImage || '/images/jardin.webp';
    $('hero-preview').src = hero;
    render();
  } catch(e) { showNotice(e.message, true); }
}
function render() {
  $('page-title').textContent = labels[filter];
  $('stats').hidden = filter !== 'all';
  $('settings-panel').hidden = filter !== 'settings';
  $('list-panel').hidden = filter === 'settings';
  $('new-item').hidden = filter === 'settings';
  document.querySelectorAll('.tab').forEach(x => x.classList.toggle('active', x.dataset.filter === filter));
  const subset = filter === 'all' ? items : items.filter(x => x.type === filter);
  const root = $('content-list');
  if (!subset.length) {
    root.innerHTML = '<p>Il n’y a pas encore de contenu dans cette rubrique. Clique sur « Ajouter un contenu » !</p>';
    return;
  }
  root.innerHTML = subset.map(x => {
    const coverHtml = x.cover ? '<img class="thumb" src="'+escapeHtml(x.cover)+'" alt="">' : '<span class="thumb">'+({livre:'📚',histoire:'🎬',coloriage:'🎨'}[x.type])+'</span>';
    return '<div class="item-row">'+coverHtml+'<div><div class="item-name">'+escapeHtml(x.title)+' <span class="status '+(x.published?'':'draft')+'">'+(x.published?'Publié':'Brouillon')+'</span></div><div class="item-meta">'+escapeHtml(labels[x.type])+' · '+escapeHtml(x.category || 'Sans catégorie')+'</div></div><div class="item-buttons"><button data-action="edit" data-id="'+escapeHtml(x.id)+'">Modifier</button><button class="delete" data-action="delete" data-id="'+escapeHtml(x.id)+'">Supprimer</button></div></div>';
  }).join('');
}
function selectTab(value) { filter = value; render(); }
document.querySelectorAll('.tab').forEach(x => x.addEventListener('click', () => selectTab(x.dataset.filter)));
$('login-form').addEventListener('submit', async event => {
  event.preventDefault();
  $('login-error').textContent = '';
  const btn = event.target.querySelector('button'); btn.disabled = true;
  try {
    await api('/api/admin/login','POST',{ password: $('password').value });
    $('password').value = '';
    showApp();
  } catch(e) { $('login-error').textContent = e.message; }
  finally { btn.disabled = false; }
});
$('logout').addEventListener('click', async () => {
  try { await api('/api/admin/logout', 'POST', {}); } catch {}
  location.reload();
});
function syncFields() {
  const type = $('item-type').value;
  $('body-field').hidden = type === 'coloriage';
  $('minutes-field').hidden = type !== 'histoire';
  $('external-field').hidden = type === 'coloriage';
  $('download-field').querySelector('.hint').textContent = type === 'coloriage'
    ? 'Fichier de coloriage PNG, JPEG, WebP ou PDF (obligatoire pour publier).'
    : 'PDF du livre ou document complémentaire (facultatif).';
}
$('item-type').addEventListener('change',syncFields);
function editItem(item) {
  editing = item?.id || null;
  cover = item?.cover || '';
  download = item?.download || '';
  $('item-form').reset();
  $('item-type').value = item?.type || (filter !== 'all' && filter !== 'settings' ? filter : 'livre');
  $('item-type').disabled = !!item;
  $('item-title').value = item?.title || '';
  $('item-category').value = item?.category || '';
  $('item-description').value = item?.description || '';
  $('item-body').value = item?.body || '';
  $('item-minutes').value = item?.readMinutes || 3;
  $('item-external').value = item?.externalUrl || '';
  $('item-published').checked = !!item?.published;
  $('cover-preview').hidden = !cover;
  if (cover) $('cover-preview').src = cover;
  $('download-preview').hidden = !download;
  if (download) $('download-preview').href = download;
  $('editor-title').textContent = item ? 'Modifier : ' + item.title : 'Ajouter un contenu';
  $('cover-file').value = ''; $('download-file').value = '';
  syncFields();
  $('editor').showModal();
}
$('new-item').addEventListener('click', () => editItem(null));
$('close-editor').addEventListener('click', () => $('editor').close());
$('cancel-editor').addEventListener('click', () => $('editor').close());
$('content-list').addEventListener('click', async event => {
  const button = event.target.closest('button[data-action]');
  if (!button) return;
  const item = items.find(x => x.id === button.dataset.id);
  if (!item) return;
  if (button.dataset.action === 'edit') return editItem(item);
  if (button.dataset.action === 'delete' && confirm('Supprimer définitivement « ' + item.title + ' » ?')) {
    try { await api('/api/admin/items/' + encodeURIComponent(item.id), 'DELETE', {}); showNotice('Contenu supprimé.'); await refresh(); }
    catch(e) { showNotice(e.message,true); }
  }
});
async function uploadFile(file) {
  if (!file) return '';
  if (file.size > 10 * 1024 * 1024) throw Error('Fichier trop lourd : 10 Mo maximum.');
  if (!['image/png','image/jpeg','image/webp','application/pdf'].includes(file.type)) throw Error('Format non autorisé : PNG, JPEG, WebP ou PDF uniquement.');
  const encoded = await new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onerror = reject;
    reader.onload = () => resolve(String(reader.result).split(',')[1]);
    reader.readAsDataURL(file);
  });
  const response = await api('/api/admin/upload','POST',{ mime:file.type, data:encoded });
  return response.url;
}
$('item-form').addEventListener('submit', async event => {
  event.preventDefault();
  const button = $('save-item'); button.disabled = true; button.textContent = 'Enregistrement…';
  try {
    if ($('cover-file').files[0]) cover = await uploadFile($('cover-file').files[0]);
    if ($('download-file').files[0]) download = await uploadFile($('download-file').files[0]);
    const data = {
      type:$('item-type').value, title:$('item-title').value,
      category:$('item-category').value, description:$('item-description').value,
      body:$('item-body').value, readMinutes:Number($('item-minutes').value),
      externalUrl:$('item-external').value, cover, download,
      published:$('item-published').checked
    };
    await api(editing ? '/api/admin/items/'+editing : '/api/admin/items', editing ? 'PUT' : 'POST', data);
    $('editor').close();
    showNotice(editing ? 'Contenu modifié avec succès !' : 'Contenu créé avec succès !');
    await refresh();
  } catch(e) { alert(e.message); }
  finally { button.disabled = false; button.textContent = 'Enregistrer le contenu'; }
});
$('hero-upload').addEventListener('change',async event=>{
  const file = event.target.files[0]; if (!file) return;
  try { hero = await uploadFile(file); $('hero-preview').src = hero; showNotice('Illustration transférée. Clique sur Enregistrer la bannière pour la publier.'); }
  catch(e){showNotice(e.message,true);}
});
$('save-hero').addEventListener('click',async()=>{
  try { await api('/api/admin/settings','PUT',{heroImage:hero}); showNotice('La nouvelle bannière est en ligne !'); }
  catch(e){showNotice(e.message,true);}
});
api('/api/admin/me').then(showApp).catch(() => { $('login-screen').hidden = false; });
