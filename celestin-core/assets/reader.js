document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('.cel-book-reader').forEach(function(reader){
    const pdfUrl=reader.dataset.pdf, stage=reader.querySelector('.cel-book-stage');
    const left=reader.querySelector('.cel-left-sheet canvas'),right=reader.querySelector('.cel-right-sheet canvas');
    const prev=reader.querySelector('.cel-reader-prev'),next=reader.querySelector('.cel-reader-next');
    const counter=reader.querySelector('.cel-reader-counter'),error=reader.querySelector('.cel-reader-error');
    let documentPdf=null,page=1,busy=false;
    const single=()=>window.matchMedia('(max-width: 720px)').matches;
    const visible=()=>single()?[page]:page===1?[null,1]:[page,page+1];
    async function draw(canvas, number){
      const ctx=canvas.getContext('2d');
      if(!number||number>documentPdf.numPages){
        canvas.width=400;canvas.height=400;ctx.clearRect(0,0,400,400);return;
      }
      const p=await documentPdf.getPage(number);
      const natural=p.getViewport({scale:1});
      const ratio=Math.min((window.devicePixelRatio||1)*1.1,2);
      const pixelSize=Math.min(1000,Math.max(450,stage.clientWidth/(single()?1:2)*ratio));
      const viewport=p.getViewport({scale:pixelSize/natural.width});
      canvas.width=viewport.width;canvas.height=viewport.height;
      await p.render({canvasContext:ctx,viewport:viewport}).promise;
    }
    async function render(){
      if(!documentPdf)return;
      busy=true;prev.disabled=true;next.disabled=true;
      try{
        const nums=visible();
        await Promise.all([draw(left,single()?null:nums[0]),draw(right,nums[nums.length-1])]);
        const shown=nums.filter(n=>n&&n<=documentPdf.numPages);
        counter.textContent='Page'+(shown.length>1?'s ':' ')+shown.join(' – ')+' / '+documentPdf.numPages;
      }catch(e){error.hidden=false;error.textContent='Impossible de charger une page de ce livre.'}
      prev.disabled=page<=1;next.disabled=page>=documentPdf.numPages;busy=false;
    }
    function turn(direction){
      if(busy||!documentPdf)return;
      let target;
      if(single()) target=page+direction;
      else if(direction>0) target=page===1?2:page+2;
      else target=page<=2?1:page-2;
      target=Math.max(1,Math.min(documentPdf.numPages,target));
      if(target===page)return;
      page=target;
      stage.classList.remove('turn-next','turn-prev');void stage.offsetWidth;
      stage.classList.add(direction>0?'turn-next':'turn-prev');
      render();
    }
    prev.addEventListener('click',()=>turn(-1));next.addEventListener('click',()=>turn(1));
    window.addEventListener('resize',()=>{if(!documentPdf)return; if(!single()&&page>1&&page%2===1)page--;render()});
    if(!window.pdfjsLib){counter.textContent='Visionneuse indisponible';error.hidden=false;error.textContent='Utilise le lien PDF ci-dessous.';return}
    window.pdfjsLib.GlobalWorkerOptions.workerSrc='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    window.pdfjsLib.getDocument({url:pdfUrl}).promise.then(function(pdf){documentPdf=pdf;render()}).catch(function(){
      counter.textContent='Lecture indisponible';error.hidden=false;
      error.textContent='Le PDF ne peut pas être affiché ici. Utilise le lien ci-dessous.';
    });
  });
});
