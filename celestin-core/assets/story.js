document.addEventListener('DOMContentLoaded',()=>{
 const page=document.querySelector('[data-story-page]');if(!page)return;
 const audio=page.querySelector('[data-cel-story-audio]'),preview=page.querySelector('[data-cel-story-preview]');
 const play=page.querySelector('[data-story-play]'),start=page.querySelector('[data-story-start]'),teaser=page.querySelector('[data-story-teaser]'),seek=page.querySelector('[data-story-seek]'),mute=page.querySelector('[data-story-mute]');
 const players=[audio,preview].filter(Boolean),status=page.querySelector('[data-story-status]');
 let active=null;
 const format=t=>Number.isFinite(t)&&t>=0?Math.floor(t/60)+':'+String(Math.floor(t%60)).padStart(2,'0'):'–:––';
 const announce=t=>{if(status){status.textContent=t;status.hidden=false;}};
 const symbols=(button,playing)=>{if(!button)return;button.querySelector('[data-play-symbol]').hidden=playing;button.querySelector('[data-pause-symbol]').hidden=!playing;};
 const toggle=el=>{if(!el)return;if(el.paused){el.play().catch(()=>announce('Impossible de lancer l’audio.'));}else el.pause();};
 const session='mediaSession' in navigator?navigator.mediaSession:null;
 const setAction=(name,fn)=>{if(session)try{session.setActionHandler(name,fn);}catch(e){}};
 function media(el){
  if(!session||!el)return;
  try{if('MediaMetadata' in window)session.metadata=new MediaMetadata({title:page.dataset.storyTitle+(el===preview?' — Teaser':''),artist:'Célestin le renardeau',artwork:page.dataset.storyArt?[{src:page.dataset.storyArt}]:[]});}catch(e){}
  setAction('play',()=>el.play().catch(()=>{}));setAction('pause',()=>el.pause());
  setAction('seekbackward',d=>{el.currentTime=Math.max(0,el.currentTime-(d.seekOffset||10));});
  setAction('seekforward',d=>{if(Number.isFinite(el.duration))el.currentTime=Math.min(el.duration,el.currentTime+(d.seekOffset||10));});
  setAction('seekto',d=>{if(Number.isFinite(d.seekTime)&&Number.isFinite(el.duration))el.currentTime=Math.max(0,Math.min(el.duration,d.seekTime));});
 }
 function position(el){
  if(!session||active!==el)return;
  try{session.playbackState=el.ended?'none':el.paused?'paused':'playing';if(Number.isFinite(el.duration)&&el.duration>0&&session.setPositionState)session.setPositionState({duration:el.duration,playbackRate:el.playbackRate,position:Math.max(0,Math.min(el.duration,el.currentTime))});}catch(e){}
 }
 function render(){
  if(audio){
   const playing=!audio.paused&&!audio.ended;
   symbols(play,playing);play.setAttribute('aria-label',playing?'Mettre en pause':'Écouter l’histoire');
   start.querySelector('span').textContent=playing?'Mettre en pause':'Écouter l’histoire';
   start.querySelector('svg').innerHTML=playing?'<path d="M7 5h3v14H7zm7 0h3v14h-3z" fill="currentColor" stroke="none"/>':'<path d="M8 5v14l12-7z" fill="currentColor" stroke="none"/>';
   page.querySelector('[data-story-current]').textContent=format(audio.currentTime);
   page.querySelector('[data-story-duration]').textContent=format(audio.duration);
   const valid=Number.isFinite(audio.duration)&&audio.duration>0;seek.disabled=!valid;
   seek.value=valid?Math.round(1000*audio.currentTime/audio.duration):0;
   if(valid){page.querySelector('[data-story-duration-meta]').hidden=false;page.querySelector('[data-story-duration-label]').textContent=Math.floor(audio.duration/60)+' min '+Math.floor(audio.duration%60)+' s';}
   mute.setAttribute('aria-pressed',String(audio.muted));mute.setAttribute('aria-label',audio.muted?'Rétablir le son':'Couper le son');
  }
  if(preview){const playing=!preview.paused&&!preview.ended;symbols(teaser,playing);teaser.setAttribute('aria-pressed',String(playing));teaser.querySelector('[data-teaser-label]').textContent=playing?'Mettre le teaser en pause':'Écouter le teaser';}
 }
 if(audio){
  play.onclick=()=>toggle(audio);start.onclick=()=>toggle(audio);mute.onclick=()=>{audio.muted=!audio.muted;};
  seek.addEventListener('input',()=>{if(Number.isFinite(audio.duration))audio.currentTime=audio.duration*Number(seek.value)/1000;});
  audio.controls=false;audio.hidden=true;page.querySelector('.cel-story-player-ui').hidden=false;start.hidden=false;
 }
 if(preview){teaser.onclick=()=>toggle(preview);preview.controls=false;preview.hidden=true;teaser.hidden=false;}
 players.forEach(el=>{
  el.addEventListener('play',()=>{active=el;players.forEach(other=>{if(other!==el)other.pause();});media(el);position(el);render();});
  ['pause','ended','timeupdate','durationchange','loadedmetadata','seeked','ratechange','volumechange'].forEach(event=>el.addEventListener(event,()=>{render();position(el);}));
  el.addEventListener('error',()=>announce('Cet audio est momentanément indisponible.'));
 });
 render();
 const share=page.querySelector('[data-story-share]');share.hidden=false;share.onclick=async()=>{
  const url=page.dataset.storyUrl;
  if(navigator.share){try{await navigator.share({title:page.dataset.storyTitle,url});return;}catch(e){if(e.name==='AbortError')return;}}
  try{await navigator.clipboard.writeText(url);announce('Lien de l’histoire copié !');}catch(e){window.prompt('Copier le lien de l’histoire :',url);}
 };
 const fav=page.querySelector('[data-story-favorite]'),key='celestin-favorite-'+page.dataset.storyId;
 try{
  const update=()=>{const on=localStorage.getItem(key)==='1';fav.setAttribute('aria-pressed',String(on));fav.querySelector('span').textContent=on?'Retirer des favoris':'Ajouter aux favoris';};update();fav.hidden=false;
  fav.onclick=()=>{try{if(localStorage.getItem(key)==='1')localStorage.removeItem(key);else localStorage.setItem(key,'1');update();}catch(e){announce('Les favoris ne sont pas disponibles sur ce navigateur.');}};
 }catch(e){}
 const reading=document.getElementById('cel-story-reading'),read=page.querySelector('[data-story-read]');
 if(read&&reading)read.addEventListener('click',()=>{reading.open=true;});
 if(reading&&location.hash==='#cel-story-reading')reading.open=true;
 const transcript=document.querySelector('[data-cel-story-transcript]');
 if(audio&&transcript){
  const sentences=Array.from(transcript.querySelectorAll('.cel-story-sentence')),timings=sentences.map(s=>parseFloat(s.dataset.start));
  if(sentences.length&&timings.every(Number.isFinite)){
   let index=-1;
   const highlight=()=>{let i=-1;for(let j=0;j<timings.length;j++)if(audio.currentTime>=timings[j])i=j;if(i===index)return;sentences.forEach((s,k)=>s.classList.toggle('is-current',k===i));index=i;};
   ['timeupdate','seeked','play'].forEach(e=>audio.addEventListener(e,highlight));
   audio.addEventListener('ended',()=>{sentences.forEach(s=>s.classList.remove('is-current'));index=-1;});
  }
 }
 document.querySelectorAll('[data-story-recs]').forEach(root=>{
  const track=root.querySelector('.cel-story-recs-track'),nav=root.querySelector('.cel-story-recs-nav'),prev=root.querySelector('[data-rec-prev]'),next=root.querySelector('[data-rec-next]');
  const sync=()=>{const max=track.scrollWidth-track.clientWidth;nav.hidden=max<=2;prev.disabled=track.scrollLeft<=2;next.disabled=track.scrollLeft>=max-2;};
  const step=d=>{const card=track.querySelector('.cel-story-rec-card');if(card)track.scrollBy({left:d*(card.getBoundingClientRect().width+12),behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'});};
  prev.onclick=()=>step(-1);next.onclick=()=>step(1);track.addEventListener('scroll',sync,{passive:true});window.addEventListener('resize',sync);sync();
 });
});
