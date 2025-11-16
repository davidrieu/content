import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { FiX, FiCheck, FiZap, FiStar, FiUsers, FiTrendingUp, FiShield } from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import './PricingModal.css';

export default function PricingModal({ isOpen, onClose, currentPlan = 'free_trial', triggerType = 'limit_reached' }) {
    const [loading, setLoading] = useState(false);
    const [selectedPlan, setSelectedPlan] = useState(null);
    const [currentTestimonial, setCurrentTestimonial] = useState(0);

    // Témoignages rotatifs
    const testimonials = [
        {
            text: __("RunnWrite AI a transformé ma stratégie de contenu. Je génère en 10 minutes ce qui me prenait 3 heures !", 'ai-content-studio'),
            author: __("Marie L.", 'ai-content-studio'),
            role: __("Social Media Manager", 'ai-content-studio'),
            rating: 5
        },
        {
            text: __("La qualité des articles générés est impressionnante. Mes clients adorent le contenu que je produis maintenant.", 'ai-content-studio'),
            author: __("Thomas D.", 'ai-content-studio'),
            role: __("Freelance Content Creator", 'ai-content-studio'),
            rating: 5
        },
        {
            text: __("ROI incroyable. Le temps gagné me permet de gérer 3x plus de clients avec la même équipe.", 'ai-content-studio'),
            author: __("Sophie M.", 'ai-content-studio'),
            role: __("Agence Marketing", 'ai-content-studio'),
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
            name: __('Starter', 'ai-content-studio'),
            price: 29,
            description: __('Pour les entrepreneurs et créateurs', 'ai-content-studio'),
            features: [
                __('50 posts sociaux/mois', 'ai-content-studio'),
                __('5 articles de blog/mois', 'ai-content-studio'),
                __('25 images AI/mois', 'ai-content-studio'),
                __('Toutes les langues', 'ai-content-studio'),
                __('Toutes les plateformes', 'ai-content-studio'),
                __('Planification & calendrier', 'ai-content-studio'),
                __('Support email', 'ai-content-studio'),
            ],
            popular: false,
        },
        {
            slug: 'professional',
            name: __('Professional', 'ai-content-studio'),
            price: 59,
            description: __('Pour les professionnels du marketing', 'ai-content-studio'),
            features: [
                __('300 posts sociaux/mois', 'ai-content-studio'),
                __('30 articles de blog/mois', 'ai-content-studio'),
                __('150 images AI/mois', 'ai-content-studio'),
                __('Toutes les fonctionnalités Starter', 'ai-content-studio'),
                __('Analytics avancées', 'ai-content-studio'),
                __('Analyse de la concurrence', 'ai-content-studio'),
                __('Support prioritaire', 'ai-content-studio'),
            ],
            popular: true,
        },
        {
            slug: 'business',
            name: __('Business', 'ai-content-studio'),
            price: 149,
            description: __('Pour les agences et entreprises', 'ai-content-studio'),
            features: [
                __('Posts ILLIMITÉS', 'ai-content-studio'),
                __('Articles ILLIMITÉS', 'ai-content-studio'),
                __('Images ILLIMITÉES', 'ai-content-studio'),
                __('Toutes les fonctionnalités Pro', 'ai-content-studio'),
                __('5 membres d\'équipe', 'ai-content-studio'),
                __('API access', 'ai-content-studio'),
                __('Support dédié', 'ai-content-studio'),
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
                const errorMessage = response.message || __('Erreur lors de la création du lien de paiement', 'ai-content-studio');
                alert(errorMessage);
                setLoading(false);
                setSelectedPlan(null);
            }
        } catch (error) {
            console.error('Error getting checkout URL:', error);

            // Try to extract error message from API response
            let errorMessage = __('Une erreur s\'est produite', 'ai-content-studio');

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
                return __('Vous avez atteint votre limite d\'articles', 'ai-content-studio');
            case 'post_limit':
                return __('Vous avez atteint votre limite de posts', 'ai-content-studio');
            case 'upgrade':
                return __('Choisissez votre plan', 'ai-content-studio');
            default:
                return __('Améliorez votre plan pour continuer', 'ai-content-studio');
        }
    };

    const getModalSubtitle = () => {
        switch (triggerType) {
            case 'article_limit':
                return __('Passez à un plan supérieur pour générer plus d\'articles de blog', 'ai-content-studio');
            case 'post_limit':
                return __('Passez à un plan supérieur pour générer plus de posts sociaux', 'ai-content-studio');
            default:
                return __('Choisissez le plan qui correspond à vos besoins', 'ai-content-studio');
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
                        <span><strong>2,500+</strong> {__('créateurs de contenu', 'ai-content-studio')}</span>
                    </div>
                    <div className="acs-social-proof-stat">
                        <FiTrendingUp size={18} />
                        <span><strong>127,000+</strong> {__('posts générés ce mois', 'ai-content-studio')}</span>
                    </div>
                    <div className="acs-social-proof-stat">
                        <FiStar size={18} />
                        <span><strong>4.9/5</strong> {__('satisfaction client', 'ai-content-studio')}</span>
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
                                    {__('POPULAIRE', 'ai-content-studio')}
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
                                        {__('Redirection...', 'ai-content-studio')}
                                    </>
                                ) : (
                                    <>
                                        <FiZap size={18} />
                                        {__('Choisir ce plan', 'ai-content-studio')}
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
                        <span>{__('Paiement 100% sécurisé', 'ai-content-studio')}</span>
                    </div>
                    <div className="acs-trust-item">
                        <FiCheck size={20} />
                        <span>{__('Sans engagement', 'ai-content-studio')}</span>
                    </div>
                    <div className="acs-trust-item">
                        <FiCheck size={20} />
                        <span>{__('Garantie 14 jours', 'ai-content-studio')}</span>
                    </div>
                </div>

                {/* Footer */}
                <div className="acs-pricing-modal-footer">
                    <p className="acs-pricing-footer-note">
                        {__('🎉 Offre spéciale : Rejoignez-nous maintenant et bénéficiez de votre premier mois avec une assistance personnalisée gratuite !', 'ai-content-studio')}
                    </p>
                </div>

                {!loading && (
                    <div className="acs-pricing-modal-dismiss">
                        <button onClick={onClose} className="acs-pricing-dismiss-button">
                            {__('Je décide plus tard', 'ai-content-studio')}
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
                                {__('Annuler mon abonnement', 'ai-content-studio')}
                            </a>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
