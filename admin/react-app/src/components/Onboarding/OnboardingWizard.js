import { useState, useEffect } from '@wordpress/element';
import { FiCheck, FiBriefcase, FiUser, FiTarget, FiTrendingUp } from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import { useTranslation } from '../../contexts/TranslationContext';

export default function OnboardingWizard({ onComplete, isAddingProject = false }) {
    const { t } = useTranslation();
    const [step, setStep] = useState(1);

    // Scroll to top when step changes to 2
    useEffect(() => {
        if (step === 2) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }, [step]);
    const [formData, setFormData] = useState({
        // Informations de base
        user_type: '', // 'business', 'creator', 'freelance', 'agency'
        business_name: '',
        sector: '',
        description: '',
        website: '',

        // Objectifs et réseaux sociaux
        goals: [],
        social_platforms: [],
        posting_frequency: 'weekly',

        // SEO et mots-clés
        has_blog: false,
        blog_topics: [],
        seo_goals: [],
        primary_keywords: [],
        niche: '',
        target_audience: '',

        // Autres
        languages: ['fr'],
    });
    const [loading, setLoading] = useState(false);

    const updateField = (field, value) => {
        setFormData(prev => ({ ...prev, [field]: value }));
    };

    const toggleArrayField = (field, value) => {
        setFormData(prev => ({
            ...prev,
            [field]: prev[field].includes(value)
                ? prev[field].filter(v => v !== value)
                : [...prev[field], value]
        }));
    };

    const handleSubmit = async () => {
        setLoading(true);
        try {
            if (isAddingProject) {
                // Adding a new project
                const projectData = {
                    project_name: formData.business_name,
                    ...formData,
                };

                const response = await apiFetch({
                    path: '/acs/v1/projects',
                    method: 'POST',
                    data: projectData,
                });

                if (response.success) {
                    onComplete(response.data.project);
                }
            } else {
                // Initial profile creation (first time onboarding)
                const response = await apiFetch({
                    path: '/acs/v1/profile',
                    method: 'POST',
                    data: formData,
                });

                if (response.success) {
                    onComplete(formData);
                }
            }
        } catch (error) {
            console.error('Save error:', error);
            const errorMessage = isAddingProject
                ? t('Erreur lors de la création du projet')
                : t('Erreur lors de la sauvegarde du profil');
            alert(errorMessage + ': ' + (error.message || 'Unknown error'));
        } finally {
            setLoading(false);
        }
    };

    const canProceed = () => {
        if (step === 1) return formData.user_type && formData.business_name && formData.sector && formData.description;
        if (step === 2) return formData.goals.length > 0 && formData.social_platforms.length > 0;
        return true;
    };

    const totalSteps = 2;

    return (
        <div style={{ padding: 'var(--acs-spacing-6)', background: 'var(--acs-body-bg)', minHeight: '100vh' }}>
            {/* Progress Bar */}
            <div style={{ maxWidth: '800px', margin: '0 auto var(--acs-spacing-6)' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 'var(--acs-spacing-2)' }}>
                    <span style={{ fontSize: 'var(--acs-font-size-sm)', fontWeight: 600, color: 'var(--acs-primary)' }}>
                        {isAddingProject
                            ? t('Ajouter un nouveau projet')
                            : t('Configuration de votre profil')}
                    </span>
                    <span style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)' }}>
                        {step}/{totalSteps}
                    </span>
                </div>
                <div className="acs-progress">
                    <div className="acs-progress-bar" style={{ width: `${(step / totalSteps) * 100}%` }} />
                </div>
            </div>

            {/* Content Card */}
            <div className="acs-card" style={{ maxWidth: '800px', margin: '0 auto' }}>

                {/* Step 1: Informations de base */}
                {step === 1 && (
                    <div>
                        <div style={{ textAlign: 'center', marginBottom: 'var(--acs-spacing-6)' }}>
                            <h2 style={{ fontSize: '1.75rem', fontWeight: 700, marginBottom: 'var(--acs-spacing-2)' }}>
                                {isAddingProject
                                    ? t('Créer un nouveau projet') + ' 🚀'
                                    : t('Bienvenue sur AI Content Studio') + ' 👋'}
                            </h2>
                            <p style={{ color: 'var(--acs-gray-600)' }}>
                                {isAddingProject
                                    ? t('Remplissez les informations de votre nouveau projet client')
                                    : t('Pour personnaliser votre expérience, dites-nous qui vous êtes')}
                            </p>
                        </div>

                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 'var(--acs-spacing-4)', marginBottom: 'var(--acs-spacing-5)' }}>
                            {[
                                { value: 'business', icon: FiBriefcase, label: t('Entreprise'), desc: t('PME, startup, e-commerce') },
                                { value: 'creator', icon: FiUser, label: t('Créateur'), desc: t('Artiste, influenceur, coach') },
                                { value: 'freelance', icon: FiTrendingUp, label: t('Freelance'), desc: t('Consultant, indépendant') },
                                { value: 'agency', icon: FiBriefcase, label: t('Agence'), desc: t('Agence marketing, com') },
                            ].map((type) => {
                                const Icon = type.icon;
                                return (
                                    <div
                                        key={type.value}
                                        onClick={() => updateField('user_type', type.value)}
                                        style={{
                                            padding: 'var(--acs-spacing-4)',
                                            border: `2px solid ${formData.user_type === type.value ? 'var(--acs-primary)' : 'var(--acs-gray-200)'}`,
                                            borderRadius: 'var(--acs-radius-lg)',
                                            textAlign: 'center',
                                            cursor: 'pointer',
                                            transition: 'var(--acs-transition)',
                                            background: formData.user_type === type.value ? 'rgba(99, 102, 241, 0.05)' : 'var(--acs-white)',
                                        }}
                                    >
                                        <Icon size={32} style={{ color: formData.user_type === type.value ? 'var(--acs-primary)' : 'var(--acs-gray-400)', marginBottom: 'var(--acs-spacing-2)' }} />
                                        <h3 style={{ fontSize: 'var(--acs-font-size-lg)', fontWeight: 600, marginBottom: 'var(--acs-spacing-1)' }}>{type.label}</h3>
                                        <p style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)', margin: 0 }}>{type.desc}</p>
                                    </div>
                                );
                            })}
                        </div>

                        <div className="acs-form-group">
                            <label className="acs-form-label">
                                {isAddingProject
                                    ? t('Nom du projet client')
                                    : t('Nom de votre entreprise/marque')} *
                            </label>
                            <input
                                type="text"
                                className="acs-form-control"
                                value={formData.business_name}
                                onChange={(e) => updateField('business_name', e.target.value)}
                                placeholder={isAddingProject
                                    ? t('Ex: Client ABC')
                                    : t('Ex: Mon Entreprise')}
                            />
                        </div>

                        <div className="acs-form-group">
                            <label className="acs-form-label">{t('Secteur d\'activité')} *</label>
                            <select
                                className="acs-select"
                                value={formData.sector}
                                onChange={(e) => updateField('sector', e.target.value)}
                            >
                                <option value="">{t('Sélectionnez un secteur')}</option>
                                <option value="ecommerce">{t('E-commerce')}</option>
                                <option value="services">{t('Services')}</option>
                                <option value="tech">{t('Technologie')}</option>
                                <option value="food">{t('Alimentation & Restauration')}</option>
                                <option value="health">{t('Santé & Bien-être')}</option>
                                <option value="music">{t('Musique & Arts')}</option>
                                <option value="fashion">{t('Mode & Beauté')}</option>
                                <option value="education">{t('Éducation & Formation')}</option>
                                <option value="finance">{t('Finance & Assurance')}</option>
                                <option value="realestate">{t('Immobilier')}</option>
                                <option value="travel">{t('Voyage & Tourisme')}</option>
                                <option value="sports">{t('Sport & Fitness')}</option>
                                <option value="entertainment">{t('Divertissement & Loisirs')}</option>
                                <option value="other">{t('Autre')}</option>
                            </select>
                        </div>

                        <div className="acs-form-group">
                            <label className="acs-form-label">{t('Décrivez votre activité en quelques mots')} *</label>
                            <textarea
                                className="acs-textarea"
                                value={formData.description}
                                onChange={(e) => updateField('description', e.target.value)}
                                placeholder={t('Ex: Nous aidons les entrepreneurs à développer leur présence en ligne grâce au marketing digital...')}
                                rows={4}
                            />
                        </div>

                        <div className="acs-form-group">
                            <label className="acs-form-label">{t('Site web (optionnel)')}</label>
                            <input
                                type="url"
                                className="acs-form-control"
                                value={formData.website}
                                onChange={(e) => updateField('website', e.target.value)}
                                placeholder="https://www.exemple.com"
                            />
                        </div>
                    </div>
                )}

                {/* Step 2: Objectifs & Réseaux sociaux */}
                {step === 2 && (
                    <div>
                        <h2 style={{ fontSize: '1.5rem', fontWeight: 700, marginBottom: 'var(--acs-spacing-2)' }}>
                            {t('Quels sont vos objectifs?')}
                        </h2>
                        <p style={{ color: 'var(--acs-gray-600)', marginBottom: 'var(--acs-spacing-5)' }}>
                            {t('Sélectionnez tous les objectifs qui s\'appliquent')}
                        </p>

                        <div style={{ display: 'grid', gap: 'var(--acs-spacing-3)', marginBottom: 'var(--acs-spacing-6)' }}>
                            {[
                                { value: 'brand_awareness', label: t('Notoriété de marque'), icon: '🎯' },
                                { value: 'lead_generation', label: t('Génération de leads'), icon: '📈' },
                                { value: 'sales', label: t('Augmenter les ventes'), icon: '💰' },
                                { value: 'engagement', label: t('Engagement communauté'), icon: '❤️' },
                                { value: 'traffic', label: t('Trafic website'), icon: '🚀' },
                                { value: 'seo', label: t('Référencement SEO'), icon: '🔍' },
                                { value: 'education', label: t('Éduquer l\'audience'), icon: '📚' },
                                { value: 'recruitment', label: t('Recrutement'), icon: '👥' },
                            ].map((goal) => (
                                <label
                                    key={goal.value}
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        padding: 'var(--acs-spacing-3)',
                                        background: formData.goals.includes(goal.value) ? 'rgba(99, 102, 241, 0.1)' : 'var(--acs-gray-50)',
                                        border: `2px solid ${formData.goals.includes(goal.value) ? 'var(--acs-primary)' : 'var(--acs-gray-200)'}`,
                                        borderRadius: 'var(--acs-radius-lg)',
                                        cursor: 'pointer',
                                        transition: 'var(--acs-transition)',
                                    }}
                                >
                                    <input
                                        type="checkbox"
                                        checked={formData.goals.includes(goal.value)}
                                        onChange={() => toggleArrayField('goals', goal.value)}
                                        style={{ marginRight: 'var(--acs-spacing-3)', accentColor: 'var(--acs-primary)' }}
                                    />
                                    <span style={{ fontSize: '1.5rem', marginRight: 'var(--acs-spacing-2)' }}>{goal.icon}</span>
                                    <span style={{ fontWeight: 500 }}>{goal.label}</span>
                                </label>
                            ))}
                        </div>

                        <h2 style={{ fontSize: '1.5rem', fontWeight: 700, marginBottom: 'var(--acs-spacing-2)' }}>
                            {t('Que souhaitez-vous faire?')}
                        </h2>
                        <p style={{ color: 'var(--acs-gray-600)', marginBottom: 'var(--acs-spacing-5)' }}>
                            {t('Sélectionnez les services que vous souhaitez utiliser')}
                        </p>

                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))', gap: 'var(--acs-spacing-3)', marginBottom: 'var(--acs-spacing-5)' }}>
                            {[
                                { value: 'seo_articles', label: 'Articles SEO', icon: '📝', color: '#10B981' },
                                { value: 'facebook', label: 'Facebook', icon: '📘', color: '#1877F2' },
                                { value: 'instagram', label: 'Instagram', icon: '📷', color: '#E4405F' },
                                { value: 'twitter', label: 'X (Twitter)', icon: '🐦', color: '#000000' },
                                { value: 'linkedin', label: 'LinkedIn', icon: '💼', color: '#0A66C2' },
                                { value: 'tiktok', label: 'TikTok', icon: '🎵', color: '#000000' },
                                { value: 'youtube', label: 'YouTube', icon: '📹', color: '#FF0000' },
                                { value: 'pinterest', label: 'Pinterest', icon: '📌', color: '#E60023' },
                                { value: 'snapchat', label: 'Snapchat', icon: '👻', color: '#FFFC00' },
                            ].map((platform) => (
                                <div
                                    key={platform.value}
                                    onClick={() => toggleArrayField('social_platforms', platform.value)}
                                    style={{
                                        padding: 'var(--acs-spacing-3)',
                                        border: `2px solid ${formData.social_platforms.includes(platform.value) ? platform.color : 'var(--acs-gray-200)'}`,
                                        borderRadius: 'var(--acs-radius-lg)',
                                        textAlign: 'center',
                                        cursor: 'pointer',
                                        transition: 'var(--acs-transition)',
                                        background: formData.social_platforms.includes(platform.value) ? 'rgba(99, 102, 241, 0.05)' : 'var(--acs-white)',
                                    }}
                                >
                                    <div style={{ fontSize: '2rem', marginBottom: 'var(--acs-spacing-1)' }}>{platform.icon}</div>
                                    <div style={{ fontSize: 'var(--acs-font-size-sm)', fontWeight: 600 }}>{platform.label}</div>
                                </div>
                            ))}
                        </div>

                        <div className="acs-form-group">
                            <label className="acs-form-label">{t('Fréquence de publication souhaitée')}</label>
                            <select
                                className="acs-select"
                                value={formData.posting_frequency}
                                onChange={(e) => updateField('posting_frequency', e.target.value)}
                            >
                                <option value="daily">{t('Quotidienne (7 posts/semaine)')}</option>
                                <option value="frequent">{t('Fréquente (4-5 posts/semaine)')}</option>
                                <option value="weekly">{t('Régulière (2-3 posts/semaine)')}</option>
                                <option value="occasional">{t('Occasionnelle (1 post/semaine)')}</option>
                            </select>
                        </div>
                    </div>
                )}

                {/* Navigation Buttons */}
                <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 'var(--acs-spacing-6)', paddingTop: 'var(--acs-spacing-4)', borderTop: '1px solid var(--acs-gray-200)' }}>
                    {step > 1 && (
                        <button className="acs-btn acs-btn-secondary" onClick={() => setStep(step - 1)} disabled={loading}>
                            {t('Précédent')}
                        </button>
                    )}
                    <div style={{ flex: 1 }}></div>
                    {step < totalSteps ? (
                        <button
                            className="acs-btn acs-btn-primary"
                            onClick={() => setStep(step + 1)}
                            disabled={!canProceed() || loading}
                        >
                            {t('Suivant')}
                        </button>
                    ) : (
                        <button
                            className="acs-btn acs-btn-primary"
                            onClick={handleSubmit}
                            disabled={!canProceed() || loading}
                        >
                            {loading ? t('Enregistrement...') : t('Terminer')}
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}
