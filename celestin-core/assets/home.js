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
  setInterval(()=>{if(!stopped&&!hover&&!focused&&!document.hidden){const max=track.scrollWidth-track.clientWidth;if(max<2)return;if(track.scrollLeft>=max-2)direction=-1;else if(track.scrollLeft<=2)direction=1;step(direction)}},5000);window.addEventListener('resize',sync);sync();
 });
});

document.addEventListener('DOMContentLoaded',()=>{
 const players=[];
 let active=null,frame=0;
 function stop(p){p.audio.pause();try{p.audio.currentTime=0}catch(e){}p.render();}
 function tick(){if(active){active.render();if(!active.audio.paused)frame=requestAnimationFrame(tick)}}
 document.querySelectorAll('.cel-audio-card').forEach(card=>{
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
