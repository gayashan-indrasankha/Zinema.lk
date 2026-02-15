// Simple TikTok-style trailer viewer behaviour:
// - Use IntersectionObserver to autoplay the video that is mostly in view
// - Pause others
// - Allow keyboard Up/Down to scroll to previous/next

function setupTrailerAutoplay(){
  const videos = Array.from(document.querySelectorAll('.trailer'));
  if(!videos.length) return;

  const obs = new IntersectionObserver((entries)=>{
    entries.forEach(entry=>{
      const v = entry.target;
      if(entry.intersectionRatio > 0.6){
        // bring to front and play
        try{ v.play(); }catch(e){}
      } else {
        try{ v.pause(); }catch(e){}
      }
    });
  },{threshold:[0.25,0.5,0.75]});

  videos.forEach(v=> obs.observe(v));

  // Keyboard navigation: Up/Down to scroll to prev/next snap
  document.addEventListener('keydown', (e)=>{
    if(e.key === 'ArrowDown' || e.key === 'PageDown'){
      e.preventDefault();
      window.scrollBy({top: window.innerHeight, behavior:'smooth'});
    } else if(e.key === 'ArrowUp' || e.key === 'PageUp'){
      e.preventDefault();
      window.scrollBy({top: -window.innerHeight, behavior:'smooth'});
    }
  });
}

document.addEventListener('DOMContentLoaded', setupTrailerAutoplay);
