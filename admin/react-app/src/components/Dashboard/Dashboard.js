import { useState, useEffect } from '@wordpress/element';
import { useNavigate } from 'react-router-dom';
import { FiFileText, FiTrendingUp, FiImage, FiZap, FiArrowUp, FiArrowDown } from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import { useTranslation } from '../../contexts/TranslationContext';
import PricingModal from '../common/PricingModal';

export default function Dashboard({ profile }) {
    const navigate = useNavigate();
    const { t } = useTranslation();
    const [subscription, setSubscription] = useState(null);
    const [usage, setUsage] = useState(null);
    const [loading, setLoading] = useState(true);
    const [showPricingModal, setShowPricingModal] = useState(false);

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        try {
            const [subResponse, usageResponse] = await Promise.all([
                apiFetch({ path: '/acs/v1/subscription' }),
                apiFetch({ path: '/acs/v1/subscription/usage' }),
            ]);

            if (subResponse.success) setSubscription(subResponse.data);
            if (usageResponse.success) setUsage(usageResponse.data);
        } catch (error) {
            console.error('Error loading data:', error);
        } finally {
            setLoading(false);
        }
    };

    const calculatePercentage = (used, limit) => {
        if (limit === -1) return 0;
        return Math.min((used / limit) * 100, 100);
    };

    if (loading) {
        return (
            <div style={{ textAlign: 'center', padding: '3rem' }}>
                <div className="acs-spinner" style={{ margin: '0 auto' }}></div>
                <p className="acs-text-muted" style={{ marginTop: '1rem' }}>
                    {t('Chargement...')}
                </p>
            </div>
        );
    }

    return (
        <div>
            {/* Page Title */}
            <div className="mb-4">
                <h1 className="acs-page-title">
                    {t('Bienvenue,')} {profile?.business_name}!
                </h1>
                <p className="acs-text-muted">
                    {t('Voici un aperçu de votre activité ce mois-ci')}
                </p>
            </div>

            {/* Stats Cards */}
            <div className="acs-stats-grid">
                {/* Posts Stats */}
                <div className="acs-stat-card">
                    <div className="acs-stat-header">
                        <div className="acs-stat-icon primary">
                            <FiFileText />
                        </div>
                        <div className="acs-stat-change positive">
                            <FiArrowUp size={14} /> 12%
                        </div>
                    </div>
                    <div className="acs-stat-value">{usage?.posts.used || 0}</div>
                    <div className="acs-stat-label">{t('Posts générés')}</div>
                    <div className="acs-progress">
                        <div
                            className="acs-progress-bar"
                            style={{ width: `${calculatePercentage(usage?.posts.used, usage?.posts.limit)}%` }}
                        />
                    </div>
                    <div className="acs-text-sm acs-text-muted" style={{ marginTop: '0.5rem' }}>
                        {usage?.posts.used || 0} / {usage?.posts.limit === -1 ? '∞' : usage?.posts.limit} {t('disponibles')}
                    </div>
                </div>

                {/* Articles Stats */}
                <div className="acs-stat-card success">
                    <div className="acs-stat-header">
                        <div className="acs-stat-icon success">
                            <FiTrendingUp />
                        </div>
                        <div className="acs-stat-change positive">
                            <FiArrowUp size={14} /> 8%
                        </div>
                    </div>
                    <div className="acs-stat-value">{usage?.articles.used || 0}</div>
                    <div className="acs-stat-label">{t('Articles de blog')}</div>
                    <div className="acs-progress">
                        <div
                            className="acs-progress-bar success"
                            style={{ width: `${calculatePercentage(usage?.articles.used, usage?.articles.limit)}%` }}
                        />
                    </div>
                    <div className="acs-text-sm acs-text-muted" style={{ marginTop: '0.5rem' }}>
                        {usage?.articles.used || 0} / {usage?.articles.limit === -1 ? '∞' : usage?.articles.limit} {t('disponibles')}
                    </div>
                </div>

                {/* Images Stats */}
                <div className="acs-stat-card warning">
                    <div className="acs-stat-header">
                        <div className="acs-stat-icon warning">
                            <FiImage />
                        </div>
                        <div className="acs-stat-change negative">
                            <FiArrowDown size={14} /> 3%
                        </div>
                    </div>
                    <div className="acs-stat-value">{usage?.images.used || 0}</div>
                    <div className="acs-stat-label">{t('Images IA')}</div>
                    <div className="acs-progress">
                        <div
                            className="acs-progress-bar warning"
                            style={{ width: `${calculatePercentage(usage?.images.used, usage?.images.limit)}%` }}
                        />
                    </div>
                    <div className="acs-text-sm acs-text-muted" style={{ marginTop: '0.5rem' }}>
                        {usage?.images.used || 0} / {usage?.images.limit === -1 ? '∞' : usage?.images.limit} {t('disponibles')}
                    </div>
                </div>
            </div>

            {/* Content Grid */}
            <div className="acs-grid-two-col acs-dashboard-grid" style={{ marginTop: 'var(--acs-spacing-5)' }}>
                {/* Quick Actions Card */}
                <div className="acs-card">
                    <div className="acs-card-header">
                        <h3 className="acs-card-title">{t('Actions rapides')}</h3>
                    </div>
                    <div className="acs-actions-grid" style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 'var(--acs-spacing-3)' }}>
                        <button className="acs-btn acs-btn-primary" onClick={() => navigate('/generate')}>
                            <FiZap /> {t('Nouveau post')}
                        </button>
                        <button className="acs-btn acs-btn-outline-primary" onClick={() => navigate('/blog-generator')}>
                            <FiFileText /> {t('Nouvel article')}
                        </button>
                        <button className="acs-btn acs-btn-outline-primary" onClick={() => navigate('/images')}>
                            <FiImage /> {t('Générer image')}
                        </button>
                        <button className="acs-btn acs-btn-outline-primary" onClick={() => navigate('/trends')}>
                            <FiTrendingUp /> {t('Voir tendances')}
                        </button>
                    </div>
                </div>

                {/* Subscription Card */}
                <div className="acs-card">
                    <div className="acs-card-header">
                        <h3 className="acs-card-title">{t('Votre plan')}</h3>
                        <span className={`acs-badge acs-badge-${subscription?.plan === 'free_trial' ? 'primary' : 'success'}`}>
                            {subscription?.plan_name || 'Free Trial'}
                        </span>
                    </div>
                    <p className="acs-text-muted mb-3">
                        {subscription?.plan === 'free_trial'
                            ? t('Passez à un plan supérieur pour plus de fonctionnalités')
                            : t('Vous profitez de toutes les fonctionnalités')
                        }
                    </p>
                    <button
                        className="acs-btn acs-btn-secondary w-100"
                        onClick={() => setShowPricingModal(true)}
                    >
                        {subscription?.plan === 'free_trial' ? t('Upgrader') : t('Gérer')}
                    </button>
                </div>
            </div>

            {/* Pricing Modal */}
            <PricingModal
                isOpen={showPricingModal}
                onClose={() => setShowPricingModal(false)}
                currentPlan={subscription?.plan || profile?.subscription_plan || 'free_trial'}
                triggerType="upgrade"
            />
        </div>
    );
}
