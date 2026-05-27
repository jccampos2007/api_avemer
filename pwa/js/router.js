const Router = {
  currentRoute: null,
  routes: {},
  guards: [],

  register(name, handler, options = {}) {
    this.routes[name] = { handler, title: options.title || 'AVEMER', requiresAuth: options.requiresAuth ?? true };
  },

  addGuard(fn) {
    this.guards.push(fn);
  },

  async navigate(route) {
    const routeDef = this.routes[route];
    if (!routeDef) { window.location.hash = '#dashboard'; return; }

    if (routeDef.requiresAuth && !Auth.isAuthenticated()) {
      window.location.hash = '#login';
      return;
    }

    if (!routeDef.requiresAuth && Auth.isAuthenticated() && route === 'login') {
      window.location.hash = '#dashboard';
      return;
    }

    for (const guard of this.guards) {
      const result = await guard(route, routeDef);
      if (result === false) return;
    }

    this.currentRoute = route;

    const el = document.getElementById('mainContent');
    el.innerHTML = '<div class="flex items-center justify-center h-64"><div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div></div>';

    try {
      await routeDef.handler(el);
    } catch (err) {
      el.innerHTML = `<div class="text-center py-12 text-red-500"><i class="fa fa-exclamation-triangle text-4xl mb-3"></i><p>Error al cargar la página</p></div>`;
      console.error(err);
    }

    document.title = routeDef.title;

    if (routeDef.requiresAuth) {
      document.getElementById('topbar').classList.remove('hidden');
      document.getElementById('bottomNav').classList.remove('hidden');
      document.getElementById('pageTitle').textContent = routeDef.title;
      document.querySelectorAll('.nav-link').forEach((a) => {
        a.classList.toggle('active', a.dataset.route === route);
      });
    } else {
      document.getElementById('topbar').classList.add('hidden');
      document.getElementById('bottomNav').classList.add('hidden');
    }
  },

  init() {
    window.addEventListener('hashchange', () => {
      const route = window.location.hash.slice(1) || 'dashboard';
      this.navigate(route);
    });

    const initialRoute = window.location.hash.slice(1) || 'dashboard';
    this.navigate(initialRoute);
  },
};
