import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { useTranslation } from '../../contexts/TranslationContext';
import { FiRefreshCw, FiTrash2, FiFilter, FiAlertCircle, FiInfo, FiAlertTriangle, FiXCircle, FiZap } from 'react-icons/fi';

export default function LogsViewer() {
    const { t } = useTranslation();
    const [logs, setLogs] = useState([]);
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filter, setFilter] = useState({ level: '', category: '', limit: 100 });
    const [expandedLog, setExpandedLog] = useState(null);
    const [autoRefresh, setAutoRefresh] = useState(false);

    useEffect(() => {
        fetchLogs();
        fetchStats();
    }, [filter]);

    useEffect(() => {
        if (autoRefresh) {
            const interval = setInterval(() => {
                fetchLogs();
                fetchStats();
            }, 5000); // Refresh every 5 seconds
            return () => clearInterval(interval);
        }
    }, [autoRefresh, filter]);

    const fetchLogs = async () => {
        try {
            const params = new URLSearchParams();
            if (filter.level) params.append('level', filter.level);
            if (filter.category) params.append('category', filter.category);
            params.append('limit', filter.limit);

            const response = await apiFetch({
                path: `/acs/v1/logs?${params.toString()}`,
            });

            if (response.success) {
                setLogs(response.data);
            }
        } catch (error) {
            console.error('Error fetching logs:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchStats = async () => {
        try {
            const response = await apiFetch({
                path: '/acs/v1/logs/stats',
            });

            if (response.success) {
                setStats(response.data);
            }
        } catch (error) {
            console.error('Error fetching stats:', error);
        }
    };

    const clearOldLogs = async () => {
        if (!confirm(t('Supprimer les logs de plus de 30 jours ?'))) {
            return;
        }

        try {
            const response = await apiFetch({
                path: '/acs/v1/logs/clear',
                method: 'POST',
                data: { days: 30 },
            });

            if (response.success) {
                alert(response.message);
                fetchLogs();
                fetchStats();
            }
        } catch (error) {
            console.error('Error clearing logs:', error);
            alert(t('Erreur lors de la suppression des logs'));
        }
    };

    const getLevelIcon = (level) => {
        switch (level) {
            case 'error':
                return <FiXCircle className="acs-log-icon acs-log-icon-error" />;
            case 'critical':
                return <FiAlertCircle className="acs-log-icon acs-log-icon-critical" />;
            case 'warning':
                return <FiAlertTriangle className="acs-log-icon acs-log-icon-warning" />;
            case 'info':
                return <FiInfo className="acs-log-icon acs-log-icon-info" />;
            case 'debug':
                return <FiZap className="acs-log-icon acs-log-icon-debug" />;
            default:
                return <FiInfo className="acs-log-icon" />;
        }
    };

    const getLevelClass = (level) => {
        return `acs-log-level acs-log-level-${level}`;
    };

    return (
        <div className="acs-logs-container">
            {/* Header with stats */}
            {stats && (
                <div className="acs-logs-stats">
                    <div className="acs-stat-card">
                        <div className="acs-stat-label">{t('Total')}</div>
                        <div className="acs-stat-value">{stats.total || 0}</div>
                    </div>
                    <div className="acs-stat-card acs-stat-errors">
                        <div className="acs-stat-label">{t('Erreurs')}</div>
                        <div className="acs-stat-value">{stats.errors || 0}</div>
                    </div>
                    <div className="acs-stat-card acs-stat-critical">
                        <div className="acs-stat-label">{t('Critiques')}</div>
                        <div className="acs-stat-value">{stats.critical || 0}</div>
                    </div>
                    <div className="acs-stat-card acs-stat-warnings">
                        <div className="acs-stat-label">{t('Avertissements')}</div>
                        <div className="acs-stat-value">{stats.warnings || 0}</div>
                    </div>
                    <div className="acs-stat-card">
                        <div className="acs-stat-label">{t('Dernière heure')}</div>
                        <div className="acs-stat-value">{stats.last_hour || 0}</div>
                    </div>
                </div>
            )}

            {/* Filters and actions */}
            <div className="acs-logs-toolbar">
                <div className="acs-logs-filters">
                    <select
                        className="acs-select"
                        value={filter.level}
                        onChange={(e) => setFilter({ ...filter, level: e.target.value })}
                    >
                        <option value="">{t('Tous les niveaux')}</option>
                        <option value="debug">Debug</option>
                        <option value="info">Info</option>
                        <option value="warning">Warning</option>
                        <option value="error">Error</option>
                        <option value="critical">Critical</option>
                    </select>

                    <select
                        className="acs-select"
                        value={filter.category}
                        onChange={(e) => setFilter({ ...filter, category: e.target.value })}
                    >
                        <option value="">{t('Toutes les catégories')}</option>
                        <option value="api">API</option>
                        <option value="generation">Generation</option>
                        <option value="auth">Auth</option>
                        <option value="database">Database</option>
                        <option value="general">General</option>
                    </select>

                    <select
                        className="acs-select"
                        value={filter.limit}
                        onChange={(e) => setFilter({ ...filter, limit: parseInt(e.target.value) })}
                    >
                        <option value="50">50 {t('logs')}</option>
                        <option value="100">100 {t('logs')}</option>
                        <option value="200">200 {t('logs')}</option>
                        <option value="500">500 {t('logs')}</option>
                    </select>
                </div>

                <div className="acs-logs-actions">
                    <label className="acs-checkbox-label">
                        <input
                            type="checkbox"
                            checked={autoRefresh}
                            onChange={(e) => setAutoRefresh(e.target.checked)}
                        />
                        <span>{t('Auto-refresh')}</span>
                    </label>
                    <button className="acs-btn acs-btn-sm acs-btn-secondary" onClick={fetchLogs}>
                        <FiRefreshCw /> {t('Actualiser')}
                    </button>
                    <button className="acs-btn acs-btn-sm acs-btn-danger" onClick={clearOldLogs}>
                        <FiTrash2 /> {t('Nettoyer (30j+)')}
                    </button>
                </div>
            </div>

            {/* Logs table */}
            <div className="acs-logs-list">
                {loading ? (
                    <div className="acs-loading-state">
                        <div className="acs-spinner"></div>
                        <p>{t('Chargement des logs...')}</p>
                    </div>
                ) : logs.length === 0 ? (
                    <div className="acs-empty-state">
                        <FiInfo size={48} />
                        <p>{t('Aucun log trouvé')}</p>
                    </div>
                ) : (
                    <table className="acs-table acs-logs-table">
                        <thead>
                            <tr>
                                <th style={{ width: '40px' }}></th>
                                <th style={{ width: '100px' }}>{t('Niveau')}</th>
                                <th style={{ width: '120px' }}>{t('Catégorie')}</th>
                                <th>{t('Message')}</th>
                                <th style={{ width: '150px' }}>{t('Fichier')}</th>
                                <th style={{ width: '180px' }}>{t('Date')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {logs.map((log) => (
                                <>
                                    <tr
                                        key={log.id}
                                        className="acs-log-row"
                                        onClick={() => setExpandedLog(expandedLog === log.id ? null : log.id)}
                                        style={{ cursor: 'pointer' }}
                                    >
                                        <td>{getLevelIcon(log.level)}</td>
                                        <td>
                                            <span className={getLevelClass(log.level)}>
                                                {log.level}
                                            </span>
                                        </td>
                                        <td><span className="acs-badge">{log.category}</span></td>
                                        <td>{log.message}</td>
                                        <td className="acs-text-muted acs-text-sm">
                                            {log.file && `${log.file}:${log.line}`}
                                        </td>
                                        <td className="acs-text-muted acs-text-sm">{log.created_at}</td>
                                    </tr>
                                    {expandedLog === log.id && (log.context || log.user_agent || log.ip_address) && (
                                        <tr className="acs-log-details">
                                            <td colSpan="6">
                                                <div className="acs-log-context">
                                                    {log.context && (
                                                        <div className="acs-log-context-section">
                                                            <strong>{t('Contexte:')}</strong>
                                                            <pre>{JSON.stringify(log.context, null, 2)}</pre>
                                                        </div>
                                                    )}
                                                    {log.request_uri && (
                                                        <div className="acs-log-context-section">
                                                            <strong>{t('URI:')}</strong> {log.request_uri}
                                                        </div>
                                                    )}
                                                    {log.ip_address && (
                                                        <div className="acs-log-context-section">
                                                            <strong>{t('IP:')}</strong> {log.ip_address}
                                                        </div>
                                                    )}
                                                    {log.user_agent && (
                                                        <div className="acs-log-context-section">
                                                            <strong>{t('User Agent:')}</strong> {log.user_agent}
                                                        </div>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </div>
    );
}
