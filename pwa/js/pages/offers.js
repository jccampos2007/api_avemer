async function renderOffers(container) {
  const res = await API.get('/offers');
  const offers = res?.success ? res.data : [];

  const typeIcons = { curso: 'fa-book', diplomado: 'fa-graduation-cap', maestria: 'fa-university', evento: 'fa-calendar' };
  const typeColors = { curso: 'border-l-blue-500', diplomado: 'border-l-purple-500', maestria: 'border-l-orange-500', evento: 'border-l-green-500' };
  const typeLabels = { curso: 'Curso', diplomado: 'Diplomado', maestria: 'Maestría', evento: 'Evento' };

  container.innerHTML = `
    <div class="max-w-lg mx-auto">
      <div class="flex items-center gap-2 mb-4">
        <i class="fa fa-star text-yellow-500"></i>
        <h2 class="text-lg font-bold text-gray-800">Ofertas disponibles</h2>
        <span class="text-xs bg-primary-100 text-primary-700 px-2 py-0.5 rounded-full ml-auto">${offers.length}</span>
      </div>

      ${offers.length === 0 ? `
      <div class="text-center py-12 text-gray-400">
        <i class="fa fa-box-open text-5xl mb-3"></i>
        <p>No hay ofertas disponibles</p>
      </div>
      ` : `
      <div class="space-y-3">
        ${offers.map(o => `
          <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 border-l-4 ${typeColors[o.tipo] || 'border-l-primary-500'}">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
                <i class="fa ${typeIcons[o.tipo] || 'fa-file'}"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold truncate">${o.titulo || o.nombre}</p>
                <p class="text-xs text-gray-400 mt-0.5">
                  <span class="bg-gray-100 px-2 py-0.5 rounded text-xs">${typeLabels[o.tipo] || o.tipo}</span>
                  ${o.sede ? `<span class="ml-2"><i class="fa fa-map-marker-alt mr-1"></i>${o.sede}</span>` : ''}
                </p>
                ${o.fecha_inicio ? `<p class="text-xs text-gray-400 mt-1"><i class="fa fa-calendar mr-1"></i>Inicia: ${o.fecha_inicio}</p>` : ''}
                ${o.fecha_fin ? `<p class="text-xs text-gray-400">Finaliza: ${o.fecha_fin}</p>` : ''}
              </div>
            </div>
          </div>
        `).join('')}
      </div>
      `}
    </div>
  `;
}
