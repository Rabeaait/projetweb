function toggleResult(header) {
  const body   = header.nextElementSibling;
  const icon   = header.querySelector('.toggle-icon');
  const isOpen = body.classList.contains('open');

  document.querySelectorAll('.lottery-result-body').forEach(b => b.classList.remove('open'));
  document.querySelectorAll('.toggle-icon').forEach(i => i.textContent = '▼');

  if (!isOpen) {
    body.classList.add('open');
    icon.textContent = '▲';
  }
}

document.getElementById('year-search').addEventListener('input', function () {
  const q = this.value.trim();
  let found = 0;
  document.querySelectorAll('.lottery-result-card').forEach(card => {
    const show = !q || card.dataset.year.includes(q);
    card.style.display = show ? '' : 'none';
    if (show) found++;
  });
  document.getElementById('no-results-msg').style.display = (found === 0 && q) ? '' : 'none';
});
