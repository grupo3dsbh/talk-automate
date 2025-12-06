<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatwoot Automation Compiler - SaaS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100">
    <div id="app"></div>

    <script>
        // ========== CONFIGURAÇÃO ==========
        const API_URL = window.location.origin;

        // ========== HELPERS ==========
        const storage = {
            getToken: () => localStorage.getItem('token'),
            setToken: (token) => localStorage.setItem('token', token),
            removeToken: () => localStorage.removeItem('token'),
            getUser: () => JSON.parse(localStorage.getItem('user') || 'null'),
            setUser: (user) => localStorage.setItem('user', JSON.stringify(user))
        };

        const api = {
            async request(url, options = {}) {
                const token = storage.getToken();
                const headers = {
                    'Content-Type': 'application/json',
                    ...(token && { 'Authorization': `Bearer ${token}` }),
                    ...options.headers
                };

                const response = await fetch(`${API_URL}${url}`, {
                    ...options,
                    headers
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.error || 'Erro na requisição');
                }

                return response.json();
            },

            auth: {
                login: (email, password) =>
                    api.request('/api/auth/login', {
                        method: 'POST',
                        body: JSON.stringify({ email, password })
                    }),
                register: (name, email, password) =>
                    api.request('/api/auth/register', {
                        method: 'POST',
                        body: JSON.stringify({ name, email, password })
                    })
            },

            configs: {
                list: () => api.request('/api/chatwoot-configs'),
                create: (data) =>
                    api.request('/api/chatwoot-configs', {
                        method: 'POST',
                        body: JSON.stringify(data)
                    }),
                delete: (id) =>
                    api.request(`/api/chatwoot-configs/${id}`, { method: 'DELETE' })
            },

            submissions: {
                list: () => api.request('/api/submissions'),
                get: (id) => api.request(`/api/submissions/${id}`),
                create: (data) =>
                    api.request('/api/submissions', {
                        method: 'POST',
                        body: JSON.stringify(data)
                    }),
                delete: (id) =>
                    api.request(`/api/submissions/${id}`, { method: 'DELETE' })
            },

            automations: {
                send: (id) =>
                    api.request(`/api/automations/${id}/send`, { method: 'POST' }),
                update: (id, data) =>
                    api.request(`/api/automations/${id}`, {
                        method: 'PUT',
                        body: JSON.stringify(data)
                    })
            }
        };

        // ========== ROUTER ==========
        class Router {
            constructor() {
                this.routes = {};
                this.currentRoute = null;
            }

            addRoute(path, handler) {
                this.routes[path] = handler;
                return this;
            }

            navigate(path) {
                this.currentRoute = path;
                window.history.pushState({}, '', path);
                this.render();
            }

            render() {
                const route = this.routes[this.currentRoute] || this.routes['/'];
                const app = document.getElementById('app');
                app.innerHTML = route();
            }

            init() {
                window.addEventListener('popstate', () => {
                    this.currentRoute = window.location.pathname;
                    this.render();
                });

                this.currentRoute = window.location.pathname;
                this.render();
            }
        }

        const router = new Router();

        // ========== COMPONENTES ==========
        const LoginPage = () => `
            <div class="min-h-screen flex items-center justify-center">
                <div class="bg-white p-8 rounded-lg shadow-md w-96">
                    <h1 class="text-2xl font-bold mb-6 text-center">Chatwoot Automation</h1>
                    <form id="loginForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Email</label>
                            <input type="email" name="email" required
                                   class="w-full px-3 py-2 border rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Senha</label>
                            <input type="password" name="password" required
                                   class="w-full px-3 py-2 border rounded-md">
                        </div>
                        <button type="submit"
                                class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">
                            Entrar
                        </button>
                    </form>
                    <p class="mt-4 text-center text-sm">
                        Não tem conta?
                        <a href="#" onclick="router.navigate('/register'); return false;"
                           class="text-blue-600">Registrar</a>
                    </p>
                    <div id="loginError" class="mt-4 text-red-600 text-sm text-center"></div>
                </div>
            </div>
        `;

        const DashboardPage = () => {
            loadDashboard();
            return `
                <div class="min-h-screen bg-gray-100">
                    <nav class="bg-white shadow-sm">
                        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
                            <h1 class="text-xl font-bold">Chatwoot Automation</h1>
                            <div class="space-x-4">
                                <a href="#" onclick="router.navigate('/dashboard'); return false;"
                                   class="text-gray-700">Dashboard</a>
                                <a href="#" onclick="router.navigate('/configs'); return false;"
                                   class="text-gray-700">Configurações</a>
                                <a href="#" onclick="logout(); return false;"
                                   class="text-red-600">Sair</a>
                            </div>
                        </div>
                    </nav>
                    <div class="max-w-7xl mx-auto px-4 py-8">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold">Minhas Análises</h2>
                            <button onclick="router.navigate('/new-submission'); return false;"
                                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                Nova Análise
                            </button>
                        </div>
                        <div id="submissionsList" class="space-y-4">
                            <p class="text-center text-gray-500">Carregando...</p>
                        </div>
                    </div>
                </div>
            `;
        };

        // ========== EVENT HANDLERS ==========
        document.addEventListener('submit', async (e) => {
            if (e.target.id === 'loginForm') {
                e.preventDefault();
                const formData = new FormData(e.target);
                try {
                    const result = await api.auth.login(
                        formData.get('email'),
                        formData.get('password')
                    );
                    storage.setToken(result.token);
                    storage.setUser(result.user);
                    router.navigate('/dashboard');
                } catch (error) {
                    document.getElementById('loginError').textContent = error.message;
                }
            }
        });

        async function loadDashboard() {
            setTimeout(async () => {
                try {
                    const { submissions } = await api.submissions.list();
                    const list = document.getElementById('submissionsList');
                    if (!submissions.length) {
                        list.innerHTML = '<p class="text-center text-gray-500">Nenhuma análise ainda</p>';
                        return;
                    }
                    list.innerHTML = submissions.map(s => `
                        <div class="bg-white p-4 rounded-lg shadow">
                            <h3 class="font-bold">${s.title}</h3>
                            <p class="text-sm text-gray-600">Status: ${s.status}</p>
                            <p class="text-sm text-gray-600">Automações: ${s.automation_count || 0}</p>
                            <a href="#" onclick="router.navigate('/submissions/${s.id}'); return false;"
                               class="text-blue-600 text-sm">Ver detalhes →</a>
                        </div>
                    `).join('');
                } catch (error) {
                    console.error(error);
                }
            }, 100);
        }

        function logout() {
            storage.removeToken();
            storage.setUser(null);
            router.navigate('/');
        }

        // ========== ROTAS ==========
        router
            .addRoute('/', LoginPage)
            .addRoute('/dashboard', DashboardPage)
            .init();

    </script>
</body>
</html>
