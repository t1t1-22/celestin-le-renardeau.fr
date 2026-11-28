document.addEventListener('DOMContentLoaded',()=>{
document.querySelectorAll('.cel-book-reader').forEach(reader=>{
 const spread=reader.querySelector('.cel-reader-spread'),left=reader.querySelector('.cel-reader-left'),right=reader.querySelector('.cel-reader-right'),flip=reader.querySelector('.cel-reader-flip');
 const controlsRoot=reader.closest('.cel-reader-section')||reader;
 const prev=controlsRoot.querySelector('.cel-reader-prev'),next=controlsRoot.querySelector('.cel-reader-next'),range=controlsRoot.querySelector('.cel-reader-progress'),counter=controlsRoot.querySelector('.cel-reader-counter'),thumbs=reader.querySelector('.cel-reader-thumbs'),error=controlsRoot.querySelector('.cel-reader-error');
 if(!spread||!left||!right||!prev||!next||!range||!counter||!error)return;
 let pdf,page=1,busy=false,zoom=1,resizeTimer,anchorPage=1;
 const landscape=matchMedia('(orientation: landscape) and (max-height: 600px) and (pointer: coarse), (orientation: landscape) and (max-height: 600px) and (max-width: 1024px)');
 const touchScreen=matchMedia('(pointer: coarse), (max-width: 720px)');
 const single=()=>!landscape.matches&&matchMedia('(max-width: 720px)').matches;
 const fullButton=controlsRoot.querySelector('.cel-reader-full');
 const hint=document.createElement('p');
 hint.className='cel-reader-rotate-hint';
 hint.textContent='Tourne ton téléphone pour lire les deux pages côte à côte.';
 controlsRoot.querySelector('.cel-reader-controls').before(hint);
 let modal=null,placeholder=null,autoModal=false,wasLandscape=landscape.matches,wasVisible=false,previousOverflow='';
 function visible(){
   const rect=reader.getBoundingClientRect();
   const header=document.querySelector('.cel-interior-header')?.getBoundingClientRect().bottom||0;
   return rect.bottom>Math.max(0,header)+80&&rect.top<window.innerHeight-80;
 }
 function trackVisibility(){if(!modal&&!landscape.matches)wasVisible=visible()}
 function openModal(automatic=false){
   if(modal||document.fullscreenElement||typeof HTMLDialogElement==='undefined'||!HTMLDialogElement.prototype.showModal)return false;
   placeholder=document.createElement('div');placeholder.style.height=controlsRoot.offsetHeight+'px';
   controlsRoot.before(placeholder);
   modal=document.createElement('dialog');modal.className='cel-book-fullscreen';modal.setAttribute('aria-label','Lecture du livre en plein écran');
   document.body.append(modal);modal.append(controlsRoot);
   autoModal=automatic;previousOverflow=document.body.style.overflow;
   document.body.style.overflow='hidden';
   fullButton.textContent='×';fullButton.setAttribute('aria-label','Quitter le plein écran');
   modal.addEventListener('cancel',e=>{e.preventDefault();closeModal()});
   modal.showModal();layout();return true;
 }
 function closeModal(){
   if(!modal)return;
   const old=modal;modal=null;autoModal=false;
   placeholder.replaceWith(controlsRoot);placeholder=null;old.close();old.remove();
   document.body.style.overflow=previousOverflow;
   fullButton.textContent='⛶';fullButton.setAttribute('aria-label','Plein écran');
   layout();fullButton.focus({preventScroll:true});
 }
 function orientationChanged(){
   const now=landscape.matches;
   if(now!==wasLandscape){
     if(now&&wasVisible&&!modal&&pdf)openModal(true);
     if(!now&&modal&&autoModal)closeModal();
     wasLandscape=now;
   }
   layout();if(lastMobile!==single())show(anchorPage,0,true);
   trackVisibility();
 }
 window.addEventListener('scroll',trackVisibility,{passive:true});
 trackVisibility();
 const viewport=reader.querySelector('.cel-reader-viewport');
 const normal=n=>single()||n===1||n===pdf.numPages?n:n-n%2;
 function layout(){
   const full=!!modal||document.fullscreenElement===controlsRoot;
   const header=document.querySelector('.cel-interior-header');
   const headerHeight=full?0:(header?.offsetHeight||0);
   const compact=controlsRoot.classList.contains('cel-reader-compact');
   const controls=controlsRoot.querySelector('.cel-reader-controls');
   const controlsHeight=Math.min(120,Math.max(40,controls?.offsetHeight||0));
   const overhead=controlsHeight+(full&&landscape.matches?32:85);
   const cap=full?Infinity:compact?340:480;
   const modalStyle=modal?getComputedStyle(modal):null;
   const usableHeight=modal?modal.clientHeight-parseFloat(modalStyle.paddingTop)-parseFloat(modalStyle.paddingBottom):window.innerHeight;
   const available=Math.min(cap,Math.max(full&&landscape.matches?80:220,usableHeight-headerHeight-overhead));
   const width=Math.max(1,viewport.clientWidth-10);
   const sides=single()?1:2;
   const pageSize=Math.min(width/sides,available);
   const size=Math.round(pageSize*zoom);
   viewport.classList.toggle('is-zoomed',zoom>1);
   spread.style.width=(size*sides)+'px';
   spread.style.height=size+'px';
   spread.style.setProperty('--cel-page-size',size+'px');
   viewport.style.maxHeight=(pageSize+26)+'px';
   reader.style.setProperty('--cel-reader-height',pageSize+'px');
 }
 function fail(){counter.textContent='Lecture indisponible';error.hidden=false;error.textContent='Impossible de charger le PDF. Tu peux l’ouvrir avec le lien ci-dessous.'}
 async function draw(canvas,n,pixels){
   if(!n||n>pdf.numPages){canvas.width=canvas.height=1;return}
   const p=await pdf.getPage(n),v=p.getViewport({scale:1}),vp=p.getViewport({scale:pixels/Math.max(v.width,v.height)});
   canvas.width=Math.ceil(vp.width);canvas.height=Math.ceil(vp.height);
   await p.render({canvasContext:canvas.getContext('2d'),viewport:vp}).promise;
 }
 let lastEnd=1, lastMobile=single();
 async function show(n,direction=0,keepAnchor=false){
   if(!pdf||busy)return;
   busy=true;prev.disabled=next.disabled=true;range.disabled=true;
   const target=normal(Math.max(1,Math.min(pdf.numPages,n)));
   const mobile=single(), first=target===1, final=target===pdf.numPages;
   const leftPage=mobile||first?null:target;
   const rightPage=mobile||first?target:final||target+1===pdf.numPages?null:target+1;
   const end=rightPage||leftPage||target;
   const a=document.createElement('canvas'),b=document.createElement('canvas');
   const copy=(dest,src)=>{dest.width=src.width;dest.height=src.height;dest.getContext('2d').drawImage(src,0,0)};
   const setSide=(dest,src)=>{copy(dest,src);dest.classList.toggle('cel-reader-blank',src.width===1)};
   try{
     await Promise.all([draw(a,leftPage,1300),draw(b,rightPage,1300)]);
     const adjacent=direction>0?target===lastEnd+1:page===end+1;
     const animate=direction&&adjacent&&!mobile&&!lastMobile&&target!==page&&!matchMedia('(prefers-reduced-motion: reduce)').matches;
     spread.classList.toggle('single',mobile);
     layout();
     if(animate){
       const leaf=document.createElement('div');leaf.className='cel-turn-leaf '+(direction>0?'forward':'backward');
       const front=document.createElement('canvas'),back=document.createElement('canvas');
       front.className='cel-turn-front';back.className='cel-turn-back';
       copy(front,direction>0?right:left);copy(back,direction>0?a:b);
       leaf.append(front,back);spread.append(leaf);
       if(direction>0)setSide(right,b);else setSide(left,a);
       try{
         await leaf.animate([{transform:'rotateY(0deg)'},{transform:'rotateY('+(direction>0?-180:180)+'deg)'}],{duration:650,easing:'cubic-bezier(.42,0,.25,1)',fill:'forwards'}).finished;
       }finally{setSide(left,a);setSide(right,b);leaf.remove();}
     }else{setSide(left,a);setSide(right,b);}
     page=target;lastEnd=end;lastMobile=mobile;if(!keepAnchor)anchorPage=Math.max(1,Math.min(pdf.numPages,n));
     counter.textContent=page===1?'Première de couverture':page===pdf.numPages?'Quatrième de couverture':'Page'+(end!==page?'s '+page+' – '+end:' '+page)+' sur '+pdf.numPages;
     range.value=page;thumbs?.querySelectorAll('button').forEach(b=>b.classList.toggle('active',Number(b.dataset.page)>=page&&Number(b.dataset.page)<=end));
     error.hidden=true;
   }catch(e){fail()}
   finally{busy=false;prev.disabled=page<=1;next.disabled=lastEnd>=pdf.numPages;range.disabled=false;if(lastMobile!==single()&&error.hidden)show(anchorPage,0,true);}
 }
 function turn(d){if(!pdf||busy||(d>0?lastEnd>=pdf.numPages:page<=1))return;show(d>0?lastEnd+1:Math.max(1,page-1),d)}
 let lastSwipe=0;
 spread.addEventListener('click',e=>{
   if(Date.now()-lastSwipe<450||busy||!pdf)return;
   const rect=spread.getBoundingClientRect();
   if(single()){turn(e.clientX<rect.left+rect.width/2?-1:1);return;}
   if(e.target===left&&!left.classList.contains('cel-reader-blank'))turn(-1);
   else if(e.target===right&&!right.classList.contains('cel-reader-blank'))turn(1);
 });
 prev.addEventListener('click',()=>turn(-1));next.addEventListener('click',()=>turn(1));range.addEventListener('change',()=>show(Number(range.value),Math.sign(Number(range.value)-page)));
 function changeZoom(d){zoom=Math.min(2,Math.max(.5,zoom+d));layout();}
 controlsRoot.querySelector('.cel-reader-zoom-in').onclick=()=>changeZoom(.25);
 controlsRoot.querySelector('.cel-reader-zoom-out').onclick=()=>changeZoom(-.25);
 fullButton.onclick=()=>{
   if(modal){closeModal();return}
   if(document.fullscreenElement){document.exitFullscreen?.();return}
   if(touchScreen.matches&&openModal())return;
   if(controlsRoot.requestFullscreen)controlsRoot.requestFullscreen().catch(()=>openModal());
   else openModal();
 };
 let touchX=0;spread.addEventListener('touchstart',e=>{touchX=e.changedTouches[0].clientX},{passive:true});spread.addEventListener('touchend',e=>{const dx=e.changedTouches[0].clientX-touchX;if(Math.abs(dx)>65&&zoom<=1){lastSwipe=Date.now();turn(dx<0?1:-1)}},{passive:true});
 window.addEventListener('resize',()=>{clearTimeout(resizeTimer);resizeTimer=setTimeout(orientationChanged,120)});
 if(landscape.addEventListener)landscape.addEventListener('change',orientationChanged);
 else landscape.addListener(orientationChanged);
 document.addEventListener('fullscreenchange',layout);
 if(window.ResizeObserver)new ResizeObserver(layout).observe(viewport.parentElement);
 if(!window.pdfjsLib){fail();return}
 pdfjsLib.GlobalWorkerOptions.workerSrc='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
 pdfjsLib.getDocument({url:reader.dataset.pdf,isEvalSupported:false}).promise.then(async doc=>{
   pdf=doc;range.max=doc.numPages;document.querySelectorAll('[data-cel-page-total]').forEach(x=>x.textContent=doc.numPages+' pages');
   if(thumbs)for(let n=1;n<=pdf.numPages;n++){const b=document.createElement('button'),c=document.createElement('canvas');b.type='button';b.dataset.page=n;b.setAttribute('aria-label','Page '+n);b.append(c);const label=document.createElement('span');label.textContent=n;b.append(label);b.onclick=()=>show(n,Math.sign(n-page));thumbs.append(b);}
   await show(1);
   if(thumbs)for(const b of thumbs.querySelectorAll('button')){try{await draw(b.querySelector('canvas'),Number(b.dataset.page),145)}catch(e){}}
 }).catch(fail);
});
});
document.addEventListener('DOMContentLoaded',()=>{
 const intro=document.querySelector('.cel-book-intro'),cover=intro?.querySelector('.cel-book-cover'),copy=intro?.querySelector('.cel-book-intro-copy'),summary=intro?.querySelector('.cel-book-resume'),toggle=intro?.querySelector('.cel-resume-toggle');
 if(!cover||!copy||!summary||!toggle)return;
 let expanded=false;
 function layout(){
   summary.style.maxHeight='none';summary.classList.remove('is-collapsed');toggle.hidden=false;
   const other=copy.getBoundingClientRect().height-summary.getBoundingClientRect().height;
   const limit=matchMedia('(max-width:720px)').matches?160:Math.max(48,Math.floor(cover.getBoundingClientRect().height-other));
   const overflow=summary.scrollHeight>limit+3;
   toggle.hidden=!overflow;
   if(overflow&&!expanded){summary.style.maxHeight=limit+'px';summary.classList.add('is-collapsed')}
   toggle.textContent=expanded?'Lire moins ↑':'Lire plus ↓';toggle.setAttribute('aria-expanded',String(expanded));
 }
 toggle.addEventListener('click',()=>{expanded=!expanded;layout()});
 window.addEventListener('resize',layout);document.fonts?.ready.then(layout);layout();
 if(window.ResizeObserver)new ResizeObserver(layout).observe(cover);
});
