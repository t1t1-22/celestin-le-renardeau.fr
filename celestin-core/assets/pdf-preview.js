jQuery(function($){
 const input=$('#cel_f_pdf'), panel=$('#cel-pdf-preview');if(!input.length||!panel.length)return;
 const status=panel.find('.cel-pdf-status'),grid=panel.find('.cel-pdf-grid');let generation=0,task=null,doc=null,timer,view=0,views=[],drawing=false;
 const reader=panel.find('.cel-admin-reader'),canvases=reader.find('canvas');
 async function show(i){if(!doc||drawing||!views.length)return;drawing=true;const token=generation;view=Math.max(0,Math.min(i,views.length-1));const nums=views[view];try{for(let k=0;k<2;k++){const c=canvases[k],n=nums[k];c.hidden=!n;if(!n)continue;const p=await doc.getPage(n);if(token!==generation)return;const v=p.getViewport({scale:1}),vp=p.getViewport({scale:650/Math.max(v.width,v.height)});c.width=vp.width;c.height=vp.height;await p.render({canvasContext:c.getContext('2d'),viewport:vp}).promise;}reader.find('.cel-admin-counter').text(view===0?'Première de couverture':view===views.length-1?'Quatrième de couverture':'Pages '+nums.join(' – '));reader.find('.cel-admin-prev').prop('disabled',view===0);reader.find('.cel-admin-next').prop('disabled',view===views.length-1);}catch(e){}finally{drawing=false;}}
 reader.find('.cel-admin-prev').on('click',()=>show(view-1));reader.find('.cel-admin-next').on('click',()=>show(view+1));
 async function preview(){
  const token=++generation,url=input.val().trim();grid.empty();reader.attr('hidden',true);
  if(task){try{await task.destroy()}catch(e){}}task=null;doc=null;
  if(token!==generation)return;
  if(!url){status.text('Choisis le PDF du livre pour afficher ses pages.');return}
  if(!window.pdfjsLib){status.text('La prévisualisation PDF est indisponible.');return}
  status.text('Chargement des pages…');
  try{
   pdfjsLib.GlobalWorkerOptions.workerSrc='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
   task=pdfjsLib.getDocument({url,isEvalSupported:false});doc=await task.promise;
   if(token!==generation)return;
   const pdf=doc;views=[[1]];for(let i=2;i<pdf.numPages;i+=2)views.push(i+1<pdf.numPages?[i,i+1]:[i]);if(pdf.numPages>1)views.push([pdf.numPages]);reader.removeAttr('hidden');await show(0);$('#cel_f_nombre_pages').val(pdf.numPages);status.text(pdf.numPages+' pages — vérifie la couverture, les pages intérieures et la quatrième de couverture.');
   for(let n=1;n<=pdf.numPages;n++){
    if(token!==generation)return;
    const label=n===1?'Première de couverture':n===pdf.numPages?'Quatrième de couverture':'Page '+n;
    const card=$('<figure class="cel-pdf-thumb">'),canvas=document.createElement('canvas');card.append(canvas,$('<figcaption>').text(label));grid.append(card);card.attr('tabindex','0').on('click keydown',e=>{if(e.type==='click'||e.key==='Enter')show(views.findIndex(v=>v.includes(n)))});
    const page=await pdf.getPage(n);if(token!==generation)return;
    const v=page.getViewport({scale:1}),vp=page.getViewport({scale:220/Math.max(v.width,v.height)});
    canvas.width=Math.ceil(vp.width);canvas.height=Math.ceil(vp.height);
    await page.render({canvasContext:canvas.getContext('2d'),viewport:vp}).promise;
    if(Math.abs(v.width/v.height-1)>.025)card.append($('<small>').text('Page non carrée'));
   }
  }catch(e){if(token===generation)status.text('Impossible de prévisualiser ce PDF. Vérifie que le fichier est accessible depuis la médiathèque.')}
 }
 input.on('change input',()=>{clearTimeout(timer);timer=setTimeout(preview,300)});preview();
});