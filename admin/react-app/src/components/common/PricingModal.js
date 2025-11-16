import { useState, useEffect } from '@wordpress/element';
import { FiX, FiCheck, FiZap, FiStar, FiUsers, FiTrendingUp, FiShield } from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import { useTranslation } from '../../contexts/TranslationContext';
import './PricingModal.css';

export default function PricingModal({ isOpen, onClose, currentPlan = 'free_trial', triggerType = 'limit_reached' }) {
    const { t } = useTranslation();
    const [loading, setLoading] = useState(false);
    const [selectedPlan, setSelectedPlan] = useState(null);
    const [currentTestimonial, setCurrentTestimonial] = useState(0);

    // Témoignages rotatifs
    const testimonials = [
        {
            text: t("RunnWrite AI a transformé ma stratégie de contenu. Je génère en 10 minutes ce qui me prenait 3 heures !"),
            author: t("Marie L."),
            role: t("Social Media Manager"),
            rating: 5
        },
        {
            text: t("La qualité des articles générés est impressionnante. Mes clients adorent le contenu que je produis maintenant."),
            author: t("Thomas D."),
            role: t("Freelance Content Creator"),
            rating: 5
        },
        {
            text: t("ROI incroyable. Le temps gagné me permet de gérer 3x plus de clients avec la même équipe."),
            author: t("Sophie M."),
            role: t("Agence Marketing"),
            rating: 5
        }
    ];

    // Rotation automatique des témoignages
    useEffect(() => {
        if (!isOpen) return;

        const interval = setInterval(() => {
            setCurrentTestimonial((prev) => (prev + 1) % testimonials.length);
        }, 4000);

        return () => clearInterval(interval);
    }, [isOpen, testimonials.length]);

    if (!isOpen) return null;

    const plans = [
        {
            slug: 'starter',
            name: t('Starter'),
            price: 29,
            description: t('Pour les entrepreneurs et créateurs'),
            features: [
                t('50 posts sociaux/mois'),
                t('5 articles de blog/mois'),
                t('25 images AI/mois'),
                t('Toutes les langues'),
                t('Toutes les plateformes'),
                t('Planification & calendrier'),
                t('Support email'),
            ],
            popular: false,
        },
        {
            slug: 'professional',
            name: t('Professional'),
            price: 59,
            description: t('Pour les professionnels du marketing'),
            features: [
                t('300 posts sociaux/mois'),
                t('30 articles de blog/mois'),
                t('150 images AI/mois'),
                t('Toutes les fonctionnalités Starter'),
                t('Analytics avancées'),
                t('Analyse de la concurrence'),
                t('Support prioritaire'),
            ],
            popular: true,
        },
        {
            slug: 'business',
            name: t('Business'),
            price: 149,
            description: t('Pour les agences et entreprises'),
            features: [
                t('Posts ILLIMITÉS'),
                t('Articles ILLIMITÉS'),
                t('Images ILLIMITÉES'),
                t('Toutes les fonctionnalités Pro'),
                t('5 membres d\'équipe'),
                t('API access'),
                t('Support dédié'),
            ],
            popular: false,
        },
    ];

    const handleSelectPlan = async (planSlug) => {
        setSelectedPlan(planSlug);
        setLoading(true);

        try {
            // Get the product checkout URL
            const response = await apiFetch({
                path: '/acs/v1/subscription/checkout-url',
                method: 'POST',
                data: {
                    plan: planSlug,
                },
            });

            if (response.success && response.data.checkout_url) {
                // Redirect to WooCommerce checkout
                window.location.href = response.data.checkout_url;
            } else {
                // Display specific error message from server
                const errorMessage = response.message || t('Erreur lors de la création du lien de paiement');
                alert(errorMessage);
                setLoading(false);
                setSelectedPlan(null);
            }
        } catch (error) {
            console.error('Error getting checkout URL:', error);

            // Try to extract error message from API response
            let errorMessage = t('Une erreur s\'est produite');

            if (error.message) {
                errorMessage = error.message;
            } else if (error.data && error.data.message) {
                errorMessage = error.data.message;
            }

            alert(errorMessage);
            setLoading(false);
            setSelectedPlan(null);
        }
    };

    const getModalTitle = () => {
        switch (triggerType) {
            case 'article_limit':
                return t('Vous avez atteint votre limite d\'articles');
            case 'post_limit':
                return t('Vous avez atteint votre limite de posts');
            case 'upgrade':
                return t('Choisissez votre plan');
            default:
                return t('Améliorez votre plan pour continuer');
        }
    };

    const getModalSubtitle = () => {
        switch (triggerType) {
            case 'article_limit':
                return t('Passez à un plan supérieur pour générer plus d\'articles de blog');
            case 'post_limit':
                return t('Passez à un plan supérieur pour générer plus de posts sociaux');
            default:
                return t('Choisissez le plan qui correspond à vos besoins');
        }
    };

    return (
        <div className="acs-pricing-modal-overlay" onClick={onClose}>
            <div className="acs-pricing-modal" onClick={(e) => e.stopPropagation()}>
                {/* Close button */}
                <button className="acs-pricing-modal-close" onClick={onClose} disabled={loading}>
                    <FiX size={24} />
                </button>

                {/* Header */}
                <div className="acs-pricing-modal-header">
                    <h2 className="acs-pricing-modal-title">{getModalTitle()}</h2>
                    <p className="acs-pricing-modal-subtitle">{getModalSubtitle()}</p>
                </div>

                {/* Social Proof Banner */}
                <div className="acs-social-proof-banner">
                    <div className="acs-social-proof-stat">
                        <FiUsers size={18} />
                        <span><strong>2,500+</strong> {t('créateurs de contenu')}</span>
                    </div>
                    <div className="acs-social-proof-stat">
                        <FiTrendingUp size={18} />
                        <span><strong>127,000+</strong> {t('posts générés ce mois')}</span>
                    </div>
                    <div className="acs-social-proof-stat">
                        <FiStar size={18} />
                        <span><strong>4.9/5</strong> {t('satisfaction client')}</span>
                    </div>
                </div>

                {/* Testimonial Carousel */}
                <div className="acs-testimonial-carousel">
                    <div className="acs-testimonial-content">
                        <div className="acs-testimonial-stars">
                            {[...Array(testimonials[currentTestimonial].rating)].map((_, i) => (
                                <FiStar key={i} size={16} fill="currentColor" />
                            ))}
                        </div>
                        <p className="acs-testimonial-text">"{testimonials[currentTestimonial].text}"</p>
                        <div className="acs-testimonial-author">
                            <strong>{testimonials[currentTestimonial].author}</strong>
                            <span>{testimonials[currentTestimonial].role}</span>
                        </div>
                    </div>
                    <div className="acs-testimonial-dots">
                        {testimonials.map((_, index) => (
                            <button
                                key={index}
                                className={`acs-testimonial-dot ${index === currentTestimonial ? 'active' : ''}`}
                                onClick={() => setCurrentTestimonial(index)}
                                aria-label={`Témoignage ${index + 1}`}
                            />
                        ))}
                    </div>
                </div>

                {/* Plans Grid */}
                <div className="acs-pricing-grid">
                    {plans.map((plan) => (
                        <div
                            key={plan.slug}
                            className={`acs-pricing-card ${plan.popular ? 'popular' : ''} ${
                                selectedPlan === plan.slug ? 'loading' : ''
                            }`}
                        >
                            {plan.popular && (
                                <div className="acs-pricing-badge">
                                    <FiStar size={14} />
                                    {t('POPULAIRE')}
                                </div>
                            )}

                            <div className="acs-pricing-card-header">
                                <h3 className="acs-pricing-plan-name">{plan.name}</h3>
                                <div className="acs-pricing-price">
                                    <span className="acs-pricing-currency">$</span>
                                    <span className="acs-pricing-amount">{plan.price}</span>
                                    <span className="acs-pricing-period">/mois</span>
                                </div>
                                <p className="acs-pricing-description">{plan.description}</p>
                            </div>

                            <ul className="acs-pricing-features">
                                {plan.features.map((feature, index) => (
                                    <li key={index} className="acs-pricing-feature">
                                        <FiCheck className="acs-pricing-check" />
                                        <span>{feature}</span>
                                    </li>
                                ))}
                            </ul>

                            <button
                                className={`acs-pricing-button ${plan.popular ? 'primary' : 'secondary'}`}
                                onClick={() => handleSelectPlan(plan.slug)}
                                disabled={loading}
                            >
                                {selectedPlan === plan.slug ? (
                                    <>
                                        <div className="acs-spinner-small"></div>
                                        {t('Redirection...')}
                                    </>
                                ) : (
                                    <>
                                        <FiZap size={18} />
                                        {t('Choisir ce plan')}
                                    </>
                                )}
                            </button>
                        </div>
                    ))}
                </div>

                {/* Trust Banner */}
                <div className="acs-trust-banner">
                    <div className="acs-trust-item">
                        <FiShield size={20} />
                        <span>{t('Paiement 100% sécurisé')}</span>
                    </div>
                    <div className="acs-trust-item">
                        <FiCheck size={20} />
                        <span>{t('Sans engagement')}</span>
                    </div>
                    <div className="acs-trust-item">
                        <FiCheck size={20} />
                        <span>{t('Garantie 14 jours')}</span>
                    </div>
                </div>

                {/* Footer */}
                <div className="acs-pricing-modal-footer">
                    <p className="acs-pricing-footer-note">
                        {t('🎉 Offre spéciale : Rejoignez-nous maintenant et bénéficiez de votre premier mois avec une assistance personnalisée gratuite !')}
                    </p>
                </div>

                {!loading && (
                    <div className="acs-pricing-modal-dismiss">
                        <button onClick={onClose} className="acs-pricing-dismiss-button">
                            {t('Je décide plus tard')}
                        </button>
                        {currentPlan !== 'free_trial' && (
                            <a
                                href="/my-account"
                                className="acs-pricing-cancel-link"
                                style={{
                                    fontSize: '0.8rem',
                                    color: 'var(--acs-gray-500)',
                                    textDecoration: 'underline',
                                    marginTop: '8px',
                                    display: 'inline-block',
                                    transition: 'color 0.2s'
                                }}
                                onMouseEnter={(e) => e.target.style.color = 'var(--acs-danger)'}
                                onMouseLeave={(e) => e.target.style.color = 'var(--acs-gray-500)'}
                            >
                                {t('Annuler mon abonnement')}
                            </a>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
