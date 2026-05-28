function renderLogin(container) {
  container.innerHTML = `
    <div class="min-h-screen flex items-center justify-center -mt-12">
      <div class="w-full max-w-sm px-6">
        <div class="text-center mb-8">
          <img src="icons/icon.svg" alt="AVEMER" class="w-20 h-20 mx-auto mb-4">
          <h1 class="text-2xl font-bold text-primary-500">AVEMER</h1>
          <p class="text-gray-500 text-sm mt-1">Portal de Alumnos</p>
        </div>
        <form id="loginForm" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
            <input type="email" id="loginEmail" required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-sm"
              placeholder="correo@ejemplo.com">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
            <div class="relative">
              <input type="password" id="loginPassword" required
                class="w-full px-4 py-2.5 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-sm"
                placeholder="••••••••">
              <button type="button" id="togglePassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                <i class="fa fa-eye"></i>
              </button>
            </div>
          </div>
          <button type="submit" id="loginBtn"
            class="w-full bg-primary-500 text-white font-semibold py-2.5 px-4 rounded-lg hover:bg-primary-700 transition text-sm">
            <i class="fa fa-sign-in-alt mr-2"></i>Ingresar
          </button>
          <p id="loginError" class="text-red-500 text-sm text-center hidden"></p>
        </form>
        <p class="text-center text-xs text-gray-400 mt-8">AVEMER &copy; 2026</p>
      </div>
    </div>
  `;

  document.getElementById('togglePassword')?.addEventListener('click', () => {
    const input = document.getElementById('loginPassword');
    const icon = document.querySelector('#togglePassword i');
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    icon.className = `fa ${isPassword ? 'fa-eye-slash' : 'fa-eye'}`;
  });

  document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value.trim();
    const btn = document.getElementById('loginBtn');
    const errorEl = document.getElementById('loginError');

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i>Ingresando...';
    errorEl.classList.add('hidden');

    const res = await Auth.login(email, password);

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
