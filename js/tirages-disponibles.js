let currentUser  = null;
let activeTirage = null;

/* ── Notification bell ── */
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
  } catch (e) { /* silencieux */ }
}

/* ── Vérification éligibilité ── */
function checkEligibility(tirage, participe) {
  const block = document.getElementById('eligibility-block');
  const title = document.getElementById('eligibility-title');
  const list  = document.getElementById('eligibility-list');
  const btn   = document.getElementById('join-btn');
  const badge = document.getElementById('statut-badge');

  if (participe) {
    badge.className = 'badge badge-success';
    badge.textContent = '✅ Inscrit';
    btn.disabled = true;
    btn.textContent = '✅ Déjà inscrit';
    block.style.cssText = 'margin-top:16px; padding:14px; border-radius:var(--radius); border:1px solid var(--success); background:#f0faf4;';
    title.style.color = 'var(--success)';
    title.textContent = 'Vous êtes déjà inscrit à ce tirage.';
    list.innerHTML = '';
    return;
  }

  if (!tirage || tirage.statut === 'termine') {
    btn.disabled = true;
    block.style.cssText = 'margin-top:16px; padding:14px; border-radius:var(--radius); border:1px solid var(--danger); background:#fff5f5;';
    title.style.color = 'var(--danger)';
    title.textContent = '❌ Ce tirage est terminé.';
    list.innerHTML = '';
    return;
  }

  const today = new Date();
  const ouv   = new Date(tirage.date_ouverture);
  const clot  = new Date(tirage.date_cloture);
  const isOpen = today >= ouv && today <= clot;

  const ddn     = currentUser.ddn;
  const birth   = ddn ? new Date(ddn) : null;
  let age       = 0;
  if (birth) {
    age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
  }
  const isAdult = age >= 18;
  const allOk   = isOpen && isAdult;

  const checks = [
    { ok: isOpen,   txt: 'Inscriptions ouvertes' },
    { ok: isAdult,  txt: 'Âge ≥ 18 ans'          }
  ];

  if (allOk) {
    block.style.cssText = 'margin-top:16px; padding:14px; border-radius:var(--radius); border:1px solid var(--success); background:#f0faf4;';
    title.style.color = 'var(--success)';
    title.textContent = '✅ Vous êtes éligible à ce tirage.';
    btn.disabled = false;
  } else {
    block.style.cssText = 'margin-top:16px; padding:14px; border-radius:var(--radius); border:1px solid var(--danger); background:#fff5f5;';
    title.style.color = 'var(--danger)';
    title.textContent = '❌ Vous n\'êtes pas éligible à ce tirage.';
    btn.disabled      = true;
    btn.style.opacity = '0.5';
    btn.style.cursor  = 'not-allowed';
  }

  list.innerHTML = checks.map(c =>
    `<li style="font-size:.825rem; color:${c.ok ? 'var(--success)' : 'var(--danger)'}; padding:2px 0;">
      ${c.ok ? '✅' : '❌'} ${c.txt}
    </li>`
  ).join('');
}

/* ── Historique participations ── */
function renderPastParticipations(participations) {
  const tbody = document.getElementById('participations-tbody');
  if (!participations.length) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:var(--text-secondary); padding:24px 0;">Aucune participation passée.</td></tr>';
    return;
  }
  tbody.innerHTML = participations.map(p => {
    const badge = p.resultat === 'gagnant'
      ? '<span class="badge badge-success">🏆 Sélectionné</span>'
      : p.resultat === 'perdu'
        ? '<span class="badge badge-neutral">Non sélectionné</span>'
        : '<span class="badge badge-warning">En attente</span>';
    return `<tr>
      <td><strong>${p.tirage_nom || 'Tirage'}</strong></td>
      <td>${p.date_inscription}</td>
      <td>${p.date_tirage || '--'}</td>
      <td><span class="badge badge-neutral">Terminé</span></td>
      <td>${badge}</td>
    </tr>`;
  }).join('');
}

/* ── Modale de confirmation ── */
function confirmJoin() {
  document.getElementById('confirm-modal').classList.remove('hidden');
}

function closeModal() {
  document.getElementById('confirm-modal').classList.add('hidden');
}

async function joinLottery() {
  const btn = document.querySelector('#confirm-modal .btn-success');
  btn.disabled    = true;
  btn.textContent = 'Inscription…';

  try {
    const res  = await fetch('../api/user/tirage.php', { method: 'POST' });
    const data = await res.json();

    closeModal();

    if (data.success) {
      const toast = document.getElementById('join-success');
      toast.classList.remove('hidden');
      setTimeout(() => toast.classList.add('hidden'), 3500);
      await init();
    } else {
      alert(data.error || "Impossible de s'inscrire.");
    }
  } catch (err) {
    alert("Erreur réseau.");
  } finally {
    btn.disabled    = false;
    btn.textContent = '✅ Confirmer l\'inscription';
  }
}

/* ── Initialisation ── */
async function init() {
  try {
    // Vérification de la session
    const meRes  = await fetch('../api/me.php');
    const meData = await meRes.json();
    if (!meData.success) { window.location.href = '../login.html'; return; }
    currentUser = meData.user;

    const initials = (((currentUser.nom || '')[0]) + ((currentUser.prenom || '')[0])).toUpperCase();
    document.getElementById('user-avatar').textContent = initials;
    document.getElementById('user-name').textContent   = (currentUser.nom || '') + ' ' + (currentUser.prenom || '');

    // Données du tirage
    const res  = await fetch('../api/user/tirage.php');
    const data = await res.json();

    if (!data.success) return;

    activeTirage = data.tirage;

    if (activeTirage) {
      document.getElementById('nb-participants').textContent =
        parseInt(activeTirage.nb_participants || 0).toLocaleString('fr-FR');
    }

    checkEligibility(activeTirage, data.participe);
    renderPastParticipations(data.participations || []);
    await loadNotifBell();

  } catch (e) {
    window.location.href = '../login.html';
  }
}

init();
