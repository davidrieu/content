import { useState } from '@wordpress/element';
import { Button, Card, CardBody } from '@wordpress/components';
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

    return (
        <div className="acs-onboarding">
            <Card>
                <CardBody>
                    <h1>{__('Bienvenue sur AI Content Studio!', 'ai-content-studio')}</h1>

                    {step === 1 && (
                        <div>
                            <h2>{__('Parlez-nous de votre activité', 'ai-content-studio')}</h2>
                            <input
                                type="text"
                                placeholder={__('Nom de votre business', 'ai-content-studio')}
                                value={formData.business_name}
                                onChange={(e) => updateField('business_name', e.target.value)}
                                className="acs-input"
                            />
                            <select
                                value={formData.business_type}
                                onChange={(e) => updateField('business_type', e.target.value)}
                                className="acs-select"
                            >
                                <option value="">{__('Sélectionnez votre secteur', 'ai-content-studio')}</option>
                                <option value="restaurant">{__('Restaurant', 'ai-content-studio')}</option>
                                <option value="retail">{__('Commerce', 'ai-content-studio')}</option>
                                <option value="services">{__('Services', 'ai-content-studio')}</option>
                                <option value="ecommerce">{__('E-commerce', 'ai-content-studio')}</option>
                            </select>
                            <textarea
                                placeholder={__('Décrivez votre activité en quelques mots', 'ai-content-studio')}
                                value={formData.description}
                                onChange={(e) => updateField('description', e.target.value)}
                                className="acs-textarea"
                            />
                            <Button isPrimary onClick={() => setStep(2)}>
                                {__('Suivant', 'ai-content-studio')}
                            </Button>
                        </div>
                    )}

                    {step === 2 && (
                        <div>
                            <h2>{__('Vos objectifs', 'ai-content-studio')}</h2>
                            <label>
                                <input
                                    type="checkbox"
                                    checked={formData.goals.includes('awareness')}
                                    onChange={(e) => {
                                        const goals = e.target.checked
                                            ? [...formData.goals, 'awareness']
                                            : formData.goals.filter(g => g !== 'awareness');
                                        updateField('goals', goals);
                                    }}
                                />
                                {__('Augmenter la notoriété', 'ai-content-studio')}
                            </label>
                            <label>
                                <input
                                    type="checkbox"
                                    checked={formData.goals.includes('sales')}
                                    onChange={(e) => {
                                        const goals = e.target.checked
                                            ? [...formData.goals, 'sales']
                                            : formData.goals.filter(g => g !== 'sales');
                                        updateField('goals', goals);
                                    }}
                                />
                                {__('Générer des ventes', 'ai-content-studio')}
                            </label>
                            <Button isSecondary onClick={() => setStep(1)}>
                                {__('Précédent', 'ai-content-studio')}
                            </Button>
                            <Button isPrimary onClick={handleSubmit} disabled={loading}>
                                {loading ? __('Création en cours...', 'ai-content-studio') : __('Terminer', 'ai-content-studio')}
                            </Button>
                        </div>
                    )}
                </CardBody>
            </Card>
        </div>
    );
}
