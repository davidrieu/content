import { useState } from '@wordpress/element';
import { FiCheck, FiBriefcase, FiUser, FiTarget, FiTrendingUp, FiFileText, FiHash } from 'react-icons/fi';
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
        <div className="acs-onboarding-wizard">
            {/* Progress Bar */}
            <div className="acs-wizard-progress">
                <div className="acs-progress-bar">
                    <div className="acs-progress-fill" style={{ width: `${(step / totalSteps) * 100}%` }}></div>
                </div>
                <div className="acs-progress-steps">
                    {[...Array(totalSteps)].map((_, i) => (
                        <div key={i} className={`acs-step-dot ${i + 1 <= step ? 'active' : ''} ${i + 1 < step ? 'completed' : ''}`}>
                            {i + 1 < step ? <FiCheck size={14} /> : i + 1}
                        </div>
                    ))}
                </div>
            </div>

            {/* Step Content */}
            <div className="acs-wizard-content">
                {/* STEP 1: Informations de base */}
                {step === 1 && (
                    <div className="acs-wizard-step">
                        <div className="acs-step-header">
                            <FiBriefcase size={32} />
                            <h2>{__('Informations de base', 'ai-content-studio')}</h2>
                            <p>{__('Parlez-nous de vous ou de votre entreprise', 'ai-content-studio')}</p>
                        </div>

                        {/* Type d'utilisateur */}
                        <div className="acs-form-group">
                            <label>{__('Vous êtes', 'ai-content-studio')} *</label>
                            <div className="acs-user-type-grid">
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
                                            className={`acs-type-card ${formData.user_type === type.value ? 'selected' : ''}`}
                                            onClick={() => updateField('user_type', type.value)}
                                        >
                                            <Icon size={28} />
                                            <h3>{type.label}</h3>
                                            <p>{type.desc}</p>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Nom */}
                        <div className="acs-form-group">
                            <label>{__('Nom de l\'entreprise / Votre nom', 'ai-content-studio')} *</label>
                            <input
                                type="text"
                                className="acs-input"
                                value={formData.business_name}
                                onChange={(e) => updateField('business_name', e.target.value)}
                                placeholder={formData.user_type === 'creator' ? __('Ex: Marie Dupont', 'ai-content-studio') : __('Ex: Mon Entreprise', 'ai-content-studio')}
                            />
                        </div>

                        {/* Secteur */}
                        <div className="acs-form-group">
                            <label>{__('Secteur d\'activité', 'ai-content-studio')} *</label>
                            <select className="acs-select" value={formData.sector} onChange={(e) => updateField('sector', e.target.value)}>
                                <option value="">{__('Sélectionnez votre secteur', 'ai-content-studio')}</option>
                                <option value="ecommerce">{__('E-commerce', 'ai-content-studio')}</option>
                                <option value="services">{__('Services', 'ai-content-studio')}</option>
                                <option value="tech">{__('Technologie', 'ai-content-studio')}</option>
                                <option value="food">{__('Alimentation & Restauration', 'ai-content-studio')}</option>
                                <option value="health">{__('Santé & Bien-être', 'ai-content-studio')}</option>
                                <option value="music">{__('Musique & Arts', 'ai-content-studio')}</option>
                                <option value="fashion">{__('Mode & Beauté', 'ai-content-studio')}</option>
                                <option value="education">{__('Éducation & Formation', 'ai-content-studio')}</option>
                                <option value="finance">{__('Finance & Assurance', 'ai-content-studio')}</option>
                                <option value="real-estate">{__('Immobilier', 'ai-content-studio')}</option>
                                <option value="travel">{__('Voyage & Tourisme', 'ai-content-studio')}</option>
                                <option value="sports">{__('Sport & Fitness', 'ai-content-studio')}</option>
                                <option value="entertainment">{__('Divertissement', 'ai-content-studio')}</option>
                                <option value="other">{__('Autre', 'ai-content-studio')}</option>
                            </select>
                        </div>

                        {/* Description */}
                        <div className="acs-form-group">
                            <label>{__('Description de votre activité', 'ai-content-studio')} *</label>
                            <textarea
                                className="acs-textarea"
                                rows="4"
                                value={formData.description}
                                onChange={(e) => updateField('description', e.target.value)}
                                placeholder={__('Décrivez brièvement votre activité, vos services ou produits...', 'ai-content-studio')}
                            ></textarea>
                        </div>

                        {/* Site web */}
                        <div className="acs-form-group">
                            <label>{__('Site web', 'ai-content-studio')} ({__('optionnel', 'ai-content-studio')})</label>
                            <input
                                type="url"
                                className="acs-input"
                                value={formData.website}
                                onChange={(e) => updateField('website', e.target.value)}
                                placeholder="https://www.example.com"
                            />
                        </div>
                    </div>
                )}

                {/* STEP 2: Objectifs & Réseaux sociaux */}
                {step === 2 && (
                    <div className="acs-wizard-step">
                        <div className="acs-step-header">
                            <FiTarget size={32} />
                            <h2>{__('Objectifs & Réseaux sociaux', 'ai-content-studio')}</h2>
                            <p>{__('Définissez vos objectifs et les plateformes que vous utilisez', 'ai-content-studio')}</p>
                        </div>

                        {/* Objectifs */}
                        <div className="acs-form-group">
                            <label>{__('Vos objectifs principaux', 'ai-content-studio')} * ({__('Sélectionnez au moins un', 'ai-content-studio')})</label>
                            <div className="acs-checkbox-grid">
                                {[
                                    { value: 'brand_awareness', label: __('Notoriété de marque', 'ai-content-studio') },
                                    { value: 'lead_generation', label: __('Génération de leads', 'ai-content-studio') },
                                    { value: 'sales', label: __('Ventes directes', 'ai-content-studio') },
                                    { value: 'engagement', label: __('Engagement communautaire', 'ai-content-studio') },
                                    { value: 'traffic', label: __('Trafic web', 'ai-content-studio') },
                                    { value: 'seo', label: __('Référencement SEO', 'ai-content-studio') },
                                    { value: 'education', label: __('Éducation / Information', 'ai-content-studio') },
                                    { value: 'recruitment', label: __('Recrutement', 'ai-content-studio') },
                                ].map((goal) => (
                                    <label key={goal.value} className="acs-checkbox-label">
                                        <input
                                            type="checkbox"
                                            checked={formData.goals.includes(goal.value)}
                                            onChange={() => toggleArrayField('goals', goal.value)}
                                        />
                                        <span>{goal.label}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        {/* Réseaux sociaux */}
                        <div className="acs-form-group">
                            <label>{__('Plateformes sociales', 'ai-content-studio')} * ({__('Sélectionnez au moins une', 'ai-content-studio')})</label>
                            <div className="acs-checkbox-grid">
                                {[
                                    { value: 'facebook', label: 'Facebook' },
                                    { value: 'instagram', label: 'Instagram' },
                                    { value: 'twitter', label: 'Twitter / X' },
                                    { value: 'linkedin', label: 'LinkedIn' },
                                    { value: 'tiktok', label: 'TikTok' },
                                    { value: 'youtube', label: 'YouTube' },
                                    { value: 'pinterest', label: 'Pinterest' },
                                    { value: 'snapchat', label: 'Snapchat' },
                                ].map((platform) => (
                                    <label key={platform.value} className="acs-checkbox-label">
                                        <input
                                            type="checkbox"
                                            checked={formData.social_platforms.includes(platform.value)}
                                            onChange={() => toggleArrayField('social_platforms', platform.value)}
                                        />
                                        <span>{platform.label}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        {/* Fréquence de publication */}
                        <div className="acs-form-group">
                            <label>{__('Fréquence de publication souhaitée', 'ai-content-studio')}</label>
                            <div className="acs-radio-group">
                                {[
                                    { value: 'daily', label: __('Quotidien (7x/semaine)', 'ai-content-studio') },
                                    { value: 'frequent', label: __('Fréquent (3-5x/semaine)', 'ai-content-studio') },
                                    { value: 'weekly', label: __('Hebdomadaire (1-2x/semaine)', 'ai-content-studio') },
                                    { value: 'occasional', label: __('Occasionnel (quelques fois/mois)', 'ai-content-studio') },
                                ].map((freq) => (
                                    <label key={freq.value} className="acs-radio-label">
                                        <input
                                            type="radio"
                                            name="posting_frequency"
                                            checked={formData.posting_frequency === freq.value}
                                            onChange={() => updateField('posting_frequency', freq.value)}
                                        />
                                        <span>{freq.label}</span>
                                    </label>
                                ))}
                            </div>
                        </div>
                    </div>
                )}

                {/* STEP 3: SEO & Mots-clés */}
                {step === 3 && (
                    <div className="acs-wizard-step">
                        <div className="acs-step-header">
                            <FiHash size={32} />
                            <h2>{__('SEO & Mots-clés', 'ai-content-studio')}</h2>
                            <p>{__('Optimisez votre présence en ligne avec le bon ciblage', 'ai-content-studio')}</p>
                        </div>

                        {/* Niche */}
                        <div className="acs-form-group">
                            <label>{__('Votre niche / spécialité', 'ai-content-studio')} *</label>
                            <input
                                type="text"
                                className="acs-input"
                                value={formData.niche}
                                onChange={(e) => updateField('niche', e.target.value)}
                                placeholder={__('Ex: Coaching fitness pour femmes, Pâtisserie vegan, Marketing digital B2B...', 'ai-content-studio')}
                            />
                        </div>

                        {/* Public cible */}
                        <div className="acs-form-group">
                            <label>{__('Public cible', 'ai-content-studio')} *</label>
                            <input
                                type="text"
                                className="acs-input"
                                value={formData.target_audience}
                                onChange={(e) => updateField('target_audience', e.target.value)}
                                placeholder={__('Ex: Femmes 25-40 ans, Entrepreneurs, Parents...', 'ai-content-studio')}
                            />
                        </div>

                        {/* Mots-clés principaux */}
                        <div className="acs-form-group">
                            <label>{__('Mots-clés principaux', 'ai-content-studio')} ({__('max 5', 'ai-content-studio')})</label>
                            <div className="acs-keyword-input">
                                <input
                                    type="text"
                                    className="acs-input"
                                    value={keywordInput}
                                    onChange={(e) => setKeywordInput(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), addKeyword())}
                                    placeholder={__('Tapez un mot-clé et appuyez sur Entrée', 'ai-content-studio')}
                                    disabled={formData.primary_keywords.length >= 5}
                                />
                                <button
                                    type="button"
                                    className="acs-btn-secondary"
                                    onClick={addKeyword}
                                    disabled={formData.primary_keywords.length >= 5}
                                >
                                    {__('Ajouter', 'ai-content-studio')}
                                </button>
                            </div>
                            <div className="acs-keyword-pills">
                                {formData.primary_keywords.map((keyword, index) => (
                                    <div key={index} className="acs-keyword-pill">
                                        {keyword}
                                        <button type="button" onClick={() => removeKeyword(keyword)}>×</button>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Blog */}
                        <div className="acs-form-group">
                            <label className="acs-checkbox-label">
                                <input
                                    type="checkbox"
                                    checked={formData.has_blog}
                                    onChange={(e) => updateField('has_blog', e.target.checked)}
                                />
                                <span>{__('J\'ai un blog ou je souhaite publier des articles', 'ai-content-studio')}</span>
                            </label>
                        </div>

                        {/* Blog topics (si blog activé) */}
                        {formData.has_blog && (
                            <>
                                <div className="acs-form-group">
                                    <label>{__('Sujets de blog préférés', 'ai-content-studio')}</label>
                                    <div className="acs-keyword-input">
                                        <input
                                            type="text"
                                            className="acs-input"
                                            value={blogTopicInput}
                                            onChange={(e) => setBlogTopicInput(e.target.value)}
                                            onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), addBlogTopic())}
                                            placeholder={__('Ex: Conseils nutrition, Tutoriels...', 'ai-content-studio')}
                                        />
                                        <button type="button" className="acs-btn-secondary" onClick={addBlogTopic}>
                                            {__('Ajouter', 'ai-content-studio')}
                                        </button>
                                    </div>
                                    <div className="acs-keyword-pills">
                                        {formData.blog_topics.map((topic, index) => (
                                            <div key={index} className="acs-keyword-pill">
                                                {topic}
                                                <button type="button" onClick={() => removeBlogTopic(topic)}>×</button>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {/* Objectifs SEO */}
                                <div className="acs-form-group">
                                    <label>{__('Objectifs SEO', 'ai-content-studio')}</label>
                                    <div className="acs-checkbox-grid">
                                        {[
                                            { value: 'organic_traffic', label: __('Augmenter le trafic organique', 'ai-content-studio') },
                                            { value: 'ranking', label: __('Améliorer le classement Google', 'ai-content-studio') },
                                            { value: 'long_tail', label: __('Cibler des mots-clés longue traîne', 'ai-content-studio') },
                                            { value: 'authority', label: __('Devenir une autorité dans ma niche', 'ai-content-studio') },
                                        ].map((goal) => (
                                            <label key={goal.value} className="acs-checkbox-label">
                                                <input
                                                    type="checkbox"
                                                    checked={formData.seo_goals.includes(goal.value)}
                                                    onChange={() => toggleArrayField('seo_goals', goal.value)}
                                                />
                                                <span>{goal.label}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            </>
                        )}
                    </div>
                )}
            </div>

            {/* Navigation */}
            <div className="acs-wizard-footer">
                {step > 1 && (
                    <button className="acs-btn-secondary" onClick={() => setStep(step - 1)} disabled={loading}>
                        {__('Précédent', 'ai-content-studio')}
                    </button>
                )}
                <div style={{ flex: 1 }}></div>
                {step < totalSteps ? (
                    <button
                        className="acs-btn-primary"
                        onClick={() => setStep(step + 1)}
                        disabled={!canProceed() || loading}
                    >
                        {__('Suivant', 'ai-content-studio')}
                    </button>
                ) : (
                    <button
                        className="acs-btn-primary"
                        onClick={handleSubmit}
                        disabled={!canProceed() || loading}
                    >
                        {loading ? __('Enregistrement...', 'ai-content-studio') : __('Terminer', 'ai-content-studio')}
                    </button>
                )}
            </div>
        </div>
    );
}
