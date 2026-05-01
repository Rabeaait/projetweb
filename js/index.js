const backTop = document.getElementById('back-to-top');

window.addEventListener('scroll', function () {
  backTop.classList.toggle('visible', window.scrollY > 200);
});

backTop.addEventListener('click', function (e) {
  e.preventDefault();
  window.scrollTo({ top: 0, behavior: 'smooth' });
});
