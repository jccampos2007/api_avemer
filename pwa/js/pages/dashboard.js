async function renderDashboard(container) {
  const user = Auth.getUser();

  const [profileRes, debtsRes, enrollmentsRes] = await Promise.all([
    API.get('/profile'),
    API.get('/debts'),
    API.get('/enrollments'),
  ]);

  const profile = profileRes?.success ? profileRes.data : user;
  const debts = debtsRes?.success ? debtsRes.data : { total_deuda: 0, deudas: [] };
  const enrollments = enrollmentsRes?.success ? enrollmentsRes.data : [];

  const nombre = profile?.nombre_completo || (user?.nombre + ' ' + user?.apellido) || 'Alumno';
  const totalDeuda = debts.total_deuda || 0;
  const activeEnrollments = enrollments.filter(e => e.estatus_id === 1).length;

  container.innerHTML = `
    <div class="max-w-lg mx-auto space-y-5">
      <div class="bg-gradient-to-r from-primary-500 to-primary-700 text-white rounded-2xl p-5 shadow-lg">
        <p class="text-sm opacity-80">Bienvenido,</p>
        <h2 class="text-xl font-bold mt-0.5">${nombre}</h2>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
          <div class="text-3xl text-primary-500 mb-1"><i class="fa fa-graduation-cap"></i></div>
          <p class="text-2xl font-bold">${activeEnrollments}</p>
          <p class="text-xs text-gray-500">Inscripciones activas</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
          <div class="text-3xl ${totalDeuda > 0 ? 'text-red-500' : 'text-green-500'} mb-1">
            <i class="fa ${totalDeuda > 0 ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i>
          </div>
          <p class="text-2xl font-bold">$${totalDeuda.toFixed(2)}</p>
          <p class="text-xs text-gray-500">Deuda total</p>
        </div>
      </div>

      ${enrollments.length > 0 ? `
      <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-700 mb-3"><i class="fa fa-clock mr-2 text-primary-500"></i>Últimas inscripciones</h3>
        ${enrollments.slice(0, 3).map(e => `
          <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate">${e.titulo}</p>
              <p class="text-xs text-gray-400">${e.fecha?.split(' ')[0] || ''}</p>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full ${e.estatus_id === 1 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}">${e.estatus || 'N/A'}</span>
          </div>
        `).join('')}
      </div>
      ` : ''}

      <div class="grid grid-cols-2 gap-3">
        <a href="#enrollments" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center hover:shadow-md transition">
          <i class="fa fa-list text-2xl text-primary-500 mb-1"></i>
          <p class="text-sm font-medium">Ver cursos</p>
        </a>
        <a href="#payments" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center hover:shadow-md transition">
          <i class="fa fa-credit-card text-2xl text-primary-500 mb-1"></i>
          <p class="text-sm font-medium">Mis pagos</p>
        </a>
        <a href="#profile" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center hover:shadow-md transition">
          <i class="fa fa-user text-2xl text-primary-500 mb-1"></i>
          <p class="text-sm font-medium">Mi perfil</p>
        </a>
        <a href="#offers" class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center hover:shadow-md transition">
          <i class="fa fa-star text-2xl text-primary-500 mb-1"></i>
          <p class="text-sm font-medium">Ofertas</p>
        </a>
      </div>
    </div>
  `;
}
