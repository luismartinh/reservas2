// web/js/reservas-public.js

(function() {
  const bgImages = document.querySelectorAll('.dh-bg-img');
  if (!bgImages.length) return;

  let bgIndex = 0;
  bgImages[0].classList.add('active');

  setInterval(() => {
    bgImages[bgIndex].classList.remove('active');
    bgIndex = (bgIndex + 1) % bgImages.length;
    bgImages[bgIndex].classList.add('active');
  }, 8000);
})();
