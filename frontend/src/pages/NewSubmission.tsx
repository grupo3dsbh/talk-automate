import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';
import { ChatwootConfig } from '../types';
import { Send } from 'lucide-react';

const NewSubmission = () => {
  const [configs, setConfigs] = useState<ChatwootConfig[]>([]);
  const [formData, setFormData] = useState({
    title: '',
    originalText: '',
    configId: '',
    aiProvider: 'groq',
  });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  useEffect(() => {
    loadConfigs();
  }, []);

  const loadConfigs = async () => {
    try {
      const response = await api.get<ChatwootConfig[]>('/chatwoot-configs');
      setConfigs(response.data.filter((c) => c.isActive));
    } catch (error) {
      console.error('Erro ao carregar configurações:', error);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const response = await api.post('/submissions', formData);
      navigate(`/submission/${response.data.id}`);
    } catch (err: any) {
      setError(err.response?.data?.error || 'Erro ao criar submissão');
      setLoading(false);
    }
  };

  if (configs.length === 0) {
    return (
      <div className="bg-white p-8 rounded-lg shadow text-center">
        <h2 className="text-xl font-semibold mb-4">Nenhuma configuração disponível</h2>
        <p className="text-gray-600 mb-4">
          Você precisa adicionar uma configuração do Chatwoot antes de criar uma análise.
        </p>
        <button
          onClick={() => navigate('/configs')}
          className="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
        >
          Ir para Configurações
        </button>
      </div>
    );
  }

  return (
    <div>
      <h1 className="text-3xl font-bold text-gray-800 mb-6">Nova Análise de Texto</h1>

      <div className="bg-white p-6 rounded-lg shadow">
        {error && (
          <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit}>
          <div className="mb-4">
            <label className="block text-gray-700 font-semibold mb-2">
              Título da Análise
            </label>
            <input
              type="text"
              value={formData.title}
              onChange={(e) => setFormData({ ...formData, title: e.target.value })}
              placeholder="Ex: FAQ Clube Esmeraldas"
              className="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            />
          </div>

          <div className="mb-4">
            <label className="block text-gray-700 font-semibold mb-2">
              Configuração do Chatwoot
            </label>
            <select
              value={formData.configId}
              onChange={(e) => setFormData({ ...formData, configId: e.target.value })}
              className="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            >
              <option value="">Selecione uma configuração</option>
              {configs.map((config) => (
                <option key={config.id} value={config.id}>
                  {config.name}
                </option>
              ))}
            </select>
          </div>

          <div className="mb-4">
            <label className="block text-gray-700 font-semibold mb-2">
              Provider de IA
            </label>
            <select
              value={formData.aiProvider}
              onChange={(e) => setFormData({ ...formData, aiProvider: e.target.value })}
              className="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="groq">Groq (Mixtral)</option>
              <option value="openai">OpenAI (GPT-4)</option>
              <option value="anthropic">Anthropic (Claude)</option>
            </select>
          </div>

          <div className="mb-6">
            <label className="block text-gray-700 font-semibold mb-2">
              Texto para Análise
            </label>
            <textarea
              value={formData.originalText}
              onChange={(e) => setFormData({ ...formData, originalText: e.target.value })}
              placeholder="Cole aqui o texto com informações (FAQ, horários, preços, etc.)"
              rows={15}
              className="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
              required
            />
            <p className="text-sm text-gray-500 mt-2">
              Cole o texto contendo informações como horários, endereços, preços, etc. A IA irá analisar e sugerir automações.
            </p>
          </div>

          <div className="flex justify-end">
            <button
              type="submit"
              disabled={loading}
              className="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:bg-gray-400 flex items-center gap-2"
            >
              <Send size={18} />
              {loading ? 'Analisando...' : 'Analisar com IA'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default NewSubmission;
