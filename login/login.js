document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('form-login');
  const emailInput = document.getElementById('email');
  const passwordInput = document.getElementById('password');
  const rememberCheckbox = document.getElementById('remember');
  const submitBtn = document.getElementById('btn-submit');
  const alertBox = document.getElementById('login-alert');
  const emailError = document.getElementById('email-error');
  const passwordError = document.getElementById('password-error');

  const API_LOGIN = '../api/login.php';
  const DASHBOARD_URL = '../dashboard/';

  // Cek sesi yang sedang aktif
  checkCurrentSession();

  // Reset pesan error saat input diubah
  emailInput.addEventListener('input', () => {
    emailInput.classList.remove('is-invalid');
    emailError.classList.remove('is-visible');
    emailError.textContent = '';
  });

  passwordInput.addEventListener('input', () => {
    passwordInput.classList.remove('is-invalid');
    passwordError.classList.remove('is-visible');
    passwordError.textContent = '';
  });

  // Proses Submit
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      clearErrors();

      const email = emailInput.value.trim();
      const password = passwordInput.value;
      const remember = rememberCheckbox ? rememberCheckbox.checked : false;

      let hasError = false;

      if (!email) {
        showFieldError(emailInput, emailError, 'Email wajib diisi.');
        hasError = true;
      } else if (!isValidEmail(email)) {
        showFieldError(emailInput, emailError, 'Format email tidak valid.');
        hasError = true;
      }

      if (!password) {
        showFieldError(passwordInput, passwordError, 'Kata sandi wajib diisi.');
        hasError = true;
      }

      if (hasError) return;

      setLoading(true);

      try {
        const response = await fetch(API_LOGIN, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({ email, password, remember })
        });

        const result = await response.json().catch(() => null);

        if (!response.ok || !result || result.status !== 'success') {
          const errMsg = result?.message || 'Login gagal. Periksa kembali email dan kata sandi Anda.';
          showAlert(errMsg, 'error');
          setLoading(false);
          return;
        }

        const user = result.data?.user;
        if (user) {
          localStorage.setItem('currentUser', JSON.stringify(user));
        }

        showAlert(result.message || 'Login berhasil. Mengalihkan ke dashboard...', 'success');

        setTimeout(() => {
          window.location.href = DASHBOARD_URL;
        }, 700);

      } catch (err) {
        console.error('Fetch error:', err);
        showAlert('Tidak dapat terhubung ke server. Pastikan server lokal aktif.', 'error');
        setLoading(false);
      }
    });
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  function showFieldError(inputEl, errorEl, msg) {
    inputEl.classList.add('is-invalid');
    errorEl.textContent = msg;
    errorEl.classList.add('is-visible');
    inputEl.focus();
  }

  function clearErrors() {
    if (emailInput) emailInput.classList.remove('is-invalid');
    if (passwordInput) passwordInput.classList.remove('is-invalid');
    if (emailError) {
      emailError.classList.remove('is-visible');
      emailError.textContent = '';
    }
    if (passwordError) {
      passwordError.classList.remove('is-visible');
      passwordError.textContent = '';
    }
    if (alertBox) {
      alertBox.className = 'login-alert';
      alertBox.textContent = '';
    }
  }

  function showAlert(message, type = 'error') {
    if (!alertBox) return;
    alertBox.className = `login-alert alert-${type} is-visible`;
    alertBox.textContent = message;
  }

  function setLoading(isLoading) {
    if (!submitBtn) return;
    if (isLoading) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Memproses...';
    } else {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Masuk';
    }
  }

  async function checkCurrentSession() {
    try {
      const response = await fetch(API_LOGIN, {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' }
      });
      if (!response.ok) return;

      const data = await response.json().catch(() => null);
      if (data && data.authenticated && data.data?.user) {
        const user = data.data.user;
        const roleLabel = user.role ? ` (${user.role})` : '';
        
        if (alertBox) {
          alertBox.className = 'login-alert alert-info is-visible';
          alertBox.innerHTML = `
            <div>Anda sedang login sebagai <strong>${escapeHtml(user.nama)}</strong>${roleLabel}.</div>
            <div style="margin-top: 0.4rem;">
              <a href="${DASHBOARD_URL}" style="font-weight: 600; text-decoration: underline; margin-right: 0.75rem;">Buka Dashboard</a>
              <button type="button" id="btn-quick-logout" style="background:none; border:none; color:inherit; text-decoration:underline; cursor:pointer; padding:0; font-size:inherit;">Logout</button>
            </div>
          `;

          const logoutBtn = alertBox.querySelector('#btn-quick-logout');
          if (logoutBtn) {
            logoutBtn.addEventListener('click', async () => {
              await fetch(API_LOGIN, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'logout' })
              });
              localStorage.removeItem('currentUser');
              window.location.reload();
            });
          }
        }
      }
    } catch {
      // Abaikan jika server offline saat cek sesi
    }
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
});
