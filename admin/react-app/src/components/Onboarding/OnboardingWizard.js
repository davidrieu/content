import { useState } from '@wordpress/element';
import { motion, AnimatePresence } from 'framer-motion';
import { FiCheck, FiArrowRight, FiArrowLeft, FiTarget, FiUsers, FiGlobe, FiTrendingUp } from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

const steps = [
    { id: 1, label: 'Activité', icon: FiTarget },
    { id: 2, label: 'Audience', icon: FiUsers },
    { id: 3, label: 'Objectifs', icon: FiTrendingUp },
    { id: 4, label: 'Plateformes', icon: FiGlobe },
];

export default function OnboardingWizard({ onComplete }) {
    const [step, setStep] = useState(1);
    const [formData, setFormData] = useState({
        business_type: '',
        business_name: '',
        description: '',
        niche: '',
        target_audience: { type: 'B2C', age_range: '', interests: [] },
        goals: [],
        platforms: [],
        languages: ['fr'],
        has_blog: false,
    });
    const [loading, setLoading] = useState(false);

    const updateField = (field, value) => {
        setFormData(prev => ({ ...prev, [field]: value }));
    };

    const updateNestedField = (parent, field, value) => {
        setFormData(prev => ({
            ...prev,
            [parent]: { ...prev[parent], [field]: value }
        }));
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
            const response = await apiFetch({
                path: '/acs/v1/profile',
                method: 'POST',
                data: formData,
            });

            if (response.success) {
                onComplete(formData);
            }
        } catch (error) {
            alert('Erreur: ' + error.message);
        } finally {
            setLoading(false);
        }
    };

    const canProceed = () => {
        if (step === 1) return formData.business_name && formData.business_type && formData.description;
        if (step === 2) return formData.target_audience.age_range;
        if (step === 3) return formData.goals.length > 0;
        if (step === 4) return formData.platforms.length > 0;
        return true;
    };

    const progressPercentage = (step / steps.length) * 100;

    return (
        <div className="acs-onboarding">
            {/* Progress Indicator */}
            <div className="acs-onboarding-progress">
                <div
                    className="acs-progress-line"
                    style={{ width: `${progressPercentage}%` }}
                />
                {steps.map((s) => {
                    const Icon = s.icon;
                    const isActive = s.id === step;
                    const isCompleted = s.id < step;

                    return (
                        <motion.div
                            key={s.id}
                            className="acs-step-indicator"
                            initial={{ scale: 0.8, opacity: 0 }}
                            animate={{ scale: 1, opacity: 1 }}
                            transition={{ delay: s.id * 0.1 }}
                        >
                            <div className={`acs-step-circle ${isActive ? 'active' : ''} ${isCompleted ? 'completed' : ''}`}>
                                {isCompleted ? <FiCheck /> : <Icon />}
                            </div>
                            <span className={`acs-step-label ${isActive ? 'active' : ''}`}>
                                {s.label}
                            </span>
                        </motion.div>
                    );
                })}
            </div>

            {/* Content */}
            <div className="acs-onboarding-content">
                <AnimatePresence mode="wait">
                    {/* Step 1: Business Info */}
                    {step === 1 && (
                        <motion.div
                            key="step1"
                            initial={{ opacity: 0, x: 50 }}
                            animate={{ opacity: 1, x: 0 }}
                            exit={{ opacity: 0, x: -50 }}
                            transition={{ duration: 0.3 }}
                        >
                            <h2 className="acs-heading-lg">
                                👋 {__('Bienvenue sur AI Content Studio!', 'ai-content-studio')}
                            </h2>
                            <p className="acs-text-body">
                                {__('Commençons par configurer votre profil pour générer du contenu personnalisé.', 'ai-content-studio')}
                            </p>

                            <div className="acs-form-section">
                                <label className="acs-text-sm" style={{ fontWeight: 600, marginBottom: '0.5rem', display: 'block' }}>
                                    {__('Nom de votre entreprise', 'ai-content-studio')}
                                </label>
                                <input
                                    type="text"
                                    placeholder={__('Ex: Ma Super Boutique', 'ai-content-studio')}
                                    value={formData.business_name}
                                    onChange={(e) => updateField('business_name', e.target.value)}
                                    className="acs-input"
                                />
                            </div>

                            <div className="acs-form-section">
                                <label className="acs-text-sm" style={{ fontWeight: 600, marginBottom: '0.5rem', display: 'block' }}>
                                    {__('Secteur d\'activité', 'ai-content-studio')}
                                </label>
                                <select
                                    value={formData.business_type}
                                    onChange={(e) => updateField('business_type', e.target.value)}
                                    className="acs-select"
                                >
                                    <option value="">{__('Sélectionnez votre secteur', 'ai-content-studio')}</option>
                                    <option value="restaurant">{__('🍽️ Restaurant & Café', 'ai-content-studio')}</option>
                                    <option value="retail">{__('🛍️ Commerce de détail', 'ai-content-studio')}</option>
                                    <option value="services">{__('💼 Services professionnels', 'ai-content-studio')}</option>
                                    <option value="ecommerce">{__('🛒 E-commerce', 'ai-content-studio')}</option>
                                    <option value="health">{__('⚕️ Santé & Bien-être', 'ai-content-studio')}</option>
                                    <option value="education">{__('📚 Éducation & Formation', 'ai-content-studio')}</option>
                                    <option value="tech">{__('💻 Technologie', 'ai-content-studio')}</option>
                                    <option value="creative">{__('🎨 Créatif & Design', 'ai-content-studio')}</option>
                                </select>
                            </div>

                            <div className="acs-form-section">
                                <label className="acs-text-sm" style={{ fontWeight: 600, marginBottom: '0.5rem', display: 'block' }}>
                                    {__('Décrivez votre activité', 'ai-content-studio')}
                                </label>
                                <textarea
                                    placeholder={__('Ex: Nous vendons des vêtements écologiques pour jeunes adultes...', 'ai-content-studio')}
                                    value={formData.description}
                                    onChange={(e) => updateField('description', e.target.value)}
                                    className="acs-textarea"
                                />
                            </div>
                        </motion.div>
                    )}

                    {/* Step 2: Target Audience */}
                    {step === 2 && (
                        <motion.div
                            key="step2"
                            initial={{ opacity: 0, x: 50 }}
                            animate={{ opacity: 1, x: 0 }}
                            exit={{ opacity: 0, x: -50 }}
                            transition={{ duration: 0.3 }}
                        >
                            <h2 className="acs-heading-lg">
                                🎯 {__('Qui est votre audience?', 'ai-content-studio')}
                            </h2>
                            <p className="acs-text-body">
                                {__('Définissez votre audience cible pour un contenu personnalisé.', 'ai-content-studio')}
                            </p>

                            <div className="acs-form-section">
                                <label className="acs-text-sm" style={{ fontWeight: 600, marginBottom: '0.5rem', display: 'block' }}>
                                    {__('Type d\'audience', 'ai-content-studio')}
                                </label>
                                <div className="acs-checkbox-group" style={{ gridTemplateColumns: '1fr 1fr' }}>
                                    <label className={`acs-checkbox-card ${formData.target_audience.type === 'B2C' ? 'checked' : ''}`}>
                                        <input
                                            type="radio"
                                            name="audience_type"
                                            checked={formData.target_audience.type === 'B2C'}
                                            onChange={() => updateNestedField('target_audience', 'type', 'B2C')}
                                        />
                                        <div>
                                            <div style={{ fontWeight: 600 }}>{__('B2C', 'ai-content-studio')}</div>
                                            <div className="acs-text-sm">{__('Grand public', 'ai-content-studio')}</div>
                                        </div>
                                    </label>
                                    <label className={`acs-checkbox-card ${formData.target_audience.type === 'B2B' ? 'checked' : ''}`}>
                                        <input
                                            type="radio"
                                            name="audience_type"
                                            checked={formData.target_audience.type === 'B2B'}
                                            onChange={() => updateNestedField('target_audience', 'type', 'B2B')}
                                        />
                                        <div>
                                            <div style={{ fontWeight: 600 }}>{__('B2B', 'ai-content-studio')}</div>
                                            <div className="acs-text-sm">{__('Entreprises', 'ai-content-studio')}</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div className="acs-form-section">
                                <label className="acs-text-sm" style={{ fontWeight: 600, marginBottom: '0.5rem', display: 'block' }}>
                                    {__('Tranche d\'âge', 'ai-content-studio')}
                                </label>
                                <select
                                    value={formData.target_audience.age_range}
                                    onChange={(e) => updateNestedField('target_audience', 'age_range', e.target.value)}
                                    className="acs-select"
                                >
                                    <option value="">{__('Sélectionnez la tranche d\'âge', 'ai-content-studio')}</option>
                                    <option value="18-24">{__('18-24 ans (Gen Z)', 'ai-content-studio')}</option>
                                    <option value="25-34">{__('25-34 ans (Jeunes adultes)', 'ai-content-studio')}</option>
                                    <option value="35-44">{__('35-44 ans (Adultes)', 'ai-content-studio')}</option>
                                    <option value="45-54">{__('45-54 ans (Seniors)', 'ai-content-studio')}</option>
                                    <option value="55+">{__('55+ ans', 'ai-content-studio')}</option>
                                    <option value="all">{__('Tous âges', 'ai-content-studio')}</option>
                                </select>
                            </div>
                        </motion.div>
                    )}

                    {/* Step 3: Goals */}
                    {step === 3 && (
                        <motion.div
                            key="step3"
                            initial={{ opacity: 0, x: 50 }}
                            animate={{ opacity: 1, x: 0 }}
                            exit={{ opacity: 0, x: -50 }}
                            transition={{ duration: 0.3 }}
                        >
                            <h2 className="acs-heading-lg">
                                🚀 {__('Quels sont vos objectifs?', 'ai-content-studio')}
                            </h2>
                            <p className="acs-text-body">
                                {__('Sélectionnez un ou plusieurs objectifs (minimum 1).', 'ai-content-studio')}
                            </p>

                            <div className="acs-checkbox-group">
                                {[
                                    { value: 'awareness', label: __('Notoriété', 'ai-content-studio'), desc: __('Faire connaître votre marque', 'ai-content-studio'), icon: '📢' },
                                    { value: 'engagement', label: __('Engagement', 'ai-content-studio'), desc: __('Augmenter l\'interaction', 'ai-content-studio'), icon: '💬' },
                                    { value: 'sales', label: __('Ventes', 'ai-content-studio'), desc: __('Générer des revenus', 'ai-content-studio'), icon: '💰' },
                                    { value: 'traffic', label: __('Trafic', 'ai-content-studio'), desc: __('Attirer plus de visiteurs', 'ai-content-studio'), icon: '📈' },
                                    { value: 'community', label: __('Communauté', 'ai-content-studio'), desc: __('Créer une communauté fidèle', 'ai-content-studio'), icon: '👥' },
                                    { value: 'education', label: __('Éducation', 'ai-content-studio'), desc: __('Informer et éduquer', 'ai-content-studio'), icon: '🎓' },
                                ].map((goal) => (
                                    <motion.label
                                        key={goal.value}
                                        className={`acs-checkbox-card ${formData.goals.includes(goal.value) ? 'checked' : ''}`}
                                        whileHover={{ scale: 1.02 }}
                                        whileTap={{ scale: 0.98 }}
                                    >
                                        <input
                                            type="checkbox"
                                            checked={formData.goals.includes(goal.value)}
                                            onChange={() => toggleArrayField('goals', goal.value)}
                                        />
                                        <div>
                                            <div style={{ fontSize: '1.25rem', marginBottom: '0.25rem' }}>{goal.icon}</div>
                                            <div style={{ fontWeight: 600 }}>{goal.label}</div>
                                            <div className="acs-text-sm">{goal.desc}</div>
                                        </div>
                                    </motion.label>
                                ))}
                            </div>
                        </motion.div>
                    )}

                    {/* Step 4: Platforms */}
                    {step === 4 && (
                        <motion.div
                            key="step4"
                            initial={{ opacity: 0, x: 50 }}
                            animate={{ opacity: 1, x: 0 }}
                            exit={{ opacity: 0, x: -50 }}
                            transition={{ duration: 0.3 }}
                        >
                            <h2 className="acs-heading-lg">
                                🌐 {__('Sur quelles plateformes?', 'ai-content-studio')}
                            </h2>
                            <p className="acs-text-body">
                                {__('Sélectionnez les réseaux sociaux que vous utilisez.', 'ai-content-studio')}
                            </p>

                            <div className="acs-checkbox-group">
                                {[
                                    { value: 'facebook', label: 'Facebook', icon: '📘', color: '#1877f2' },
                                    { value: 'instagram', label: 'Instagram', icon: '📸', color: '#e4405f' },
                                    { value: 'twitter', label: 'X (Twitter)', icon: '𝕏', color: '#000000' },
                                    { value: 'linkedin', label: 'LinkedIn', icon: '💼', color: '#0a66c2' },
                                    { value: 'tiktok', label: 'TikTok', icon: '🎵', color: '#000000' },
                                    { value: 'youtube', label: 'YouTube', icon: '📹', color: '#ff0000' },
                                ].map((platform) => (
                                    <motion.label
                                        key={platform.value}
                                        className={`acs-checkbox-card ${formData.platforms.includes(platform.value) ? 'checked' : ''}`}
                                        whileHover={{ scale: 1.02 }}
                                        whileTap={{ scale: 0.98 }}
                                    >
                                        <input
                                            type="checkbox"
                                            checked={formData.platforms.includes(platform.value)}
                                            onChange={() => toggleArrayField('platforms', platform.value)}
                                        />
                                        <div>
                                            <div style={{ fontSize: '1.5rem', marginBottom: '0.25rem' }}>{platform.icon}</div>
                                            <div style={{ fontWeight: 600 }}>{platform.label}</div>
                                        </div>
                                    </motion.label>
                                ))}
                            </div>

                            <div className="acs-form-section" style={{ marginTop: '2rem' }}>
                                <label className={`acs-checkbox-card ${formData.has_blog ? 'checked' : ''}`}>
                                    <input
                                        type="checkbox"
                                        checked={formData.has_blog}
                                        onChange={(e) => updateField('has_blog', e.target.checked)}
                                    />
                                    <div>
                                        <div style={{ fontWeight: 600 }}>✍️ {__('J\'ai un blog', 'ai-content-studio')}</div>
                                        <div className="acs-text-sm">{__('Pour générer des articles de blog', 'ai-content-studio')}</div>
                                    </div>
                                </label>
                            </div>
                        </motion.div>
                    )}
                </AnimatePresence>

                {/* Navigation Buttons */}
                <div className="acs-button-group">
                    <button
                        className="acs-btn acs-btn-ghost"
                        onClick={() => setStep(step - 1)}
                        disabled={step === 1}
                        style={{ visibility: step === 1 ? 'hidden' : 'visible' }}
                    >
                        <FiArrowLeft /> {__('Précédent', 'ai-content-studio')}
                    </button>

                    {step < steps.length ? (
                        <button
                            className="acs-btn acs-btn-primary"
                            onClick={() => setStep(step + 1)}
                            disabled={!canProceed()}
                        >
                            {__('Suivant', 'ai-content-studio')} <FiArrowRight />
                        </button>
                    ) : (
                        <button
                            className="acs-btn acs-btn-success"
                            onClick={handleSubmit}
                            disabled={loading || !canProceed()}
                        >
                            {loading ? (
                                <>
                                    <div className="acs-spinner" style={{ width: '20px', height: '20px', borderWidth: '2px' }} />
                                    {__('Création...', 'ai-content-studio')}
                                </>
                            ) : (
                                <>
                                    <FiCheck /> {__('Terminer', 'ai-content-studio')}
                                </>
                            )}
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}
