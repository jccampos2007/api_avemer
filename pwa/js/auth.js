const Auth = {
  TOKEN_KEY: 'avemer_token',
  USER_KEY: 'avemer_user',

  async login(email, password) {
    const res = await API.post('/auth/login', { email, password });
    if (res && res.success) {
      this.setToken(res.data.access_token);
      this.setUser(res.data.user);
    }
    return res;
  },

  logout() {
    localStorage.removeItem(this.TOKEN_KEY);
    localStorage.removeItem(this.USER_KEY);
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

  isAuthenticated() {
    return !!this.getToken();
  },
};
