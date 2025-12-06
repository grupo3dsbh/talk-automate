import { Outlet, Link, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { LogOut, Settings, Plus, Home } from 'lucide-react';

const Layout = () => {
  const { user, logout } = useAuth();
  const location = useLocation();

  const isActive = (path: string) => {
    return location.pathname === path
      ? 'bg-blue-700 text-white'
      : 'text-gray-300 hover:bg-blue-600 hover:text-white';
  };

  return (
    <div className="min-h-screen bg-gray-100">
      {/* Navbar */}
      <nav className="bg-blue-600 text-white shadow-lg">
        <div className="container mx-auto px-4">
          <div className="flex items-center justify-between h-16">
            <div className="flex items-center space-x-8">
              <h1 className="text-xl font-bold">Chatwoot Automation</h1>
              <div className="flex space-x-2">
                <Link
                  to="/"
                  className={`px-4 py-2 rounded-md transition-colors flex items-center gap-2 ${isActive('/')}`}
                >
                  <Home size={18} />
                  Dashboard
                </Link>
                <Link
                  to="/configs"
                  className={`px-4 py-2 rounded-md transition-colors flex items-center gap-2 ${isActive('/configs')}`}
                >
                  <Settings size={18} />
                  Configurações
                </Link>
                <Link
                  to="/new"
                  className={`px-4 py-2 rounded-md transition-colors flex items-center gap-2 ${isActive('/new')}`}
                >
                  <Plus size={18} />
                  Nova Análise
                </Link>
              </div>
            </div>
            <div className="flex items-center space-x-4">
              <span className="text-sm">{user?.name}</span>
              <button
                onClick={logout}
                className="px-4 py-2 rounded-md bg-red-500 hover:bg-red-600 transition-colors flex items-center gap-2"
              >
                <LogOut size={18} />
                Sair
              </button>
            </div>
          </div>
        </div>
      </nav>

      {/* Content */}
      <main className="container mx-auto px-4 py-8">
        <Outlet />
      </main>
    </div>
  );
};

export default Layout;
