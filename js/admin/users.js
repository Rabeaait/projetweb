let allUsers = [];
let targetId = null;

function statusBadge(statut) {
  if (statut === 'actif')   return '<span class="badge badge-success">✅ Actif</span>';
  if (statut === 'attente') return '<span class="badge badge-warning">⏳ En attente</span>';
  if (statut === 'bloque')  return '<span class="badge badge-danger">🚫 Bloqué</span>';
  return '';
}

function actionBtns(u) {
  if (u.statut === 'actif') return `
    <div style="display:flex; gap:6px; flex-wrap:wrap;">
      <button class="btn btn-warning btn-sm" onclick="openBlockModal(${u.id})">🔒 Bloquer</button>
      <button class="btn btn-danger btn-sm"  onclick="openDeleteModal(${u.id})">🗑️ Supprimer</button>
    </div>`;
  if (u.statut === 'bloque') return `
    <div style="display:flex; gap:6px;">
      <button class="btn btn-success btn-sm" onclick="unblockUser(${u.id})">🔓 Débloquer</button>
      <button class="btn btn-danger btn-sm"  onclick="openDeleteModal(${u.id})">🗑️ Supprimer</button>
    </div>`;
  return `
    <div style="display:flex; gap:6px;">
      <button class="btn btn-danger btn-sm" onclick="openDeleteModal(${u.id})">🗑️ Supprimer</button>
    </div>`;
}

function renderTable(users) {
  const tbody = document.getElementById('users-tbody');
  if (!users.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:var(--text-secondary); padding:24px 0;">Aucun utilisateur trouvé.</td></tr>';
    return;
  }
  const initials = u => (((u.nom || '')[0]) + ((u.prenom || '')[0])).toUpperCase();
  tbody.innerHTML = users.map(u => `
    <tr>
      <td>
        <div class="user-cell">
          <div class="user-avatar-sm">${initials(u)}</div>
          <div>
            <div class="name">${u.nom || ''} ${u.prenom || ''}</div>
            <div class="email">${u.email || ''}</div>
          </div>
        </div>
      </td>
      <td style="font-family:monospace; font-size:.82rem;">${u.nin || '--'}</td>
      <td>${u.ddn || '--'}</td>
      <td>${u.telephone || '--'}</td>
      <td>${statusBadge(u.statut)}</td>
      <td>${u.date_validation || u.date_soumission || '--'}</td>
      <td>${actionBtns(u)}</td>
    </tr>`
  ).join('');
}

function updateStats(stats) {
  document.getElementById('stat-total').textContent   = stats.total;
  document.getElementById('stat-actifs').textContent  = stats.actifs;
  document.getElementById('stat-attente').textContent = stats.attente;
  document.getElementById('stat-bloques').textContent = stats.bloques;
  document.getElementById('topbar-subtitle').textContent =
    stats.total + ' utilisateur' + (stats.total > 1 ? 's' : '') + ' au total';
}

async function loadPage(search = '') {
  try {
    const url  = '../api/admin/users.php' + (search ? '?search=' + encodeURIComponent(search) : '');
    const res  = await fetch(url);
    const data = await res.json();
    if (!data.success) return;

    allUsers = data.users;
    renderTable(allUsers);
    updateStats(data.stats);

    // Badge demandes en attente dans la sidebar
    const res2  = await fetch('../api/admin/demandes.php');
    const data2 = await res2.json();
    const badge = document.getElementById('sidebar-count');
    if (badge && data2.success) {
      badge.textContent   = data2.count || '';
      badge.style.display = data2.count > 0 ? '' : 'none';
    }

  } catch (e) { console.error(e); }
}

// Recherche en temps réel
document.getElementById('search-input').addEventListener('input', function () {
  loadPage(this.value.trim());
});

function showToast(id) {
  const el = document.getElementById(id);
  el.classList.remove('hidden');
  setTimeout(() => el.classList.add('hidden'), 3000);
}

async function postAction(action, id) {
  const res  = await fetch('../api/admin/users.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ action, id })
  });
  return await res.json();
}

/* ── Bloquer ── */
function openBlockModal(id) {
  targetId = id;
  const u  = allUsers.find(u => u.id === id);
  document.getElementById('block-user-name').textContent = (u.nom || '') + ' ' + (u.prenom || '');
  document.getElementById('block-modal').classList.remove('hidden');
}

function closeBlockModal() {
  document.getElementById('block-modal').classList.add('hidden');
}

async function confirmBlock() {
  const data = await postAction('block', targetId);
  closeBlockModal();
  if (data.success) { await loadPage(); showToast('toast-block'); }
  else alert(data.error);
}

/* ── Débloquer ── */
async function unblockUser(id) {
  const data = await postAction('unblock', id);
  if (data.success) { await loadPage(); showToast('toast-unblock'); }
  else alert(data.error);
}

/* ── Supprimer ── */
function openDeleteModal(id) {
  targetId = id;
  const u  = allUsers.find(u => u.id === id);
  document.getElementById('delete-user-name').textContent = (u.nom || '') + ' ' + (u.prenom || '');
  document.getElementById('delete-modal').classList.remove('hidden');
}

function closeDeleteModal() {
  document.getElementById('delete-modal').classList.add('hidden');
}

async function confirmDelete() {
  const data = await postAction('delete', targetId);
  closeDeleteModal();
  if (data.success) { await loadPage(); showToast('toast-delete'); }
  else alert(data.error);
}

loadPage();
