import { useState, useEffect } from '@wordpress/element';
import {
    FiChevronLeft,
    FiChevronRight,
    FiCalendar,
    FiPlus,
    FiEdit,
    FiTrash2,
    FiClock,
    FiCheck,
} from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import { useTranslation } from '../../contexts/TranslationContext';
import PostDetailModal from '../shared/PostDetailModal';

const DAYS_OF_WEEK = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
const MONTHS = [
    'Janvier',
    'Février',
    'Mars',
    'Avril',
    'Mai',
    'Juin',
    'Juillet',
    'Août',
    'Septembre',
    'Octobre',
    'Novembre',
    'Décembre',
];

const STATUS_COLORS = {
    draft: { bg: '#F3F4F6', color: '#6B7280', label: 'Brouillon' },
    scheduled: { bg: '#DBEAFE', color: '#1D4ED8', label: 'Planifié' },
    published: { bg: '#D1FAE5', color: '#059669', label: 'Publié' },
    failed: { bg: '#FEE2E2', color: '#DC2626', label: 'Échec' },
};

const PLATFORM_EMOJIS = {
    instagram: '📷',
    facebook: '📘',
    linkedin: '💼',
    twitter: '🐦',
    all: '🌐',
};

export default function ContentCalendar({ profile }) {
    const { t } = useTranslation();
    const [currentDate, setCurrentDate] = useState(new Date());
    const [posts, setPosts] = useState([]);
    const [loading, setLoading] = useState(false);
    const [selectedDate, setSelectedDate] = useState(null);
    const [view, setView] = useState('month'); // month, week, list
    const [filterPlatform, setFilterPlatform] = useState('all');
    const [filterStatus, setFilterStatus] = useState('all');
    const [selectedPost, setSelectedPost] = useState(null);
    const [postDetailModalOpen, setPostDetailModalOpen] = useState(false);
    const [draggedPost, setDraggedPost] = useState(null);

    const currentYear = currentDate.getFullYear();
    const currentMonth = currentDate.getMonth();

    useEffect(() => {
        loadPosts();
    }, [currentMonth, currentYear]);

    const loadPosts = async () => {
        setLoading(true);
        try {
            const response = await apiFetch({
                path: `/acs/v1/calendar/posts?month=${currentMonth + 1}&year=${currentYear}`,
            });
            if (response.success) {
                setPosts(response.data || []);
            }
        } catch (err) {
            console.error('Error loading posts:', err);
        } finally {
            setLoading(false);
        }
    };

    const getDaysInMonth = (year, month) => {
        return new Date(year, month + 1, 0).getDate();
    };

    const getFirstDayOfMonth = (year, month) => {
        const day = new Date(year, month, 1).getDay();
        return day === 0 ? 6 : day - 1; // Convert Sunday (0) to 6, and shift others
    };

    const previousMonth = () => {
        setCurrentDate(new Date(currentYear, currentMonth - 1, 1));
    };

    const nextMonth = () => {
        setCurrentDate(new Date(currentYear, currentMonth + 1, 1));
    };

    const goToToday = () => {
        setCurrentDate(new Date());
    };

    const getPostsForDate = (day) => {
        const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        return posts.filter((post) => {
            const postDate = post.scheduled_for ? new Date(post.scheduled_for) : null;
            if (!postDate) return false;
            const postDateStr = `${postDate.getFullYear()}-${String(postDate.getMonth() + 1).padStart(2, '0')}-${String(postDate.getDate()).padStart(2, '0')}`;
            return postDateStr === dateStr;
        });
    };

    const handlePostClick = (post, e) => {
        e.stopPropagation();
        setSelectedPost(post);
        setPostDetailModalOpen(true);
    };

    const handlePostUpdate = (updatedPost) => {
        setPosts(posts.map(p => p.id === updatedPost.id ? updatedPost : p));
        loadPosts(); // Reload to get fresh data
    };

    const handlePostDelete = (postId) => {
        setPosts(posts.filter(p => p.id !== postId));
    };

    const handleDragStart = (post, e) => {
        e.stopPropagation();
        setDraggedPost(post);
        e.dataTransfer.effectAllowed = 'move';
    };

    const handleDragOver = (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    };

    const handleDrop = async (day, e) => {
        e.preventDefault();
        e.stopPropagation();

        if (!draggedPost) return;

        const newDate = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

        // Keep the same time, just change the date
        const oldDate = new Date(draggedPost.scheduled_for);
        const newScheduledFor = `${newDate} ${String(oldDate.getHours()).padStart(2, '0')}:${String(oldDate.getMinutes()).padStart(2, '0')}:00`;

        try {
            const response = await apiFetch({
                path: `/acs/v1/calendar/posts/${draggedPost.id}`,
                method: 'PUT',
                data: {
                    scheduled_for: newScheduledFor,
                },
            });

            if (response.success) {
                // Update local state
                setPosts(posts.map(p =>
                    p.id === draggedPost.id
                        ? { ...p, scheduled_for: newScheduledFor }
                        : p
                ));
            }
        } catch (err) {
            console.error('Error moving post:', err);
            alert(t('Erreur lors du déplacement du post'));
        } finally {
            setDraggedPost(null);
        }
    };

    const renderCalendarDays = () => {
        const daysInMonth = getDaysInMonth(currentYear, currentMonth);
        const firstDay = getFirstDayOfMonth(currentYear, currentMonth);
        const days = [];

        // Empty cells before first day
        for (let i = 0; i < firstDay; i++) {
            days.push(
                <div
                    key={`empty-${i}`}
                    style={{
                        padding: 'var(--acs-spacing-2)',
                        background: 'var(--acs-gray-50)',
                        borderRadius: 'var(--acs-radius)',
                        minHeight: '120px',
                    }}
                />
            );
        }

        // Calendar days
        for (let day = 1; day <= daysInMonth; day++) {
            const dayPosts = getPostsForDate(day);
            const isToday =
                day === new Date().getDate() &&
                currentMonth === new Date().getMonth() &&
                currentYear === new Date().getFullYear();

            days.push(
                <div
                    key={day}
                    style={{
                        padding: 'var(--acs-spacing-2)',
                        background: isToday ? 'rgba(99, 102, 241, 0.05)' : 'var(--acs-white)',
                        border: `1px solid ${isToday ? 'var(--acs-primary)' : 'var(--acs-gray-200)'}`,
                        borderRadius: 'var(--acs-radius)',
                        minHeight: '120px',
                        cursor: 'pointer',
                        transition: 'var(--acs-transition)',
                        position: 'relative',
                    }}
                    onMouseEnter={(e) => {
                        e.currentTarget.style.boxShadow = 'var(--acs-shadow)';
                        e.currentTarget.style.transform = 'translateY(-2px)';
                    }}
                    onMouseLeave={(e) => {
                        e.currentTarget.style.boxShadow = 'none';
                        e.currentTarget.style.transform = 'translateY(0)';
                    }}
                    onClick={() => setSelectedDate(day)}
                    onDragOver={handleDragOver}
                    onDrop={(e) => handleDrop(day, e)}
                >
                    <div
                        style={{
                            fontWeight: 600,
                            fontSize: 'var(--acs-font-size-base)',
                            color: isToday ? 'var(--acs-primary)' : 'var(--acs-gray-900)',
                            marginBottom: 'var(--acs-spacing-2)',
                        }}
                    >
                        {day}
                    </div>

                    {/* Posts for this day */}
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--acs-spacing-1)' }}>
                        {dayPosts.slice(0, 3).map((post, idx) => (
                            <div
                                key={post.id}
                                draggable
                                onDragStart={(e) => handleDragStart(post, e)}
                                onClick={(e) => handlePostClick(post, e)}
                                style={{
                                    fontSize: 'var(--acs-font-size-sm)',
                                    padding: '4px 6px',
                                    background: STATUS_COLORS[post.status]?.bg || STATUS_COLORS.draft.bg,
                                    color: STATUS_COLORS[post.status]?.color || STATUS_COLORS.draft.color,
                                    borderRadius: '4px',
                                    overflow: 'hidden',
                                    textOverflow: 'ellipsis',
                                    whiteSpace: 'nowrap',
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '4px',
                                    cursor: 'grab',
                                    transition: 'all 0.2s',
                                }}
                                onMouseEnter={(e) => {
                                    e.currentTarget.style.opacity = '0.8';
                                    e.currentTarget.style.transform = 'scale(1.02)';
                                }}
                                onMouseLeave={(e) => {
                                    e.currentTarget.style.opacity = '1';
                                    e.currentTarget.style.transform = 'scale(1)';
                                }}
                            >
                                <span>{PLATFORM_EMOJIS[post.platform] || '📝'}</span>
                                <span style={{ flex: 1, overflow: 'hidden', textOverflow: 'ellipsis' }}>
                                    {post.content?.substring(0, 20)}...
                                </span>
                            </div>
                        ))}
                        {dayPosts.length > 3 && (
                            <div
                                style={{
                                    fontSize: 'var(--acs-font-size-sm)',
                                    color: 'var(--acs-gray-600)',
                                    textAlign: 'center',
                                    fontWeight: 500,
                                }}
                            >
                                +{dayPosts.length - 3} posts
                            </div>
                        )}
                    </div>

                    {/* Add button */}
                    <button
                        style={{
                            position: 'absolute',
                            bottom: '8px',
                            right: '8px',
                            width: '24px',
                            height: '24px',
                            borderRadius: '50%',
                            border: 'none',
                            background: 'var(--acs-primary)',
                            color: 'var(--acs-white)',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            cursor: 'pointer',
                            opacity: 0.7,
                            transition: 'var(--acs-transition)',
                        }}
                        onMouseEnter={(e) => {
                            e.currentTarget.style.opacity = '1';
                            e.currentTarget.style.transform = 'scale(1.1)';
                        }}
                        onMouseLeave={(e) => {
                            e.currentTarget.style.opacity = '0.7';
                            e.currentTarget.style.transform = 'scale(1)';
                        }}
                        onClick={(e) => {
                            e.stopPropagation();
                            alert(`Ajouter un post pour le ${day} ${MONTHS[currentMonth]}`);
                        }}
                    >
                        <FiPlus size={14} />
                    </button>
                </div>
            );
        }

        return days;
    };

    const renderListView = () => {
        const filteredPosts = posts.filter((post) => {
            if (filterPlatform !== 'all' && post.platform !== filterPlatform) return false;
            if (filterStatus !== 'all' && post.status !== filterStatus) return false;
            return true;
        });

        if (filteredPosts.length === 0) {
            return (
                <div style={{ textAlign: 'center', padding: 'var(--acs-spacing-6)', color: 'var(--acs-gray-500)' }}>
                    <FiCalendar size={48} style={{ marginBottom: 'var(--acs-spacing-3)' }} />
                    <p>{t('Aucun post planifié pour ce mois')}</p>
                </div>
            );
        }

        return (
            <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--acs-spacing-3)' }}>
                {filteredPosts.map((post) => (
                    <div
                        key={post.id}
                        className="acs-card"
                        style={{
                            padding: 'var(--acs-spacing-4)',
                            borderLeft: `4px solid ${STATUS_COLORS[post.status]?.color || STATUS_COLORS.draft.color}`,
                        }}
                    >
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'start', marginBottom: 'var(--acs-spacing-3)' }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 'var(--acs-spacing-3)' }}>
                                <span style={{ fontSize: '1.5rem' }}>{PLATFORM_EMOJIS[post.platform]}</span>
                                <div>
                                    <div style={{ fontWeight: 600, fontSize: 'var(--acs-font-size-lg)' }}>
                                        {post.platform.charAt(0).toUpperCase() + post.platform.slice(1)}
                                    </div>
                                    <div style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)' }}>
                                        <FiClock size={12} style={{ marginRight: '4px' }} />
                                        {post.scheduled_for ? new Date(post.scheduled_for).toLocaleString('fr-FR') : 'Non planifié'}
                                    </div>
                                </div>
                            </div>
                            <div style={{ display: 'flex', gap: 'var(--acs-spacing-2)', alignItems: 'center' }}>
                                <span
                                    style={{
                                        padding: '4px 12px',
                                        borderRadius: '12px',
                                        fontSize: 'var(--acs-font-size-sm)',
                                        fontWeight: 600,
                                        background: STATUS_COLORS[post.status]?.bg,
                                        color: STATUS_COLORS[post.status]?.color,
                                    }}
                                >
                                    {STATUS_COLORS[post.status]?.label}
                                </span>
                                <button className="acs-btn acs-btn-sm" style={{ padding: '4px 8px' }}>
                                    <FiEdit size={14} />
                                </button>
                                <button className="acs-btn acs-btn-sm" style={{ padding: '4px 8px' }}>
                                    <FiTrash2 size={14} />
                                </button>
                            </div>
                        </div>

                        <div
                            style={{
                                padding: 'var(--acs-spacing-3)',
                                background: 'var(--acs-gray-50)',
                                borderRadius: 'var(--acs-radius)',
                                marginBottom: 'var(--acs-spacing-2)',
                            }}
                        >
                            {post.content}
                        </div>

                        {post.hashtags && post.hashtags.length > 0 && (
                            <div className="acs-hashtags">
                                {JSON.parse(post.hashtags).map((tag, i) => (
                                    <span key={i} className="acs-hashtag">
                                        {tag}
                                    </span>
                                ))}
                            </div>
                        )}
                    </div>
                ))}
            </div>
        );
    };

    return (
        <div>
            <div style={{ marginBottom: 'var(--acs-spacing-4)' }}>
                <h1 className="acs-page-title">{t('Calendrier Éditorial')}</h1>
                <p className="acs-text-muted">
                    {t('Planifiez et organisez votre contenu sur tous vos réseaux sociaux')}
                </p>
            </div>

            {/* Controls */}
            <div className="acs-card" style={{ marginBottom: 'var(--acs-spacing-4)' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 'var(--acs-spacing-3)' }}>
                    {/* Month navigation */}
                    <div style={{ display: 'flex', alignItems: 'center', gap: 'var(--acs-spacing-3)' }}>
                        <button className="acs-btn acs-btn-sm" onClick={previousMonth}>
                            <FiChevronLeft />
                        </button>
                        <h2 style={{ fontSize: '1.25rem', fontWeight: 600, margin: 0 }}>
                            {MONTHS[currentMonth]} {currentYear}
                        </h2>
                        <button className="acs-btn acs-btn-sm" onClick={nextMonth}>
                            <FiChevronRight />
                        </button>
                        <button className="acs-btn acs-btn-outline-primary acs-btn-sm" onClick={goToToday}>
                            {t("Aujourd'hui")}
                        </button>
                    </div>

                    {/* View switcher and filters */}
                    <div style={{ display: 'flex', gap: 'var(--acs-spacing-2)', flexWrap: 'wrap' }}>
                        <select className="acs-select" style={{ width: 'auto' }} value={filterPlatform} onChange={(e) => setFilterPlatform(e.target.value)}>
                            <option value="all">{t('Toutes plateformes')}</option>
                            <option value="instagram">📷 Instagram</option>
                            <option value="facebook">📘 Facebook</option>
                            <option value="linkedin">💼 LinkedIn</option>
                            <option value="twitter">🐦 Twitter</option>
                        </select>

                        <select className="acs-select" style={{ width: 'auto' }} value={filterStatus} onChange={(e) => setFilterStatus(e.target.value)}>
                            <option value="all">{t('Tous statuts')}</option>
                            <option value="draft">Brouillon</option>
                            <option value="scheduled">Planifié</option>
                            <option value="published">Publié</option>
                        </select>

                        <div
                            style={{
                                display: 'inline-flex',
                                background: 'var(--acs-gray-100)',
                                borderRadius: 'var(--acs-radius)',
                                padding: '4px',
                            }}
                        >
                            <button
                                className={`acs-btn acs-btn-sm ${view === 'month' ? 'acs-btn-primary' : ''}`}
                                onClick={() => setView('month')}
                                style={{ margin: 0 }}
                            >
                                {t('Mois')}
                            </button>
                            <button
                                className={`acs-btn acs-btn-sm ${view === 'list' ? 'acs-btn-primary' : ''}`}
                                onClick={() => setView('list')}
                                style={{ margin: 0 }}
                            >
                                {t('Liste')}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Calendar view */}
            {view === 'month' ? (
                <div className="acs-card">
                    {/* Days of week header */}
                    <div
                        style={{
                            display: 'grid',
                            gridTemplateColumns: 'repeat(7, 1fr)',
                            gap: 'var(--acs-spacing-2)',
                            marginBottom: 'var(--acs-spacing-2)',
                        }}
                    >
                        {DAYS_OF_WEEK.map((day) => (
                            <div
                                key={day}
                                style={{
                                    fontWeight: 600,
                                    textAlign: 'center',
                                    padding: 'var(--acs-spacing-2)',
                                    color: 'var(--acs-gray-700)',
                                    fontSize: 'var(--acs-font-size-sm)',
                                }}
                            >
                                {day}
                            </div>
                        ))}
                    </div>

                    {/* Calendar grid */}
                    <div
                        style={{
                            display: 'grid',
                            gridTemplateColumns: 'repeat(7, 1fr)',
                            gap: 'var(--acs-spacing-2)',
                        }}
                    >
                        {loading ? (
                            <div style={{ gridColumn: '1 / -1', textAlign: 'center', padding: 'var(--acs-spacing-6)' }}>
                                <div className="acs-spinner" />
                            </div>
                        ) : (
                            renderCalendarDays()
                        )}
                    </div>
                </div>
            ) : (
                renderListView()
            )}

            {/* Stats footer */}
            <div className="acs-card" style={{ marginTop: 'var(--acs-spacing-4)' }}>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))', gap: 'var(--acs-spacing-4)' }}>
                    <div>
                        <div style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)', marginBottom: 'var(--acs-spacing-1)' }}>
                            Posts planifiés
                        </div>
                        <div style={{ fontSize: '1.5rem', fontWeight: 700, color: 'var(--acs-primary)' }}>
                            {posts.filter((p) => p.status === 'scheduled').length}
                        </div>
                    </div>
                    <div>
                        <div style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)', marginBottom: 'var(--acs-spacing-1)' }}>
                            Brouillons
                        </div>
                        <div style={{ fontSize: '1.5rem', fontWeight: 700, color: 'var(--acs-gray-600)' }}>
                            {posts.filter((p) => p.status === 'draft').length}
                        </div>
                    </div>
                    <div>
                        <div style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)', marginBottom: 'var(--acs-spacing-1)' }}>
                            Publiés ce mois
                        </div>
                        <div style={{ fontSize: '1.5rem', fontWeight: 700, color: 'var(--acs-success)' }}>
                            {posts.filter((p) => p.status === 'published').length}
                        </div>
                    </div>
                </div>
            </div>

            {/* Post Detail Modal */}
            {postDetailModalOpen && selectedPost && (
                <PostDetailModal
                    isOpen={postDetailModalOpen}
                    onClose={() => setPostDetailModalOpen(false)}
                    post={selectedPost}
                    onUpdate={handlePostUpdate}
                    onDelete={handlePostDelete}
                />
            )}
        </div>
    );
}
