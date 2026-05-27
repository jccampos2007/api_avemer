async function renderPayments(container) {
  const [debtsRes, paymentsRes, bancosRes, formasRes] = await Promise.all([
    API.get('/debts'),
    API.get('/payments'),
    API.get('/payments/bancos'),
    API.get('/payments/formas-pago'),
  ]);

  const debts = debtsRes?.success ? debtsRes.data : { total_deuda: 0, deudas: [] };
  const payments = paymentsRes?.success ? paymentsRes.data : [];
  const bancos = bancosRes?.success ? bancosRes.data : [];
  const formas = formasRes?.success ? formasRes.data : [];

  const totalDeuda = debts.total_deuda || 0;
  const cuotas = debts.deudas || [];

  container.innerHTML = `
    <div class="max-w-lg mx-auto space-y-5">

      <!-- Resumen de deuda -->
      <div class="bg-gradient-to-r from-primary-500 to-primary-700 text-white rounded-2xl p-5 shadow-lg">
        <p class="text-sm opacity-80">Deuda total</p>
        <p class="text-3xl font-bold mt-1">$${totalDeuda.toFixed(2)}</p>
        <p class="text-xs opacity-60 mt-1">${cuotas.length} cuota(s) pendiente(s)</p>
      </div>

      ${cuotas.length > 0 ? `
      <!-- Cuotas pendientes -->
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-700 mb-3"><i class="fa fa-exclamation-circle mr-2 text-red-500"></i>Cuotas pendientes</h3>
        ${cuotas.map(c => `
          <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate">${c.concepto || 'Cuota'}</p>
              <p class="text-xs text-gray-400">Vence: ${c.fecha_vencimiento || ''}</p>
            </div>
            <span class="text-sm font-bold text-red-500">$${parseFloat(c.monto).toFixed(2)}</span>
          </div>
        `).join('')}
      </div>
      ` : `
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 text-center py-6">
        <i class="fa fa-check-circle text-4xl text-green-500 mb-2"></i>
        <p class="text-gray-600 font-medium">No tienes deudas pendientes</p>
      </div>
      `}

      <!-- Historial de pagos -->
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-700 mb-3"><i class="fa fa-history mr-2 text-primary-500"></i>Historial de pagos</h3>
        ${payments.length === 0 ? '<p class="text-sm text-gray-400 text-center py-4">No hay pagos registrados</p>' : `
        <div class="space-y-2">
          ${payments.map(p => `
            <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">${p.concepto || 'Pago'}</p>
                <p class="text-xs text-gray-400">${p.fecha_pago?.split(' ')[0] || ''} · ${p.referencia || ''}</p>
              </div>
              <span class="text-sm font-bold text-green-600">$${parseFloat(p.monto).toFixed(2)}</span>
              <span class="text-xs px-2 py-0.5 rounded-full ml-2 ${(p.estatus_pago_id || 0) === 1 ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'}">${p.estatus_pago || 'Pendiente'}</span>
            </div>
          `).join('')}
        </div>
        `}
      </div>

      <!-- Reportar pago -->
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-700 mb-4"><i class="fa fa-plus-circle mr-2 text-primary-500"></i>Reportar pago</h3>
        <form id="paymentForm" class="space-y-3">
          <select id="payCuota" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none" ${cuotas.length === 0 ? 'disabled' : ''}>
            <option value="">Seleccionar cuota</option>
            ${cuotas.map(c => `<option value="${c.cuota_id}">${c.concepto || 'Cuota'} - $${parseFloat(c.monto).toFixed(2)}</option>`).join('')}
          </select>
          <input type="number" id="payMonto" step="0.01" min="0.01" placeholder="Monto a pagar"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none" ${cuotas.length === 0 ? 'disabled' : ''}>
          <input type="text" id="payRef" placeholder="Número de referencia"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none" ${cuotas.length === 0 ? 'disabled' : ''}>
          <select id="payBanco" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none" ${cuotas.length === 0 ? 'disabled' : ''}>
            <option value="">Seleccionar banco</option>
            ${bancos.map(b => `<option value="${b.id}">${b.nombre}</option>`).join('')}
          </select>
          <select id="payForma" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none" ${cuotas.length === 0 ? 'disabled' : ''}>
            <option value="">Forma de pago</option>
            ${formas.map(f => `<option value="${f.id}">${f.nombre}</option>`).join('')}
          </select>
          <button type="submit" class="w-full bg-green-600 text-white font-semibold py-2 rounded-lg hover:bg-green-700 transition text-sm" ${cuotas.length === 0 ? 'disabled' : ''}>
            <i class="fa fa-paper-plane mr-2"></i>Reportar pago
          </button>
        </form>
      </div>
    </div>
  `;

  document.getElementById('paymentForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const cuota_id = document.getElementById('payCuota').value;
    const monto = document.getElementById('payMonto').value;
    const referencia = document.getElementById('payRef').value.trim();
    const banco_id = document.getElementById('payBanco').value;
    const forma_pago_id = document.getElementById('payForma').value;

    if (!cuota_id || !monto || !referencia || !banco_id || !forma_pago_id) {
      showToast('Completa todos los campos', 'error');
      return;
    }

    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i>Enviando...';

    const res = await API.post('/payments/report', {
      cuota_id: parseInt(cuota_id),
      monto: parseFloat(monto),
      numero_control: referencia,
      banco_id: parseInt(banco_id),
      forma_pago_id: parseInt(forma_pago_id),
    });

    if (res?.success) {
      showToast(res.message || 'Pago reportado', 'success');
      renderPayments(container);
    } else {
      showToast(res?.message || 'Error al reportar pago', 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-paper-plane mr-2"></i>Reportar pago';
    }
  });
}
