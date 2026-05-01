function formatDate(dateStr) {
  if (!dateStr) return '--';
  return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
}

function getStatutLabel(statut) {
  if (statut === 'ouvert')  return 'Inscriptions ouvertes';
  if (statut === 'cloture') return 'Inscriptions clôturées';
  if (statut === 'termine') return 'Tirage terminé';
  return 'En attente';
}

function showToast(id) {
  const el = document.getElementById(id);
  el.classList.remove('hidden');
  setTimeout(() => el.classList.add('hidden'), 3000);
}

async function loadPage() {
  try {
    const res  = await fetch('../api/admin/tirages.php');
    const data = await res.json();
    if (!data.success) return;

    // Tirage actif
    const t = data.actif;
    if (t) {
      document.getElementById('tirage-titre').textContent      = 'Hadj ' + t.annee + ' — Tirage en cours';
      document.getElementById('tirage-sous-titre').textContent = getStatutLabel(t.statut);
      document.getElementById('val-gagnants').textContent      = t.nb_gagnants;
      document.getElementById('val-ouverture').textContent     = formatDate(t.date_ouverture);
      document.getElementById('val-cloture').textContent       = formatDate(t.date_cloture);
      document.getElementById('val-date-tirage').textContent   = formatDate(t.date_tirage);
      document.getElementById('val-statut').textContent        = getStatutLabel(t.statut);
      document.getElementById('val-participants').textContent  =
        parseInt(t.nb_participants || 0).toLocaleString('fr-FR');

      const btnLancer = document.getElementById('btn-lancer');
      if (t.statut === 'termine') {
        btnLancer.disabled    = true;
        btnLancer.textContent = 'Tirage terminé';
      } else {
        btnLancer.disabled    = false;
        btnLancer.textContent = 'Lancer le processus de sélection';
      }
    }

    // Badge demandes en attente
    const badge = document.getElementById('sidebar-count');
    if (badge) {
      badge.textContent   = data.pendingCount || '';
      badge.style.display = data.pendingCount > 0 ? '' : 'none';
    }

    // Historique dynamique
    renderHistory(data.historique || []);

  } catch (e) { console.error(e); }
}

function renderHistory(history) {
  const tbody = document.querySelector('table tbody');
  if (!tbody) return;

  document.querySelectorAll('tr[data-dynamic]').forEach(r => r.remove());

  if (!history.length) return;

  const rows = history.map(t => `
    <tr data-dynamic="1">
      <td><strong>${t.nom}</strong></td>
      <td>${formatDate(t.date_tirage)}</td>
      <td>${formatDate(t.date_ouverture)} – ${formatDate(t.date_cloture)}</td>
      <td>${parseInt(t.nb_participants || 0).toLocaleString('fr-FR')}</td>
      <td>${t.nb_gagnants}</td>
      <td><span class="badge badge-neutral">Terminé</span></td>
    </tr>`
  ).join('');

  tbody.insertAdjacentHTML('afterbegin', rows);
}

/* ── Créer un tirage ── */
function openCreateModal() {
  document.getElementById('create-error').classList.add('hidden');
  document.getElementById('nb-gagnants').value    = '';
  document.getElementById('date-tirage').value    = '';
  document.getElementById('date-ouverture').value = '';
  document.getElementById('date-cloture').value   = '';
  document.getElementById('create-modal').classList.remove('hidden');
}

function closeCreateModal() {
  document.getElementById('create-modal').classList.add('hidden');
}

async function creerTirage() {
  const errEl = document.getElementById('create-error');
  errEl.classList.add('hidden');

  const payload = {
    action:          'create',
    nb_gagnants:     parseInt(document.getElementById('nb-gagnants').value),
    date_tirage:     document.getElementById('date-tirage').value,
    date_ouverture:  document.getElementById('date-ouverture').value,
    date_cloture:    document.getElementById('date-cloture').value
  };

  try {
    const res  = await fetch('../api/admin/tirages.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      closeCreateModal();
      await loadPage();
      showToast('toast-create');
    } else {
      const msg = data.errors ? data.errors.join('\n') : (data.error || 'Erreur inconnue.');
      errEl.textContent = msg;
      errEl.classList.remove('hidden');
    }
  } catch (e) {
    errEl.textContent = 'Erreur réseau.';
    errEl.classList.remove('hidden');
  }
}

/* ── Lancer le tirage ── */
function lancerTirage() {
  document.getElementById('lancer-modal').classList.remove('hidden');
}

function closeLancerModal() {
  document.getElementById('lancer-modal').classList.add('hidden');
}

async function confirmerLancement() {
  const btn = document.querySelector('#lancer-modal .btn-danger');
  btn.disabled    = true;
  btn.textContent = 'Lancement…';

  try {
    const res  = await fetch('../api/admin/tirages.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ action: 'launch' })
    });
    const data = await res.json();

    closeLancerModal();

    if (data.success) {
      await loadPage();
      showToast('toast-lancer');
    } else {
      alert(data.error || 'Erreur lors du lancement.');
    }
  } catch (e) {
    alert('Erreur réseau.');
  } finally {
    btn.disabled    = false;
    btn.textContent = 'Confirmer le lancement';
  }
}

loadPage();
