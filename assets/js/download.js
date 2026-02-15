// Countdown for download page. Unblocks the download link after the timer.
document.addEventListener('DOMContentLoaded', ()=>{
  const countdownEl = document.getElementById('countdown');
  const link = document.getElementById('downloadLink');
  if(!countdownEl || !link) return;
  let t = parseInt(countdownEl.textContent, 10) || 5;
  const params = new URLSearchParams(location.search);
  const file = params.get('file');
  const target = file ? '/uploads/' + encodeURIComponent(file) : '#';

  const iv = setInterval(()=>{
    t--;
    countdownEl.textContent = t;
    if(t <= 0){
      clearInterval(iv);
      link.href = target;
      link.classList.remove('opacity-50');
      link.classList.add('opacity-100');
      link.classList.remove('pointer-events-none');
      link.textContent = 'Click to download';
    }
  }, 1000);
});
