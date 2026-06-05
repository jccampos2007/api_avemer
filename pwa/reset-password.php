<?php
$token = $_GET['token'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Restablecer contraseña - AVEMER</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: { 50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc', 400: '#818cf8', 500: '#1e3a5f', 600: '#162d4a', 700: '#0f1f35' },
          }
        }
      }
    }
  </script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-sm">
    <div class="text-center mb-8">
      <h1 class="text-2xl font-bold text-primary-500">AVEMER</h1>
      <p class="text-gray-500 text-sm mt-1">Restablecer contraseña</p>
    </div>

    <div id="app">
      <form id="resetForm" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 space-y-4">
        <p class="text-sm text-gray-600">Revisa tu correo, ingresa el código de verificación y tu nueva contraseña.</p>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Código de verificación</label>
          <input type="text" id="otp" value="<?= htmlspecialchars($token) ?>" maxlength="6" inputmode="numeric" pattern="[0-9]*"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-2xl font-bold text-center tracking-[8px]"
            placeholder="000000" autofocus>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nueva contraseña</label>
          <input type="password" id="password" required minlength="8"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-sm"
            placeholder="Mínimo 8 caracteres">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña</label>
          <input type="password" id="passwordConfirm" required minlength="8"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-sm"
            placeholder="Repite la contraseña">
        </div>
        <p id="errorMsg" class="text-red-500 text-sm text-center hidden"></p>
        <p id="successMsg" class="text-green-600 text-sm text-center hidden"></p>
        <button type="submit" id="resetBtn"
          class="w-full bg-primary-500 text-white font-semibold py-2.5 px-4 rounded-lg hover:bg-primary-700 transition text-sm">
          <i class="fa fa-save mr-2"></i>Restablecer contraseña
        </button>
      </form>
      <div id="successView" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 text-center hidden">
        <i class="fa fa-check-circle text-5xl text-green-500 mb-3"></i>
        <p class="text-gray-700 font-medium mb-2">Contraseña actualizada</p>
        <p class="text-sm text-gray-500 mb-4">Tu contraseña se ha restablecido exitosamente.</p>
        <a href="/api/pwa/" class="inline-block bg-primary-500 text-white font-semibold py-2.5 px-6 rounded-lg hover:bg-primary-700 transition text-sm">
          <i class="fa fa-sign-in-alt mr-2"></i>Iniciar sesión
        </a>
      </div>
    </div>

    <p class="text-center text-xs text-gray-400 mt-8">AVEMER &copy; 2026</p>
  </div>

  <script>
    const API_BASE = '/api/public/v1';

    document.getElementById('otp')?.addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });

    document.getElementById('resetForm')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const otp = document.getElementById('otp').value.replace(/\D/g, '');
      const password = document.getElementById('password').value;
      const confirm = document.getElementById('passwordConfirm').value;
      const errorEl = document.getElementById('errorMsg');
      const successEl = document.getElementById('successMsg');
      const btn = document.getElementById('resetBtn');

      errorEl.classList.add('hidden');
      successEl.classList.add('hidden');

      if (otp.length !== 6) {
        errorEl.textContent = 'Ingresa el código de 6 dígitos';
        errorEl.classList.remove('hidden');
        return;
      }

      if (password !== confirm) {
        errorEl.textContent = 'Las contraseñas no coinciden';
        errorEl.classList.remove('hidden');
        return;
      }

      if (password.length < 8) {
        errorEl.textContent = 'La contraseña debe tener al menos 8 caracteres';
        errorEl.classList.remove('hidden');
        return;
      }

      btn.disabled = true;
      btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i>Procesando...';

      try {
        const res = await fetch(API_BASE + '/auth/reset-password', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ token: otp, password }),
        });

        const json = await res.json();

        if (json.success) {
          document.getElementById('resetForm').classList.add('hidden');
          document.getElementById('successView').classList.remove('hidden');
        } else {
          errorEl.textContent = json.message || 'Error al restablecer la contraseña';
          errorEl.classList.remove('hidden');
          btn.disabled = false;
          btn.innerHTML = '<i class="fa fa-save mr-2"></i>Restablecer contraseña';
        }
      } catch {
        errorEl.textContent = 'Error de conexión';
        errorEl.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-save mr-2"></i>Restablecer contraseña';
      }
    });
  </script>
</body>
</html>
