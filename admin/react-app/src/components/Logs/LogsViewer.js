import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { FiRefreshCw, FiTrash2, FiFilter, FiAlertCircle, FiInfo, FiAlertTriangle, FiXCircle, FiZap } from 'react-icons/fi';

export default function LogsViewer() {
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
        if (!confirm(__('Supprimer les logs de plus de 30 jours ?', 'ai-content-studio'))) {
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
            alert(__('Erreur lors de la suppression des logs', 'ai-content-studio'));
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
                        <div className="acs-stat-label">{__('Total', 'ai-content-studio')}</div>
                        <div className="acs-stat-value">{stats.total || 0}</div>
                    </div>
                    <div className="acs-stat-card acs-stat-errors">
                        <div className="acs-stat-label">{__('Erreurs', 'ai-content-studio')}</div>
                        <div className="acs-stat-value">{stats.errors || 0}</div>
                    </div>
                    <div className="acs-stat-card acs-stat-critical">
                        <div className="acs-stat-label">{__('Critiques', 'ai-content-studio')}</div>
                        <div className="acs-stat-value">{stats.critical || 0}</div>
                    </div>
                    <div className="acs-stat-card acs-stat-warnings">
                        <div className="acs-stat-label">{__('Avertissements', 'ai-content-studio')}</div>
                        <div className="acs-stat-value">{stats.warnings || 0}</div>
                    </div>
                    <div className="acs-stat-card">
                        <div className="acs-stat-label">{__('Dernière heure', 'ai-content-studio')}</div>
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
                        <option value="">{__('Tous les niveaux', 'ai-content-studio')}</option>
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
                        <option value="">{__('Toutes les catégories', 'ai-content-studio')}</option>
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
                        <option value="50">50 {__('logs', 'ai-content-studio')}</option>
                        <option value="100">100 {__('logs', 'ai-content-studio')}</option>
                        <option value="200">200 {__('logs', 'ai-content-studio')}</option>
                        <option value="500">500 {__('logs', 'ai-content-studio')}</option>
                    </select>
                </div>

                <div className="acs-logs-actions">
                    <label className="acs-checkbox-label">
                        <input
                            type="checkbox"
                            checked={autoRefresh}
                            onChange={(e) => setAutoRefresh(e.target.checked)}
                        />
                        <span>{__('Auto-refresh', 'ai-content-studio')}</span>
                    </label>
                    <button className="acs-btn acs-btn-sm acs-btn-secondary" onClick={fetchLogs}>
                        <FiRefreshCw /> {__('Actualiser', 'ai-content-studio')}
                    </button>
                    <button className="acs-btn acs-btn-sm acs-btn-danger" onClick={clearOldLogs}>
                        <FiTrash2 /> {__('Nettoyer (30j+)', 'ai-content-studio')}
                    </button>
                </div>
            </div>

            {/* Logs table */}
            <div className="acs-logs-list">
                {loading ? (
                    <div className="acs-loading-state">
                        <div className="acs-spinner"></div>
                        <p>{__('Chargement des logs...', 'ai-content-studio')}</p>
                    </div>
                ) : logs.length === 0 ? (
                    <div className="acs-empty-state">
                        <FiInfo size={48} />
                        <p>{__('Aucun log trouvé', 'ai-content-studio')}</p>
                    </div>
                ) : (
                    <table className="acs-table acs-logs-table">
                        <thead>
                            <tr>
                                <th style={{ width: '40px' }}></th>
                                <th style={{ width: '100px' }}>{__('Niveau', 'ai-content-studio')}</th>
                                <th style={{ width: '120px' }}>{__('Catégorie', 'ai-content-studio')}</th>
                                <th>{__('Message', 'ai-content-studio')}</th>
                                <th style={{ width: '150px' }}>{__('Fichier', 'ai-content-studio')}</th>
                                <th style={{ width: '180px' }}>{__('Date', 'ai-content-studio')}</th>
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
                                                            <strong>{__('Contexte:', 'ai-content-studio')}</strong>
                                                            <pre>{JSON.stringify(log.context, null, 2)}</pre>
                                                        </div>
                                                    )}
                                                    {log.request_uri && (
                                                        <div className="acs-log-context-section">
                                                            <strong>{__('URI:', 'ai-content-studio')}</strong> {log.request_uri}
                                                        </div>
                                                    )}
                                                    {log.ip_address && (
                                                        <div className="acs-log-context-section">
                                                            <strong>{__('IP:', 'ai-content-studio')}</strong> {log.ip_address}
                                                        </div>
                                                    )}
                                                    {log.user_agent && (
                                                        <div className="acs-log-context-section">
                                                            <strong>{__('User Agent:', 'ai-content-studio')}</strong> {log.user_agent}
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
