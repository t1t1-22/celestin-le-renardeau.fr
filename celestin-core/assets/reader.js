document.addEventListener('DOMContentLoaded',()=>{
document.querySelectorAll('.cel-book-reader').forEach(reader=>{
 const spread=reader.querySelector('.cel-reader-spread'),left=reader.querySelector('.cel-reader-left'),right=reader.querySelector('.cel-reader-right'),flip=reader.querySelector('.cel-reader-flip');
 const controlsRoot=reader.closest('.cel-reader-section')||reader;
 const prev=controlsRoot.querySelector('.cel-reader-prev'),next=controlsRoot.querySelector('.cel-reader-next'),range=controlsRoot.querySelector('.cel-reader-progress'),counter=controlsRoot.querySelector('.cel-reader-counter'),thumbs=reader.querySelector('.cel-reader-thumbs'),error=controlsRoot.querySelector('.cel-reader-error');
 if(!spread||!left||!right||!prev||!next||!range||!counter||!thumbs||!error)return;
 let pdf,page=1,busy=false,zoom=1,resizeTimer;
 const single=()=>matchMedia('(max-width: 720px)').matches;
 const cover=()=>page===1||page===pdf.numPages||page===pdf.numPages-1&&page%2===0;
 const normal=n=>single()||n===1||n===pdf.numPages?n:n-n%2;
 function fail(){counter.textContent='Lecture indisponible';error.hidden=false;error.textContent='Impossible de charger le PDF. Tu peux l’ouvrir avec le lien ci-dessous.'}
 async function draw(canvas,n,pixels){
   if(!n||n>pdf.numPages){canvas.width=canvas.height=1;return}
   const p=await pdf.getPage(n),v=p.getViewport({scale:1}),vp=p.getViewport({scale:pixels/Math.max(v.width,v.height)});
   canvas.width=Math.ceil(vp.width);canvas.height=Math.ceil(vp.height);
   await p.render({canvasContext:canvas.getContext('2d'),viewport:vp}).promise;
 }
 async function show(n,direction=0){
   if(!pdf||busy)return;
   busy=true;prev.disabled=next.disabled=true;range.disabled=true;
   const target=normal(Math.max(1,Math.min(pdf.numPages,n)));
   const one=single()||target===1||target===pdf.numPages||target===pdf.numPages-1&&target%2===0;
   const end=one?target:Math.min(pdf.numPages,target+1);
   const a=document.createElement('canvas'),b=document.createElement('canvas');
   const copy=(dest,src)=>{dest.width=src.width;dest.height=src.height;dest.getContext('2d').drawImage(src,0,0)};
   try{
     // Prepare the next spread offscreen: never clear a visible page during PDF rendering.
     await Promise.all([draw(a,one?null:target,1300),draw(b,end,1300)]);
     const oldSingle=spread.classList.contains('single');
     const animate=direction&&target!==page&&right.width>1&&!matchMedia('(prefers-reduced-motion: reduce)').matches;
     if(animate&&!single()&&!oldSingle&&!one){
       // Keep the old stationary leaf. Put the next stationary leaf underneath
       // a two-sided turning leaf, then swap only after its movement finishes.
       const leaf=document.createElement('div');leaf.className='cel-turn-leaf '+(direction>0?'forward':'backward');
       const front=document.createElement('canvas'),back=document.createElement('canvas');
       front.className='cel-turn-front';back.className='cel-turn-back';
       copy(front,direction>0?right:left);copy(back,direction>0?a:b);
       leaf.append(front,back);spread.append(leaf);
       if(direction>0)copy(right,b);else copy(left,a);
       try{
         const anim=leaf.animate([{transform:'rotateY(0deg)'},{transform:'rotateY('+(direction>0?-180:180)+'deg)'}],{duration:850,easing:'cubic-bezier(.42,0,.25,1)',fill:'forwards'});
         await anim.finished;
       }finally{copy(left,a);copy(right,b);leaf.remove();}
     }else if(animate){
       // Covers open and close around their inner edge; content changes at midpoint.
       const moving=right;
       moving.style.transformOrigin=direction>0?'left center':'right center';
       await moving.animate([{transform:'rotateY(0deg)'},{transform:'rotateY('+(direction>0?-85:85)+'deg)'}],{duration:320,easing:'ease-in',fill:'none'}).finished;
       spread.classList.toggle('single',one);copy(left,a);copy(right,b);
       await right.animate([{transform:'rotateY('+(direction>0?70:-70)+'deg)'},{transform:'rotateY(0deg)'}],{duration:360,easing:'ease-out'}).finished;
       moving.style.transformOrigin='';
     }else{spread.classList.toggle('single',one);copy(left,a);copy(right,b);}
     page=target;
     counter.textContent=page===1?'Première de couverture':page===pdf.numPages?'Quatrième de couverture':'Page'+(end!==page?'s '+page+' – '+end:' '+page)+' sur '+pdf.numPages;
     range.value=page;thumbs.querySelectorAll('button').forEach(b=>b.classList.toggle('active',Number(b.dataset.page)>=page&&Number(b.dataset.page)<=end));
     error.hidden=true;
   }catch(e){fail()}
   finally{busy=false;prev.disabled=page<=1;next.disabled=end>=pdf.numPages;range.disabled=false;}
 }
 function turn(d){if(!pdf||busy)return;const n=single()?page+d:d>0?(page===1?2:page+2):(page===pdf.numPages?Math.max(1,normal(page-1)):page<=2?1:page-2);show(n,d)}
 prev.addEventListener('click',()=>turn(-1));next.addEventListener('click',()=>turn(1));range.addEventListener('change',()=>show(Number(range.value),Math.sign(Number(range.value)-page)));
 function changeZoom(d){zoom=Math.min(2,Math.max(1,zoom+d));spread.style.width=(zoom*100)+'%';}
 controlsRoot.querySelector('.cel-reader-zoom-in').onclick=()=>changeZoom(.25);
 controlsRoot.querySelector('.cel-reader-zoom-out').onclick=()=>changeZoom(-.25);
 controlsRoot.querySelector('.cel-reader-full').onclick=()=>{if(document.fullscreenElement)document.exitFullscreen?.();else if(controlsRoot.requestFullscreen)controlsRoot.requestFullscreen().catch(()=>{});else reader.classList.toggle('expanded')};
 let touchX=0;spread.addEventListener('touchstart',e=>{touchX=e.changedTouches[0].clientX},{passive:true});spread.addEventListener('touchend',e=>{const dx=e.changedTouches[0].clientX-touchX;if(Math.abs(dx)>65&&zoom===1)turn(dx<0?1:-1)},{passive:true});
 window.addEventListener('resize',()=>{clearTimeout(resizeTimer);resizeTimer=setTimeout(()=>show(page),200)});
 if(!window.pdfjsLib){fail();return}
 pdfjsLib.GlobalWorkerOptions.workerSrc='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
 pdfjsLib.getDocument({url:reader.dataset.pdf,isEvalSupported:false}).promise.then(async doc=>{
   pdf=doc;range.max=doc.numPages;document.querySelectorAll('[data-cel-page-total]').forEach(x=>x.textContent=doc.numPages+' pages');
   for(let n=1;n<=pdf.numPages;n++){const b=document.createElement('button'),c=document.createElement('canvas');b.type='button';b.dataset.page=n;b.setAttribute('aria-label','Page '+n);b.append(c);const label=document.createElement('span');label.textContent=n;b.append(label);b.onclick=()=>show(n,Math.sign(n-page));thumbs.append(b);}
   await show(1);
   for(const b of thumbs.querySelectorAll('button')){try{await draw(b.querySelector('canvas'),Number(b.dataset.page),145)}catch(e){}}
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
