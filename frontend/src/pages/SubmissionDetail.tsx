import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import api from '../services/api';
import { Submission, Automation } from '../types';
import { Send, CheckCircle, XCircle, Clock, ArrowLeft, Loader } from 'lucide-react';

const SubmissionDetail = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [submission, setSubmission] = useState<Submission | null>(null);
  const [automations, setAutomations] = useState<Automation[]>([]);
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState<string | null>(null);
  const [sendingAll, setSendingAll] = useState(false);

  useEffect(() => {
    loadData();
    const interval = setInterval(() => {
      if (submission?.status === 'PROCESSING') {
        loadData();
      }
    }, 3000);

    return () => clearInterval(interval);
  }, [id]);

  const loadData = async () => {
    try {
      const [subResponse, autoResponse] = await Promise.all([
        api.get<Submission>(`/submissions/${id}`),
        api.get<Automation[]>(`/automations/submission/${id}`),
      ]);
      setSubmission(subResponse.data);
      setAutomations(autoResponse.data);
    } catch (error) {
      console.error('Erro ao carregar dados:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSend = async (automationId: string) => {
    setSending(automationId);
    try {
      await api.post(`/automations/${automationId}/send`);
      loadData();
    } catch (error: any) {
      alert(error.response?.data?.error || 'Erro ao enviar automação');
    } finally {
      setSending(null);
    }
  };

  const handleSendAll = async () => {
    if (!confirm('Deseja enviar todas as automações para o Chatwoot?')) {
      return;
    }

    setSendingAll(true);
    try {
      const response = await api.post(`/automations/submission/${id}/send-all`);
      alert(`${response.data.success} automações enviadas com sucesso!`);
      if (response.data.failed > 0) {
        alert(`${response.data.failed} automações falharam.`);
      }
      loadData();
    } catch (error: any) {
      alert(error.response?.data?.error || 'Erro ao enviar automações');
    } finally {
      setSendingAll(false);
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'DRAFT':
        return (
          <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-800">
            <Clock size={14} /> Rascunho
          </span>
        );
      case 'SENT':
        return (
          <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm bg-green-100 text-green-800">
            <CheckCircle size={14} /> Enviado
          </span>
        );
      case 'ERROR':
        return (
          <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm bg-red-100 text-red-800">
            <XCircle size={14} /> Erro
          </span>
        );
      default:
        return null;
    }
  };

  if (loading) {
    return <div className="text-center">Carregando...</div>;
  }

  if (!submission) {
    return <div className="text-center">Submissão não encontrada</div>;
  }

  const draftAutomations = automations.filter((a) => a.status === 'DRAFT');

  return (
    <div>
      <button
        onClick={() => navigate('/')}
        className="mb-4 flex items-center gap-2 text-blue-600 hover:text-blue-800"
      >
        <ArrowLeft size={20} /> Voltar
      </button>

      <div className="bg-white p-6 rounded-lg shadow mb-6">
        <div className="flex items-center justify-between mb-4">
          <h1 className="text-3xl font-bold text-gray-800">{submission.title}</h1>
          {submission.status === 'PROCESSING' && (
            <span className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-yellow-100 text-yellow-800">
              <Loader className="animate-spin" size={18} />
              IA processando...
            </span>
          )}
          {submission.status === 'COMPLETED' && draftAutomations.length > 0 && (
            <button
              onClick={handleSendAll}
              disabled={sendingAll}
              className="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors disabled:bg-gray-400 flex items-center gap-2"
            >
              <Send size={18} />
              {sendingAll ? 'Enviando...' : 'Enviar Todas'}
            </button>
          )}
        </div>

        <div className="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span className="text-gray-600">Configuração:</span>
            <span className="ml-2 font-semibold">{submission.config.name}</span>
          </div>
          <div>
            <span className="text-gray-600">Provider de IA:</span>
            <span className="ml-2 font-semibold uppercase">{submission.aiProvider}</span>
          </div>
          <div>
            <span className="text-gray-600">Data:</span>
            <span className="ml-2 font-semibold">
              {new Date(submission.createdAt).toLocaleString('pt-BR')}
            </span>
          </div>
          <div>
            <span className="text-gray-600">Automações geradas:</span>
            <span className="ml-2 font-semibold">{automations.length}</span>
          </div>
        </div>

        <div className="mt-4">
          <h3 className="text-sm font-semibold text-gray-700 mb-2">Texto Original:</h3>
          <div className="bg-gray-50 p-4 rounded border text-sm font-mono whitespace-pre-wrap max-h-60 overflow-y-auto">
            {submission.originalText}
          </div>
        </div>
      </div>

      <h2 className="text-2xl font-bold text-gray-800 mb-4">
        Automações Geradas ({automations.length})
      </h2>

      {automations.length === 0 ? (
        <div className="bg-white p-8 rounded-lg shadow text-center">
          <p className="text-gray-600">
            {submission.status === 'PROCESSING'
              ? 'Aguarde enquanto a IA analisa o texto...'
              : 'Nenhuma automação foi gerada.'}
          </p>
        </div>
      ) : (
        <div className="space-y-4">
          {automations.map((automation) => (
            <div key={automation.id} className="bg-white p-6 rounded-lg shadow">
              <div className="flex items-start justify-between mb-4">
                <div className="flex-1">
                  <h3 className="text-lg font-semibold text-gray-800 mb-2">
                    {automation.name}
                  </h3>
                  <p className="text-gray-600 text-sm mb-3">{automation.description}</p>
                  {getStatusBadge(automation.status)}
                </div>
                {automation.status === 'DRAFT' && (
                  <button
                    onClick={() => handleSend(automation.id)}
                    disabled={sending === automation.id}
                    className="ml-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:bg-gray-400 flex items-center gap-2"
                  >
                    <Send size={16} />
                    {sending === automation.id ? 'Enviando...' : 'Enviar'}
                  </button>
                )}
                {automation.status === 'SENT' && automation.chatwootRuleId && (
                  <div className="ml-4 text-sm text-green-600">
                    ✓ ID: {automation.chatwootRuleId}
                  </div>
                )}
              </div>

              <div className="grid grid-cols-2 gap-4 mt-4">
                <div>
                  <h4 className="font-semibold text-gray-700 mb-2 text-sm">Condições:</h4>
                  <pre className="bg-gray-50 p-3 rounded text-xs overflow-x-auto border">
                    {JSON.stringify(automation.conditions, null, 2)}
                  </pre>
                </div>
                <div>
                  <h4 className="font-semibold text-gray-700 mb-2 text-sm">Ações:</h4>
                  <pre className="bg-gray-50 p-3 rounded text-xs overflow-x-auto border">
                    {JSON.stringify(automation.actions, null, 2)}
                  </pre>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default SubmissionDetail;
