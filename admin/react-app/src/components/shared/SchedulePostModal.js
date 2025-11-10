import { useState } from '@wordpress/element';
import { FiX, FiCalendar, FiClock, FiSave } from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Modal de planification de post
 *
 * @param {Object} props
 * @param {boolean} props.isOpen - Modal ouvert ou fermé
 * @param {Function} props.onClose - Callback pour fermer le modal
 * @param {Object} props.post - Post à planifier {content, hashtags, platform}
 * @param {Function} props.onSuccess - Callback après succès
 */
export default function SchedulePostModal({ isOpen, onClose, post, onSuccess }) {
    const [scheduledDate, setScheduledDate] = useState('');
    const [scheduledTime, setScheduledTime] = useState('09:00');
    const [status, setStatus] = useState('scheduled'); // scheduled ou draft
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    // Initialiser avec la date du jour au montage
    useState(() => {
        const today = new Date();
        const dateStr = today.toISOString().split('T')[0];
        setScheduledDate(dateStr);
    }, []);

    if (!isOpen) return null;

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!scheduledDate || !scheduledTime) {
            setError(__('Date et heure requises', 'ai-content-studio'));
            return;
        }

        setSaving(true);
        setError('');

        try {
            const scheduledFor = `${scheduledDate} ${scheduledTime}:00`;

            const response = await apiFetch({
                path: '/acs/v1/calendar/posts',
                method: 'POST',
                data: {
                    content: post.content,
                    hashtags: post.hashtags || [],
                    platform: post.platform,
                    scheduled_for: scheduledFor,
                    status: status,
                    language: post.language || 'fr',
                    metadata: post.metadata || {},
                },
            });

            if (response.success) {
                if (onSuccess) {
                    onSuccess(response.data);
                }
                onClose();
            } else {
                setError(response.message || __('Erreur lors de la planification', 'ai-content-studio'));
            }
        } catch (err) {
            console.error('Error scheduling post:', err);
            setError(err.message || __('Erreur lors de la planification', 'ai-content-studio'));
        } finally {
            setSaving(false);
        }
    };

    const getMinDate = () => {
        return new Date().toISOString().split('T')[0];
    };

    const PLATFORM_EMOJIS = {
        instagram: '📷',
        facebook: '📘',
        linkedin: '💼',
        twitter: '🐦',
        tiktok: '🎵',
        youtube: '📹',
        pinterest: '📌',
        snapchat: '👻',
    };

    return (
        <div
            style={{
                position: 'fixed',
                top: 0,
                left: 0,
                right: 0,
                bottom: 0,
                background: 'rgba(0, 0, 0, 0.5)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                zIndex: 9999,
                padding: 'var(--acs-spacing-4)',
            }}
            onClick={onClose}
        >
            <div
                style={{
                    background: 'var(--acs-white)',
                    borderRadius: 'var(--acs-radius-lg)',
                    maxWidth: '600px',
                    width: '100%',
                    maxHeight: '90vh',
                    overflow: 'auto',
                    boxShadow: '0 20px 60px rgba(0, 0, 0, 0.3)',
                }}
                onClick={(e) => e.stopPropagation()}
            >
                {/* Header */}
                <div
                    style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        padding: 'var(--acs-spacing-4)',
                        borderBottom: '1px solid var(--acs-gray-200)',
                    }}
                >
                    <div style={{ display: 'flex', alignItems: 'center', gap: 'var(--acs-spacing-2)' }}>
                        <FiCalendar size={24} style={{ color: 'var(--acs-primary)' }} />
                        <h2 style={{ margin: 0, fontSize: '1.25rem', fontWeight: 600 }}>
                            {__('Planifier ce post', 'ai-content-studio')}
                        </h2>
                    </div>
                    <button
                        onClick={onClose}
                        style={{
                            background: 'none',
                            border: 'none',
                            cursor: 'pointer',
                            padding: '8px',
                            color: 'var(--acs-gray-600)',
                            transition: 'color 0.2s',
                        }}
                        onMouseEnter={(e) => (e.currentTarget.style.color = 'var(--acs-gray-900)')}
                        onMouseLeave={(e) => (e.currentTarget.style.color = 'var(--acs-gray-600)')}
                    >
                        <FiX size={24} />
                    </button>
                </div>

                {/* Body */}
                <form onSubmit={handleSubmit} style={{ padding: 'var(--acs-spacing-4)' }}>
                    {/* Prévisualisation du post */}
                    <div style={{ marginBottom: 'var(--acs-spacing-4)' }}>
                        <label className="acs-form-label">{__('Aperçu du post', 'ai-content-studio')}</label>
                        <div
                            style={{
                                padding: 'var(--acs-spacing-4)',
                                background: 'var(--acs-gray-50)',
                                borderRadius: 'var(--acs-radius)',
                                border: '1px solid var(--acs-gray-200)',
                            }}
                        >
                            <div style={{ display: 'flex', alignItems: 'center', gap: 'var(--acs-spacing-2)', marginBottom: 'var(--acs-spacing-2)' }}>
                                <span style={{ fontSize: '1.5rem' }}>{PLATFORM_EMOJIS[post.platform] || '📝'}</span>
                                <span style={{ fontWeight: 600, textTransform: 'capitalize' }}>{post.platform}</span>
                            </div>
                            <div
                                style={{
                                    fontSize: 'var(--acs-font-size-sm)',
                                    lineHeight: 1.6,
                                    whiteSpace: 'pre-wrap',
                                    maxHeight: '150px',
                                    overflow: 'auto',
                                    marginBottom: 'var(--acs-spacing-2)',
                                }}
                            >
                                {post.content}
                            </div>
                            {post.hashtags && post.hashtags.length > 0 && (
                                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 'var(--acs-spacing-1)' }}>
                                    {post.hashtags.map((tag, i) => (
                                        <span
                                            key={i}
                                            style={{
                                                fontSize: 'var(--acs-font-size-sm)',
                                                color: 'var(--acs-primary)',
                                                fontWeight: 500,
                                            }}
                                        >
                                            {tag}
                                        </span>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Date et Heure */}
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 'var(--acs-spacing-3)', marginBottom: 'var(--acs-spacing-4)' }}>
                        <div className="acs-form-group">
                            <label className="acs-form-label">
                                <FiCalendar size={14} style={{ marginRight: '4px' }} />
                                {__('Date', 'ai-content-studio')}
                            </label>
                            <input
                                type="date"
                                className="acs-form-control"
                                value={scheduledDate}
                                onChange={(e) => setScheduledDate(e.target.value)}
                                min={getMinDate()}
                                required
                            />
                        </div>

                        <div className="acs-form-group">
                            <label className="acs-form-label">
                                <FiClock size={14} style={{ marginRight: '4px' }} />
                                {__('Heure', 'ai-content-studio')}
                            </label>
                            <input
                                type="time"
                                className="acs-form-control"
                                value={scheduledTime}
                                onChange={(e) => setScheduledTime(e.target.value)}
                                required
                            />
                        </div>
                    </div>

                    {/* Statut */}
                    <div className="acs-form-group" style={{ marginBottom: 'var(--acs-spacing-4)' }}>
                        <label className="acs-form-label">{__('Statut', 'ai-content-studio')}</label>
                        <div style={{ display: 'flex', gap: 'var(--acs-spacing-2)' }}>
                            <label
                                style={{
                                    flex: 1,
                                    padding: 'var(--acs-spacing-3)',
                                    border: `2px solid ${status === 'scheduled' ? 'var(--acs-primary)' : 'var(--acs-gray-200)'}`,
                                    borderRadius: 'var(--acs-radius)',
                                    cursor: 'pointer',
                                    background: status === 'scheduled' ? 'rgba(99, 102, 241, 0.05)' : 'var(--acs-white)',
                                    transition: 'all 0.2s',
                                    textAlign: 'center',
                                }}
                            >
                                <input
                                    type="radio"
                                    name="status"
                                    value="scheduled"
                                    checked={status === 'scheduled'}
                                    onChange={(e) => setStatus(e.target.value)}
                                    style={{ display: 'none' }}
                                />
                                <div style={{ fontWeight: 600, marginBottom: '4px' }}>📅 {__('Planifié', 'ai-content-studio')}</div>
                                <div style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)' }}>
                                    {__('Sera publié à la date choisie', 'ai-content-studio')}
                                </div>
                            </label>

                            <label
                                style={{
                                    flex: 1,
                                    padding: 'var(--acs-spacing-3)',
                                    border: `2px solid ${status === 'draft' ? 'var(--acs-primary)' : 'var(--acs-gray-200)'}`,
                                    borderRadius: 'var(--acs-radius)',
                                    cursor: 'pointer',
                                    background: status === 'draft' ? 'rgba(99, 102, 241, 0.05)' : 'var(--acs-white)',
                                    transition: 'all 0.2s',
                                    textAlign: 'center',
                                }}
                            >
                                <input
                                    type="radio"
                                    name="status"
                                    value="draft"
                                    checked={status === 'draft'}
                                    onChange={(e) => setStatus(e.target.value)}
                                    style={{ display: 'none' }}
                                />
                                <div style={{ fontWeight: 600, marginBottom: '4px' }}>📝 {__('Brouillon', 'ai-content-studio')}</div>
                                <div style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)' }}>
                                    {__('Enregistrer sans publier', 'ai-content-studio')}
                                </div>
                            </label>
                        </div>
                    </div>

                    {/* Error message */}
                    {error && (
                        <div
                            className="acs-alert acs-alert-danger"
                            style={{ marginBottom: 'var(--acs-spacing-4)' }}
                        >
                            {error}
                        </div>
                    )}

                    {/* Footer buttons */}
                    <div style={{ display: 'flex', gap: 'var(--acs-spacing-2)', justifyContent: 'flex-end' }}>
                        <button
                            type="button"
                            className="acs-btn acs-btn-outline-primary"
                            onClick={onClose}
                            disabled={saving}
                        >
                            {__('Annuler', 'ai-content-studio')}
                        </button>
                        <button
                            type="submit"
                            className="acs-btn acs-btn-primary"
                            disabled={saving}
                        >
                            {saving ? (
                                <>
                                    <div className="acs-spinner" style={{ width: '16px', height: '16px', borderWidth: '2px' }} />
                                    {__('Enregistrement...', 'ai-content-studio')}
                                </>
                            ) : (
                                <>
                                    <FiSave />
                                    {status === 'scheduled' ? __('Planifier', 'ai-content-studio') : __('Sauvegarder', 'ai-content-studio')}
                                </>
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
