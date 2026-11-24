document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('.cel-story-scroll').forEach(function(el){
    el.addEventListener('wheel',function(e){e.stopPropagation()},{passive:true});
  });
});

document.addEventListener('DOMContentLoaded',()=>{
 document.querySelectorAll('.cel-news-carousel').forEach(root=>{
  const track=root.querySelector('.cel-news-track'),prev=root.querySelector('.cel-news-prev'),next=root.querySelector('.cel-news-next'),pause=root.querySelector('.cel-news-pause');
  if(!track||!prev||!next||!pause)return;
  let stopped=matchMedia('(prefers-reduced-motion: reduce)').matches,hover=false,focused=false,direction=1;
  function step(d){const card=track.querySelector('.cel-card');if(!card)return;const max=track.scrollWidth-track.clientWidth;if(max<2)return;const gap=parseFloat(getComputedStyle(track).columnGap)||0;const target=d>0&&track.scrollLeft>=max-2?0:d<0&&track.scrollLeft<=2?max:track.scrollLeft+d*(card.offsetWidth+gap);track.scrollTo({left:target,behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});}
  function sync(){pause.textContent=stopped?'Lecture':'Pause';pause.setAttribute('aria-label',stopped?'Reprendre le défilement':'Mettre le défilement en pause');const overflow=track.scrollWidth>track.clientWidth+2;root.querySelector('.cel-news-controls').hidden=!overflow;}
  prev.onclick=()=>step(-1);next.onclick=()=>step(1);pause.onclick=()=>{stopped=!stopped;sync()};
  root.addEventListener('mouseenter',()=>hover=true);root.addEventListener('mouseleave',()=>hover=false);root.addEventListener('focusin',()=>focused=true);root.addEventListener('focusout',e=>{if(!root.contains(e.relatedTarget))focused=false});
  setInterval(()=>{if(!stopped&&!hover&&!focused&&!document.hidden&&!root.querySelector('.cel-audio-toggle[aria-pressed="true"]')){const max=track.scrollWidth-track.clientWidth;if(max<2)return;if(track.scrollLeft>=max-2)direction=-1;else if(track.scrollLeft<=2)direction=1;step(direction)}},5000);window.addEventListener('resize',sync);sync();
 });
});

document.addEventListener('DOMContentLoaded',()=>{
 const players=[];
 let active=null,frame=0;
 function stop(p){p.audio.pause();try{p.audio.currentTime=0}catch(e){}p.render();}
 function tick(){if(active){active.render();if(!active.audio.paused)frame=requestAnimationFrame(tick)}}
 document.querySelectorAll('.cel-audio-card,.cel-news-card').forEach(card=>{
   const audio=card.querySelector('audio'),button=card.querySelector('.cel-audio-toggle');if(!audio||!button)return;
   const ring=button.querySelector('.cel-audio-ring'),error=card.querySelector('.cel-audio-error');
   const p={audio,button,render(){const playing=!audio.paused&&!audio.ended;const progress=Number.isFinite(audio.duration)&&audio.duration>0?Math.min(100,100*audio.currentTime/audio.duration):0;ring.setAttribute('stroke-dasharray',progress+' 100');button.setAttribute('aria-pressed',String(playing));button.setAttribute('aria-label',(playing?'Arrêter ':'Écouter ')+button.dataset.title)}};
   players.push(p);
   button.addEventListener('click',()=>{
     if(active===p&&!audio.paused){stop(p);active=null;cancelAnimationFrame(frame);return;}
     players.forEach(other=>{if(other!==p)stop(other)});active=p;error.hidden=true;
     audio.play().catch(()=>{if(active===p){error.hidden=false;active=null;p.render()}});
   });
   audio.addEventListener('play',()=>{if(active!==p){stop(p);return;}cancelAnimationFrame(frame);tick()});
   ['pause','timeupdate','loadedmetadata','seeked'].forEach(event=>audio.addEventListener(event,p.render));
   audio.addEventListener('ended',()=>{stop(p);if(active===p)active=null;cancelAnimationFrame(frame)});
   audio.addEventListener('error',()=>{error.hidden=false;p.render();if(active===p){active=null;cancelAnimationFrame(frame)}});
   p.render();
 });
});

document.addEventListener('DOMContentLoaded',()=>{
 const root=document.querySelector('.cel-feature-slider');if(!root)return;
 const slides=[...root.querySelectorAll('.cel-feature-slide')],dots=[...root.querySelectorAll('.cel-feature-pagination button[data-slide]')],pause=root.querySelector('.cel-feature-pause');
 let current=0,stopped=matchMedia('(prefers-reduced-motion: reduce)').matches,hover=false;
 // Quantification de couleurs de l'image, en privilégiant les teintes chromatiques.
 function palette(slide){
  if(slide.querySelector('.cel-news-cel_coloriage,.cel-news-cel_personnage'))return;
  const img=slide.querySelector('.cel-card-image img');if(!img)return;
  const extract=()=>{try{
   const c=document.createElement('canvas');c.width=c.height=48;const ctx=c.getContext('2d',{willReadFrequently:true});ctx.drawImage(img,0,0,48,48);const data=ctx.getImageData(0,0,48,48).data;
   const bins=Array.from({length:24},()=>({score:0,r:0,g:0,b:0,w:0}));
   for(let i=0;i<data.length;i+=4){const r=data[i]/255,g=data[i+1]/255,b=data[i+2]/255,max=Math.max(r,g,b),min=Math.min(r,g,b),d=max-min;if(data[i+3]<200||d<.12||max<.17||min>.88)continue;
    let h=max===r?(g-b)/d:max===g?(b-r)/d+2:(r-g)/d+4;h=(h*60+360)%360;const bin=bins[Math.floor(h/15)];const w=d*(.5+max);bin.score+=w;bin.r+=data[i]*w;bin.g+=data[i+1]*w;bin.b+=data[i+2]*w;bin.w+=w;
   }
   const best=bins.sort((a,b)=>b.score-a.score)[0];if(!best.w)return;
   const rgb=[best.r,best.g,best.b].map(x=>x/best.w);
   const mix=(k,target)=>'rgb('+rgb.map(x=>Math.round(x*(1-k)+target*k)).join(',')+')';
   slide.style.setProperty('--cel-slide-light',mix(.83,255));slide.style.setProperty('--cel-slide-mid',mix(.58,255));slide.style.setProperty('--cel-slide-dark',mix(.13,0));
  }catch(e){/* La palette de secours reste disponible si l'image ne peut pas être lue. */}};
  if(img.complete&&img.naturalWidth)extract();else img.addEventListener('load',extract,{once:true});
 }
 slides.forEach((s,i)=>{palette(s);s.inert=i!==0;s.setAttribute('aria-hidden',String(i!==0))});
 function show(n){if(n===current)return;const old=slides[current];old.querySelectorAll('audio').forEach(a=>{a.pause();try{a.currentTime=0}catch(e){}});current=(n+slides.length)%slides.length;slides.forEach((s,i)=>{s.hidden=i!==current;s.inert=i!==current;s.setAttribute('aria-hidden',String(i!==current))});dots.forEach((d,i)=>d.setAttribute('aria-current',String(i===current)));}
 dots.forEach((b,i)=>b.onclick=()=>show(i));
 function sync(){if(pause){pause.textContent=stopped?'▷':'Ⅱ';pause.setAttribute('aria-label',stopped?'Reprendre le défilement':'Mettre le défilement en pause')}}
 if(pause)pause.onclick=()=>{stopped=!stopped;sync()};sync();
 root.addEventListener('mouseenter',()=>hover=true);root.addEventListener('mouseleave',()=>hover=false);
 if(slides.length>1)setInterval(()=>{if(!stopped&&!hover&&!document.hidden&&!root.contains(document.activeElement)&&!root.querySelector('.cel-audio-toggle[aria-pressed="true"]'))show(current+1)},6500);
});

document.addEventListener('DOMContentLoaded',()=>{
 const format=t=>Number.isFinite(t)?Math.floor(t/60)+':'+String(Math.floor(t%60)).padStart(2,'0'):'--:--';
 document.querySelectorAll('.cel-feature-audio-progress').forEach(root=>{
 const audio=root.closest('.cel-feature-layout').querySelector('audio'),range=root.querySelector('input'),current=root.querySelector('.cel-time-current'),total=root.querySelector('.cel-time-total');if(!audio)return;
 const update=()=>{const valid=Number.isFinite(audio.duration)&&audio.duration>0;range.disabled=!valid;range.value=valid?Math.round(audio.currentTime/audio.duration*1000):0;range.style.setProperty('--cel-progress',range.value/10+'%');current.textContent=format(audio.currentTime);total.textContent=format(audio.duration)};
 ['loadedmetadata','durationchange','timeupdate','seeked','ended','emptied'].forEach(e=>audio.addEventListener(e,update));
 range.addEventListener('input',()=>{if(Number.isFinite(audio.duration)&&audio.duration>0)audio.currentTime=audio.duration*Number(range.value)/1000;update()});update();
 });
});
