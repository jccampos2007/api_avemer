async function renderOffers(container) {
  const [offersRes, enrollRes] = await Promise.all([
    API.get('/offers'),
    API.get('/enrollments?limit=200'),
  ]);

  const offers = offersRes?.success ? offersRes.data : [];
  const enrollments = enrollRes?.success ? enrollRes.data : [];

  const enrolled = new Set(enrollments.map(e => `${e.tipo}:${e.abierto_id}`));
  const available = offers.filter(o => !enrolled.has(`${o.tipo}:${o.abierto_id}`));

  const groups = {};
  for (const o of available) {
    if (!groups[o.tipo]) groups[o.tipo] = [];
    groups[o.tipo].push(o);
  }

  const typeIcons = { curso: 'fa-book', diplomado: 'fa-graduation-cap', maestria: 'fa-university', evento: 'fa-calendar' };
  const typeLabels = { curso: 'Curso', diplomado: 'Diplomado', maestria: 'Maestría', evento: 'Evento' };
  const groupLabels = { curso: 'Cursos', diplomado: 'Diplomados', maestria: 'Maestrías', evento: 'Eventos' };
  const groupOrder = ['curso', 'diplomado', 'maestria', 'evento'];

  container.innerHTML = `
    <div class="max-w-lg mx-auto">
      <div class="flex items-center gap-2 mb-4">
        <i class="fa fa-star text-yellow-500"></i>
        <h2 class="text-lg font-bold text-gray-800">Ofertas disponibles</h2>
        <span class="text-xs bg-primary-100 text-primary-700 px-2 py-0.5 rounded-full ml-auto">${available.length}</span>
      </div>

      ${available.length === 0 ? `
      <div class="text-center py-12 text-gray-400">
        <i class="fa fa-box-open text-5xl mb-3"></i>
        <p>No hay ofertas disponibles</p>
      </div>
      ` : `
      <div class="space-y-2">
        ${groupOrder.filter(t => groups[t]?.length).map(t => `
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="offer-group-header flex items-center gap-3 p-3 cursor-pointer hover:bg-gray-50 transition select-none"
              data-group="${t}">
              <i class="fa ${typeIcons[t]} text-primary-500"></i>
              <span class="text-sm font-semibold flex-1">${groupLabels[t]}</span>
              <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">${groups[t].length}</span>
              <i class="offer-group-chevron fa fa-chevron-down text-gray-400 text-xs transition-transform duration-200"></i>
            </div>
            <div class="offer-group-content space-y-2 p-2 hidden">
              ${groups[t].map(o => `
                <div class="cursor-pointer select-none offer-card-wrapper" data-card="${o.tipo}:${o.abierto_id}">
                  <div class="flex items-stretch">
                    <div class="offer-card-slider bg-green-600 text-white flex flex-col items-center justify-center gap-1 rounded-l-xl font-semibold text-sm shrink-0 cursor-pointer"
                      data-tipo="${o.tipo}" data-abierto-id="${o.abierto_id}">
                      <i class="fa fa-pen text-lg"></i>
                      <span>Inscribirse</span>
                    </div>
                    <div class="offer-info-card bg-white rounded-xl p-4 shadow-sm border border-gray-100 border-l-4 border-l-gray-500 flex-1 min-w-0" data-border-class="border-l-gray-500">
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
                  </div>
                </div>
              `).join('')}
            </div>
          </div>
        `).join('')}
      </div>
      `}
    </div>
  `;

  container.querySelectorAll('.offer-group-header').forEach(header => {
    header.addEventListener('click', () => {
      const isOpen = header.nextElementSibling.style.display !== 'none';
      container.querySelectorAll('.offer-group-content').forEach(el => el.style.display = 'none');
      container.querySelectorAll('.offer-group-chevron').forEach(el => el.style.transform = '');
      if (!isOpen) {
        header.nextElementSibling.style.display = 'block';
        header.querySelector('.offer-group-chevron').style.transform = 'rotate(180deg)';
      }
    });
  });

  // Toggle slider + border-l on card click
  container.querySelectorAll('.offer-card-wrapper').forEach(wrapper => {
    wrapper.addEventListener('click', function (e) {
      // Si el click fue en el slider de inscripción, no hacer nada aquí
      if (e.target.closest('.offer-card-slider')) return;

      const slider   = this.querySelector('.offer-card-slider');
      const infoCard = this.querySelector('.offer-info-card');
      const borderCls = infoCard?.dataset.borderClass;

      // Cerrar todos los demás sliders y restaurar sus bordes
      container.querySelectorAll('.offer-card-wrapper').forEach(other => {
        if (other === this) return;
        const otherSlider = other.querySelector('.offer-card-slider');
        const otherInfo   = other.querySelector('.offer-info-card');
        const otherBorder = otherInfo?.dataset.borderClass;
        if (otherSlider?.classList.contains('show')) {
          otherSlider.classList.remove('show');
          if (otherInfo && otherBorder) {
            otherInfo.classList.add('border-l-4', otherBorder);
          }
        }
      });

      // Toggle este slider
      const opening = !slider.classList.contains('show');
      slider.classList.toggle('show');

      if (infoCard && borderCls) {
        if (opening) {
          // Quitar el borde izquierdo al abrir
          infoCard.classList.remove('border-l-4', borderCls);
        } else {
          // Restaurar el borde al cerrar
          infoCard.classList.add('border-l-4', borderCls);
        }
      }
    });
  });

  // Click en botón Inscribirse dentro del slider
  container.querySelectorAll('.offer-card-slider').forEach(slider => {
    slider.addEventListener('click', function (e) {
      e.stopPropagation();
      window.preEnroll(this);
    });
  });
}

window.preEnroll = async (el) => {
  const tipo = el.dataset.tipo;
  const abiertoId = el.dataset.abiertoId;

  el.innerHTML = '<i class="fa fa-spinner fa-spin text-lg"></i><span>Procesando...</span>';

  const res = await API.post('/enrollments/pre-register', { tipo, abierto_id: abiertoId });

  if (res?.success) {
    showToast('Pre-inscripción exitosa', 'success');

    const card = document.querySelector(`[data-card="${tipo}:${abiertoId}"]`);
    if (card) {
      const groupContent = card.closest('.offer-group-content');
      const group = groupContent?.closest('.bg-white.rounded-xl.shadow-sm');
      card.remove();
      const remaining = groupContent?.children.length ?? 0;
      if (remaining > 0) {
        const badge = group?.querySelector('.offer-group-header .rounded-full');
        if (badge) badge.textContent = remaining;
      } else {
        if (group) group.remove();
      }
      const totalBadge = document.querySelector('.flex.items-center.gap-2.mb-4 .rounded-full');
      if (totalBadge) {
        totalBadge.textContent = Math.max(0, parseInt(totalBadge.textContent) - 1);
      }
    }

    el.classList.remove('show');
    setTimeout(() => {
      el.innerHTML = '<i class="fa fa-pen text-lg"></i><span>Inscribirse</span>';
    }, 2000);
  } else {
    showToast(res?.message || 'Error al pre-inscribir', 'error');
    el.innerHTML = '<i class="fa fa-pen text-lg"></i><span>Inscribirse</span>';
  }
};
