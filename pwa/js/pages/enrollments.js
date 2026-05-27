async function renderEnrollments(container) {
  const res = await API.get('/enrollments?limit=100');
  const enrollments = res?.success ? res.data : [];

  const typeIcons = { curso: 'fa-book', diplomado: 'fa-graduation-cap', maestria: 'fa-university', evento: 'fa-calendar' };
  const typeColors = { curso: 'bg-blue-100 text-blue-700', diplomado: 'bg-purple-100 text-purple-700', maestria: 'bg-orange-100 text-orange-700', evento: 'bg-green-100 text-green-700' };

  container.innerHTML = `
    <div class="max-w-lg mx-auto space-y-3">
      ${enrollments.length === 0 ? `
      <div class="text-center py-12 text-gray-400">
        <i class="fa fa-graduation-cap text-5xl mb-3"></i>
        <p>No tienes inscripciones</p>
      </div>
      ` : enrollments.map(e => `
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full ${typeColors[e.tipo] || 'bg-gray-100'} flex items-center justify-center shrink-0">
              <i class="fa ${typeIcons[e.tipo] || 'fa-file'}"></i>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold truncate">${e.titulo}</p>
              <p class="text-xs text-gray-400 mt-0.5">
                <i class="fa fa-calendar mr-1"></i>${e.fecha?.split(' ')[0] || ''}
                <span class="ml-2 capitalize">${e.tipo}</span>
              </p>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full shrink-0 ${e.estatus_id === 1 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'}">
              ${e.estatus || 'N/A'}
            </span>
          </div>
        </div>
      `).join('')}
    </div>
  `;
}
