async function renderProfile(container) {
  const res = await API.get('/profile');
  if (!res?.success) { container.innerHTML = '<p class="text-center text-red-500 py-10">Error al cargar perfil</p>'; return; }

  const p = res.data;
  const user = Auth.getUser();

  const nombre = p.nombre_completo || (user?.nombre + ' ' + user?.apellido) || '';
  const fotoSrc = p.foto_base64 ? `data:image/jpeg;base64,${p.foto_base64}` : '';

  container.innerHTML = `
    <div class="max-w-lg mx-auto space-y-5">
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center">
        <div class="w-24 h-24 rounded-full bg-primary-100 mx-auto mb-3 flex items-center justify-center overflow-hidden border-4 border-primary-200">
          ${fotoSrc ? `<img src="${fotoSrc}" alt="Foto" class="w-full h-full object-cover">` : ''}
          <i class="fa fa-user text-4xl text-primary-400"></i>
        </div>
        <h2 class="text-lg font-bold">${nombre}</h2>
        <p class="text-sm text-gray-500">${p.ci_pasapote || ''}</p>
        <label class="inline-block mt-3 text-sm text-primary-600 cursor-pointer hover:underline">
          <i class="fa fa-camera mr-1"></i>Cambiar foto
          <input type="file" id="photoInput" accept="image/*" class="hidden">
        </label>
      </div>

      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-700 mb-4"><i class="fa fa-edit mr-2 text-primary-500"></i>Editar perfil</h3>
        <form id="profileForm" class="space-y-3">
          <div>
            <label class="block text-xs text-gray-500 mb-0.5">Correo</label>
            <input type="email" id="pfCorreo" value="${p.correo || ''}"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none">
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-0.5">Teléfono celular</label>
            <input type="text" id="pfCelular" value="${p.tlf_celular || ''}"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none">
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-0.5">Teléfono habitación</label>
            <input type="text" id="pfTlfHab" value="${p.tlf_habitacion || ''}"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none">
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-0.5">Teléfono trabajo</label>
            <input type="text" id="pfTlfTrab" value="${p.tlf_trabajo || ''}"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none">
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-0.5">Dirección</label>
            <textarea id="pfDireccion" rows="2"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none">${p.direccion || ''}</textarea>
          </div>
          <button type="submit" class="w-full bg-primary-500 text-white font-semibold py-2 rounded-lg hover:bg-primary-700 transition text-sm">
            <i class="fa fa-save mr-2"></i>Guardar cambios
          </button>
        </form>
      </div>

      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-700 mb-4"><i class="fa fa-lock mr-2 text-primary-500"></i>Cambiar contraseña</h3>
        <form id="passwordForm" class="space-y-3">
          <input type="password" id="pwCurrent" placeholder="Contraseña actual"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none" required>
          <input type="password" id="pwNew" placeholder="Nueva contraseña (mín. 8 caracteres)"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none" required minlength="8">
          <button type="submit" class="w-full bg-gray-700 text-white font-semibold py-2 rounded-lg hover:bg-gray-800 transition text-sm">
            <i class="fa fa-key mr-2"></i>Actualizar contraseña
          </button>
        </form>
      </div>
    </div>
  `;

  document.getElementById('photoInput')?.addEventListener('change', async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    const res = await API.uploadPhoto(file);
    if (res?.success) {
      showToast('Foto actualizada', 'success');
      await renderProfile(container);
    } else {
      showToast(res?.message || 'Error al subir foto', 'error');
    }
  });

  document.getElementById('profileForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = {
      correo: document.getElementById('pfCorreo').value.trim(),
      tlf_celular: document.getElementById('pfCelular').value.trim(),
      tlf_habitacion: document.getElementById('pfTlfHab').value.trim(),
      tlf_trabajo: document.getElementById('pfTlfTrab').value.trim(),
      direccion: document.getElementById('pfDireccion').value.trim(),
    };
    const res = await API.put('/profile', data);
    if (res?.success) {
      showToast('Perfil actualizado', 'success');
    } else {
      showToast(res?.message || 'Error al actualizar', 'error');
    }
  });

  document.getElementById('passwordForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const current = document.getElementById('pwCurrent').value;
    const newPw = document.getElementById('pwNew').value;
    const res = await API.put('/profile/password', { current_password: current, new_password: newPw });
    if (res?.success) {
      showToast('Contraseña actualizada', 'success');
      document.getElementById('passwordForm').reset();
    } else {
      showToast(res?.message || 'Error al cambiar contraseña', 'error');
    }
  });
}
