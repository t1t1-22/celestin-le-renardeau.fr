document.addEventListener('DOMContentLoaded',()=>{
document.querySelectorAll('.cel-book-reader').forEach(reader=>{
 const spread=reader.querySelector('.cel-reader-spread'),left=reader.querySelector('.cel-reader-left'),right=reader.querySelector('.cel-reader-right'),flip=reader.querySelector('.cel-reader-flip');
 const controlsRoot=reader.closest('.cel-reader-section')||reader;
 const prev=controlsRoot.querySelector('.cel-reader-prev'),next=controlsRoot.querySelector('.cel-reader-next'),range=controlsRoot.querySelector('.cel-reader-progress'),counter=controlsRoot.querySelector('.cel-reader-counter'),thumbs=reader.querySelector('.cel-reader-thumbs'),error=controlsRoot.querySelector('.cel-reader-error');
 if(!spread||!left||!right||!prev||!next||!range||!counter||!thumbs||!error)return;
 let pdf,page=1,busy=false,zoom=1,resizeTimer;
 const single=()=>matchMedia('(max-width: 720px)').matches;
 const cover=()=>page===1||(!single()&&page===pdf.numPages&&page%2===0);
 const normal=n=>single()||n===1?n: n-n%2;
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
   const old=direction>0?right:left;
   if(direction&&old.width>1&&flip.animate&&!matchMedia('(prefers-reduced-motion: reduce)').matches){
     flip.width=old.width;flip.height=old.height;flip.getContext('2d').drawImage(old,0,0);
     flip.className='cel-reader-flip visible '+(direction>0?'forward':'backward');
   }
   page=normal(Math.max(1,Math.min(pdf.numPages,n)));
   const one=single()||cover(),end=one?page:Math.min(pdf.numPages,page+1);
   spread.classList.toggle('single',one);
   try{
     await Promise.all([draw(left,one?null:page,1100),draw(right,end,1100)]);
     counter.textContent='Page'+(end!==page?'s '+page+' – '+end:' '+page)+' sur '+pdf.numPages;
     range.value=page;thumbs.querySelectorAll('button').forEach(b=>b.classList.toggle('active',Number(b.dataset.page)>=page&&Number(b.dataset.page)<=end));
     if(flip.classList.contains('visible')){
       const anim=flip.animate([{transform:'rotateY(0deg)'},{transform:direction>0?'rotateY(-180deg)':'rotateY(180deg)'}],{duration:520,easing:'ease-in-out'});
       try{await anim.finished}catch(e){}
       flip.className='cel-reader-flip';
     }
   }catch(e){fail()}
   busy=false;prev.disabled=page<=1;next.disabled=end>=pdf.numPages;range.disabled=false;
 }
 function turn(d){if(!pdf||busy)return;const n=single()?page+d:d>0?(page===1?2:page+2):(page<=2?1:page-2);show(n,d)}
 prev.addEventListener('click',()=>turn(-1));next.addEventListener('click',()=>turn(1));range.addEventListener('change',()=>show(Number(range.value)));
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
   for(let n=1;n<=pdf.numPages;n++){const b=document.createElement('button'),c=document.createElement('canvas');b.type='button';b.dataset.page=n;b.setAttribute('aria-label','Page '+n);b.append(c);const label=document.createElement('span');label.textContent=n;b.append(label);b.onclick=()=>show(n);thumbs.append(b);}
   await show(1);
   for(const b of thumbs.querySelectorAll('button')){try{await draw(b.querySelector('canvas'),Number(b.dataset.page),145)}catch(e){}}
 }).catch(fail);
});
});