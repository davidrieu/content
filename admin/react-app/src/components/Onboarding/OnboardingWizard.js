import { useState } from '@wordpress/element';
import { FiCheck } from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

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

    return (
        <div className="acs-wizard" style={{ padding: 'var(--acs-spacing-6)', background: 'var(--acs-body-bg)', minHeight: '100vh' }}>
            {/* Steps Indicator */}
            <div className="acs-wizard-steps">
                {[
                    { num: 1, label: __('Activité', 'ai-content-studio') },
                    { num: 2, label: __('Audience', 'ai-content-studio') },
                    { num: 3, label: __('Objectifs', 'ai-content-studio') },
                    { num: 4, label: __('Plateformes', 'ai-content-studio') },
                ].map((s) => (
                    <div key={s.num} className={`acs-wizard-step ${step === s.num ? 'active' : ''} ${step > s.num ? 'completed' : ''}`}>
                        <div className="acs-step-number">
                            {step > s.num ? <FiCheck /> : s.num}
                        </div>
                        <div className="acs-step-label">{s.label}</div>
                    </div>
                ))}
            </div>

            {/* Content Card */}
            <div className="acs-card">
                <div className="acs-card-header">
                    <h2 className="acs-card-title">
                        {step === 1 && __('Parlez-nous de votre activité', 'ai-content-studio')}
                        {step === 2 && __('Qui est votre audience?', 'ai-content-studio')}
                        {step === 3 && __('Quels sont vos objectifs?', 'ai-content-studio')}
                        {step === 4 && __('Sur quelles plateformes?', 'ai-content-studio')}
                    </h2>
                </div>

                {/* Step 1: Business Info */}
                {step === 1 && (
                    <div>
                        <div className="acs-form-group">
                            <label className="acs-form-label">{__('Nom de votre entreprise', 'ai-content-studio')}</label>
                            <input
                                type="text"
                                className="acs-form-control"
                                placeholder={__('Ex: Ma Super Boutique', 'ai-content-studio')}
                                value={formData.business_name}
                                onChange={(e) => updateField('business_name', e.target.value)}
                            />
                        </div>

                        <div className="acs-form-group">
                            <label className="acs-form-label">{__('Secteur d\'activité', 'ai-content-studio')}</label>
                            <select className="acs-select" value={formData.business_type} onChange={(e) => updateField('business_type', e.target.value)}>
                                <option value="">{__('Sélectionnez votre secteur', 'ai-content-studio')}</option>
                                <option value="restaurant">{__('Restaurant & Café', 'ai-content-studio')}</option>
                                <option value="retail">{__('Commerce de détail', 'ai-content-studio')}</option>
                                <option value="services">{__('Services professionnels', 'ai-content-studio')}</option>
                                <option value="ecommerce">{__('E-commerce', 'ai-content-studio')}</option>
                                <option value="health">{__('Santé & Bien-être', 'ai-content-studio')}</option>
                                <option value="education">{__('Éducation & Formation', 'ai-content-studio')}</option>
                                <option value="tech">{__('Technologie', 'ai-content-studio')}</option>
                                <option value="creative">{__('Créatif & Design', 'ai-content-studio')}</option>
                            </select>
                        </div>

                        <div className="acs-form-group">
                            <label className="acs-form-label">{__('Décrivez votre activité', 'ai-content-studio')}</label>
                            <textarea
                                className="acs-textarea"
                                placeholder={__('Ex: Nous vendons des vêtements écologiques...', 'ai-content-studio')}
                                value={formData.description}
                                onChange={(e) => updateField('description', e.target.value)}
                            />
                        </div>
                    </div>
                )}

                {/* Step 2: Audience */}
                {step === 2 && (
                    <div>
                        <div className="acs-form-group">
                            <label className="acs-form-label">{__('Tranche d\'âge', 'ai-content-studio')}</label>
                            <select className="acs-select" value={formData.target_audience.age_range} onChange={(e) => updateNestedField('target_audience', 'age_range', e.target.value)}>
                                <option value="">{__('Sélectionnez la tranche d\'âge', 'ai-content-studio')}</option>
                                <option value="18-24">{__('18-24 ans', 'ai-content-studio')}</option>
                                <option value="25-34">{__('25-34 ans', 'ai-content-studio')}</option>
                                <option value="35-44">{__('35-44 ans', 'ai-content-studio')}</option>
                                <option value="45-54">{__('45-54 ans', 'ai-content-studio')}</option>
                                <option value="55+">{__('55+ ans', 'ai-content-studio')}</option>
                                <option value="all">{__('Tous âges', 'ai-content-studio')}</option>
                            </select>
                        </div>
                    </div>
                )}

                {/* Step 3: Goals */}
                {step === 3 && (
                    <div>
                        <p className="acs-text-muted mb-3">{__('Sélectionnez un ou plusieurs objectifs', 'ai-content-studio')}</p>
                        <div style={{ display: 'grid', gap: 'var(--acs-spacing-2)' }}>
                            {[
                                { value: 'awareness', label: __('Notoriété', 'ai-content-studio') },
                                { value: 'engagement', label: __('Engagement', 'ai-content-studio') },
                                { value: 'sales', label: __('Ventes', 'ai-content-studio') },
                                { value: 'traffic', label: __('Trafic', 'ai-content-studio') },
                                { value: 'community', label: __('Communauté', 'ai-content-studio') },
                            ].map((goal) => (
                                <label key={goal.value} style={{ display: 'flex', alignItems: 'center', padding: 'var(--acs-spacing-3)', background: formData.goals.includes(goal.value) ? 'rgba(33, 150, 243, 0.1)' : 'var(--acs-gray-50)', border: `2px solid ${formData.goals.includes(goal.value) ? 'var(--acs-primary)' : 'var(--acs-gray-200)'}`, borderRadius: 'var(--acs-radius)', cursor: 'pointer' }}>
                                    <input
                                        type="checkbox"
                                        checked={formData.goals.includes(goal.value)}
                                        onChange={() => toggleArrayField('goals', goal.value)}
                                        style={{ marginRight: 'var(--acs-spacing-2)' }}
                                    />
                                    <span style={{ fontWeight: 500 }}>{goal.label}</span>
                                </label>
                            ))}
                        </div>
                    </div>
                )}

                {/* Step 4: Platforms */}
                {step === 4 && (
                    <div>
                        <p className="acs-text-muted mb-3">{__('Sélectionnez les réseaux sociaux que vous utilisez', 'ai-content-studio')}</p>
                        <div style={{ display: 'grid', gap: 'var(--acs-spacing-2)' }}>
                            {[
                                { value: 'facebook', label: 'Facebook' },
                                { value: 'instagram', label: 'Instagram' },
                                { value: 'twitter', label: 'X (Twitter)' },
                                { value: 'linkedin', label: 'LinkedIn' },
                                { value: 'tiktok', label: 'TikTok' },
                            ].map((platform) => (
                                <label key={platform.value} style={{ display: 'flex', alignItems: 'center', padding: 'var(--acs-spacing-3)', background: formData.platforms.includes(platform.value) ? 'rgba(33, 150, 243, 0.1)' : 'var(--acs-gray-50)', border: `2px solid ${formData.platforms.includes(platform.value) ? 'var(--acs-primary)' : 'var(--acs-gray-200)'}`, borderRadius: 'var(--acs-radius)', cursor: 'pointer' }}>
                                    <input
                                        type="checkbox"
                                        checked={formData.platforms.includes(platform.value)}
                                        onChange={() => toggleArrayField('platforms', platform.value)}
                                        style={{ marginRight: 'var(--acs-spacing-2)' }}
                                    />
                                    <span style={{ fontWeight: 500 }}>{platform.label}</span>
                                </label>
                            ))}
                        </div>
                    </div>
                )}

                {/* Navigation */}
                <div style={{ display: 'flex', gap: 'var(--acs-spacing-3)', justifyContent: 'space-between', marginTop: 'var(--acs-spacing-5)' }}>
                    {step > 1 && (
                        <button className="acs-btn acs-btn-outline-primary" onClick={() => setStep(step - 1)}>
                            {__('Précédent', 'ai-content-studio')}
                        </button>
                    )}
                    <div style={{ flex: 1 }} />
                    {step < 4 ? (
                        <button className="acs-btn acs-btn-primary" onClick={() => setStep(step + 1)} disabled={!canProceed()}>
                            {__('Suivant', 'ai-content-studio')}
                        </button>
                    ) : (
                        <button className="acs-btn acs-btn-success" onClick={handleSubmit} disabled={loading || !canProceed()}>
                            {loading ? __('Création...', 'ai-content-studio') : __('Terminer', 'ai-content-studio')}
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}
