function renderLogin(container) {
  const remembered = Auth.getRememberedUser();

  container.innerHTML = `
    <div class="min-h-screen flex items-center justify-center -mt-12">
      <div class="w-full max-w-sm px-6">
        <div class="text-center mb-8">
          <img src="icons/icon.svg" alt="AVEMER" class="w-20 h-20 mx-auto mb-4">
          <h1 class="text-2xl font-bold text-primary-500">AVEMER</h1>
          <p class="text-gray-500 text-sm mt-1">Portal de Alumnos</p>
        </div>

        ${remembered ? `
        <!-- Welcome back (remembered user) -->
        <div id="welcomeView">
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 text-center mb-4">
            <div class="w-20 h-20 rounded-full bg-primary-100 mx-auto mb-3 flex items-center justify-center overflow-hidden border-4 border-primary-200">
              ${remembered.foto
                ? `<img src="data:image/jpeg;base64,${remembered.foto}" alt="Foto" class="w-full h-full object-cover">`
                : '<i class="fa fa-user text-4xl text-primary-400"></i>'}
            </div>
            <p class="text-xl font-semibold text-gray-800">Hola, ${remembered.nombre}!</p>
            <p class="text-sm text-gray-400 mt-1">${remembered.email}</p>
          </div>
          <form id="welcomeForm" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
              <div class="relative">
                <input type="password" id="welcomePassword" required autocomplete="current-password"
                  class="w-full px-4 py-2.5 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-sm"
                  placeholder="••••••••">
                <button type="button" id="toggleWelcomePassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                  <i class="fa fa-eye"></i>
                </button>
              </div>
            </div>
            <button type="submit" id="welcomeBtn"
              class="w-full bg-primary-500 text-white font-semibold py-2.5 px-4 rounded-lg hover:bg-primary-700 transition text-sm">
              <i class="fa fa-sign-in-alt mr-2"></i>Ingresar
            </button>
            <p id="welcomeError" class="text-red-500 text-sm text-center hidden"></p>
            <div class="text-center">
              <button type="button" id="switchToFull" class="text-xs text-gray-500 hover:text-gray-700 underline">
                ¿No eres ${remembered.nombre}?
              </button>
            </div>
            <div class="text-center">
              <button type="button" id="forgotLinkWelcome" class="text-xs text-primary-500 hover:text-primary-700 underline">
                ¿Olvidaste tu contraseña?
              </button>
            </div>
          </form>
        </div>
        ` : ''}

        <!-- Full login form -->
        <form id="loginForm" class="space-y-4 ${remembered ? 'hidden' : ''}">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
            <input type="email" id="loginEmail" required autocomplete="email"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-sm"
              placeholder="correo@ejemplo.com">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
            <div class="relative">
              <input type="password" id="loginPassword" required autocomplete="current-password"
                class="w-full px-4 py-2.5 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-sm"
                placeholder="••••••••">
              <button type="button" id="togglePassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                <i class="fa fa-eye"></i>
              </button>
            </div>
          </div>
          <div class="flex items-center">
            <input type="checkbox" id="loginRemember"
              class="w-4 h-4 text-primary-500 border-gray-300 rounded focus:ring-primary-500">
            <label for="loginRemember" class="ml-2 text-sm text-gray-600">Recordarme</label>
          </div>
          <button type="submit" id="loginBtn"
            class="w-full bg-primary-500 text-white font-semibold py-2.5 px-4 rounded-lg hover:bg-primary-700 transition text-sm">
            <i class="fa fa-sign-in-alt mr-2"></i>Ingresar
          </button>
          <p id="loginError" class="text-red-500 text-sm text-center hidden"></p>
          <div class="text-center">
            <button type="button" id="forgotLink" class="text-xs text-primary-500 hover:text-primary-700 underline">
              ¿Olvidaste tu contraseña?
            </button>
          </div>
        </form>

        <!-- Forgot password form -->
        <form id="forgotForm" class="space-y-4 hidden">
          <p class="text-sm text-gray-600 text-center">Ingresa tu correo y te enviaremos un código de verificación.</p>
          <div>
            <input type="email" id="forgotEmail" required autocomplete="email"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-sm"
              placeholder="correo@ejemplo.com">
          </div>
          <button type="submit" id="forgotBtn"
            class="w-full bg-primary-500 text-white font-semibold py-2.5 px-4 rounded-lg hover:bg-primary-700 transition text-sm">
            <i class="fa fa-paper-plane mr-2"></i>Enviar código
          </button>
          <p id="forgotError" class="text-red-500 text-sm text-center hidden"></p>
          <p id="forgotSuccess" class="text-green-600 text-sm text-center hidden"></p>
          <div class="text-center">
            <button type="button" id="backToLogin" class="text-xs text-gray-500 hover:text-gray-700 underline">
              Volver al inicio de sesión
            </button>
          </div>
        </form>

        <!-- Reset password forms (two steps) -->
        <div id="resetContainer" class="hidden">
          <!-- Step 1: OTP verification -->
          <form id="otpForm" class="space-y-4">
            <p class="text-sm text-gray-600 text-center" id="otpMessage">Revisa tu correo e ingresa el c\u00f3digo de 6 d\u00edgitos.</p>
            <div>
              <input type="text" id="resetOtp" maxlength="6" inputmode="numeric" pattern="[0-9]*"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-2xl font-bold text-center tracking-[8px]"
                placeholder="000000">
            </div>
            <button type="submit" id="otpBtn"
              class="w-full bg-primary-500 text-white font-semibold py-2.5 px-4 rounded-lg hover:bg-primary-700 transition text-sm">
              <i class="fa fa-check mr-2"></i>Verificar c\u00f3digo
            </button>
            <p id="otpError" class="text-red-500 text-sm text-center hidden"></p>
            <div class="text-center">
              <button type="button" id="backToLogin2" class="text-xs text-gray-500 hover:text-gray-700 underline">
                Volver al inicio de sesi\u00f3n
              </button>
            </div>
          </form>

          <!-- Step 2: New password (shown after OTP verified) -->
          <form id="passwordForm" class="space-y-4 hidden">
            <p class="text-sm text-gray-600 text-center" id="passwordMessage">C\u00f3digo verificado. Ingresa tu nueva contrase\u00f1a.</p>
            <div class="relative">
              <input type="password" id="resetPassword" required minlength="8"
                class="w-full px-4 py-2.5 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-sm"
                placeholder="Nueva contrase\u00f1a (m\u00edn. 8 caracteres)">
              <button type="button" id="toggleResetPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                <i class="fa fa-eye"></i>
              </button>
            </div>
            <div class="relative">
              <input type="password" id="resetPasswordConfirm" required minlength="8"
                class="w-full px-4 py-2.5 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-sm"
                placeholder="Confirmar contrase\u00f1a">
              <button type="button" id="toggleResetPasswordConfirm"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                <i class="fa fa-eye"></i>
              </button>
            </div>
            <button type="submit" id="resetBtn"
              class="w-full bg-primary-500 text-white font-semibold py-2.5 px-4 rounded-lg hover:bg-primary-700 transition text-sm">
              <i class="fa fa-save mr-2"></i>Restablecer contrase\u00f1a
            </button>
            <p id="resetError" class="text-red-500 text-sm text-center hidden"></p>
            <p id="resetSuccess" class="text-green-600 text-sm text-center hidden"></p>
            <div class="text-center">
              <button type="button" id="backToLogin3" class="text-xs text-gray-500 hover:text-gray-700 underline">
                Volver al inicio de sesi\u00f3n
              </button>
            </div>
          </form>
        </div>

        <p class="text-center text-xs text-gray-400 mt-8">AVEMER &copy; 2026</p>
      </div>
    </div>
  `;

  // ---------- Welcome view (remembered user) ----------
  if (remembered) {
    document.getElementById('toggleWelcomePassword')?.addEventListener('click', () => {
      const input = document.getElementById('welcomePassword');
      const icon = document.querySelector('#toggleWelcomePassword i');
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      icon.className = `fa ${isPassword ? 'fa-eye-slash' : 'fa-eye'}`;
    });

    document.getElementById('switchToFull')?.addEventListener('click', () => {
      Auth.clearRememberedUser();
      renderLogin(container);
    });

    document.getElementById('forgotLinkWelcome')?.addEventListener('click', (e) => {
      e.preventDefault();
      document.getElementById('welcomeView').classList.add('hidden');
      document.getElementById('forgotForm').classList.remove('hidden');
    });

    document.getElementById('welcomeForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const password = document.getElementById('welcomePassword').value.trim();
      const btn = document.getElementById('welcomeBtn');
      const errorEl = document.getElementById('welcomeError');

      btn.disabled = true;
      btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i>Ingresando...';
      errorEl.classList.add('hidden');

      const res = await Auth.login(remembered.email, password, true);

      if (res && res.success) {
        window.location.hash = '#dashboard';
      } else {
        errorEl.textContent = res?.message || 'Error al iniciar sesión';
        errorEl.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-sign-in-alt mr-2"></i>Ingresar';
      }
    });
  }

  // ---------- Full login form ----------
  document.getElementById('togglePassword')?.addEventListener('click', () => {
    const input = document.getElementById('loginPassword');
    const icon = document.querySelector('#togglePassword i');
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    icon.className = `fa ${isPassword ? 'fa-eye-slash' : 'fa-eye'}`;
  });

  document.getElementById('forgotLink')?.addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('loginForm').classList.add('hidden');
    document.getElementById('forgotForm').classList.remove('hidden');
  });

  document.getElementById('backToLogin')?.addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('forgotForm').classList.add('hidden');
    document.getElementById('loginForm').classList.remove('hidden');
    document.getElementById('forgotError').classList.add('hidden');
    document.getElementById('forgotSuccess').classList.add('hidden');
  });

  document.getElementById('backToLogin2')?.addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('resetContainer').classList.add('hidden');
    document.getElementById('otpError').classList.add('hidden');
    document.getElementById('loginForm').classList.remove('hidden');
  });

  document.getElementById('backToLogin3')?.addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('resetContainer').classList.add('hidden');
    document.getElementById('resetError').classList.add('hidden');
    document.getElementById('resetSuccess').classList.add('hidden');
    document.getElementById('loginForm').classList.remove('hidden');
  });

  document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value.trim();
    const remember = document.getElementById('loginRemember').checked;
    const btn = document.getElementById('loginBtn');
    const errorEl = document.getElementById('loginError');

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i>Ingresando...';
    errorEl.classList.add('hidden');

    const res = await Auth.login(email, password, remember);

    if (res && res.success) {
      window.location.hash = '#dashboard';
    } else {
      errorEl.textContent = res?.message || 'Error al iniciar sesión';
      errorEl.classList.remove('hidden');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-sign-in-alt mr-2"></i>Ingresar';
    }
  });

  // ---------- Forgot password ----------
  document.getElementById('forgotForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('forgotEmail').value.trim();
    const btn = document.getElementById('forgotBtn');
    const errorEl = document.getElementById('forgotError');

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i>Enviando...';
    errorEl.classList.add('hidden');

    try {
      const res = await API.post('/auth/forgot-password', { email });
      if (res && res.success) {
        document.getElementById('forgotForm').classList.add('hidden');
        document.getElementById('otpMessage').textContent =
          'Enviamos un c\u00f3digo de 6 d\u00edgitos a ' + email + '.';
        document.getElementById('resetContainer').classList.remove('hidden');
        document.getElementById('resetOtp').focus();
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-paper-plane mr-2"></i>Enviar c\u00f3digo';
      } else {
        errorEl.textContent = res?.message || 'Error al enviar el c\u00f3digo';
        errorEl.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-paper-plane mr-2"></i>Enviar c\u00f3digo';
      }
    } catch {
      errorEl.textContent = 'Error de conexi\u00f3n';
      errorEl.classList.remove('hidden');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-paper-plane mr-2"></i>Enviar c\u00f3digo';
    }
  });

  // ---------- Step 1: Verify OTP ----------
  document.getElementById('otpForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const otp = document.getElementById('resetOtp').value.replace(/\D/g, '');
    const btn = document.getElementById('otpBtn');
    const errorEl = document.getElementById('otpError');

    errorEl.classList.add('hidden');

    if (otp.length !== 6) {
      errorEl.textContent = 'Ingresa el c\u00f3digo de 6 d\u00edgitos';
      errorEl.classList.remove('hidden');
      return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i>Verificando...';

    try {
      const res = await API.post('/auth/verify-otp', { token: otp });
      if (res && res.success) {
        document.getElementById('otpForm').classList.add('hidden');
        document.getElementById('passwordForm').classList.remove('hidden');
        document.getElementById('resetPassword').focus();
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-check mr-2"></i>Verificar c\u00f3digo';
      } else {
        errorEl.textContent = res?.message || 'C\u00f3digo inv\u00e1lido o expirado';
        errorEl.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-check mr-2"></i>Verificar c\u00f3digo';
      }
    } catch {
      errorEl.textContent = 'Error de conexi\u00f3n';
      errorEl.classList.remove('hidden');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-check mr-2"></i>Verificar c\u00f3digo';
    }
  });

  // ---------- Step 2: Reset password ----------
  document.getElementById('passwordForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const otp = document.getElementById('resetOtp').value.replace(/\D/g, '');
    const password = document.getElementById('resetPassword').value;
    const confirm = document.getElementById('resetPasswordConfirm').value;
    const errorEl = document.getElementById('resetError');
    const successEl = document.getElementById('resetSuccess');
    const btn = document.getElementById('resetBtn');

    errorEl.classList.add('hidden');
    successEl.classList.add('hidden');

    if (password !== confirm) {
      errorEl.textContent = 'Las contrase\u00f1as no coinciden';
      errorEl.classList.remove('hidden');
      return;
    }
    if (password.length < 8) {
      errorEl.textContent = 'La contrase\u00f1a debe tener al menos 8 caracteres';
      errorEl.classList.remove('hidden');
      return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i>Procesando...';

    try {
      const res = await API.post('/auth/reset-password', { token: otp, password });
      if (res && res.success) {
        successEl.textContent = res.message || 'Contrase\u00f1a actualizada. Ahora puedes iniciar sesi\u00f3n.';
        successEl.classList.remove('hidden');
        btn.classList.add('hidden');
        document.getElementById('backToLogin3').textContent = 'Iniciar sesi\u00f3n';
      } else {
        errorEl.textContent = res?.message || 'Error al restablecer la contrase\u00f1a';
        errorEl.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-save mr-2"></i>Restablecer contrase\u00f1a';
      }
    } catch {
      errorEl.textContent = 'Error de conexi\u00f3n';
      errorEl.classList.remove('hidden');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-save mr-2"></i>Restablecer contrase\u00f1a';
    }
  });

  // ---------- OTP input filter ----------
  document.getElementById('resetOtp')?.addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 6);
  });

  // ---------- Eye toggles for reset password ----------
  document.getElementById('toggleResetPassword')?.addEventListener('click', () => {
    const input = document.getElementById('resetPassword');
    const icon = document.querySelector('#toggleResetPassword i');
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    icon.className = `fa ${isPassword ? 'fa-eye-slash' : 'fa-eye'}`;
  });

  document.getElementById('toggleResetPasswordConfirm')?.addEventListener('click', () => {
    const input = document.getElementById('resetPasswordConfirm');
    const icon = document.querySelector('#toggleResetPasswordConfirm i');
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    icon.className = `fa ${isPassword ? 'fa-eye-slash' : 'fa-eye'}`;
  });
}
