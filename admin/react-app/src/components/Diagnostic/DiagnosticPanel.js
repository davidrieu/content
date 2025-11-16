import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { useTranslation } from '../../contexts/TranslationContext';
import { FiCheck, FiX, FiAlertTriangle, FiRefreshCw, FiTool } from 'react-icons/fi';

export default function DiagnosticPanel() {
    const { t } = useTranslation();
    const [diagnostic, setDiagnostic] = useState(null);
    const [loading, setLoading] = useState(true);
    const [migrating, setMigrating] = useState(false);

    useEffect(() => {
        fetchDiagnostic();
    }, []);

    const fetchDiagnostic = async () => {
        setLoading(true);
        try {
            const response = await apiFetch({
                path: '/acs/v1/diagnostic',
            });

            if (response.success) {
                // Store both data and recommendations
                setDiagnostic({
                    ...response.data,
                    recommendations: response.recommendations || []
                });
            }
        } catch (error) {
            console.error('Error fetching diagnostic:', error);
        } finally {
            setLoading(false);
        }
    };

    const runMigration = async () => {
        if (!confirm(t('Exécuter la migration de la base de données ?'))) {
            return;
        }

        setMigrating(true);
        try {
            const response = await apiFetch({
                path: '/acs/v1/diagnostic/migrate',
                method: 'POST',
            });

            if (response.success) {
                alert(t('Migration réussie !'));
                fetchDiagnostic(); // Refresh diagnostic
            } else {
                alert(t('Erreur lors de la migration: ') + response.message);
            }
        } catch (error) {
            console.error('Migration error:', error);
            alert(t('Erreur lors de la migration'));
        } finally {
            setMigrating(false);
        }
    };

    const getStatusIcon = (status) => {
        if (status) {
            return <FiCheck style={{ color: '#10b981' }} size={20} />;
        } else {
            return <FiX style={{ color: '#ef4444' }} size={20} />;
        }
    };

    if (loading) {
        return (
            <div className="acs-card" style={{ padding: 'var(--acs-spacing-6)' }}>
                <div className="acs-loading-state">
                    <div className="acs-spinner"></div>
                    <p>{t('Chargement du diagnostic...')}</p>
                </div>
            </div>
        );
    }

    if (!diagnostic) {
        return (
            <div className="acs-card" style={{ padding: 'var(--acs-spacing-6)' }}>
                <p>{t('Impossible de charger le diagnostic')}</p>
            </div>
        );
    }

    return (
        <div style={{ padding: 'var(--acs-spacing-6)' }}>
            <div style={{ marginBottom: 'var(--acs-spacing-6)' }}>
                <h1 style={{ fontSize: '1.875rem', fontWeight: 700, marginBottom: 'var(--acs-spacing-2)' }}>
                    {t('Diagnostic Système')}
                </h1>
                <p style={{ color: 'var(--acs-gray-600)' }}>
                    {t('Vérifiez l\'état de votre installation et résolvez les problèmes')}
                </p>
            </div>

            {/* Recommendations */}
            {diagnostic.recommendations && diagnostic.recommendations.length > 0 && (
                <div className="acs-card" style={{ marginBottom: 'var(--acs-spacing-6)', borderLeft: '4px solid #f59e0b' }}>
                    <div style={{ padding: 'var(--acs-spacing-5)' }}>
                        <h3 style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', marginBottom: 'var(--acs-spacing-4)' }}>
                            <FiAlertTriangle style={{ color: '#f59e0b' }} />
                            {t('Recommandations')}
                        </h3>
                        {diagnostic.recommendations.map((rec, index) => (
                            <div key={index} style={{ marginBottom: 'var(--acs-spacing-3)', display: 'flex', gap: 'var(--acs-spacing-4)', alignItems: 'flex-start' }}>
                                <span className="acs-badge" style={{
                                    backgroundColor: rec.severity === 'critical' ? '#ef4444' :
                                        rec.severity === 'warning' ? '#f59e0b' : '#3b82f6',
                                    color: 'white',
                                    textTransform: 'uppercase',
                                    fontSize: '0.75rem',
                                }}>
                                    {rec.severity}
                                </span>
                                <span style={{ flex: 1 }}>{rec.message}</span>
                                {rec.action === 'run_migration' && (
                                    <button
                                        className="acs-btn acs-btn-sm acs-btn-primary"
                                        onClick={runMigration}
                                        disabled={migrating}
                                    >
                                        <FiTool /> {migrating ? t('Migration...') : t('Migrer')}
                                    </button>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* System Status */}
            <div className="acs-card" style={{ marginBottom: 'var(--acs-spacing-6)' }}>
                <div className="acs-card-header">
                    <h3>{t('État du Système')}</h3>
                </div>
                <div style={{ padding: 'var(--acs-spacing-5)' }}>
                    <table style={{ width: '100%' }}>
                        <tbody>
                            <tr style={{ borderBottom: '1px solid var(--acs-gray-200)' }}>
                                <td style={{ padding: 'var(--acs-spacing-3)' }}>
                                    <strong>{t('Table system_logs')}</strong>
                                </td>
                                <td style={{ padding: 'var(--acs-spacing-3)', textAlign: 'right' }}>
                                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'flex-end', gap: '0.5rem' }}>
                                        {getStatusIcon(diagnostic.system_logs_table?.exists)}
                                        <span>{diagnostic.system_logs_table?.exists ? t('Existe') : t('Manquante')}</span>
                                    </div>
                                </td>
                            </tr>
                            {diagnostic.system_logs_table?.exists && (
                                <tr style={{ borderBottom: '1px solid var(--acs-gray-200)' }}>
                                    <td style={{ padding: 'var(--acs-spacing-3)' }}>
                                        {t('Nombre de logs')}
                                    </td>
                                    <td style={{ padding: 'var(--acs-spacing-3)', textAlign: 'right' }}>
                                        <strong>{diagnostic.system_logs_table.count}</strong>
                                    </td>
                                </tr>
                            )}
                            <tr style={{ borderBottom: '1px solid var(--acs-gray-200)' }}>
                                <td style={{ padding: 'var(--acs-spacing-3)' }}>
                                    <strong>{t('Clé API Claude')}</strong>
                                </td>
                                <td style={{ padding: 'var(--acs-spacing-3)', textAlign: 'right' }}>
                                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'flex-end', gap: '0.5rem' }}>
                                        {getStatusIcon(diagnostic.api_configuration?.claude_api_key_configured)}
                                        <span>{diagnostic.api_configuration?.claude_api_key_preview}</span>
                                    </div>
                                </td>
                            </tr>
                            <tr style={{ borderBottom: '1px solid var(--acs-gray-200)' }}>
                                <td style={{ padding: 'var(--acs-spacing-3)' }}>
                                    <strong>{t('Profil utilisateur')}</strong>
                                </td>
                                <td style={{ padding: 'var(--acs-spacing-3)', textAlign: 'right' }}>
                                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'flex-end', gap: '0.5rem' }}>
                                        {getStatusIcon(diagnostic.user_profile?.has_profile)}
                                        <span>{diagnostic.user_profile?.has_profile ? t('Configuré') : t('Manquant')}</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Recent Logs */}
            {diagnostic.system_logs_table?.exists && diagnostic.system_logs_table.recent?.length > 0 && (
                <div className="acs-card">
                    <div className="acs-card-header">
                        <h3>{t('Logs Récents')}</h3>
                    </div>
                    <div style={{ padding: 'var(--acs-spacing-5)' }}>
                        <table className="acs-table">
                            <thead>
                                <tr>
                                    <th>{t('Niveau')}</th>
                                    <th>{t('Catégorie')}</th>
                                    <th>{t('Message')}</th>
                                    <th>{t('Date')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {diagnostic.system_logs_table.recent.map((log) => (
                                    <tr key={log.id}>
                                        <td>
                                            <span className={`acs-badge acs-log-level acs-log-level-${log.level}`}>
                                                {log.level}
                                            </span>
                                        </td>
                                        <td><span className="acs-badge">{log.category}</span></td>
                                        <td>{log.message}</td>
                                        <td className="acs-text-sm acs-text-muted">{log.created_at}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* Refresh Button */}
            <div style={{ marginTop: 'var(--acs-spacing-6)', textAlign: 'center' }}>
                <button className="acs-btn acs-btn-secondary" onClick={fetchDiagnostic}>
                    <FiRefreshCw /> {t('Actualiser le diagnostic')}
                </button>
            </div>
        </div>
    );
}
