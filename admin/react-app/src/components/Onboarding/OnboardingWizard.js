import { useState } from '@wordpress/element';
import { FiCheck, FiBriefcase, FiUser, FiTarget, FiTrendingUp } from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

export default function OnboardingWizard({ onComplete }) {
    const [step, setStep] = useState(1);
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
    const [keywordInput, setKeywordInput] = useState('');
    const [blogTopicInput, setBlogTopicInput] = useState('');

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

    const addKeyword = () => {
        const keyword = keywordInput.trim();
        if (keyword && !formData.primary_keywords.includes(keyword) && formData.primary_keywords.length < 5) {
            setFormData(prev => ({
                ...prev,
                primary_keywords: [...prev.primary_keywords, keyword]
            }));
            setKeywordInput('');
        }
    };

    const removeKeyword = (keyword) => {
        setFormData(prev => ({
            ...prev,
            primary_keywords: prev.primary_keywords.filter(k => k !== keyword)
        }));
    };

    const addBlogTopic = () => {
        const topic = blogTopicInput.trim();
        if (topic && !formData.blog_topics.includes(topic)) {
            setFormData(prev => ({
                ...prev,
                blog_topics: [...prev.blog_topics, topic]
            }));
            setBlogTopicInput('');
        }
    };

    const removeBlogTopic = (topic) => {
        setFormData(prev => ({
            ...prev,
            blog_topics: prev.blog_topics.filter(t => t !== topic)
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
            console.error('Profile save error:', error);
            alert(__('Erreur lors de la sauvegarde du profil', 'ai-content-studio') + ': ' + (error.message || 'Unknown error'));
        } finally {
            setLoading(false);
        }
    };

    const canProceed = () => {
        if (step === 1) return formData.user_type && formData.business_name && formData.sector && formData.description;
        if (step === 2) return formData.goals.length > 0 && formData.social_platforms.length > 0;
        if (step === 3) return formData.niche && formData.target_audience;
        return true;
    };

    const totalSteps = 3;

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

                {/* Step 1: Informations de base */}
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

                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 'var(--acs-spacing-4)', marginBottom: 'var(--acs-spacing-5)' }}>
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
                                        <p style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)', margin: 0 }}>{type.desc}</p>
                                    </div>
                                );
                            })}
                        </div>

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
                                <option value="music">{__('Musique & Arts', 'ai-content-studio')}</option>
                                <option value="fashion">{__('Mode & Beauté', 'ai-content-studio')}</option>
                                <option value="education">{__('Éducation & Formation', 'ai-content-studio')}</option>
                                <option value="finance">{__('Finance & Assurance', 'ai-content-studio')}</option>
                                <option value="realestate">{__('Immobilier', 'ai-content-studio')}</option>
                                <option value="travel">{__('Voyage & Tourisme', 'ai-content-studio')}</option>
                                <option value="sports">{__('Sport & Fitness', 'ai-content-studio')}</option>
                                <option value="entertainment">{__('Divertissement & Loisirs', 'ai-content-studio')}</option>
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

                {/* Step 2: Objectifs & Réseaux sociaux */}
                {step === 2 && (
                    <div>
                        <h2 style={{ fontSize: '1.5rem', fontWeight: 700, marginBottom: 'var(--acs-spacing-2)' }}>
                            {__('Quels sont vos objectifs?', 'ai-content-studio')}
                        </h2>
                        <p style={{ color: 'var(--acs-gray-600)', marginBottom: 'var(--acs-spacing-5)' }}>
                            {__('Sélectionnez tous les objectifs qui s\'appliquent', 'ai-content-studio')}
                        </p>

                        <div style={{ display: 'grid', gap: 'var(--acs-spacing-3)', marginBottom: 'var(--acs-spacing-6)' }}>
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
                                        style={{ marginRight: 'var(--acs-spacing-3)', accentColor: 'var(--acs-primary)' }}
                                    />
                                    <span style={{ fontSize: '1.5rem', marginRight: 'var(--acs-spacing-2)' }}>{goal.icon}</span>
                                    <span style={{ fontWeight: 500 }}>{goal.label}</span>
                                </label>
                            ))}
                        </div>

                        <h2 style={{ fontSize: '1.5rem', fontWeight: 700, marginBottom: 'var(--acs-spacing-2)' }}>
                            {__('Sur quels réseaux sociaux êtes-vous actif?', 'ai-content-studio')}
                        </h2>
                        <p style={{ color: 'var(--acs-gray-600)', marginBottom: 'var(--acs-spacing-5)' }}>
                            {__('Sélectionnez les plateformes que vous utilisez ou souhaitez utiliser', 'ai-content-studio')}
                        </p>

                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))', gap: 'var(--acs-spacing-3)', marginBottom: 'var(--acs-spacing-5)' }}>
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

                        <div className="acs-form-group">
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

                {/* Step 3: SEO & Mots-clés */}
                {step === 3 && (
                    <div>
                        <h2 style={{ fontSize: '1.5rem', fontWeight: 700, marginBottom: 'var(--acs-spacing-5)' }}>
                            {__('Stratégie SEO & Ciblage', 'ai-content-studio')}
                        </h2>

                        <div className="acs-form-group">
                            <label className="acs-form-label">{__('Votre niche / spécialité', 'ai-content-studio')} *</label>
                            <input
                                type="text"
                                className="acs-form-control"
                                value={formData.niche}
                                onChange={(e) => updateField('niche', e.target.value)}
                                placeholder={__('Ex: Coaching fitness pour femmes, Pâtisserie vegan, Marketing digital B2B...', 'ai-content-studio')}
                            />
                        </div>

                        <div className="acs-form-group">
                            <label className="acs-form-label">{__('Public cible', 'ai-content-studio')} *</label>
                            <input
                                type="text"
                                className="acs-form-control"
                                value={formData.target_audience}
                                onChange={(e) => updateField('target_audience', e.target.value)}
                                placeholder={__('Ex: Femmes 25-40 ans, Entrepreneurs, Parents...', 'ai-content-studio')}
                            />
                        </div>

                        <div className="acs-form-group">
                            <label className="acs-form-label">{__('Mots-clés principaux', 'ai-content-studio')} ({__('max 5', 'ai-content-studio')})</label>
                            <div style={{ display: 'flex', gap: 'var(--acs-spacing-2)', marginBottom: 'var(--acs-spacing-2)' }}>
                                <input
                                    type="text"
                                    className="acs-form-control"
                                    value={keywordInput}
                                    onChange={(e) => setKeywordInput(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), addKeyword())}
                                    placeholder={__('Tapez un mot-clé et appuyez sur Entrée', 'ai-content-studio')}
                                    disabled={formData.primary_keywords.length >= 5}
                                    style={{ flex: 1 }}
                                />
                                <button
                                    type="button"
                                    className="acs-btn acs-btn-secondary"
                                    onClick={addKeyword}
                                    disabled={formData.primary_keywords.length >= 5}
                                >
                                    {__('Ajouter', 'ai-content-studio')}
                                </button>
                            </div>
                            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 'var(--acs-spacing-2)' }}>
                                {formData.primary_keywords.map((keyword, index) => (
                                    <span
                                        key={index}
                                        style={{
                                            display: 'inline-flex',
                                            alignItems: 'center',
                                            gap: 'var(--acs-spacing-2)',
                                            background: 'linear-gradient(135deg, var(--acs-primary), var(--acs-secondary))',
                                            color: 'var(--acs-white)',
                                            padding: '6px 12px',
                                            borderRadius: '16px',
                                            fontSize: '13px',
                                            fontWeight: 500,
                                        }}
                                    >
                                        {keyword}
                                        <button
                                            type="button"
                                            onClick={() => removeKeyword(keyword)}
                                            style={{
                                                background: 'none',
                                                border: 'none',
                                                color: 'var(--acs-white)',
                                                fontSize: '18px',
                                                fontWeight: 'bold',
                                                cursor: 'pointer',
                                                padding: 0,
                                                width: '18px',
                                                height: '18px',
                                                display: 'flex',
                                                alignItems: 'center',
                                                justifyContent: 'center',
                                                borderRadius: '50%',
                                            }}
                                        >
                                            ×
                                        </button>
                                    </span>
                                ))}
                            </div>
                        </div>

                        <div className="acs-form-group">
                            <label style={{ display: 'flex', alignItems: 'center', fontWeight: 600, cursor: 'pointer' }}>
                                <input
                                    type="checkbox"
                                    checked={formData.has_blog}
                                    onChange={(e) => updateField('has_blog', e.target.checked)}
                                    style={{ marginRight: 'var(--acs-spacing-2)', accentColor: 'var(--acs-primary)' }}
                                />
                                <span>{__('J\'ai un blog ou je souhaite publier des articles', 'ai-content-studio')}</span>
                            </label>
                        </div>

                        {formData.has_blog && (
                            <>
                                <div className="acs-form-group">
                                    <label className="acs-form-label">{__('Sujets de blog préférés', 'ai-content-studio')}</label>
                                    <div style={{ display: 'flex', gap: 'var(--acs-spacing-2)', marginBottom: 'var(--acs-spacing-2)' }}>
                                        <input
                                            type="text"
                                            className="acs-form-control"
                                            value={blogTopicInput}
                                            onChange={(e) => setBlogTopicInput(e.target.value)}
                                            onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), addBlogTopic())}
                                            placeholder={__('Ex: Conseils nutrition, Tutoriels...', 'ai-content-studio')}
                                            style={{ flex: 1 }}
                                        />
                                        <button type="button" className="acs-btn acs-btn-secondary" onClick={addBlogTopic}>
                                            {__('Ajouter', 'ai-content-studio')}
                                        </button>
                                    </div>
                                    <div style={{ display: 'flex', flexWrap: 'wrap', gap: 'var(--acs-spacing-2)' }}>
                                        {formData.blog_topics.map((topic, index) => (
                                            <span
                                                key={index}
                                                style={{
                                                    display: 'inline-flex',
                                                    alignItems: 'center',
                                                    gap: 'var(--acs-spacing-2)',
                                                    background: 'linear-gradient(135deg, var(--acs-primary), var(--acs-secondary))',
                                                    color: 'var(--acs-white)',
                                                    padding: '6px 12px',
                                                    borderRadius: '16px',
                                                    fontSize: '13px',
                                                    fontWeight: 500,
                                                }}
                                            >
                                                {topic}
                                                <button
                                                    type="button"
                                                    onClick={() => removeBlogTopic(topic)}
                                                    style={{
                                                        background: 'none',
                                                        border: 'none',
                                                        color: 'var(--acs-white)',
                                                        fontSize: '18px',
                                                        fontWeight: 'bold',
                                                        cursor: 'pointer',
                                                        padding: 0,
                                                        width: '18px',
                                                        height: '18px',
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent: 'center',
                                                        borderRadius: '50%',
                                                    }}
                                                >
                                                    ×
                                                </button>
                                            </span>
                                        ))}
                                    </div>
                                </div>

                                <div className="acs-form-group">
                                    <label className="acs-form-label">{__('Objectifs SEO', 'ai-content-studio')}</label>
                                    <div style={{ display: 'grid', gap: 'var(--acs-spacing-2)' }}>
                                        {[
                                            { value: 'organic_traffic', label: __('Augmenter le trafic organique', 'ai-content-studio'), icon: '📊' },
                                            { value: 'ranking', label: __('Améliorer le classement Google', 'ai-content-studio'), icon: '🏆' },
                                            { value: 'long_tail', label: __('Cibler des mots-clés longue traîne', 'ai-content-studio'), icon: '🎣' },
                                            { value: 'authority', label: __('Devenir une autorité dans ma niche', 'ai-content-studio'), icon: '👑' },
                                        ].map((goal) => (
                                            <label
                                                key={goal.value}
                                                style={{
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    padding: 'var(--acs-spacing-3)',
                                                    background: formData.seo_goals.includes(goal.value) ? 'rgba(99, 102, 241, 0.1)' : 'var(--acs-gray-50)',
                                                    border: `2px solid ${formData.seo_goals.includes(goal.value) ? 'var(--acs-primary)' : 'var(--acs-gray-200)'}`,
                                                    borderRadius: 'var(--acs-radius-lg)',
                                                    cursor: 'pointer',
                                                    transition: 'var(--acs-transition)',
                                                }}
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={formData.seo_goals.includes(goal.value)}
                                                    onChange={() => toggleArrayField('seo_goals', goal.value)}
                                                    style={{ marginRight: 'var(--acs-spacing-3)', accentColor: 'var(--acs-primary)' }}
                                                />
                                                <span style={{ fontSize: '1.5rem', marginRight: 'var(--acs-spacing-2)' }}>{goal.icon}</span>
                                                <span style={{ fontWeight: 500 }}>{goal.label}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            </>
                        )}
                    </div>
                )}

                {/* Navigation Buttons */}
                <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 'var(--acs-spacing-6)', paddingTop: 'var(--acs-spacing-4)', borderTop: '1px solid var(--acs-gray-200)' }}>
                    {step > 1 && (
                        <button className="acs-btn acs-btn-secondary" onClick={() => setStep(step - 1)} disabled={loading}>
                            {__('Précédent', 'ai-content-studio')}
                        </button>
                    )}
                    <div style={{ flex: 1 }}></div>
                    {step < totalSteps ? (
                        <button
                            className="acs-btn acs-btn-primary"
                            onClick={() => setStep(step + 1)}
                            disabled={!canProceed() || loading}
                        >
                            {__('Suivant', 'ai-content-studio')}
                        </button>
                    ) : (
                        <button
                            className="acs-btn acs-btn-primary"
                            onClick={handleSubmit}
                            disabled={!canProceed() || loading}
                        >
                            {loading ? __('Enregistrement...', 'ai-content-studio') : __('Terminer', 'ai-content-studio')}
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}
