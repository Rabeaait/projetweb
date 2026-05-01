let selectedRole = 'user';

function selectRole(role) {
  selectedRole = role;
  document.getElementById('role-user').classList.toggle('selected', role === 'user');
  document.getElementById('role-admin').classList.toggle('selected', role === 'admin');
}

async function handleLogin(event) {
  event.preventDefault();

  const email    = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  const btn      = event.target.querySelector('[type="submit"]');

  document.getElementById('login-error').classList.add('hidden');
  document.getElementById('pending-msg').classList.add('hidden');
  btn.disabled    = true;
  btn.textContent = 'Connexion…';

  try {
    const res  = await fetch('api/login.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ email, password, role: selectedRole })
    });
    const data = await res.json();

    if (data.success) {
      window.location.href = data.redirect;
      return;
    }

    if (data.code === 'PENDING') {
      document.getElementById('pending-msg').classList.remove('hidden');
    } else {
      document.getElementById('login-error').classList.remove('hidden');
    }

  } catch (err) {
    document.getElementById('login-error').classList.remove('hidden');
  } finally {
    btn.disabled    = false;
    btn.textContent = 'Se connecter';
  }
}
