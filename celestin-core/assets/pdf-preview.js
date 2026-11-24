jQuery(function($){
 const input=$('#cel_f_pdf'), panel=$('#cel-pdf-preview');if(!input.length||!panel.length)return;
 const status=panel.find('.cel-pdf-status'),grid=panel.find('.cel-pdf-grid');let generation=0,task=null,doc=null,timer;
 async function preview(){
  const token=++generation,url=input.val().trim();grid.empty();
  if(task){try{await task.destroy()}catch(e){}}task=null;doc=null;
  if(token!==generation)return;
  if(!url){status.text('Choisis le PDF du livre pour afficher ses pages.');return}
  if(!window.pdfjsLib){status.text('La prévisualisation PDF est indisponible.');return}
  status.text('Chargement des pages…');
  try{
   pdfjsLib.GlobalWorkerOptions.workerSrc='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
   task=pdfjsLib.getDocument({url,isEvalSupported:false});doc=await task.promise;
   if(token!==generation)return;
   const pdf=doc;$('#cel_f_nombre_pages').val(pdf.numPages);status.text(pdf.numPages+' pages — vérifie la couverture, les pages intérieures et la quatrième de couverture.');
   for(let n=1;n<=pdf.numPages;n++){
    if(token!==generation)return;
    const label=n===1?'Première de couverture':n===pdf.numPages?'Quatrième de couverture':'Page '+n;
    const card=$('<figure class="cel-pdf-thumb">'),canvas=document.createElement('canvas');card.append(canvas,$('<figcaption>').text(label));grid.append(card);
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