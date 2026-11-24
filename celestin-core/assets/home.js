document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('.cel-story-scroll').forEach(function(el){
    el.addEventListener('wheel',function(e){e.stopPropagation()},{passive:true});
  });
});

document.addEventListener('DOMContentLoaded',()=>{
 document.querySelectorAll('.cel-news-carousel').forEach(root=>{
  const track=root.querySelector('.cel-news-track'),prev=root.querySelector('.cel-news-prev'),next=root.querySelector('.cel-news-next'),pause=root.querySelector('.cel-news-pause');
  if(!track||!prev||!next||!pause)return;
  let stopped=matchMedia('(prefers-reduced-motion: reduce)').matches,hover=false,focused=false;
  function step(d){const card=track.querySelector('.cel-card');if(!card)return;const max=track.scrollWidth-track.clientWidth;if(max<2)return;const gap=parseFloat(getComputedStyle(track).columnGap)||0;const target=d>0&&track.scrollLeft>=max-2?0:d<0&&track.scrollLeft<=2?max:track.scrollLeft+d*(card.offsetWidth+gap);track.scrollTo({left:target,behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});}
  function sync(){pause.textContent=stopped?'Lecture':'Pause';pause.setAttribute('aria-label',stopped?'Reprendre le défilement':'Mettre le défilement en pause');const overflow=track.scrollWidth>track.clientWidth+2;root.querySelector('.cel-news-controls').hidden=!overflow;}
  prev.onclick=()=>step(-1);next.onclick=()=>step(1);pause.onclick=()=>{stopped=!stopped;sync()};
  root.addEventListener('mouseenter',()=>hover=true);root.addEventListener('mouseleave',()=>hover=false);root.addEventListener('focusin',()=>focused=true);root.addEventListener('focusout',e=>{if(!root.contains(e.relatedTarget))focused=false});
  setInterval(()=>{if(!stopped&&!hover&&!focused&&!document.hidden)step(1)},5000);window.addEventListener('resize',sync);sync();
 });
});
