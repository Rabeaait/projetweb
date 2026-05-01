let pendingList = [];
let selectedId  = null;

function initials(nom, prenom) {
  return (((nom || '')[0]) + ((prenom || '')[0])).toUpperCase();
}

function showToast(id) {
  const el = document.getElementById(id);
  el.classList.remove('hidden');
  setTimeout(() => el.classList.add('hidden'), 3000);
}

async function postAction(action, id) {
  const res  = await fetch('../api/admin/demandes.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ action, id })
  });
  return await res.json();
}

function renderTable(list) {
  pendingList = list;
  const tbody        = document.getElementById('demandes-tbody');
  const countBadge   = document.getElementById('count-badge');
  const sidebarCount = document.getElementById('sidebar-count');
  const emptyState   = document.getElementById('empty-state');
  const demandesCard = document.getElementById('demandes-card');

  countBadge.textContent   = list.length + ' en attente';
  sidebarCount.textContent = list.length || '';
  sidebarCount.style.display = list.length ? '' : 'none';

  if (!list.length) {
    emptyState.classList.remove('hidden');
    demandesCard.classList.add('hidden');
    return;
  }

  emptyState.classList.add('hidden');
  demandesCard.classList.remove('hidden');

  tbody.innerHTML = list.map(d => `
    <tr>
      <td>
        <div style="display:flex; align-items:center; gap:10px;">
          <div style="width:34px; height:34px;
                      background:linear-gradient(135deg,var(--primary-dark),var(--primary));
                      border-radius:50%; display:flex; align-items:center; justify-content:center;
                      color:white; font-size:.75rem; font-weight:700; flex-shrink:0;">
            ${initials(d.nom, d.prenom)}
          </div>
          <div>
            <div style="font-weight:600; font-size:.9rem;">${d.nom} ${d.prenom}</div>
            <div style="font-size:.75rem; color:var(--text-secondary);">${d.email}</div>
          </div>
        </div>
      </td>
      <td style="font-family:monospace; font-size:.82rem;">${d.nin || '--'}</td>
      <td>${d.email || '--'}</td>
      <td>${d.telephone || '--'}</td>
      <td>${d.date_soumission || '--'}</td>
      <td>
        <div style="display:flex; gap:8px;">
          <button class="btn btn-ghost btn-sm"     onclick="openDetail(${d.id})">Voir</button>
          <button class="btn btn-success btn-sm"   onclick="validateDirect(${d.id})">✅ Valider</button>
          <button class="btn btn-secondary btn-sm" onclick="ignoreDirect(${d.id})">Ignorer</button>
          <button class="btn btn-danger btn-sm"    onclick="blockDirect(${d.id})">🚫 Bloquer</button>
        </div>
      </td>
    </tr>
  `).join('');
}

async function loadPage() {
  try {
    const res  = await fetch('../api/admin/demandes.php');
    const data = await res.json();
    if (!data.success) return;
    renderTable(data.demandes || []);
  } catch (e) { console.error(e); }
}

/* ── Détail ── */
function openDetail(id) {
  selectedId = id;
  const d = pendingList.find(x => x.id === id);
  if (!d) return;

  document.getElementById('detail-body').innerHTML = `
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; font-size:.875rem;">
      <div><div style="color:var(--text-secondary); font-size:.75rem; margin-bottom:2px;">NOM</div><strong>${d.nom}</strong></div>
      <div><div style="color:var(--text-secondary); font-size:.75rem; margin-bottom:2px;">PRÉNOM</div><strong>${d.prenom}</strong></div>
      <div><div style="color:var(--text-secondary); font-size:.75rem; margin-bottom:2px;">NIN</div><span style="font-family:monospace;">${d.nin || '--'}</span></div>
      <div><div style="color:var(--text-secondary); font-size:.75rem; margin-bottom:2px;">DATE DE NAISSANCE</div>${d.ddn || '--'}</div>
      <div><div style="color:var(--text-secondary); font-size:.75rem; margin-bottom:2px;">PRÉNOM PÈRE</div>${d.nom_pere || '--'}</div>
      <div><div style="color:var(--text-secondary); font-size:.75rem; margin-bottom:2px;">PRÉNOM GRAND-PÈRE</div>${d.nom_grand_pere || '--'}</div>
      <div><div style="color:var(--text-secondary); font-size:.75rem; margin-bottom:2px;">PRÉNOM MÈRE</div>${d.prenom_mere || '--'}</div>
      <div><div style="color:var(--text-secondary); font-size:.75rem; margin-bottom:2px;">NOM MÈRE</div>${d.nom_mere || '--'}</div>
      <div style="grid-column:span 2;"><div style="color:var(--text-secondary); font-size:.75rem; margin-bottom:2px;">ADRESSE</div>${d.adresse || '--'}</div>
      <div><div style="color:var(--text-secondary); font-size:.75rem; margin-bottom:2px;">EMAIL</div>${d.email || '--'}</div>
      <div><div style="color:var(--text-secondary); font-size:.75rem; margin-bottom:2px;">TÉLÉPHONE</div>${d.telephone || '--'}</div>
    </div>
  `;

  document.getElementById('detail-validate-btn').onclick = () => { closeDetail(); validateDirect(id); };
  document.getElementById('detail-ignore-btn').onclick   = () => { closeDetail(); ignoreDirect(id); };
  document.getElementById('detail-block-btn').onclick    = () => { closeDetail(); openBlockModal(id); };
  document.getElementById('detail-modal').classList.remove('hidden');
}

function closeDetail() {
  document.getElementById('detail-modal').classList.add('hidden');
}

/* ── Valider ── */
async function validateDirect(id) {
  const data = await postAction('validate', id);
  if (data.success) { await loadPage(); showToast('toast-validate'); }
  else alert(data.error);
}

/* ── Ignorer ── */
async function ignoreDirect(id) {
  const data = await postAction('ignore', id);
  if (data.success) { await loadPage(); showToast('toast-ignore'); }
  else alert(data.error);
}

/* ── Bloquer ── */
function openBlockModal(id) {
  selectedId = id;
  document.getElementById('block-modal').classList.remove('hidden');
}

function closeBlockModal() {
  document.getElementById('block-modal').classList.add('hidden');
}

function blockDirect(id) {
  openBlockModal(id);
}

async function confirmBlock() {
  const data = await postAction('block', selectedId);
  closeBlockModal();
  if (data.success) { await loadPage(); showToast('toast-block'); }
  else alert(data.error);
}

loadPage();
