import { useState, useEffect } from '@wordpress/element';
import { FiCalendar, FiTarget, FiTrendingUp, FiLock } from 'react-icons/fi';
import { useNavigate } from 'react-router-dom';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { CONTENT_TYPES, CONTENT_MIX_RECOMMENDATIONS, BEST_POSTING_TIMES } from '../../data/contentTemplates';
import PricingModal from '../common/PricingModal';

export default function StrategyGenerator({ profile }) {
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [strategy, setStrategy] = useState(null);
    const [contentIdeas, setContentIdeas] = useState([]);
    const [generatingIdeas, setGeneratingIdeas] = useState(false);
    const [language, setLanguage] = useState('fr');
    const [languageLoaded, setLanguageLoaded] = useState(false);
    const [showPricingModal, setShowPricingModal] = useState(false);

    // Check if user has access to strategy features
    const hasStrategyAccess = profile?.subscription_plan && profile.subscription_plan !== 'free_trial';

    // Charger la langue de l'utilisateur au montage
    useEffect(() => {
        const fetchUserLanguage = async () => {
            try {
                const response = await apiFetch({
                    path: '/acs/v1/profile/language',
                    method: 'GET',
                });
                if (response.success && response.data.language) {
                    setLanguage(response.data.language);
                }
            } catch (err) {
                console.log('Could not fetch user language, using default');
            } finally {
                setLanguageLoaded(true);
            }
        };
        fetchUserLanguage();
    }, []);

    // Charger ou générer automatiquement la stratégie au montage (quand langue est chargée)
    useEffect(() => {
        if (!profile || !languageLoaded) return;
        // Ne charger la stratégie que si l'utilisateur a accès
        if (hasStrategyAccess) {
            loadOrGenerateStrategy();
        } else {
            setLoading(false); // Arrêter le chargement pour afficher le lock screen
        }
    }, [profile, languageLoaded, hasStrategyAccess]);

    // Régénérer automatiquement la stratégie quand la langue change
    useEffect(() => {
        if (!languageLoaded || !profile) return; // Skip initial load
        if (!strategy) return; // Skip if no strategy yet

        console.log('Language changed, regenerating strategy...');
        generateStrategyAutomatically();

        // Régénérer aussi les content ideas si elles existent
        if (contentIdeas && contentIdeas.length > 0) {
            console.log('Language changed, regenerating content ideas...');
            generateContentIdeas();
        }
    }, [language]);

    const loadOrGenerateStrategy = async () => {
        setLoading(true);
        try {
            // Toujours régénérer la stratégie dans la langue actuelle de l'utilisateur
            // Cela garantit que la stratégie est toujours dans la bonne langue
            console.log('Generating strategy in user language:', language);
            await generateStrategyAutomatically();
        } catch (err) {
            console.error('Error loading/generating strategy:', err);
        } finally {
            setLoading(false);
        }
    };

    const generateStrategyAutomatically = async () => {
        setLoading(true);
        try {
            const goals = profile?.goals ? (typeof profile.goals === 'string' ? JSON.parse(profile.goals) : profile.goals) : [];
            const mainGoal = goals[0] || 'brand_awareness';

            const response = await apiFetch({
                path: '/acs/v1/strategy/generate',
                method: 'POST',
                data: {
                    month: new Date().getMonth() + 1,
                    year: new Date().getFullYear(),
                    goal: mainGoal,
                    posts_per_week: 3,
                    language: language,
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
            }
        } catch (err) {
            console.error('Error generating strategy:', err);
        } finally {
            setLoading(false);
        }
    };

    const generateContentIdeas = async () => {
        setGeneratingIdeas(true);
        try {
            const goals = profile?.goals ? (typeof profile.goals === 'string' ? JSON.parse(profile.goals) : profile.goals) : [];
            const mainGoal = goals[0] || 'engagement';

            const response = await apiFetch({
                path: '/acs/v1/strategy/content-ideas',
                method: 'POST',
                data: {
                    goal: mainGoal,
                    language: language,
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

    if (loading) {
        return (
            <div>
                <div style={{ marginBottom: 'var(--acs-spacing-4)' }}>
                    <h1 className="acs-page-title">{__('Votre Stratégie de Contenu', 'ai-content-studio')}</h1>
                    <p className="acs-text-muted">
                        {__('Génération de votre stratégie personnalisée...', 'ai-content-studio')}
                    </p>
                </div>
                <div className="acs-card" style={{ textAlign: 'center', padding: 'var(--acs-spacing-6)' }}>
                    <div className="acs-spinner" style={{ width: '40px', height: '40px', margin: '0 auto var(--acs-spacing-3)' }} />
                    <p>{__('Analyse de votre profil et création de votre stratégie...', 'ai-content-studio')}</p>
                </div>
            </div>
        );
    }

    if (!strategy) {
        return (
            <div>
                <div style={{ marginBottom: 'var(--acs-spacing-4)' }}>
                    <h1 className="acs-page-title">{__('Votre Stratégie de Contenu', 'ai-content-studio')}</h1>
                </div>
                <div className="acs-card" style={{ textAlign: 'center', padding: 'var(--acs-spacing-6)' }}>
                    <p>{__('Impossible de générer votre stratégie. Veuillez réessayer plus tard.', 'ai-content-studio')}</p>
                </div>
            </div>
        );
    }

    const goals = profile?.goals ? (typeof profile.goals === 'string' ? JSON.parse(profile.goals) : profile.goals) : [];
    const mainGoal = goals[0] || 'brand_awareness';
    const contentMix = CONTENT_MIX_RECOMMENDATIONS[mainGoal] || CONTENT_MIX_RECOMMENDATIONS.brand_awareness;

    return (
        <div>
            <div style={{ marginBottom: 'var(--acs-spacing-4)' }}>
                <h1 className="acs-page-title">{__('🎯 Votre Stratégie de Contenu', 'ai-content-studio')}</h1>
                <p className="acs-text-muted">
                    {__('Stratégie personnalisée basée sur votre profil et vos objectifs', 'ai-content-studio')}
                </p>
            </div>

            {/* Language Selector */}
            <div className="acs-card" style={{ marginBottom: 'var(--acs-spacing-4)' }}>
                <div className="acs-form-group" style={{ marginBottom: 0 }}>
                    <label className="acs-form-label">{__('Langue de génération', 'ai-content-studio')}</label>
                    <select className="acs-select" value={language} onChange={(e) => setLanguage(e.target.value)}>
                        <optgroup label="🌍 Europe">
                            <option value="fr">🇫🇷 Français</option>
                            <option value="en">🇬🇧 English</option>
                            <option value="es">🇪🇸 Español</option>
                            <option value="de">🇩🇪 Deutsch</option>
                            <option value="it">🇮🇹 Italiano</option>
                            <option value="pt">🇵🇹 Português</option>
                            <option value="ru">🇷🇺 Русский</option>
                            <option value="pl">🇵🇱 Polski</option>
                            <option value="nl">🇳🇱 Nederlands</option>
                            <option value="tr">🇹🇷 Türkçe</option>
                            <option value="uk">🇺🇦 Українська</option>
                            <option value="el">🇬🇷 Ελληνικά</option>
                            <option value="sv">🇸🇪 Svenska</option>
                            <option value="da">🇩🇰 Dansk</option>
                            <option value="fi">🇫🇮 Suomi</option>
                            <option value="no">🇳🇴 Norsk</option>
                            <option value="cs">🇨🇿 Čeština</option>
                            <option value="ro">🇷🇴 Română</option>
                            <option value="hu">🇭🇺 Magyar</option>
                            <option value="ca">🇪🇸 Català</option>
                        </optgroup>
                        <optgroup label="🌏 Asie">
                            <option value="zh">🇨🇳 中文 (Mandarin)</option>
                            <option value="ja">🇯🇵 日本語</option>
                            <option value="ko">🇰🇷 한국어</option>
                            <option value="hi">🇮🇳 हिन्दी</option>
                            <option value="ar">🇸🇦 العربية</option>
                            <option value="vi">🇻🇳 Tiếng Việt</option>
                            <option value="id">🇮🇩 Bahasa Indonesia</option>
                            <option value="th">🇹🇭 ไทย</option>
                            <option value="bn">🇧🇩 বাংলা</option>
                            <option value="he">🇮🇱 עברית</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            {/* Afficher le contenu seulement si l'utilisateur a accès */}
            {hasStrategyAccess ? (
                <>
                    {/* Strategy Overview */}
                    <div className="acs-card" style={{ marginBottom: 'var(--acs-spacing-4)' }}>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 'var(--acs-spacing-3)', marginBottom: 'var(--acs-spacing-5)' }}>
                    <div className="acs-stat-card primary">
                        <div className="acs-stat-header">
                            <div>
                                <div className="acs-stat-value">{strategy.total_posts || 12}</div>
                                <div className="acs-stat-label">{__('Posts/mois', 'ai-content-studio')}</div>
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

                {/* Strategy Summary */}
                {strategy.strategy_summary && (
                    <div
                        style={{
                            padding: 'var(--acs-spacing-4)',
                            background: 'linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05))',
                            borderRadius: 'var(--acs-radius-lg)',
                            marginBottom: 'var(--acs-spacing-4)',
                        }}
                    >
                        <h4 style={{ marginBottom: 'var(--acs-spacing-2)' }}>📋 {__('Résumé de votre stratégie', 'ai-content-studio')}</h4>
                        <p style={{ margin: 0, color: 'var(--acs-gray-700)' }}>{strategy.strategy_summary}</p>
                    </div>
                )}

                {/* Content Mix */}
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
                        {Object.entries(contentMix).map(([type, percentage]) => {
                            const contentType = CONTENT_TYPES[type.toUpperCase()] || CONTENT_TYPES.EDUCATIONAL;
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

                {/* Generate Ideas Button - Masqué si des idées existent déjà */}
                {contentIdeas.length === 0 && (
                    <div style={{ textAlign: 'center' }}>
                        <button className="acs-btn acs-btn-primary acs-btn-lg" onClick={generateContentIdeas} disabled={generatingIdeas}>
                            {generatingIdeas ? (
                                <>
                                    <div className="acs-spinner" style={{ width: '20px', height: '20px', borderWidth: '2px' }} />
                                    {__('Génération...', 'ai-content-studio')}
                                </>
                            ) : (
                                <>
                                    💡 {__('Générer 50 idées de posts', 'ai-content-studio')}
                                </>
                            )}
                        </button>
                    </div>
                )}
            </div>

            {/* Content Ideas List */}
            {contentIdeas.length > 0 && (
                <div className="acs-card" style={{ marginBottom: 'var(--acs-spacing-4)' }}>
                    <div style={{ marginBottom: 'var(--acs-spacing-4)' }}>
                        <h3 className="acs-card-title">💡 {__('50 Idées de Posts', 'ai-content-studio')}</h3>
                    </div>
                    <p className="acs-text-muted" style={{ marginBottom: 'var(--acs-spacing-4)' }}>
                        {__('Cliquez sur "Générer" pour créer un post complet à partir d\'une idée', 'ai-content-studio')}
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

            {/* Weekly Themes */}
            <div className="acs-card" style={{ marginBottom: 'var(--acs-spacing-4)' }}>
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
                            </div>
                            <p style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-700)', margin: 0 }}>{week.description}</p>
                        </div>
                    ))}
                </div>
            </div>

            {/* Best posting times */}
            <div className="acs-card">
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
                </>
            ) : (
                /* Message simple pour les utilisateurs sans accès */
                <div className="acs-card" style={{ textAlign: 'center', padding: 'var(--acs-spacing-6)' }}>
                    <p style={{ color: 'var(--acs-gray-600)' }}>
                        {__('Cette fonctionnalité est réservée aux abonnés premium.', 'ai-content-studio')}
                    </p>
                </div>
            )}

            {/* Lock Overlay for Free Trial Users */}
            {!hasStrategyAccess && (
                <div style={{
                    position: 'fixed',
                    top: 0,
                    left: 0,
                    right: 0,
                    bottom: 0,
                    background: 'rgba(255, 255, 255, 0.95)',
                    backdropFilter: 'blur(10px)',
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    zIndex: 9999,
                    padding: 'var(--acs-spacing-4)',
                }}>
                    <div style={{
                        textAlign: 'center',
                        maxWidth: '500px',
                        background: 'white',
                        padding: 'var(--acs-spacing-6)',
                        borderRadius: 'var(--acs-radius-lg)',
                        boxShadow: '0 20px 60px rgba(0, 0, 0, 0.15)',
                    }}>
                        <div style={{
                            width: '80px',
                            height: '80px',
                            background: 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)',
                            borderRadius: '50%',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            margin: '0 auto var(--acs-spacing-4)',
                        }}>
                            <FiLock size={40} color="white" />
                        </div>
                        <h2 style={{ marginBottom: 'var(--acs-spacing-2)', color: 'var(--acs-gray-900)' }}>
                            {__('Fonctionnalité Premium', 'ai-content-studio')}
                        </h2>
                        <p style={{ color: 'var(--acs-gray-600)', marginBottom: 'var(--acs-spacing-4)', lineHeight: '1.6' }}>
                            {__('La génération de stratégies de contenu est réservée aux abonnés. Passez à un plan payant pour débloquer cette fonctionnalité et bien plus encore.', 'ai-content-studio')}
                        </p>
                        <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--acs-spacing-2)' }}>
                            <button
                                className="acs-btn acs-btn-primary"
                                onClick={() => setShowPricingModal(true)}
                                style={{ width: '100%' }}
                            >
                                {__('Voir les plans', 'ai-content-studio')}
                            </button>
                            <button
                                className="acs-btn acs-btn-outline-secondary"
                                onClick={() => navigate('/dashboard')}
                                style={{ width: '100%' }}
                            >
                                {__('Retour au tableau de bord', 'ai-content-studio')}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Pricing Modal */}
            <PricingModal
                isOpen={showPricingModal}
                onClose={() => setShowPricingModal(false)}
                currentPlan={profile?.subscription_plan || 'free_trial'}
                triggerType="strategy_locked"
            />
        </div>
    );
}
