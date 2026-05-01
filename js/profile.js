function toggleNotif() {
  document.getElementById('notif-dropdown').classList.toggle('hidden');
}

document.addEventListener('click', function (e) {
  const btn = document.getElementById('notif-btn');
  if (btn && !btn.contains(e.target)) {
    document.getElementById('notif-dropdown').classList.add('hidden');
  }
});

async function loadNotifBell() {
  try {
    const res  = await fetch('../api/user/notifications.php');
    const data = await res.json();
    if (!data.success) return;

    const dot  = document.querySelector('.notif-dot');
    if (dot) dot.style.display = data.unread_count > 0 ? 'block' : 'none';

    const body = document.querySelector('.notif-dropdown-body');
    if (!body) return;

    if (!data.notifications.length) {
      body.innerHTML = '<div class="notif-empty">Aucune notification pour l\'instant.</div>';
    } else {
      body.innerHTML = data.notifications.slice(0, 5).map(n =>
        `<div style="padding:12px 16px; border-bottom:1px solid var(--border); font-size:.85rem;">
          <div style="font-weight:600; margin-bottom:2px;">${n.titre}</div>
          <div style="color:var(--text-secondary);">${n.message}</div>
          <div style="font-size:.75rem; color:var(--text-light); margin-top:4px;">${n.date_creation}</div>
        </div>`
      ).join('') +
      `<div style="padding:10px 16px; text-align:center;">
        <a href="notifications.html" style="font-size:.82rem; color:var(--primary);">Voir toutes les notifications</a>
      </div>`;
    }
  } catch (e) { /* silencieux */ }
}

async function loadProfile() {
  let user;
  try {
    const res  = await fetch('../api/me.php');
    const data = await res.json();
    if (!data.success) { window.location.href = '../login.html'; return; }
    user = data.user;
  } catch (e) {
    window.location.href = '../login.html'; return;
  }

  const initials = (((user.nom || '')[0]) + ((user.prenom || '')[0])).toUpperCase();

  const sidebarInitiales = document.getElementById('sidebar-initiales');
  const sidebarNom       = document.getElementById('sidebar-nom');
  if (sidebarInitiales) sidebarInitiales.textContent = initials;
  if (sidebarNom)       sidebarNom.textContent       = (user.nom || '') + ' ' + (user.prenom || '');

  document.getElementById('avatar-initiales').textContent  = initials;
  document.getElementById('hero-nom-complet').textContent  = (user.nom || '') + ' ' + (user.prenom || '');
  document.getElementById('hero-email').textContent        = user.email || '--';
  document.getElementById('hero-telephone').textContent    = user.telephone || '--';

  const adresseParts = (user.adresse || '').split(',');
  document.getElementById('hero-ville').textContent = adresseParts[adresseParts.length - 1].trim() || '--';

  document.getElementById('p-nin').textContent            = user.nin || '--';
  document.getElementById('p-ddn').textContent            = user.ddn || '--';
  document.getElementById('p-nom').textContent            = user.nom || '--';
  document.getElementById('p-prenom').textContent         = user.prenom || '--';
  document.getElementById('p-nom-pere').textContent       = user.nom_pere || '--';
  document.getElementById('p-nom-grand-pere').textContent = user.nom_grand_pere || '--';
  document.getElementById('p-prenom-mere').textContent    = user.prenom_mere || '--';
  document.getElementById('p-nom-mere').textContent       = user.nom_mere || '--';
  document.getElementById('p-adresse').textContent        = user.adresse || '--';
  document.getElementById('p-email').textContent          = user.email || '--';
  document.getElementById('p-telephone').textContent      = user.telephone || '--';

  await loadNotifBell();
}

loadProfile();
