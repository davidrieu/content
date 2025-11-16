import { useState, useEffect } from '@wordpress/element';
import { FiHeart, FiCopy, FiTrash2, FiSearch, FiFilter, FiEdit, FiCalendar } from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import { useTranslation } from '../../contexts/TranslationContext';
import SchedulePostModal from '../shared/SchedulePostModal';

const PLATFORM_EMOJIS = {
    instagram: '📷',
    facebook: '📘',
    linkedin: '💼',
    twitter: '🐦',
    all: '🌐',
};

export default function ContentLibrary({ profile }) {
    const { t } = useTranslation();
    const [savedPosts, setSavedPosts] = useState([]);
    const [templates, setTemplates] = useState([]);
    const [loading, setLoading] = useState(false);
    const [activeTab, setActiveTab] = useState('posts'); // posts, templates, hashtags
    const [searchQuery, setSearchQuery] = useState('');
    const [filterPlatform, setFilterPlatform] = useState('all');
    const [filterCategory, setFilterCategory] = useState('all');
    const [scheduleModalOpen, setScheduleModalOpen] = useState(false);
    const [postToSchedule, setPostToSchedule] = useState(null);

    useEffect(() => {
        loadLibraryContent();
    }, [activeTab]);

    const loadLibraryContent = async () => {
        setLoading(true);
        try {
            if (activeTab === 'posts') {
                const response = await apiFetch({ path: '/acs/v1/library/posts' });
                if (response.success) {
                    setSavedPosts(response.data || []);
                }
            } else if (activeTab === 'templates') {
                const response = await apiFetch({ path: '/acs/v1/library/templates' });
                if (response.success) {
                    setTemplates(response.data || []);
                }
            }
        } catch (err) {
            console.error('Error loading library:', err);
        } finally {
            setLoading(false);
        }
    };

    const deleteItem = async (id, type) => {
        if (!confirm(t('Êtes-vous sûr de vouloir supprimer cet élément ?'))) {
            return;
        }

        try {
            await apiFetch({
                path: `/acs/v1/library/${type}/${id}`,
                method: 'DELETE',
            });
            loadLibraryContent();
        } catch (err) {
            alert(t('Erreur lors de la suppression'));
        }
    };

    const toggleFavorite = async (id, type) => {
        try {
            await apiFetch({
                path: `/acs/v1/library/${type}/${id}/favorite`,
                method: 'POST',
            });
            loadLibraryContent();
        } catch (err) {
            alert(t('Erreur'));
        }
    };

    const copyToClipboard = (content) => {
        navigator.clipboard.writeText(content);
        alert(t('Copié dans le presse-papier !'));
    };

    const openScheduleModal = (post) => {
        setPostToSchedule({
            content: post.content,
            hashtags: typeof post.hashtags === 'string' ? JSON.parse(post.hashtags) : post.hashtags,
            platform: post.platform,
            language: 'fr',
            metadata: {
                category: post.category,
            },
        });
        setScheduleModalOpen(true);
    };

    const handleScheduleSuccess = (data) => {
        alert(t('✅ Post planifié avec succès !'));
    };

    const filteredPosts = savedPosts.filter((post) => {
        if (filterPlatform !== 'all' && post.platform !== filterPlatform) return false;
        if (filterCategory !== 'all' && post.category !== filterCategory) return false;
        if (searchQuery && !post.content.toLowerCase().includes(searchQuery.toLowerCase())) return false;
        return true;
    });

    return (
        <div>
            <div style={{ marginBottom: 'var(--acs-spacing-4)' }}>
                <h1 className="acs-page-title">{t('Bibliothèque de Contenu')}</h1>
                <p className="acs-text-muted">
                    {t('Retrouvez tous vos posts sauvegardés, templates personnalisés et sets de hashtags')}
                </p>
            </div>

            {/* Tabs */}
            <div
                style={{
                    display: 'flex',
                    gap: 'var(--acs-spacing-2)',
                    marginBottom: 'var(--acs-spacing-4)',
                    borderBottom: '2px solid var(--acs-gray-200)',
                }}
            >
                {[
                    { id: 'posts', label: 'Posts sauvegardés', icon: '💾' },
                    { id: 'templates', label: 'Mes templates', icon: '📄' },
                    { id: 'hashtags', label: 'Sets de hashtags', icon: '#️⃣' },
                ].map((tab) => (
                    <button
                        key={tab.id}
                        className="acs-btn"
                        onClick={() => setActiveTab(tab.id)}
                        style={{
                            borderBottom: activeTab === tab.id ? '2px solid var(--acs-primary)' : 'none',
                            borderRadius: 0,
                            background: activeTab === tab.id ? 'rgba(99, 102, 241, 0.05)' : 'transparent',
                            color: activeTab === tab.id ? 'var(--acs-primary)' : 'var(--acs-gray-700)',
                            fontWeight: activeTab === tab.id ? 600 : 400,
                            marginBottom: '-2px',
                        }}
                    >
                        <span style={{ marginRight: '8px' }}>{tab.icon}</span>
                        {tab.label}
                    </button>
                ))}
            </div>

            {/* Filters */}
            <div className="acs-card" style={{ marginBottom: 'var(--acs-spacing-4)' }}>
                <div style={{ display: 'flex', gap: 'var(--acs-spacing-3)', flexWrap: 'wrap' }}>
                    <div style={{ flex: 1, minWidth: '200px' }}>
                        <div className="acs-input-with-icon">
                            <FiSearch className="acs-input-icon" />
                            <input
                                type="text"
                                className="acs-form-control acs-with-icon"
                                placeholder={t('Rechercher...')}
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                        </div>
                    </div>

                    <select className="acs-select" style={{ width: 'auto' }} value={filterPlatform} onChange={(e) => setFilterPlatform(e.target.value)}>
                        <option value="all">{t('Toutes plateformes')}</option>
                        <option value="instagram">📷 Instagram</option>
                        <option value="facebook">📘 Facebook</option>
                        <option value="linkedin">💼 LinkedIn</option>
                        <option value="twitter">🐦 Twitter</option>
                    </select>

                    <select className="acs-select" style={{ width: 'auto' }} value={filterCategory} onChange={(e) => setFilterCategory(e.target.value)}>
                        <option value="all">{t('Toutes catégories')}</option>
                        <option value="educational">📚 Éducatif</option>
                        <option value="promotional">🎯 Promotionnel</option>
                        <option value="engagement">💬 Engagement</option>
                        <option value="storytelling">📖 Storytelling</option>
                    </select>
                </div>
            </div>

            {/* Content Grid */}
            {loading ? (
                <div style={{ textAlign: 'center', padding: 'var(--acs-spacing-6)' }}>
                    <div className="acs-spinner" />
                </div>
            ) : activeTab === 'posts' ? (
                filteredPosts.length === 0 ? (
                    <div className="acs-card" style={{ textAlign: 'center', padding: '3rem 2rem' }}>
                        <div style={{ fontSize: '3rem', marginBottom: 'var(--acs-spacing-3)' }}>📚</div>
                        <h3 style={{ marginBottom: 'var(--acs-spacing-2)' }}>{t('Votre bibliothèque est vide')}</h3>
                        <p className="acs-text-muted">
                            {t('Sauvegardez vos meilleurs posts pour les réutiliser facilement')}
                        </p>
                    </div>
                ) : (
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(350px, 1fr))', gap: 'var(--acs-spacing-4)' }}>
                        {filteredPosts.map((post) => (
                            <div key={post.id} className="acs-card">
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'start', marginBottom: 'var(--acs-spacing-3)' }}>
                                    <div style={{ display: 'flex', alignItems: 'center', gap: 'var(--acs-spacing-2)' }}>
                                        <span style={{ fontSize: '1.5rem' }}>{PLATFORM_EMOJIS[post.platform]}</span>
                                        <div>
                                            <div style={{ fontWeight: 600, textTransform: 'capitalize' }}>{post.platform}</div>
                                            <div style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)' }}>
                                                {post.category || 'Général'}
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        className="acs-btn acs-btn-sm"
                                        onClick={() => toggleFavorite(post.id, 'post')}
                                        style={{
                                            padding: '4px 8px',
                                            color: post.is_favorite ? 'var(--acs-danger)' : 'var(--acs-gray-500)',
                                        }}
                                    >
                                        <FiHeart fill={post.is_favorite ? 'currentColor' : 'none'} />
                                    </button>
                                </div>

                                <div
                                    style={{
                                        padding: 'var(--acs-spacing-3)',
                                        background: 'var(--acs-gray-50)',
                                        borderRadius: 'var(--acs-radius)',
                                        marginBottom: 'var(--acs-spacing-3)',
                                        maxHeight: '150px',
                                        overflow: 'auto',
                                    }}
                                >
                                    {post.content}
                                </div>

                                {post.hashtags && post.hashtags.length > 0 && (
                                    <div className="acs-hashtags" style={{ marginBottom: 'var(--acs-spacing-3)' }}>
                                        {(typeof post.hashtags === 'string' ? JSON.parse(post.hashtags) : post.hashtags).slice(0, 5).map((tag, i) => (
                                            <span key={i} className="acs-hashtag">
                                                {tag}
                                            </span>
                                        ))}
                                    </div>
                                )}

                                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 'var(--acs-spacing-2)', marginBottom: 'var(--acs-spacing-2)' }}>
                                    <button
                                        className="acs-btn acs-btn-outline-primary acs-btn-sm"
                                        onClick={() => copyToClipboard(post.content)}
                                    >
                                        <FiCopy /> {t('Copier')}
                                    </button>
                                    <button
                                        className="acs-btn acs-btn-outline-primary acs-btn-sm"
                                        onClick={() => openScheduleModal(post)}
                                    >
                                        <FiCalendar /> {t('Planifier')}
                                    </button>
                                </div>
                                <div style={{ display: 'flex', gap: 'var(--acs-spacing-2)' }}>
                                    <button className="acs-btn acs-btn-outline-primary acs-btn-sm" style={{ flex: 1 }}>
                                        <FiEdit /> {t('Éditer')}
                                    </button>
                                    <button
                                        className="acs-btn acs-btn-sm"
                                        onClick={() => deleteItem(post.id, 'post')}
                                        style={{ color: 'var(--acs-danger)' }}
                                    >
                                        <FiTrash2 />
                                    </button>
                                </div>

                                <div style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-500)', marginTop: 'var(--acs-spacing-2)' }}>
                                    {t('Utilisé')} {post.usage_count || 0} {t('fois')}
                                </div>
                            </div>
                        ))}
                    </div>
                )
            ) : activeTab === 'templates' ? (
                <div className="acs-card" style={{ textAlign: 'center', padding: '3rem 2rem' }}>
                    <div style={{ fontSize: '3rem', marginBottom: 'var(--acs-spacing-3)' }}>📄</div>
                    <h3 style={{ marginBottom: 'var(--acs-spacing-2)' }}>{t('Templates personnalisés')}</h3>
                    <p className="acs-text-muted">{t('Fonctionnalité à venir !')}</p>
                </div>
            ) : (
                <div className="acs-card" style={{ textAlign: 'center', padding: '3rem 2rem' }}>
                    <div style={{ fontSize: '3rem', marginBottom: 'var(--acs-spacing-3)' }}>#️⃣</div>
                    <h3 style={{ marginBottom: 'var(--acs-spacing-2)' }}>{t('Sets de hashtags')}</h3>
                    <p className="acs-text-muted">{t('Fonctionnalité à venir !')}</p>
                </div>
            )}

            {/* Schedule Modal */}
            {scheduleModalOpen && postToSchedule && (
                <SchedulePostModal
                    isOpen={scheduleModalOpen}
                    onClose={() => setScheduleModalOpen(false)}
                    post={postToSchedule}
                    onSuccess={handleScheduleSuccess}
                />
            )}
        </div>
    );
}
