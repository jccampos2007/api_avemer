const API = {
  async request(method, path, data = null) {
    const url = CONFIG.API_BASE + path;
    const headers = { 'Accept': 'application/json' };
    const token = Auth.getToken();

    if (token) {
      headers['Authorization'] = 'Bearer ' + token;
    }

    const options = { method, headers };

    if (data !== null) {
      headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(data);
    }

    const res = await fetch(url, options);
    const json = await res.json();

    if (!res.ok && res.status === 401 && token) {
      Auth.logout();
      window.location.hash = '#login';
      return null;
    }

    return json;
  },

  get(path) { return this.request('GET', path); },
  post(path, data) { return this.request('POST', path, data); },
  put(path, data) { return this.request('PUT', path, data); },

  async postFormData(path, formData) {
    const url = CONFIG.API_BASE + path;
    const headers = { 'Authorization': 'Bearer ' + Auth.getToken() };
    const res = await fetch(url, { method: 'POST', headers, body: formData });
    const json = await res.json();
    if (!res.ok && res.status === 401 && Auth.getToken()) {
      Auth.logout();
      window.location.hash = '#login';
      return null;
    }
    return json;
  },

  async uploadPhoto(file) {
    const url = CONFIG.API_BASE + '/profile/photo';
    const formData = new FormData();
    formData.append('foto', file);

    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Authorization': 'Bearer ' + Auth.getToken() },
      body: formData,
    });
    return await res.json();
  },
};
