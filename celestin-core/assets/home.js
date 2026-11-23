document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('.cel-story-scroll').forEach(function(el){
    el.addEventListener('wheel',function(e){e.stopPropagation()},{passive:true});
  });
});
