document.addEventListener('DOMContentLoaded',function(){
 const audio=document.querySelector('[data-cel-story-audio]');
 const transcript=document.querySelector('[data-cel-story-transcript]');
 if(!audio||!transcript)return;
 const sentences=Array.from(transcript.querySelectorAll('.cel-story-sentence'));
 const timings=sentences.map(s=>parseFloat(s.dataset.start));
 if(!sentences.length || timings.some(t=>!Number.isFinite(t)))return;
 let active=-1;
 function update(){
   const t=audio.currentTime;
   let i=-1;for(let j=0;j<timings.length;j++)if(t>=timings[j])i=j;
   if(i===active)return;
   sentences.forEach((el,k)=>el.classList.toggle('is-current',k===i));
   active=i;
   if(i>=0&&!audio.paused)sentences[i].scrollIntoView({block:'nearest',behavior:'smooth'});
 }
 audio.addEventListener('timeupdate',update);
 audio.addEventListener('seeked',update);
 audio.addEventListener('play',update);
 audio.addEventListener('ended',function(){sentences.forEach(s=>s.classList.remove('is-current'));active=-1});
});

document.addEventListener('DOMContentLoaded',()=>{const players=Array.from(document.querySelectorAll('[data-cel-story-audio],[data-cel-story-preview]'));players.forEach(audio=>audio.addEventListener('play',()=>players.forEach(other=>{if(other!==audio)other.pause()})));});
