async function init() {
  // Vérification de la session
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
  document.getElementById('sidebar-initiales').textContent = initials;
  document.getElementById('sidebar-nom').textContent       = (user.nom || '') + ' ' + (user.prenom || '');

  // Charger et afficher les notifications
  try {
    const res  = await fetch('../api/user/notifications.php');
    const data = await res.json();

    const container = document.getElementById('notif-container');

    if (!data.success || !data.notifications.length) {
      container.innerHTML = `
        <div class="card">
          <div class="card-body" style="text-align:center; padding:60px 20px;">
            <p style="font-size:2rem; margin-bottom:12px;">🔔</p>
            <p style="color:var(--text-secondary);">Aucune notification pour l'instant.</p>
          </div>
        </div>`;
    } else {
      container.innerHTML = data.notifications.map(n => `
        <div class="card" style="margin-bottom:16px;">
          <div class="card-body">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
              <div style="font-weight:700; font-size:.95rem;">${n.titre}</div>
              <div style="font-size:.75rem; color:var(--text-secondary);">${n.date_creation}</div>
            </div>
            <div style="font-size:.9rem; color:var(--text-secondary);">${n.message}</div>
          </div>
        </div>`
      ).join('');
    }

    // Marquer toutes comme lues
    await fetch('../api/user/notifications.php', { method: 'POST' });

  } catch (e) {
    document.getElementById('notif-container').innerHTML =
      '<p style="text-align:center; color:var(--text-secondary);">Impossible de charger les notifications.</p>';
  }
}

init();
