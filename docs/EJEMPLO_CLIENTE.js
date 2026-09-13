// Ejemplo de consumo de API con Access Token y Refresh Token
// Para usar en una aplicación JavaScript/Frontend

class AuthService {
  constructor(baseUrl = 'http://localhost/AlquilER/public') {
    this.baseUrl = baseUrl;
    this.accessToken = localStorage.getItem('access_token');
    this.refreshToken = localStorage.getItem('refresh_token');
  }

  /**
   * Realiza login con email y contraseña
   */
  async login(email, contrasena) {
    try {
      const response = await fetch(`${this.baseUrl}/api/autenticador/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, contrasena })
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || 'Error en login');
      }

      // Guardar tokens
      this.accessToken = data.data.access_token;
      this.refreshToken = data.data.refresh_token;

      localStorage.setItem('access_token', this.accessToken);
      localStorage.setItem('refresh_token', this.refreshToken);
      localStorage.setItem('usuario_id', data.data.usuario_id);

      return data.data;
    } catch (error) {
      console.error('Error en login:', error);
      throw error;
    }
  }

  /**
   * Refresca el access token usando el refresh token
   */
  async refreshAccessToken() {
    try {
      const response = await fetch(`${this.baseUrl}/api/autenticador/refresh`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ refresh_token: this.refreshToken })
      });

      const data = await response.json();

      if (!response.ok) {
        // Si falla, limpiar tokens
        this.logout();
        throw new Error(data.error || 'Error refrescando token');
      }

      // Actualizar access token
      this.accessToken = data.data.access_token;
      localStorage.setItem('access_token', this.accessToken);

      return this.accessToken;
    } catch (error) {
      console.error('Error refrescando token:', error);
      throw error;
    }
  }

  /**
   * Realiza logout
   */
  async logout() {
    try {
      await fetch(`${this.baseUrl}/api/autenticador/logout`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${this.accessToken}`,
          'Content-Type': 'application/json',
        }
      });
    } catch (error) {
      console.error('Error en logout:', error);
    }

    // Limpiar tokens locales
    this.accessToken = null;
    this.refreshToken = null;
    localStorage.removeItem('access_token');
    localStorage.removeItem('refresh_token');
    localStorage.removeItem('usuario_id');
  }

  /**
   * Realiza una solicitud GET a la API
   */
  async get(endpoint) {
    return this.request(endpoint, { method: 'GET' });
  }

  /**
   * Realiza una solicitud POST a la API
   */
  async post(endpoint, body) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(body)
    });
  }

  /**
   * Realiza una solicitud PUT a la API
   */
  async put(endpoint, body) {
    return this.request(endpoint, {
      method: 'PUT',
      body: JSON.stringify(body)
    });
  }

  /**
   * Realiza una solicitud DELETE a la API
   */
  async delete(endpoint) {
    return this.request(endpoint, { method: 'DELETE' });
  }

  /**
   * Realiza una solicitud a la API con manejo de tokens
   */
  async request(endpoint, options = {}) {
    let headers = {
      'Content-Type': 'application/json',
      ...options.headers
    };

    // Agregar token de acceso si existe
    if (this.accessToken) {
      headers['Authorization'] = `Bearer ${this.accessToken}`;
    }

    let response = await fetch(`${this.baseUrl}${endpoint}`, {
      ...options,
      headers
    });

    let data = await response.json();

    // Si el token expiró (401), intentar refrescar
    if (response.status === 401 && data.error?.includes('expirado')) {
      try {
        await this.refreshAccessToken();

        // Reintentar la solicitud con el nuevo token
        headers['Authorization'] = `Bearer ${this.accessToken}`;
        response = await fetch(`${this.baseUrl}${endpoint}`, {
          ...options,
          headers
        });
        data = await response.json();
      } catch (error) {
        console.error('No se pudo refrescar el token:', error);
        this.logout();
        throw new Error('Sesión expirada. Inicie sesión nuevamente.');
      }
    }

    if (!response.ok) {
      throw new Error(data.error || 'Error en la solicitud');
    }

    return data.data;
  }

  /**
   * Verifica si el usuario está autenticado
   */
  isAuthenticated() {
    return !!this.accessToken && !!this.refreshToken;
  }

  /**
   * Obtiene el ID del usuario autenticado
   */
  getUserId() {
    return localStorage.getItem('usuario_id');
  }
}

// Uso:
const auth = new AuthService();

// 1. Login
// auth.login('usuario@ejemplo.com', 'password123')
//   .then(data => console.log('Login exitoso:', data))
//   .catch(error => console.error('Error:', error));

// 2. Realizar solicitud autenticada
// auth.get('/api/usuarios/me')
//   .then(data => console.log('Datos del usuario:', data))
//   .catch(error => console.error('Error:', error));

// 3. Logout
// auth.logout();
