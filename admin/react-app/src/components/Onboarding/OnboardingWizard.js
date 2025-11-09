import { useState } from '@wordpress/element';
import { FiCheck, FiBriefcase, FiUser, FiTarget, FiTrendingUp, FiFileText, FiHash } from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

export default function OnboardingWizard({ onComplete }) {
    const [step, setStep] = useState(1);
    const [formData, setFormData] = useState({
        // Step 1: Type d'utilisateur
        user_type: '', // 'business', 'creator', 'freelance', 'agency'

        // Step 2: Informations de base
        business_name: '',
        sector: '', // Secteur d'activité
        description: '',
        website: '',

        // Step 3: Objectifs
        goals: [],

        // Step 4: Réseaux sociaux
        social_platforms: [],
        posting_frequency: 'weekly',

        // Step 5: SEO & Blog
        has_blog: false,
        blog_topics: [],
        target_keywords: [],
        seo_goals: [],

        // Step 6: Mots-clés et niche
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

    const addKeyword = (field, keyword) => {
        if (keyword && !formData[field].includes(keyword)) {
            setFormData(prev => ({
                ...prev,
                [field]: [...prev[field], keyword]
            }));
        }
    };

    const removeKeyword = (field, keyword) => {
        setFormData(prev => ({
            ...prev,
            [field]: prev[field].filter(k => k !== keyword)
        }));
    };

    const handleSubmit = async () => {
        setLoading(true);
        try {
            const response = await apiFetch({
                path: '/acs/v1/profile',
                method: 'POST',
                data: formData,
            });

            if (response.success) {
                onComplete(formData);
            }
        } catch (error) {
            alert(__('Erreur lors de la sauvegarde du profil', 'ai-content-studio') + ': ' + error.message);
        } finally {
            setLoading(false);
        }
    };

    const canProceed = () => {
        if (step === 1) return formData.user_type;
        if (step === 2) return formData.business_name && formData.sector && formData.description;
        if (step === 3) return formData.goals.length > 0;
        if (step === 4) return formData.social_platforms.length > 0;
        if (step === 5) return true; // SEO optionnel
        if (step === 6) return formData.niche && formData.target_audience;
        return true;
    };

    const totalSteps = 6;

    return (
        <div style={{ padding: 'var(--acs-spacing-6)', background: 'var(--acs-body-bg)', minHeight: '100vh' }}>
            {/* Progress Bar */}
            <div style={{ maxWidth: '800px', margin: '0 auto var(--acs-spacing-6)' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 'var(--acs-spacing-2)' }}>
                    <span style={{ fontSize: 'var(--acs-font-size-sm)', fontWeight: 600, color: 'var(--acs-primary)' }}>
                        {__('Configuration de votre profil', 'ai-content-studio')}
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

                {/* Step 1: Type d'utilisateur */}
                {step === 1 && (
                    <div>
                        <div style={{ textAlign: 'center', marginBottom: 'var(--acs-spacing-6)' }}>
                            <h2 style={{ fontSize: '1.75rem', fontWeight: 700, marginBottom: 'var(--acs-spacing-2)' }}>
                                {__('Bienvenue sur AI Content Studio', 'ai-content-studio')} 👋
                            </h2>
                            <p style={{ color: 'var(--acs-gray-600)' }}>
                                {__('Pour personnaliser votre expérience, dites-nous qui vous êtes', 'ai-content-studio')}
                            </p>
                        </div>

                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 'var(--acs-spacing-4)' }}>
                            {[
                                { value: 'business', icon: FiBriefcase, label: __('Entreprise', 'ai-content-studio'), desc: __('PME, startup, e-commerce', 'ai-content-studio') },
                                { value: 'creator', icon: FiUser, label: __('Créateur', 'ai-content-studio'), desc: __('Artiste, influenceur, coach', 'ai-content-studio') },
                                { value: 'freelance', icon: FiTrendingUp, label: __('Freelance', 'ai-content-studio'), desc: __('Consultant, indépendant', 'ai-content-studio') },
                                { value: 'agency', icon: FiBriefcase, label: __('Agence', 'ai-content-studio'), desc: __('Agence marketing, com', 'ai-content-studio') },
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
                                        <p style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)' }}>{type.desc}</p>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* Step 2: Informations de base */}
                {step === 2 && (
                    <div>
                        <h2 style={{ fontSize: '1.5rem', fontWeight: 700, marginBottom: 'var(--acs-spacing-5)' }}>
                            {__('Parlez-nous de votre activité', 'ai-content-studio')}
                        </h2>

                        <div className="acs-form-group">
                            <label className="acs-form-label">{__('Nom de votre entreprise/marque', 'ai-content-studio')} *</label>
                            <input
                                type="text"
                                className="acs-form-control"
                                value={formData.business_name}
                                onChange={(e) => updateField('business_name', e.target.value)}
                                placeholder={__('Ex: Mon Entreprise', 'ai-content-studio')}
                            />
                        </div>

                        <div className="acs-form-group">
                            <label className="acs-form-label">{__('Secteur d\'activité', 'ai-content-studio')} *</label>
                            <select
                                className="acs-select"
                                value={formData.sector}
                                onChange={(e) => updateField('sector', e.target.value)}
                            >
                                <option value="">{__('Sélectionnez un secteur', 'ai-content-studio')}</option>
                                <option value="ecommerce">{__('E-commerce', 'ai-content-studio')}</option>
                                <option value="services">{__('Services', 'ai-content-studio')}</option>
                                <option value="tech">{__('Technologie', 'ai-content-studio')}</option>
                                <option value="food">{__('Alimentation & Restauration', 'ai-content-studio')}</option>
                                <option value="health">{__('Santé & Bien-être', 'ai-content-studio')}</option>
                                <option value="education">{__('Éducation & Formation', 'ai-content-studio')}</option>
                                <option value="entertainment">{__('Divertissement & Loisirs', 'ai-content-studio')}</option>
                                <option value="fashion">{__('Mode & Beauté', 'ai-content-studio')}</option>
                                <option value="realestate">{__('Immobilier', 'ai-content-studio')}</option>
                                <option value="finance">{__('Finance & Assurance', 'ai-content-studio')}</option>
                                <option value="travel">{__('Voyage & Tourisme', 'ai-content-studio')}</option>
                                <option value="music">{__('Musique & Arts', 'ai-content-studio')}</option>
                                <option value="sports">{__('Sport & Fitness', 'ai-content-studio')}</option>
                                <option value="other">{__('Autre', 'ai-content-studio')}</option>
                            </select>
                        </div>

                        <div className="acs-form-group">
                            <label className="acs-form-label">{__('Décrivez votre activité en quelques mots', 'ai-content-studio')} *</label>
                            <textarea
                                className="acs-textarea"
                                value={formData.description}
                                onChange={(e) => updateField('description', e.target.value)}
                                placeholder={__('Ex: Nous aidons les entrepreneurs à développer leur présence en ligne grâce au marketing digital...', 'ai-content-studio')}
                                rows={4}
                            />
                        </div>

                        <div className="acs-form-group">
                            <label className="acs-form-label">{__('Site web (optionnel)', 'ai-content-studio')}</label>
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

                {/* Step 3: Objectifs */}
                {step === 3 && (
                    <div>
                        <h2 style={{ fontSize: '1.5rem', fontWeight: 700, marginBottom: 'var(--acs-spacing-2)' }}>
                            {__('Quels sont vos objectifs?', 'ai-content-studio')}
                        </h2>
                        <p style={{ color: 'var(--acs-gray-600)', marginBottom: 'var(--acs-spacing-5)' }}>
                            {__('Sélectionnez tous les objectifs qui s\'appliquent', 'ai-content-studio')}
                        </p>

                        <div style={{ display: 'grid', gap: 'var(--acs-spacing-3)' }}>
                            {[
                                { value: 'brand_awareness', label: __('Notoriété de marque', 'ai-content-studio'), icon: '🎯' },
                                { value: 'lead_generation', label: __('Génération de leads', 'ai-content-studio'), icon: '📈' },
                                { value: 'sales', label: __('Augmenter les ventes', 'ai-content-studio'), icon: '💰' },
                                { value: 'engagement', label: __('Engagement communauté', 'ai-content-studio'), icon: '❤️' },
                                { value: 'traffic', label: __('Trafic website', 'ai-content-studio'), icon: '🚀' },
                                { value: 'seo', label: __('Référencement SEO', 'ai-content-studio'), icon: '🔍' },
                                { value: 'education', label: __('Éduquer l\'audience', 'ai-content-studio'), icon: '📚' },
                                { value: 'recruitment', label: __('Recrutement', 'ai-content-studio'), icon: '👥' },
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
                                        style={{ marginRight: 'var(--acs-spacing-3)' }}
                                    />
                                    <span style={{ fontSize: '1.5rem', marginRight: 'var(--acs-spacing-2)' }}>{goal.icon}</span>
                                    <span style={{ fontWeight: 500 }}>{goal.label}</span>
                                </label>
                            ))}
                        </div>
                    </div>
                )}

                {/* Step 4: Réseaux sociaux */}
                {step === 4 && (
                    <div>
                        <h2 style={{ fontSize: '1.5rem', fontWeight: 700, marginBottom: 'var(--acs-spacing-2)' }}>
                            {__('Sur quels réseaux sociaux êtes-vous actif?', 'ai-content-studio')}
                        </h2>
                        <p style={{ color: 'var(--acs-gray-600)', marginBottom: 'var(--acs-spacing-5)' }}>
                            {__('Sélectionnez les plateformes que vous utilisez ou souhaitez utiliser', 'ai-content-studio')}
                        </p>

                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))', gap: 'var(--acs-spacing-3)' }}>
                            {[
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

                        <div className="acs-form-group" style={{ marginTop: 'var(--acs-spacing-5)' }}>
                            <label className="acs-form-label">{__('Fréquence de publication souhaitée', 'ai-content-studio')}</label>
                            <select
                                className="acs-select"
                                value={formData.posting_frequency}
                                onChange={(e) => updateField('posting_frequency', e.target.value)}
                            >
                                <option value="daily">{__('Quotidienne (7 posts/semaine)', 'ai-content-studio')}</option>
                                <option value="frequent">{__('Fréquente (4-5 posts/semaine)', 'ai-content-studio')}</option>
                                <option value="weekly">{__('Régulière (2-3 posts/semaine)', 'ai-content-studio')}</option>
                                <option value="occasional">{__('Occasionnelle (1 post/semaine)', 'ai-content-studio')}</option>
                            </select>
                        </div>
                    </div>
                )}

                {/* Step 5: SEO & Blog */}
                {step === 5 && (
                    <div>
                        <h2 style={{ fontSize: '1.5rem', fontWeight: 700, marginBottom: 'var(--acs-spacing-2)' }}>
                            {__('Stratégie SEO & Blog', 'ai-content-studio')}
                        </h2>
                        <p style={{ color: 'var(--acs-gray-600)', marginBottom: 'var(--acs-spacing-5)' }}>
                            {__('Optimisez votre référencement naturel avec des articles de blog', 'ai-content-studio')}
                        </p>

                        <div className="acs-form-group">
                            <label style={{ display: 'flex', alignItems: 'center', padding: 'var(--acs-spacing-4)', background: 'var(--acs-gray-50)', borderRadius: 'var(--acs-radius-lg)', cursor: 'pointer' }}>
                                <input
                                    type="checkbox"
                                    checked={formData.has_blog}
                                    onChange={(e) => updateField('has_blog', e.target.checked)}
                                    style={{ marginRight: 'var(--acs-spacing-3)' }}
                                />
                                <div>
                                    <div style={{ fontWeight: 600, marginBottom: 'var(--acs-spacing-1)' }}>
                                        {__('Je souhaite créer des articles de blog pour le SEO', 'ai-content-studio')}
                                    </div>
                                    <div style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)' }}>
                                        {__('Générez des articles optimisés pour les moteurs de recherche', 'ai-content-studio')}
                                    </div>
                                </div>
                            </label>
                        </div>

                        {formData.has_blog && (
                            <>
                                <div className="acs-form-group">
                                    <label className="acs-form-label">{__('Objectifs SEO', 'ai-content-studio')}</label>
                                    <div style={{ display: 'grid', gap: 'var(--acs-spacing-2)' }}>
                                        {[
                                            { value: 'organic_traffic', label: __('Augmenter le trafic organique', 'ai-content-studio') },
                                            { value: 'ranking', label: __('Améliorer le classement Google', 'ai-content-studio') },
                                            { value: 'long_tail', label: __('Cibler des mots-clés longue traîne', 'ai-content-studio') },
                                            { value: 'authority', label: __('Devenir une référence dans mon domaine', 'ai-content-studio') },
                                        ].map((goal) => (
                                            <label
                                                key={goal.value}
                                                style={{
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    padding: 'var(--acs-spacing-2)',
                                                    cursor: 'pointer',
                                                }}
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={formData.seo_goals.includes(goal.value)}
                                                    onChange={() => toggleArrayField('seo_goals', goal.value)}
                                                    style={{ marginRight: 'var(--acs-spacing-2)' }}
                                                />
                                                <span>{goal.label}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>

                                <div className="acs-form-group">
                                    <label className="acs-form-label">{__('Thématiques d\'articles de blog', 'ai-content-studio')}</label>
                                    <textarea
                                        className="acs-textarea"
                                        value={formData.blog_topics.join(', ')}
                                        onChange={(e) => updateField('blog_topics', e.target.value.split(',').map(t => t.trim()).filter(Boolean))}
                                        placeholder={__('Ex: Marketing digital, Réseaux sociaux, Stratégie de contenu...', 'ai-content-studio')}
                                        rows={3}
                                    />
                                    <p style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)', marginTop: 'var(--acs-spacing-1)' }}>
                                        {__('Séparez les thématiques par des virgules', 'ai-content-studio')}
                                    </p>
                                </div>
                            </>
                        )}
                    </div>
                )}

                {/* Step 6: Mots-clés et niche */}
                {step === 6 && (
                    <div>
                        <h2 style={{ fontSize: '1.5rem', fontWeight: 700, marginBottom: 'var(--acs-spacing-2)' }}>
                            {__('Mots-clés & Ciblage', 'ai-content-studio')}
                        </h2>
                        <p style={{ color: 'var(--acs-gray-600)', marginBottom: 'var(--acs-spacing-5)' }}>
                            {__('Dernière étape pour une stratégie ultra-ciblée', 'ai-content-studio')}
                        </p>

                        <div className="acs-form-group">
                            <label className="acs-form-label">{__('Votre niche/marché de prédilection', 'ai-content-studio')} *</label>
                            <input
                                type="text"
                                className="acs-form-control"
                                value={formData.niche}
                                onChange={(e) => updateField('niche', e.target.value)}
                                placeholder={__('Ex: Marketing automation pour PME, Coaching sportif pour femmes...', 'ai-content-studio')}
                            />
                        </div>

                        <div className="acs-form-group">
                            <label className="acs-form-label">{__('Audience cible', 'ai-content-studio')} *</label>
                            <textarea
                                className="acs-textarea"
                                value={formData.target_audience}
                                onChange={(e) => updateField('target_audience', e.target.value)}
                                placeholder={__('Ex: Entrepreneurs entre 25-45 ans, passionnés d\'innovation, cherchant à digitaliser leur business...', 'ai-content-studio')}
                                rows={3}
                            />
                        </div>

                        <div className="acs-form-group">
                            <label className="acs-form-label">
                                {__('Mots-clés principaux (5 maximum)', 'ai-content-studio')}
                                <span style={{ color: 'var(--acs-gray-500)', fontWeight: 400, marginLeft: 'var(--acs-spacing-1)' }}>
                                    - {formData.primary_keywords.length}/5
                                </span>
                            </label>

                            {formData.primary_keywords.length > 0 && (
                                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 'var(--acs-spacing-2)', marginBottom: 'var(--acs-spacing-3)' }}>
                                    {formData.primary_keywords.map((keyword, index) => (
                                        <div
                                            key={index}
                                            style={{
                                                display: 'inline-flex',
                                                alignItems: 'center',
                                                padding: '6px 12px',
                                                background: 'linear-gradient(135deg, var(--acs-primary), var(--acs-secondary))',
                                                color: 'var(--acs-white)',
                                                borderRadius: '20px',
                                                fontSize: 'var(--acs-font-size-sm)',
                                                fontWeight: 500,
                                            }}
                                        >
                                            {keyword}
                                            <button
                                                onClick={() => removeKeyword('primary_keywords', keyword)}
                                                style={{
                                                    marginLeft: 'var(--acs-spacing-2)',
                                                    background: 'rgba(255,255,255,0.2)',
                                                    border: 'none',
                                                    borderRadius: '50%',
                                                    width: '18px',
                                                    height: '18px',
                                                    cursor: 'pointer',
                                                    color: 'var(--acs-white)',
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    justifyContent: 'center',
                                                }}
                                            >
                                                ×
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {formData.primary_keywords.length < 5 && (
                                <div style={{ display: 'flex', gap: 'var(--acs-spacing-2)' }}>
                                    <input
                                        type="text"
                                        className="acs-form-control"
                                        id="keyword-input"
                                        placeholder={__('Ex: marketing digital, SEO, réseaux sociaux...', 'ai-content-studio')}
                                        onKeyPress={(e) => {
                                            if (e.key === 'Enter' && formData.primary_keywords.length < 5) {
                                                e.preventDefault();
                                                const input = e.target;
                                                addKeyword('primary_keywords', input.value);
                                                input.value = '';
                                            }
                                        }}
                                    />
                                    <button
                                        type="button"
                                        className="acs-btn acs-btn-primary"
                                        onClick={() => {
                                            const input = document.getElementById('keyword-input');
                                            if (input.value && formData.primary_keywords.length < 5) {
                                                addKeyword('primary_keywords', input.value);
                                                input.value = '';
                                            }
                                        }}
                                    >
                                        {__('Ajouter', 'ai-content-studio')}
                                    </button>
                                </div>
                            )}
                            <p style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)', marginTop: 'var(--acs-spacing-1)' }}>
                                {__('Ces mots-clés seront utilisés pour optimiser vos contenus SEO', 'ai-content-studio')}
                            </p>
                        </div>
                    </div>
                )}

                {/* Navigation Buttons */}
                <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 'var(--acs-spacing-6)', paddingTop: 'var(--acs-spacing-4)', borderTop: '1px solid var(--acs-gray-200)' }}>
                    {step > 1 && (
                        <button
                            className="acs-btn acs-btn-outline-primary"
                            onClick={() => setStep(step - 1)}
                            disabled={loading}
                        >
                            {__('Précédent', 'ai-content-studio')}
                        </button>
                    )}

                    <div style={{ marginLeft: 'auto' }}>
                        {step < totalSteps ? (
                            <button
                                className="acs-btn acs-btn-primary acs-btn-lg"
                                onClick={() => setStep(step + 1)}
                                disabled={!canProceed() || loading}
                            >
                                {__('Suivant', 'ai-content-studio')} →
                            </button>
                        ) : (
                            <button
                                className="acs-btn acs-btn-primary acs-btn-lg"
                                onClick={handleSubmit}
                                disabled={!canProceed() || loading}
                            >
                                {loading ? __('Enregistrement...', 'ai-content-studio') : __('Terminer', 'ai-content-studio')} ✨
                            </button>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
