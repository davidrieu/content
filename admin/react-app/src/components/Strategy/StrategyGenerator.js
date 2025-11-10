import { useState, useEffect } from '@wordpress/element';
import { FiZap, FiCalendar, FiTarget, FiTrendingUp, FiDownload, FiRefreshCw } from 'react-icons/fi';
import { useNavigate } from 'react-router-dom';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { CONTENT_TYPES, CONTENT_MIX_RECOMMENDATIONS, BEST_POSTING_TIMES } from '../../data/contentTemplates';

export default function StrategyGenerator({ profile }) {
    const navigate = useNavigate();
    const [generating, setGenerating] = useState(false);
    const [strategy, setStrategy] = useState(null);
    const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth() + 1);
    const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
    const [selectedGoal, setSelectedGoal] = useState('');
    const [postsPerWeek, setPostsPerWeek] = useState(3);
    const [contentIdeas, setContentIdeas] = useState([]);
    const [generatingIdeas, setGeneratingIdeas] = useState(false);

    useEffect(() => {
        if (profile?.goals && profile.goals.length > 0) {
            const goals = typeof profile.goals === 'string' ? JSON.parse(profile.goals) : profile.goals;
            setSelectedGoal(goals[0] || 'brand_awareness');
        }
    }, [profile]);

    const generateStrategy = async () => {
        setGenerating(true);
        try {
            const response = await apiFetch({
                path: '/acs/v1/strategy/generate',
                method: 'POST',
                data: {
                    month: selectedMonth,
                    year: selectedYear,
                    goal: selectedGoal,
                    posts_per_week: postsPerWeek,
                    profile: {
                        user_type: profile?.user_type || 'business',
                        sector: profile?.sector || '',
                        target_audience: profile?.target_audience || '',
                        platforms: profile?.social_platforms || profile?.platforms || ['instagram', 'facebook'],
                        posting_frequency: profile?.posting_frequency || 'weekly',
                        goals: profile?.goals || [],
                        keywords: profile?.primary_keywords || [],
                    },
                },
            });

            if (response.success) {
                setStrategy(response.data);
            } else {
                alert(__('Erreur lors de la génération', 'ai-content-studio'));
            }
        } catch (err) {
            alert(err.message || __('Erreur lors de la génération', 'ai-content-studio'));
        } finally {
            setGenerating(false);
        }
    };

    const applyStrategy = async () => {
        if (!strategy) return;

        try {
            const response = await apiFetch({
                path: '/acs/v1/strategy/apply',
                method: 'POST',
                data: {
                    strategy_id: strategy.id,
                    auto_generate_posts: true,
                },
            });

            if (response.success) {
                alert(__('Stratégie appliquée ! Vos posts ont été générés et ajoutés au calendrier.', 'ai-content-studio'));
                window.location.href = '#/calendar'; // Navigate to calendar
            }
        } catch (err) {
            alert(err.message);
        }
    };

    const downloadStrategy = () => {
        if (!strategy) return;

        const content = JSON.stringify(strategy, null, 2);
        const blob = new Blob([content], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `strategie-${selectedMonth}-${selectedYear}.json`;
        a.click();
    };

    const generateContentIdeas = async () => {
        setGeneratingIdeas(true);
        try {
            const response = await apiFetch({
                path: '/acs/v1/strategy/content-ideas',
                method: 'POST',
                data: {
                    goal: selectedGoal,
                    profile: {
                        user_type: profile?.user_type || 'business',
                        sector: profile?.sector || '',
                        target_audience: profile?.target_audience || '',
                        platforms: profile?.social_platforms || profile?.platforms || ['instagram', 'facebook'],
                        keywords: profile?.primary_keywords || [],
                    },
                },
            });

            if (response.success) {
                setContentIdeas(response.data);
            } else {
                alert(__('Erreur lors de la génération des idées', 'ai-content-studio'));
            }
        } catch (err) {
            alert(err.message || __('Erreur lors de la génération des idées', 'ai-content-studio'));
        } finally {
            setGeneratingIdeas(false);
        }
    };

    const handleGeneratePostFromIdea = (idea) => {
        // Navigate to generator tab with topic pre-filled via state
        navigate('/generate', { state: { topic: idea } });
    };

    const getContentMix = () => {
        const mix = CONTENT_MIX_RECOMMENDATIONS[selectedGoal] || CONTENT_MIX_RECOMMENDATIONS.brand_awareness;
        return mix;
    };

    const months = [
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

    return (
        <div>
            <div style={{ marginBottom: 'var(--acs-spacing-4)' }}>
                <h1 className="acs-page-title">{__('Générateur de Stratégie', 'ai-content-studio')}</h1>
                <p className="acs-text-muted">
                    {__('Créez automatiquement un plan de contenu complet basé sur vos objectifs et votre audience', 'ai-content-studio')}
                </p>
            </div>

            {!strategy ? (
                <>
                    {/* Configuration Form */}
                    <div className="acs-card">
                        <div className="acs-card-header">
                            <h3 className="acs-card-title">{__('Configuration du plan', 'ai-content-studio')}</h3>
                        </div>

                        {/* Period selection */}
                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 'var(--acs-spacing-4)', marginBottom: 'var(--acs-spacing-4)' }}>
                            <div className="acs-form-group">
                                <label className="acs-form-label">
                                    <FiCalendar style={{ marginRight: '8px' }} />
                                    {__('Mois', 'ai-content-studio')}
                                </label>
                                <select className="acs-select" value={selectedMonth} onChange={(e) => setSelectedMonth(parseInt(e.target.value))}>
                                    {months.map((month, i) => (
                                        <option key={i} value={i + 1}>
                                            {month}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="acs-form-group">
                                <label className="acs-form-label">{__('Année', 'ai-content-studio')}</label>
                                <select className="acs-select" value={selectedYear} onChange={(e) => setSelectedYear(parseInt(e.target.value))}>
                                    <option value={new Date().getFullYear()}>{new Date().getFullYear()}</option>
                                    <option value={new Date().getFullYear() + 1}>{new Date().getFullYear() + 1}</option>
                                </select>
                            </div>
                        </div>

                        {/* Goal selection */}
                        <div className="acs-form-group">
                            <label className="acs-form-label">
                                <FiTarget style={{ marginRight: '8px' }} />
                                {__('Objectif principal', 'ai-content-studio')}
                            </label>
                            <select className="acs-select" value={selectedGoal} onChange={(e) => setSelectedGoal(e.target.value)}>
                                <option value="brand_awareness">🎯 Notoriété de marque</option>
                                <option value="lead_generation">📈 Génération de leads</option>
                                <option value="sales">💰 Ventes</option>
                                <option value="engagement">❤️ Engagement communauté</option>
                                <option value="authority">👑 Autorité / Leadership</option>
                            </select>
                        </div>

                        {/* Posts per week */}
                        <div className="acs-form-group">
                            <label className="acs-form-label">{__('Nombre de posts par semaine', 'ai-content-studio')}</label>
                            <div style={{ display: 'flex', gap: 'var(--acs-spacing-2)' }}>
                                {[1, 2, 3, 4, 5, 7].map((num) => (
                                    <button
                                        key={num}
                                        className={`acs-btn ${postsPerWeek === num ? 'acs-btn-primary' : 'acs-btn-outline-primary'}`}
                                        onClick={() => setPostsPerWeek(num)}
                                        style={{ flex: 1 }}
                                    >
                                        {num} {num === 7 ? '/jour' : '/sem'}
                                    </button>
                                ))}
                            </div>
                            <div style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)', marginTop: 'var(--acs-spacing-2)' }}>
                                {postsPerWeek === 7 ? '🔥 Mode intensif : 1 post par jour' : `📅 ${postsPerWeek * 4} posts seront générés pour le mois`}
                            </div>
                        </div>

                        {/* Content mix preview */}
                        <div
                            style={{
                                padding: 'var(--acs-spacing-4)',
                                background: 'var(--acs-gray-50)',
                                borderRadius: 'var(--acs-radius-lg)',
                                marginBottom: 'var(--acs-spacing-4)',
                            }}
                        >
                            <h4 style={{ marginBottom: 'var(--acs-spacing-3)' }}>📊 {__('Mix de contenu recommandé', 'ai-content-studio')}</h4>
                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(120px, 1fr))', gap: 'var(--acs-spacing-3)' }}>
                                {Object.entries(getContentMix()).map(([type, percentage]) => {
                                    const contentType = CONTENT_TYPES[type] || CONTENT_TYPES.educational;
                                    return (
                                        <div
                                            key={type}
                                            style={{
                                                textAlign: 'center',
                                                padding: 'var(--acs-spacing-3)',
                                                background: 'var(--acs-white)',
                                                borderRadius: 'var(--acs-radius)',
                                                border: `2px solid ${contentType.color}`,
                                            }}
                                        >
                                            <div style={{ fontSize: '1.5rem', marginBottom: 'var(--acs-spacing-1)' }}>{contentType.emoji}</div>
                                            <div style={{ fontSize: '1.25rem', fontWeight: 700, color: contentType.color }}>{percentage}%</div>
                                            <div style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-700)' }}>{contentType.label}</div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Profile summary */}
                        {profile && (
                            <div
                                style={{
                                    padding: 'var(--acs-spacing-3)',
                                    background: 'linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05))',
                                    borderRadius: 'var(--acs-radius)',
                                    marginBottom: 'var(--acs-spacing-4)',
                                }}
                            >
                                <div style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-700)' }}>
                                    <strong>📋 Votre profil :</strong>
                                    {' '}{profile.user_type ? `Type: ${profile.user_type}` : ''}
                                    {profile.sector ? ` • Secteur: ${profile.sector}` : ''}
                                    {profile.posting_frequency ? ` • Fréquence: ${profile.posting_frequency}` : ''}
                                </div>
                            </div>
                        )}

                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 'var(--acs-spacing-3)' }}>
                            <button className="acs-btn acs-btn-primary acs-btn-lg" onClick={generateStrategy} disabled={generating || !selectedGoal}>
                                {generating ? (
                                    <>
                                        <div className="acs-spinner" style={{ width: '20px', height: '20px', borderWidth: '2px' }} />
                                        {__('Génération...', 'ai-content-studio')}
                                    </>
                                ) : (
                                    <>
                                        <FiZap /> {__('Stratégie complète', 'ai-content-studio')}
                                    </>
                                )}
                            </button>
                            <button className="acs-btn acs-btn-outline-primary acs-btn-lg" onClick={generateContentIdeas} disabled={generatingIdeas || !selectedGoal}>
                                {generatingIdeas ? (
                                    <>
                                        <div className="acs-spinner" style={{ width: '20px', height: '20px', borderWidth: '2px' }} />
                                        {__('Génération...', 'ai-content-studio')}
                                    </>
                                ) : (
                                    <>
                                        💡 {__('50 idées de posts', 'ai-content-studio')}
                                    </>
                                )}
                            </button>
                        </div>
                    </div>

                    {/* Content Ideas List */}
                    {contentIdeas.length > 0 && (
                        <div className="acs-card" style={{ marginTop: 'var(--acs-spacing-4)' }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 'var(--acs-spacing-4)' }}>
                                <h3 className="acs-card-title">💡 {__('50 Idées de Posts', 'ai-content-studio')}</h3>
                                <button className="acs-btn acs-btn-sm" onClick={() => setContentIdeas([])}>
                                    {__('Fermer', 'ai-content-studio')}
                                </button>
                            </div>
                            <p className="acs-text-muted" style={{ marginBottom: 'var(--acs-spacing-4)' }}>
                                {__('Cliquez sur "Générer" à côté d\'une idée pour créer le post complet', 'ai-content-studio')}
                            </p>
                            <div
                                style={{
                                    display: 'grid',
                                    gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))',
                                    gap: 'var(--acs-spacing-3)',
                                    maxHeight: '600px',
                                    overflow: 'auto',
                                    padding: '2px',
                                }}
                            >
                                {contentIdeas.map((idea, index) => (
                                    <div
                                        key={index}
                                        style={{
                                            display: 'flex',
                                            alignItems: 'center',
                                            gap: 'var(--acs-spacing-2)',
                                            padding: 'var(--acs-spacing-3)',
                                            background: 'var(--acs-gray-50)',
                                            borderRadius: 'var(--acs-radius)',
                                            border: '1px solid var(--acs-gray-200)',
                                            transition: 'all 0.2s',
                                        }}
                                        onMouseEnter={(e) => {
                                            e.currentTarget.style.borderColor = 'var(--acs-primary)';
                                            e.currentTarget.style.background = 'var(--acs-white)';
                                        }}
                                        onMouseLeave={(e) => {
                                            e.currentTarget.style.borderColor = 'var(--acs-gray-200)';
                                            e.currentTarget.style.background = 'var(--acs-gray-50)';
                                        }}
                                    >
                                        <div
                                            style={{
                                                width: '24px',
                                                height: '24px',
                                                borderRadius: '50%',
                                                background: 'var(--acs-primary)',
                                                color: 'var(--acs-white)',
                                                display: 'flex',
                                                alignItems: 'center',
                                                justifyContent: 'center',
                                                fontSize: 'var(--acs-font-size-sm)',
                                                fontWeight: 600,
                                                flexShrink: 0,
                                            }}
                                        >
                                            {index + 1}
                                        </div>
                                        <div style={{ flex: 1, fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-800)' }}>{idea}</div>
                                        <button
                                            className="acs-btn acs-btn-primary acs-btn-sm"
                                            onClick={() => handleGeneratePostFromIdea(idea)}
                                            style={{ flexShrink: 0 }}
                                        >
                                            {__('Générer', 'ai-content-studio')}
                                        </button>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* How it works */}
                    <div className="acs-card" style={{ marginTop: 'var(--acs-spacing-4)' }}>
                        <h3 className="acs-card-title" style={{ marginBottom: 'var(--acs-spacing-3)' }}>
                            ✨ {__('Comment ça marche ?', 'ai-content-studio')}
                        </h3>
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: 'var(--acs-spacing-4)' }}>
                            <div>
                                <div style={{ fontSize: '2rem', marginBottom: 'var(--acs-spacing-2)' }}>🎯</div>
                                <h4 style={{ marginBottom: 'var(--acs-spacing-2)' }}>1. Analyse de vos objectifs</h4>
                                <p style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)', margin: 0 }}>
                                    L'IA analyse votre profil, secteur, audience et objectifs pour créer une stratégie sur mesure
                                </p>
                            </div>
                            <div>
                                <div style={{ fontSize: '2rem', marginBottom: 'var(--acs-spacing-2)' }}>📅</div>
                                <h4 style={{ marginBottom: 'var(--acs-spacing-2)' }}>2. Planification intelligente</h4>
                                <p style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)', margin: 0 }}>
                                    Création d'un calendrier avec thèmes hebdomadaires, mix de contenu optimal et meilleurs moments de publication
                                </p>
                            </div>
                            <div>
                                <div style={{ fontSize: '2rem', marginBottom: 'var(--acs-spacing-2)' }}>🚀</div>
                                <h4 style={{ marginBottom: 'var(--acs-spacing-2)' }}>3. Génération automatique</h4>
                                <p style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)', margin: 0 }}>
                                    Les posts sont générés automatiquement et ajoutés à votre calendrier. Plus qu'à publier !
                                </p>
                            </div>
                        </div>
                    </div>
                </>
            ) : (
                <>
                    {/* Strategy Generated */}
                    <div className="acs-card">
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 'var(--acs-spacing-4)' }}>
                            <div>
                                <h2 style={{ marginBottom: 'var(--acs-spacing-1)' }}>
                                    🎉 {__('Votre stratégie est prête !', 'ai-content-studio')}
                                </h2>
                                <p style={{ color: 'var(--acs-gray-600)', margin: 0 }}>
                                    {months[selectedMonth - 1]} {selectedYear}
                                </p>
                            </div>
                            <button className="acs-btn acs-btn-outline-primary" onClick={() => setStrategy(null)}>
                                <FiRefreshCw /> {__('Générer une nouvelle stratégie', 'ai-content-studio')}
                            </button>
                        </div>

                        {/* Strategy Overview */}
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 'var(--acs-spacing-3)', marginBottom: 'var(--acs-spacing-5)' }}>
                            <div className="acs-stat-card primary">
                                <div className="acs-stat-header">
                                    <div>
                                        <div className="acs-stat-value">{strategy.total_posts || postsPerWeek * 4}</div>
                                        <div className="acs-stat-label">{__('Posts à créer', 'ai-content-studio')}</div>
                                    </div>
                                    <div className="acs-stat-icon primary">
                                        <FiCalendar />
                                    </div>
                                </div>
                            </div>

                            <div className="acs-stat-card success">
                                <div className="acs-stat-header">
                                    <div>
                                        <div className="acs-stat-value">{strategy.platforms?.length || 2}</div>
                                        <div className="acs-stat-label">{__('Plateformes', 'ai-content-studio')}</div>
                                    </div>
                                    <div className="acs-stat-icon success">
                                        <FiTrendingUp />
                                    </div>
                                </div>
                            </div>

                            <div className="acs-stat-card secondary">
                                <div className="acs-stat-header">
                                    <div>
                                        <div className="acs-stat-value">{strategy.weekly_themes?.length || 4}</div>
                                        <div className="acs-stat-label">{__('Thèmes hebdo', 'ai-content-studio')}</div>
                                    </div>
                                    <div className="acs-stat-icon secondary">
                                        <FiTarget />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Weekly Themes */}
                        <div style={{ marginBottom: 'var(--acs-spacing-5)' }}>
                            <h3 style={{ marginBottom: 'var(--acs-spacing-3)' }}>📅 {__('Thèmes hebdomadaires', 'ai-content-studio')}</h3>
                            <div style={{ display: 'grid', gap: 'var(--acs-spacing-3)' }}>
                                {(strategy.weekly_themes || [
                                    { week: 1, theme: 'Introduction & Accueil', description: 'Présentez votre activité et créez du lien' },
                                    { week: 2, theme: 'Éducation & Valeur', description: 'Partagez votre expertise et des conseils' },
                                    { week: 3, theme: 'Engagement & Communauté', description: 'Interagissez et créez de l\'engagement' },
                                    { week: 4, theme: 'Promotion & Conversion', description: 'Présentez vos offres et call-to-actions' },
                                ]).map((week, i) => (
                                    <div
                                        key={i}
                                        style={{
                                            padding: 'var(--acs-spacing-4)',
                                            background: 'var(--acs-gray-50)',
                                            borderRadius: 'var(--acs-radius-lg)',
                                            borderLeft: '4px solid var(--acs-primary)',
                                        }}
                                    >
                                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'start', marginBottom: 'var(--acs-spacing-2)' }}>
                                            <div>
                                                <span
                                                    style={{
                                                        fontSize: 'var(--acs-font-size-sm)',
                                                        fontWeight: 600,
                                                        color: 'var(--acs-primary)',
                                                        textTransform: 'uppercase',
                                                    }}
                                                >
                                                    Semaine {week.week}
                                                </span>
                                                <h4 style={{ fontSize: 'var(--acs-font-size-lg)', marginBottom: 'var(--acs-spacing-1)' }}>{week.theme}</h4>
                                            </div>
                                            <span
                                                style={{
                                                    padding: '4px 12px',
                                                    background: 'var(--acs-primary)',
                                                    color: 'var(--acs-white)',
                                                    borderRadius: '12px',
                                                    fontSize: 'var(--acs-font-size-sm)',
                                                    fontWeight: 600,
                                                }}
                                            >
                                                {postsPerWeek} posts
                                            </span>
                                        </div>
                                        <p style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-700)', margin: 0 }}>{week.description}</p>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Best posting times */}
                        <div style={{ marginBottom: 'var(--acs-spacing-5)' }}>
                            <h3 style={{ marginBottom: 'var(--acs-spacing-3)' }}>⏰ {__('Meilleurs moments de publication', 'ai-content-studio')}</h3>
                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 'var(--acs-spacing-3)' }}>
                                {(strategy.platforms || ['instagram', 'facebook']).map((platform) => {
                                    const times = BEST_POSTING_TIMES[platform]?.[profile?.user_type || 'business'] || BEST_POSTING_TIMES[platform]?.business;
                                    return (
                                        <div
                                            key={platform}
                                            style={{
                                                padding: 'var(--acs-spacing-3)',
                                                background: 'var(--acs-white)',
                                                border: '1px solid var(--acs-gray-200)',
                                                borderRadius: 'var(--acs-radius)',
                                            }}
                                        >
                                            <div style={{ fontWeight: 600, marginBottom: 'var(--acs-spacing-2)', textTransform: 'capitalize' }}>
                                                {platform === 'instagram' && '📷'} {platform === 'facebook' && '📘'} {platform === 'linkedin' && '💼'}{' '}
                                                {platform === 'twitter' && '🐦'} {platform}
                                            </div>
                                            {times && (
                                                <>
                                                    <div style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-700)' }}>
                                                        <strong>Jours :</strong> {times.best_days.join(', ')}
                                                    </div>
                                                    <div style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-700)' }}>
                                                        <strong>Heures :</strong> {times.best_times.join(', ')}
                                                    </div>
                                                </>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Actions */}
                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 'var(--acs-spacing-3)' }}>
                            <button className="acs-btn acs-btn-primary acs-btn-lg" onClick={applyStrategy}>
                                <FiZap /> {__('Appliquer & Générer les posts', 'ai-content-studio')}
                            </button>
                            <button className="acs-btn acs-btn-outline-primary acs-btn-lg" onClick={downloadStrategy}>
                                <FiDownload /> {__('Télécharger la stratégie', 'ai-content-studio')}
                            </button>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}
