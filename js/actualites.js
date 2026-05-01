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

    const dot = document.querySelector('.notif-dot');
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
        <a href="notifications.html" style="font-size:.82rem; color:var(--primary);">Voir toutes</a>
      </div>`;
    }
  } catch (e) { /* silencieux */ }
}

async function init() {
  try {
    const res  = await fetch('../api/me.php');
    const data = await res.json();
    if (!data.success) { window.location.href = '../login.html'; return; }

    const u        = data.user;
    const initials = (((u.nom || '')[0]) + ((u.prenom || '')[0])).toUpperCase();
    document.getElementById('sidebar-initiales').textContent = initials;
    document.getElementById('sidebar-nom').textContent       = (u.nom || '') + ' ' + (u.prenom || '');

    await loadNotifBell();
  } catch (e) {
    window.location.href = '../login.html';
  }
}

init();
