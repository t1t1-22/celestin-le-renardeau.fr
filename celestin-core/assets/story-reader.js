(function(){
 'use strict';
 function init(){
  const dialog=document.querySelector('[data-ereader]'),trigger=document.querySelector('[data-story-read]'),story=document.querySelector('[data-story-page]');
  if(!dialog||!trigger||!story||typeof dialog.showModal!=='function')return;
  const get=s=>dialog.querySelector(s),paper=get('[data-reader-paper]'),source=get('[data-reader-source]'),viewport=get('[data-reader-viewport]');
  const paragraphs=Array.from(source.content.querySelectorAll('p')).map(p=>p.textContent.trim()).filter(Boolean);
  if(!paragraphs.length)return;
  // Word positions provide a stable reading bookmark across font and viewport changes.
  const chunks=paragraphs.map(p=>p.split(/\s+/u));const starts=[];let total=0;
  chunks.forEach(words=>{starts.push(total);total+=words.length;});
  const prefKey='cel_ereader_preferences_v1',bookmarkKey='cel_ereader_position_'+story.dataset.storyId;
  const signature=String(total)+':'+paragraphs.join('').length+':'+paragraphs[0].slice(0,32);
  function load(key){try{return JSON.parse(localStorage.getItem(key)||'null');}catch(e){return null;}}
  function store(key,value){try{localStorage.setItem(key,JSON.stringify(value));}catch(e){}}
  const saved=load(prefKey)||{};const preferences={font:['serif','sans','rounded'].includes(saved.font)?saved.font:'serif',theme:['cream','white','dark'].includes(saved.theme)?saved.theme:'cream',size:Math.min(30,Math.max(14,Number(saved.size)||19))};
  const bookmark=load(bookmarkKey);let anchor=bookmark&&bookmark.signature===signature?Math.max(0,Math.min(total-1,Number(bookmark.offset)||0)):0;
  let pages=[],index=0,frame=0,lastWidth=0,lastHeight=0,opened=false;
  const prev=get('[data-reader-prev]'),next=get('[data-reader-next]'),count=get('[data-reader-count]'),progress=get('[data-reader-progress]');
  const smaller=get('[data-reader-smaller]'),larger=get('[data-reader-larger]'),font=get('[data-reader-font]');
  const reduced=()=>window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  function apply(){
   dialog.dataset.theme=preferences.theme;dialog.dataset.font=preferences.font;dialog.style.setProperty('--reader-size',preferences.size+'px');font.value=preferences.font;
   smaller.disabled=preferences.size<=14;larger.disabled=preferences.size>=30;
   dialog.querySelectorAll('[data-reader-theme]').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.readerTheme===preferences.theme)));
  }
  function renderParts(parts,first){
   paper.dataset.first=String(first);const fragment=document.createDocumentFragment();
   parts.forEach(part=>{const p=document.createElement('p');p.textContent=chunks[part.p].slice(part.from,part.to).join(' ');fragment.appendChild(p);});
   paper.replaceChildren(fragment);
  }
  function savePosition(){store(bookmarkKey,{signature,offset:anchor});}
  function show(i,animate=false){
   if(!pages.length)return;
   index=Math.min(pages.length-1,Math.max(0,i));const current=pages[index];anchor=current.start;
   renderParts(current.parts,index===0);
   count.textContent='Page '+(index+1)+' / '+pages.length;prev.disabled=index===0;next.disabled=index===pages.length-1;
   const percent=Math.round(100*(index+1)/pages.length);progress.setAttribute('aria-valuenow',String(percent));progress.firstElementChild.style.width=percent+'%';
   savePosition();
   if(animate&&!reduced()&&paper.animate)paper.animate([{opacity:.4,transform:'translateX(7px)'},{opacity:1,transform:'translateX(0)'}],{duration:180,easing:'ease-out'});
  }
  function paginate(){
   if(!opened||paper.clientHeight<10||paper.clientWidth<10)return;
   const target=anchor;const height=paper.clientHeight;
   lastWidth=paper.clientWidth;lastHeight=height;
   const output=[];let p=0,from=0;
   // Measure real rendered height. Keep paragraphs together where possible,
   // splitting at word boundaries only when a whole paragraph exceeds a page.
   while(p<chunks.length){
    const parts=[],start=starts[p]+from;
    paper.dataset.first=String(output.length===0);paper.replaceChildren();
    while(p<chunks.length){
     const words=chunks[p],el=document.createElement('p');
     const part={p,from,to:words.length};
     el.textContent=words.slice(from).join(' ');paper.appendChild(el);
     const fits=()=>paper.scrollHeight<=height+1;
     if(fits()){parts.push(part);p++;from=0;continue;}
     paper.removeChild(el);
     if(parts.length)break;
     paper.appendChild(el);
     let lo=from+1,hi=words.length,best=from;
     while(lo<=hi){const mid=Math.floor((lo+hi)/2);el.textContent=words.slice(from,mid).join(' ');if(fits()){best=mid;lo=mid+1;}else hi=mid-1;}
     // Avoid an infinite loop on very short landscape viewports.
     if(best===from)best=from+1;
     parts.push({p,from,to:best});from=best;if(from>=words.length){p++;from=0;}break;
    }
    output.push({start,parts});
   }
   pages=output;let i=0;for(let j=0;j<pages.length;j++){if(pages[j].start<=target)i=j;else break;}
   show(i);anchor=target;savePosition();
  }
  function schedule(){if(!opened)return;cancelAnimationFrame(frame);frame=requestAnimationFrame(paginate);}
  function open(){
   if(opened)return;opened=true;apply();dialog.showModal();document.body.classList.add('cel-ereader-open');schedule();
   if(document.fonts&&document.fonts.ready)document.fonts.ready.then(schedule);
   get('[data-reader-close]').focus();
  }
  function close(){if(dialog.open)dialog.close();}
  dialog.addEventListener('close',()=>{opened=false;cancelAnimationFrame(frame);document.body.classList.remove('cel-ereader-open');trigger.focus({preventScroll:true});});
  trigger.hidden=false;trigger.addEventListener('click',open);get('[data-reader-close]').addEventListener('click',close);
  if(location.hash==='#cel-story-reading')open();
  function change(reflow=true){apply();store(prefKey,preferences);if(reflow)schedule();}
  font.addEventListener('change',()=>{preferences.font=font.value;change();if(document.fonts&&document.fonts.ready)document.fonts.ready.then(schedule);});
  smaller.addEventListener('click',()=>{preferences.size=Math.max(14,preferences.size-1);change();});
  larger.addEventListener('click',()=>{preferences.size=Math.min(30,preferences.size+1);change();});
  dialog.querySelectorAll('[data-reader-theme]').forEach(b=>b.addEventListener('click',()=>{preferences.theme=b.dataset.readerTheme;change(false);}));
  function step(delta){if(index+delta>=0&&index+delta<pages.length)show(index+delta,true);}
  prev.addEventListener('click',()=>step(-1));next.addEventListener('click',()=>step(1));
  dialog.addEventListener('keydown',e=>{if(e.target.matches('select,input'))return;if(e.key==='ArrowRight'||e.key==='PageDown'){e.preventDefault();step(1);}else if(e.key==='ArrowLeft'||e.key==='PageUp'){e.preventDefault();step(-1);}else if((e.ctrlKey||e.metaKey)&&['c','a','x'].includes(e.key.toLowerCase()))e.preventDefault();});
  let touch=null;
  viewport.addEventListener('touchstart',e=>{if(e.touches.length===1)touch={x:e.touches[0].clientX,y:e.touches[0].clientY};else touch=null;},{passive:true});
  viewport.addEventListener('touchend',e=>{if(!touch||!e.changedTouches.length)return;const dx=e.changedTouches[0].clientX-touch.x,dy=e.changedTouches[0].clientY-touch.y;touch=null;if(Math.abs(dx)>45&&Math.abs(dx)>Math.abs(dy)*1.3)step(dx<0?1:-1);},{passive:true});
  viewport.addEventListener('touchcancel',()=>{touch=null;},{passive:true});
  ['copy','cut','contextmenu','selectstart','dragstart'].forEach(event=>paper.addEventListener(event,e=>e.preventDefault()));
  if(window.ResizeObserver)new ResizeObserver(()=>{if(opened&&(lastWidth!==paper.clientWidth||lastHeight!==paper.clientHeight))schedule();}).observe(viewport);
  window.addEventListener('resize',schedule);if(window.visualViewport)window.visualViewport.addEventListener('resize',schedule);
  const audio=story.querySelector('[data-cel-story-audio]'),audioButton=get('[data-reader-audio]');
  if(audio&&audioButton){
   function sync(){const playing=!audio.paused&&!audio.ended;audioButton.querySelector('span').textContent=playing?'Mettre en pause':'Écouter l’histoire';const svg=audioButton.querySelector('svg');if(svg)svg.innerHTML=playing?'<path d="M7 5h3v14H7zm7 0h3v14h-3z" fill="currentColor" stroke="none"/>':'<path d="M8 5v14l12-7z" fill="currentColor" stroke="none"/>';audioButton.setAttribute('aria-pressed',String(playing));}
   audioButton.addEventListener('click',()=>{if(audio.paused)audio.play().catch(()=>{audioButton.querySelector('span').textContent='Audio indisponible';});else audio.pause();});
   ['play','pause','ended'].forEach(event=>audio.addEventListener(event,sync));sync();
  }
  apply();
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
