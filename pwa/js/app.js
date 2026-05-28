function showToast(message, type) {
  const el = document.getElementById('toast');
  const colors = { success: 'bg-green-500', error: 'bg-red-500', info: 'bg-blue-500' };
  el.className = `fixed top-4 right-4 z-50 text-white px-5 py-3 rounded-lg shadow-lg text-sm ${colors[type] || colors.info} show`;
  el.textContent = message;
  setTimeout(() => { el.classList.remove('show'); el.classList.add('hidden'); }, 3500);
}

document.getElementById('menuBtn')?.addEventListener('click', () => {
  document.getElementById('bottomNav')?.classList.toggle('shadow-lg');
});

document.getElementById('logoutBtn')?.addEventListener('click', () => {
  Auth.logout();
  window.location.hash = '#login';
});

Router.register('login', renderLogin, { title: 'Iniciar Sesión', requiresAuth: false });
Router.register('dashboard', renderDashboard, { title: 'Inicio' });
Router.register('profile', renderProfile, { title: 'Mi Perfil' });
Router.register('enrollments', renderEnrollments, { title: 'Mis Inscripciones' });
Router.register('payments', renderPayments, { title: 'Pagos' });
Router.register('offers', renderOffers, { title: 'Ofertas' });

Router.addGuard(async (route, def) => {
  if (def.requiresAuth && Auth.isAuthenticated()) {
    const token = Auth.getToken();
    const payload = JSON.parse(atob(token.split('.')[1]));
    if (payload.exp * 1000 < Date.now()) {
      Auth.logout();
      window.location.hash = '#login';
      return false;
    }
  }
});

Router.init();
