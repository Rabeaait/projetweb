// Étape actuelle du formulaire (commence à 1)
let currentStep = 1;
// Nombre total d'étapes
const totalSteps = 4;

/* ── Afficher un message d'erreur sous un champ ── */
function showError(fieldId, message) {
  const field = document.getElementById(fieldId);
  // Ajoute la classe CSS qui met le champ en rouge
  field.classList.add('is-invalid');
  field.classList.remove('is-valid');
  // Crée ou réutilise le message d'erreur sous le champ
  let err = field.parentElement.querySelector('.form-error');
  if (!err) {
    err = document.createElement('span');
    err.className = 'form-error';
    field.parentElement.appendChild(err);
  }
  err.textContent = message;
  err.style.display = 'block';
}

/* ── Effacer le message d'erreur d'un champ ── */
function clearError(fieldId) {
  const field = document.getElementById(fieldId);
  // Passe le champ en vert (valide)
  field.classList.remove('is-invalid');
  field.classList.add('is-valid');
  const err = field.parentElement.querySelector('.form-error');
  if (err) err.style.display = 'none';
}

// Raccourci pour récupérer la valeur d'un champ (sans espaces inutiles)
function val(id) { return document.getElementById(id).value.trim(); }

/* ── Validation de l'étape 1 : Identité ── */
function validateStep1() {
  let ok = true;

  // Le NIN doit contenir exactement 18 chiffres
  if (!/^\d{18}$/.test(val('nin'))) {
    showError('nin', 'Le NIN doit contenir exactement 18 chiffres.');
    ok = false;
  } else clearError('nin');

  // Le nom doit avoir au moins 2 caractères
  if (val('nom').length < 2) {
    showError('nom', 'Le nom est obligatoire.');
    ok = false;
  } else clearError('nom');

  // Le prénom doit avoir au moins 2 caractères
  if (val('prenom').length < 2) {
    showError('prenom', 'Le prénom est obligatoire.');
    ok = false;
  } else clearError('prenom');

  // La date de naissance est obligatoire et ne peut pas être dans le futur
const ddn = val('ddn');
if (!ddn) {
  showError('ddn', 'La date de naissance est obligatoire.');
  ok = false;
} else {
  const dateNaissance = new Date(ddn);
  const aujourdhui = new Date();

  // Calcul de l'âge
  const age = aujourdhui.getFullYear() - dateNaissance.getFullYear();
  const moisPasse = aujourdhui.getMonth() > dateNaissance.getMonth() ||
    (aujourdhui.getMonth() === dateNaissance.getMonth() &&
     aujourdhui.getDate() >= dateNaissance.getDate());
  const ageReel = moisPasse ? age : age - 1;

  if (dateNaissance >= aujourdhui) {
    showError('ddn', 'La date de naissance ne peut pas être dans le futur.');
    ok = false;
  } else if (ageReel < 18) {
    showError('ddn', 'Vous devez avoir au moins 18 ans.');
    ok = false;
  } else {
    clearError('ddn');
  }
}

return ok;
}

/* ── Validation de l'étape 2 : Famille ── */
function validateStep2() {
  let ok = true;
  // Liste des champs obligatoires avec leurs messages d'erreur
  const fields = [
    ['nom_pere',       'Le prénom du père est obligatoire.'],
    ['nom_grand_pere', 'Le prénom du grand-père est obligatoire.'],
    ['prenom_mere',    'Le prénom de la mère est obligatoire.'],
    ['nom_mere',       'Le nom de famille de la mère est obligatoire.'],
  ];
  // Vérifie chaque champ en boucle
  fields.forEach(([id, msg]) => {
    if (val(id).length < 2) { showError(id, msg); ok = false; }
    else clearError(id);
  });
  return ok;
}

/* ── Validation de l'étape 3 : Coordonnées ── */
function validateStep3() {
  let ok = true;

  if (val('adresse').length < 5) {
    showError('adresse', "L'adresse est obligatoire.");
    ok = false;
  } else clearError('adresse');

  // Vérifie le format de l'email avec une expression régulière
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val('email_reg'))) {
    showError('email_reg', 'Adresse email non valide.');
    ok = false;
  } else clearError('email_reg');

  // Le téléphone doit commencer par 05, 06 ou 07 suivi de 8 chiffres
  if (!/^0[567]\d{8}$/.test(val('telephone').replace(/\s/g, ''))) {
    showError('telephone', 'Numéro invalide. Format attendu : 05XXXXXXXX / 06XXXXXXXX / 07XXXXXXXX.');
    ok = false;
  } else clearError('telephone');

  return ok;
}

/* ── Validation de l'étape 4 : Sécurité ── */
function validateStep4() {
  let ok = true;

  // Le mot de passe doit avoir au moins 8 caractères
  const pwd = document.getElementById('password_reg').value;
  if (pwd.length < 8) {
    showError('password_reg', 'Le mot de passe doit contenir au moins 8 caractères.');
    ok = false;
  } else clearError('password_reg');

  // Les deux mots de passe doivent être identiques
  const confirm = document.getElementById('password_confirm').value;
  if (confirm !== pwd) {
    showError('password_confirm', 'Les mots de passe ne correspondent pas.');
    ok = false;
  } else if (confirm.length > 0) clearError('password_confirm');

  // L'utilisateur doit accepter les conditions d'utilisation
  const cgu = document.querySelector('[name="accept_terms"]');
  if (!cgu.checked) {
    let err = cgu.closest('.form-group').querySelector('.form-error');
    if (!err) {
      err = document.createElement('span');
      err.className = 'form-error';
      cgu.closest('.form-group').appendChild(err);
    }
    err.textContent = "Vous devez accepter les conditions d'utilisation.";
    err.style.display = 'block';
    ok = false;
  } else {
    const err = cgu.closest('.form-group').querySelector('.form-error');
    if (err) err.style.display = 'none';
  }

  return ok;
}

// Tableau des fonctions de validation par étape (index 0 inutilisé)
const validators = [null, validateStep1, validateStep2, validateStep3, validateStep4];

/* ── Mise à jour de l'interface selon l'étape courante ── */
function updateUI() {
  // Cache tous les panneaux et affiche uniquement celui de l'étape courante
  document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('panel-' + currentStep).classList.add('active');
  // Met à jour le compteur "Étape X sur 4"
  document.getElementById('step-counter').textContent = 'Étape ' + currentStep + ' sur ' + totalSteps;
  // Affiche/cache les boutons selon l'étape
  document.getElementById('btn-prev').classList.toggle('hidden', currentStep === 1);
  document.getElementById('btn-next').classList.toggle('hidden', currentStep === totalSteps);
  document.getElementById('btn-submit').classList.toggle('hidden', currentStep !== totalSteps);
}

/* ── Passer à l'étape suivante ── */
function nextStep() {
  // Valide l'étape courante avant de continuer
  if (!validators[currentStep]()) return;
  if (currentStep < totalSteps) { currentStep++; updateUI(); }
}

/* ── Revenir à l'étape précédente ── */
function prevStep() {
  if (currentStep > 1) { currentStep--; updateUI(); }
}

/* ── Soumission finale du formulaire (envoi à l'API PHP) ── */
async function submitForm() {
  if (!validateStep4()) return;

  const btn = document.getElementById('btn-submit');
  btn.disabled    = true;
  btn.textContent = 'Envoi…';

  const payload = {
    nin:            val('nin'),
    nom:            val('nom'),
    prenom:         val('prenom'),
    ddn:            val('ddn'),
    nom_pere:       val('nom_pere'),
    nom_grand_pere: val('nom_grand_pere'),
    prenom_mere:    val('prenom_mere'),
    nom_mere:       val('nom_mere'),
    adresse:        val('adresse'),
    email:          val('email_reg'),
    telephone:      val('telephone'),
    password:       document.getElementById('password_reg').value
  };

  try {
    const res  = await fetch('api/register.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      document.getElementById('card-footer').style.display = 'none';
      document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
      document.getElementById('panel-success').classList.add('active');
      document.querySelector('.register-card-header').style.display = 'none';
      return;
    }

    // Afficher les erreurs retournées par le serveur
    if (data.errors) {
      Object.entries(data.errors).forEach(([field, msg]) => {
        const idMap = { email: 'email_reg', password: 'password_reg' };
        showError(idMap[field] || field, msg);
      });
    } else {
      alert(data.error || "Une erreur s'est produite.");
    }

  } catch (err) {
    alert("Erreur réseau. Vérifiez votre connexion.");
  } finally {
    btn.disabled    = false;
    btn.textContent = '✅ Soumettre l\'inscription';
  }
}

/* ── Vérification en temps réel de la confirmation du mot de passe ── */
document.getElementById('password_confirm').addEventListener('input', function () {
  if (this.value && this.value !== document.getElementById('password_reg').value) {
    showError('password_confirm', 'Les mots de passe ne correspondent pas.');
  } else if (this.value) {
    clearError('password_confirm');
  }
});
