import { useState } from '@wordpress/element';
import { FiX, FiEdit, FiTrash2, FiCopy, FiCalendar, FiClock } from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Modal de détails/édition d'un post
 *
 * @param {Object} props
 * @param {boolean} props.isOpen - Modal ouvert ou fermé
 * @param {Function} props.onClose - Callback pour fermer le modal
 * @param {Object} props.post - Post à afficher/éditer
 * @param {Function} props.onUpdate - Callback après mise à jour
 * @param {Function} props.onDelete - Callback après suppression
 */
export default function PostDetailModal({ isOpen, onClose, post, onUpdate, onDelete }) {
    const [isEditing, setIsEditing] = useState(false);
    const [editedContent, setEditedContent] = useState(post?.content || '');
    const [editedScheduledFor, setEditedScheduledFor] = useState('');
    const [editedScheduledTime, setEditedScheduledTime] = useState('');
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    if (!isOpen || !post) return null;

    // Initialiser la date et l'heure si scheduled_for existe
    useState(() => {
        if (post.scheduled_for) {
            const date = new Date(post.scheduled_for);
            setEditedScheduledFor(date.toISOString().split('T')[0]);
            setEditedScheduledTime(date.toTimeString().slice(0, 5));
        }
    }, [post.scheduled_for]);

    const handleSave = async () => {
        setSaving(true);
        setError('');

        try {
            const updates = {
                content: editedContent,
            };

            if (editedScheduledFor && editedScheduledTime) {
                updates.scheduled_for = `${editedScheduledFor} ${editedScheduledTime}:00`;
            }

            const response = await apiFetch({
                path: `/acs/v1/calendar/posts/${post.id}`,
                method: 'PUT',
                data: updates,
            });

            if (response.success) {
                if (onUpdate) {
                    onUpdate({ ...post, ...updates });
                }
                setIsEditing(false);
                alert(__('✅ Post mis à jour avec succès !', 'ai-content-studio'));
            } else {
                setError(response.message || __('Erreur lors de la mise à jour', 'ai-content-studio'));
            }
        } catch (err) {
            console.error('Error updating post:', err);
            setError(err.message || __('Erreur lors de la mise à jour', 'ai-content-studio'));
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = async () => {
        if (!confirm(__('Êtes-vous sûr de vouloir supprimer ce post ?', 'ai-content-studio'))) {
            return;
        }

        setSaving(true);
        try {
            const response = await apiFetch({
                path: `/acs/v1/calendar/posts/${post.id}`,
                method: 'DELETE',
            });

            if (response.success) {
                if (onDelete) {
                    onDelete(post.id);
                }
                onClose();
                alert(__('✅ Post supprimé avec succès !', 'ai-content-studio'));
            }
        } catch (err) {
            console.error('Error deleting post:', err);
            alert(__('Erreur lors de la suppression', 'ai-content-studio'));
        } finally {
            setSaving(false);
        }
    };

    const handleCopy = () => {
        const hashtags = post.hashtags ? (typeof post.hashtags === 'string' ? JSON.parse(post.hashtags) : post.hashtags) : [];
        const fullContent = post.content + '\n\n' + hashtags.join(' ');
        navigator.clipboard.writeText(fullContent);
        alert(__('✅ Copié dans le presse-papier !', 'ai-content-studio'));
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

    const STATUS_COLORS = {
        draft: { bg: '#F3F4F6', color: '#6B7280', label: 'Brouillon' },
        scheduled: { bg: '#DBEAFE', color: '#1D4ED8', label: 'Planifié' },
        published: { bg: '#D1FAE5', color: '#059669', label: 'Publié' },
        failed: { bg: '#FEE2E2', color: '#DC2626', label: 'Échec' },
    };

    const hashtags = post.hashtags ? (typeof post.hashtags === 'string' ? JSON.parse(post.hashtags) : post.hashtags) : [];

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
                    maxWidth: '700px',
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
                        <span style={{ fontSize: '2rem' }}>{PLATFORM_EMOJIS[post.platform] || '📝'}</span>
                        <div>
                            <h2 style={{ margin: 0, fontSize: '1.25rem', fontWeight: 600, textTransform: 'capitalize' }}>
                                {post.platform}
                            </h2>
                            <span
                                style={{
                                    padding: '2px 8px',
                                    borderRadius: '12px',
                                    fontSize: 'var(--acs-font-size-sm)',
                                    fontWeight: 600,
                                    background: STATUS_COLORS[post.status]?.bg,
                                    color: STATUS_COLORS[post.status]?.color,
                                    display: 'inline-block',
                                    marginTop: '4px',
                                }}
                            >
                                {STATUS_COLORS[post.status]?.label}
                            </span>
                        </div>
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
                <div style={{ padding: 'var(--acs-spacing-4)' }}>
                    {/* Scheduled date/time */}
                    {post.scheduled_for && (
                        <div
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                gap: 'var(--acs-spacing-2)',
                                marginBottom: 'var(--acs-spacing-3)',
                                padding: 'var(--acs-spacing-3)',
                                background: 'var(--acs-gray-50)',
                                borderRadius: 'var(--acs-radius)',
                            }}
                        >
                            <FiCalendar size={20} style={{ color: 'var(--acs-primary)' }} />
                            <div>
                                <div style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)' }}>
                                    {__('Planifié pour', 'ai-content-studio')}
                                </div>
                                <div style={{ fontWeight: 600 }}>
                                    {new Date(post.scheduled_for).toLocaleString('fr-FR', {
                                        dateStyle: 'full',
                                        timeStyle: 'short',
                                    })}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Content */}
                    <div style={{ marginBottom: 'var(--acs-spacing-3)' }}>
                        <label className="acs-form-label">{__('Contenu', 'ai-content-studio')}</label>
                        {isEditing ? (
                            <textarea
                                className="acs-textarea"
                                value={editedContent}
                                onChange={(e) => setEditedContent(e.target.value)}
                                rows={10}
                                style={{ width: '100%' }}
                            />
                        ) : (
                            <div
                                style={{
                                    padding: 'var(--acs-spacing-3)',
                                    background: 'var(--acs-gray-50)',
                                    borderRadius: 'var(--acs-radius)',
                                    border: '1px solid var(--acs-gray-200)',
                                    whiteSpace: 'pre-wrap',
                                    lineHeight: 1.6,
                                }}
                            >
                                {post.content}
                            </div>
                        )}
                    </div>

                    {/* Hashtags */}
                    {hashtags.length > 0 && (
                        <div style={{ marginBottom: 'var(--acs-spacing-3)' }}>
                            <label className="acs-form-label">{__('Hashtags', 'ai-content-studio')}</label>
                            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 'var(--acs-spacing-1)' }}>
                                {hashtags.map((tag, i) => (
                                    <span
                                        key={i}
                                        style={{
                                            padding: '4px 12px',
                                            background: 'var(--acs-primary-light)',
                                            color: 'var(--acs-primary)',
                                            borderRadius: '16px',
                                            fontSize: 'var(--acs-font-size-sm)',
                                            fontWeight: 500,
                                        }}
                                    >
                                        {tag}
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Edit date/time */}
                    {isEditing && post.scheduled_for && (
                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 'var(--acs-spacing-3)', marginBottom: 'var(--acs-spacing-3)' }}>
                            <div className="acs-form-group">
                                <label className="acs-form-label">
                                    <FiCalendar size={14} style={{ marginRight: '4px' }} />
                                    {__('Date', 'ai-content-studio')}
                                </label>
                                <input
                                    type="date"
                                    className="acs-form-control"
                                    value={editedScheduledFor}
                                    onChange={(e) => setEditedScheduledFor(e.target.value)}
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
                                    value={editedScheduledTime}
                                    onChange={(e) => setEditedScheduledTime(e.target.value)}
                                />
                            </div>
                        </div>
                    )}

                    {/* Error message */}
                    {error && (
                        <div className="acs-alert acs-alert-danger" style={{ marginBottom: 'var(--acs-spacing-3)' }}>
                            {error}
                        </div>
                    )}

                    {/* Actions */}
                    <div style={{ display: 'flex', gap: 'var(--acs-spacing-2)', flexWrap: 'wrap' }}>
                        {isEditing ? (
                            <>
                                <button
                                    className="acs-btn acs-btn-outline-primary"
                                    onClick={() => {
                                        setIsEditing(false);
                                        setEditedContent(post.content);
                                    }}
                                    disabled={saving}
                                >
                                    {__('Annuler', 'ai-content-studio')}
                                </button>
                                <button className="acs-btn acs-btn-primary" onClick={handleSave} disabled={saving} style={{ flex: 1 }}>
                                    {saving ? __('Enregistrement...', 'ai-content-studio') : __('Enregistrer', 'ai-content-studio')}
                                </button>
                            </>
                        ) : (
                            <>
                                <button className="acs-btn acs-btn-outline-primary" onClick={handleCopy}>
                                    <FiCopy /> {__('Copier', 'ai-content-studio')}
                                </button>
                                <button className="acs-btn acs-btn-outline-primary" onClick={() => setIsEditing(true)} style={{ flex: 1 }}>
                                    <FiEdit /> {__('Éditer', 'ai-content-studio')}
                                </button>
                                <button
                                    className="acs-btn"
                                    onClick={handleDelete}
                                    disabled={saving}
                                    style={{ color: 'var(--acs-danger)', borderColor: 'var(--acs-danger)' }}
                                >
                                    <FiTrash2 /> {__('Supprimer', 'ai-content-studio')}
                                </button>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
