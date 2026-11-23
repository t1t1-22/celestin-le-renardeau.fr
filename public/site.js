const byId = id => document.getElementById(id);
const safe = str => String(str ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
const fallback = {livre:'📚',histoire:'🌟',coloriage:'🎨'};
function card(item) {
  const container = document.createElement('article');
  container.className = 'item-card';
  const image = item.cover ? '<img loading="lazy" src="'+safe(item.cover)+'" alt="Illustration : '+safe(item.title)+'">' : '<div class="item-placeholder">'+fallback[item.type]+'</div>';
  const action = item.type === 'coloriage' && item.download
    ? '<a class="card-link" href="'+safe(item.download)+'" download>Télécharger ↓</a>'
    : item.type === 'livre' && item.externalUrl
    ? '<a class="card-link" target="_blank" rel="noopener noreferrer" href="'+safe(item.externalUrl)+'">Découvrir →</a>'
    : item.download ? '<a class="card-link" target="_blank" rel="noopener noreferrer" href="'+safe(item.download)+'">Ouvrir →</a>'
    : item.body ? '<button class="card-link read" type="button">Lire l’histoire →</button>'
    : item.type === 'livre' ? '<span class="chip">À découvrir bientôt</span>' : '';
  container.innerHTML = image+'<div class="card-details"><h3>'+safe(item.title)+'</h3><p>'+safe(item.description)+'</p>'+(item.category?'<span class="chip">'+safe(item.category)+'</span>':'')+action+'</div>';
  container.querySelector('button.read')?.addEventListener('click', () => {
    byId('reader-title').textContent = item.title;
    byId('reader-body').textContent = item.body;
    byId('reader').showModal();
  });
  return container;
}
byId('reader-close').addEventListener('click', () => byId('reader').close());
byId('reader').addEventListener('click', e => { if (e.target === byId('reader')) byId('reader').close(); });
async function init() {
  try {
    const response = await fetch('/api/content');
    if (!response.ok) throw Error('Réponse indisponible');
    const data = await response.json();
    // L'illustration de la bannière est modifiable dans l'administration.
    if (data.settings?.heroImage) byId('hero-image').style.backgroundImage = "linear-gradient(90deg,#f5fdff00 0%,#ecf8ff33 35%,#ffffff4a 52%,#fff0 75%),url('"+data.settings.heroImage+"')";
    const sections = [['livre','books-list'],['histoire','stories-list'],['coloriage','colorings-list']];
    sections.forEach(([type,id]) => {
      const items = data.items.filter(item => item.type === type);
      if (!items.length) return;
      const root = byId(id); root.replaceChildren(...items.map(card));
    });
  } catch(err) { console.warn('Le contenu dynamique est momentanément indisponible.',err); }
}
init();