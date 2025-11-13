import { useState, useEffect } from '@wordpress/element';
import { FiBookOpen, FiLoader, FiZap, FiLock } from 'react-icons/fi';
import { useNavigate } from 'react-router-dom';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import PricingModal from '../common/PricingModal';

export default function BlogStrategyGenerator({ profile }) {
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [blogIdeas, setBlogIdeas] = useState([]);
    const [language, setLanguage] = useState('fr');
    const [languageLoaded, setLanguageLoaded] = useState(false);
    const [showPricingModal, setShowPricingModal] = useState(false);

    // Check if user has access to strategy features
    // Strategy access is available for all paid plans (starter, professional, business)
    const hasStrategyAccess = profile?.subscription_plan &&
        ['starter', 'professional', 'business', 'pro'].includes(profile.subscription_plan);

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

    // Charger ou générer la stratégie au montage (quand langue est chargée)
    useEffect(() => {
        if (!profile || !languageLoaded) return;
        // Ne charger la stratégie que si l'utilisateur a accès
        if (hasStrategyAccess) {
            console.log('Loading or generating blog strategy with language:', language);
            console.log('Profile data:', profile);
            loadOrGenerateStrategy();
        } else {
            setLoading(false); // Arrêter le chargement pour afficher le lock screen
        }
    }, [profile, languageLoaded, hasStrategyAccess]);

    // Régénérer automatiquement les idées quand la langue change
    useEffect(() => {
        if (!languageLoaded || !profile) return;
        if (!blogIdeas || blogIdeas.length === 0) return;

        console.log('Language changed, regenerating blog ideas...');
        generateBlogIdeas();
    }, [language]);

    const loadOrGenerateStrategy = async () => {
        setLoading(true);
        try {
            // Essayer de charger la stratégie existante
            const response = await apiFetch({
                path: '/acs/v1/blog/strategy/current',
                method: 'GET',
            });

            if (response.success && response.data && response.data.ideas) {
                console.log('Loaded existing blog strategy from database');
                setBlogIdeas(response.data.ideas);
                // Mettre à jour la langue si différente
                if (response.data.language !== language) {
                    setLanguage(response.data.language);
                }
            } else {
                // Pas de stratégie existante, générer une nouvelle
                console.log('No existing strategy found, generating new one');
                await generateBlogIdeas();
            }
        } catch (err) {
            // Erreur ou pas de stratégie, générer une nouvelle
            console.log('Error loading strategy, generating new one:', err);
            await generateBlogIdeas();
        } finally {
            setLoading(false);
        }
    };

    const generateBlogIdeas = async () => {
        setLoading(true);
        try {
            // Parse goals si c'est une string JSON
            const goals = profile?.goals ? (typeof profile.goals === 'string' ? JSON.parse(profile.goals) : profile.goals) : [];
            const mainGoal = goals[0] || 'engagement';

            const response = await apiFetch({
                path: '/acs/v1/blog/strategy-ideas',
                method: 'POST',
                data: {
                    language: language,
                    goal: mainGoal,
                    profile: {
                        user_type: profile?.user_type || 'business',
                        business_name: profile?.business_name || '',
                        sector: profile?.sector || '',
                        description: profile?.description || '',
                        target_audience: profile?.target_audience || '',
                        niche: profile?.niche || '',
                        keywords: profile?.primary_keywords || [],
                        blog_topics: profile?.blog_topics || [],
                        goals: goals,
                        platforms: profile?.social_platforms || profile?.platforms || [],
                    },
                },
            });

            if (response.success && response.data) {
                setBlogIdeas(response.data);

                // Sauvegarder automatiquement la stratégie
                try {
                    await apiFetch({
                        path: '/acs/v1/blog/strategy/save',
                        method: 'POST',
                        data: {
                            ideas: response.data,
                            language: language,
                            goal: mainGoal,
                        },
                    });
                    console.log('Blog strategy saved successfully');
                } catch (saveErr) {
                    console.error('Error saving blog strategy:', saveErr);
                }
            } else {
                console.error('Failed to generate blog ideas');
                setBlogIdeas([]);
            }
        } catch (err) {
            console.error('Error generating blog ideas:', err);
            setBlogIdeas([]);
        } finally {
            setLoading(false);
        }
    };

    const handleIdeaClick = (idea) => {
        // Naviguer vers le générateur d'articles avec le sujet pré-rempli
        navigate('/blog-generator', { state: { subject: idea } });
    };

    if (loading) {
        return (
            <div style={{ textAlign: 'center', padding: 'var(--acs-spacing-8)' }}>
                <FiLoader style={{ fontSize: '3rem', animation: 'spin 1s linear infinite' }} />
                <p style={{ marginTop: 'var(--acs-spacing-4)', color: 'var(--acs-gray-600)' }}>
                    {__('Génération de votre stratégie blog...', 'ai-content-studio')}
                </p>
            </div>
        );
    }

    return (
        <div>
            <div style={{ marginBottom: 'var(--acs-spacing-4)' }}>
                <h1 className="acs-page-title">{__('📚 Stratégie Articles de Blog', 'ai-content-studio')}</h1>
                <p className="acs-text-muted">
                    {__('50 idées de sujets d\'articles personnalisées pour votre blog', 'ai-content-studio')}
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

            {/* Blog Ideas Grid */}
            <div className="acs-card">
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 'var(--acs-spacing-4)' }}>
                    <h3 style={{ display: 'flex', alignItems: 'center', gap: 'var(--acs-spacing-2)', margin: 0 }}>
                        <FiBookOpen />
                        {__('Idées d\'articles pour votre blog', 'ai-content-studio')}
                    </h3>
                    <button
                        className="acs-btn acs-btn-primary"
                        onClick={generateBlogIdeas}
                        disabled={loading}
                        style={{ display: 'flex', alignItems: 'center', gap: 'var(--acs-spacing-2)' }}
                    >
                        {loading ? (
                            <>
                                <FiLoader style={{ animation: 'spin 1s linear infinite' }} />
                                {__('Génération...', 'ai-content-studio')}
                            </>
                        ) : (
                            <>
                                <FiZap />
                                {blogIdeas.length === 0
                                    ? __('Générer les idées', 'ai-content-studio')
                                    : __('Régénérer', 'ai-content-studio')
                                }
                            </>
                        )}
                    </button>
                </div>

                {blogIdeas.length === 0 ? (
                    <div style={{ textAlign: 'center', padding: 'var(--acs-spacing-6)', color: 'var(--acs-gray-500)' }}>
                        {__('Cliquez sur "Générer les idées" pour obtenir 50 sujets d\'articles personnalisés', 'ai-content-studio')}
                    </div>
                ) : (
                    <div style={{ display: 'grid', gap: 'var(--acs-spacing-3)' }}>
                        {blogIdeas.map((idea, index) => (
                            <div
                                key={index}
                                onClick={() => handleIdeaClick(idea)}
                                style={{
                                    padding: 'var(--acs-spacing-3)',
                                    background: 'var(--acs-white)',
                                    border: '2px solid var(--acs-gray-200)',
                                    borderRadius: 'var(--acs-radius-lg)',
                                    cursor: 'pointer',
                                    transition: 'var(--acs-transition)',
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: 'var(--acs-spacing-3)',
                                }}
                                onMouseEnter={(e) => {
                                    e.currentTarget.style.borderColor = 'var(--acs-primary)';
                                    e.currentTarget.style.background = 'rgba(99, 102, 241, 0.05)';
                                    e.currentTarget.style.transform = 'translateX(4px)';
                                }}
                                onMouseLeave={(e) => {
                                    e.currentTarget.style.borderColor = 'var(--acs-gray-200)';
                                    e.currentTarget.style.background = 'var(--acs-white)';
                                    e.currentTarget.style.transform = 'translateX(0)';
                                }}
                            >
                                <div
                                    style={{
                                        width: '32px',
                                        height: '32px',
                                        borderRadius: '50%',
                                        background: 'linear-gradient(135deg, var(--acs-primary), var(--acs-secondary))',
                                        color: 'var(--acs-white)',
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        fontWeight: 'bold',
                                        fontSize: '14px',
                                        flexShrink: 0,
                                    }}
                                >
                                    {index + 1}
                                </div>
                                <div style={{ flex: 1, fontWeight: 500, color: 'var(--acs-gray-800)' }}>
                                    {idea}
                                </div>
                                <FiBookOpen style={{ color: 'var(--acs-primary)', flexShrink: 0 }} />
                            </div>
                        ))}
                    </div>
                )}
            </div>

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
                            {__('La génération de stratégies de blog est réservée aux abonnés. Passez à un plan payant pour débloquer cette fonctionnalité et bien plus encore.', 'ai-content-studio')}
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
                triggerType="blog_strategy_locked"
            />

            <style>{`
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
            `}</style>
        </div>
    );
}
