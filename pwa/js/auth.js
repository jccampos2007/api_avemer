const Auth = {
  TOKEN_KEY: 'avemer_token',
  USER_KEY: 'avemer_user',
  REFRESH_KEY: 'avemer_refresh',
  REMEMBER_EMAIL_KEY: 'avemer_rem_email',
  REMEMBER_NAME_KEY: 'avemer_rem_name',
  REMEMBER_PHOTO_KEY: 'avemer_rem_photo',

  async login(email, password, remember = false) {
    const res = await API.post('/auth/login', { email, password });
    if (res && res.success) {
      this.setToken(res.data.access_token);
      this.setUser(res.data.user);
      if (remember) {
        this.setRefreshToken(res.data.refresh_token || '');
        this.setRememberedEmail(email);
        const nombre = (res.data.user?.nombre || '').split(' ')[0] || email;
        this.setRememberedName(nombre);
        this.fetchAndSavePhoto();
      } else {
        this.clearRefreshToken();
        this.clearRememberedUser();
      }
    }
    return res;
  },

  async fetchAndSavePhoto() {
    try {
      const res = await API.get('/profile');
      if (res?.success && res.data?.foto_base64) {
        this.setRememberedPhoto(res.data.foto_base64);
      }
    } catch {
      // ignore
    }
  },

  async init() {
    const token = this.getToken();
    if (!token) return false;

    try {
      const payload = JSON.parse(atob(token.split('.')[1]));
      if (payload.exp * 1000 > Date.now()) {
        return true;
      }
    } catch {
      // ignore
    }

    const refreshToken = this.getRefreshToken();
    if (!refreshToken) {
      this.clearSession();
      return false;
    }

    try {
      const res = await API.post('/auth/refresh', { refresh_token: refreshToken });
      if (res && res.success && res.data.access_token) {
        this.setToken(res.data.access_token);
        return true;
      }
    } catch {
      // ignore
    }

    this.clearSession();
    return false;
  },

  logout() {
    this.clearSession();
  },

  clearSession() {
    localStorage.removeItem(this.TOKEN_KEY);
    localStorage.removeItem(this.USER_KEY);
    this.clearRefreshToken();
  },

  getToken() {
    return localStorage.getItem(this.TOKEN_KEY);
  },

  setToken(token) {
    localStorage.setItem(this.TOKEN_KEY, token);
  },

  getUser() {
    try {
      return JSON.parse(localStorage.getItem(this.USER_KEY));
    } catch {
      return null;
    }
  },

  setUser(user) {
    localStorage.setItem(this.USER_KEY, JSON.stringify(user));
  },

  getRefreshToken() {
    return localStorage.getItem(this.REFRESH_KEY);
  },

  setRefreshToken(token) {
    localStorage.setItem(this.REFRESH_KEY, token);
  },

  clearRefreshToken() {
    localStorage.removeItem(this.REFRESH_KEY);
  },

  getRememberedEmail() {
    return localStorage.getItem(this.REMEMBER_EMAIL_KEY) || '';
  },

  setRememberedEmail(email) {
    localStorage.setItem(this.REMEMBER_EMAIL_KEY, email);
  },

  getRememberedName() {
    return localStorage.getItem(this.REMEMBER_NAME_KEY) || '';
  },

  setRememberedName(name) {
    localStorage.setItem(this.REMEMBER_NAME_KEY, name);
  },

  getRememberedPhoto() {
    return localStorage.getItem(this.REMEMBER_PHOTO_KEY) || '';
  },

  setRememberedPhoto(base64) {
    localStorage.setItem(this.REMEMBER_PHOTO_KEY, base64);
  },

  clearRememberedUser() {
    localStorage.removeItem(this.REMEMBER_EMAIL_KEY);
    localStorage.removeItem(this.REMEMBER_NAME_KEY);
    localStorage.removeItem(this.REMEMBER_PHOTO_KEY);
  },

  getRememberedUser() {
    const email = this.getRememberedEmail();
    const nombre = this.getRememberedName();
    const foto = this.getRememberedPhoto();
    return email ? { email, nombre, foto } : null;
  },

  isAuthenticated() {
    return !!this.getToken();
  },
};
