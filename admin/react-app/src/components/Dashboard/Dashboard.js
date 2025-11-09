import { useState, useEffect } from '@wordpress/element';
import { motion } from 'framer-motion';
import { useNavigate } from 'react-router-dom';
import {
    FiTrendingUp, FiFileText, FiImage, FiZap,
    FiPlus, FiActivity, FiAward, FiCalendar
} from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

const container = {
    hidden: { opacity: 0 },
    show: {
        opacity: 1,
        transition: {
            staggerChildren: 0.1
        }
    }
};

const item = {
    hidden: { opacity: 0, y: 20 },
    show: { opacity: 1, y: 0 }
};

export default function Dashboard({ profile }) {
    const navigate = useNavigate();
    const [subscription, setSubscription] = useState(null);
    const [usage, setUsage] = useState(null);
    const [loading, setLoading] = useState(true);

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
            <div className="acs-loading-spinner">
                <div className="acs-spinner"></div>
                <div className="acs-text-body">{__('Chargement de votre tableau de bord...', 'ai-content-studio')}</div>
            </div>
        );
    }

    const planColors = {
        free: { from: '#6366f1', to: '#8b5cf6' },
        starter: { from: '#10b981', to: '#14b8a6' },
        pro: { from: '#ec4899', to: '#f43f5e' },
        business: { from: '#f59e0b', to: '#f97316' },
    };

    const currentPlanColor = planColors[subscription?.plan] || planColors.free;

    return (
        <div className="acs-dashboard">
            {/* Header */}
            <motion.div
                className="acs-dashboard-header"
                initial={{ opacity: 0, y: -20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.5 }}
            >
                <h1 className="acs-dashboard-title">
                    {__('Bonjour,', 'ai-content-studio')} {profile?.business_name || 'Creator'}! 👋
                </h1>
                <p className="acs-dashboard-subtitle">
                    {__('Voici un aperçu de votre activité créative', 'ai-content-studio')}
                </p>
            </motion.div>

            {/* Stats Cards */}
            <motion.div
                className="acs-dashboard-grid"
                variants={container}
                initial="hidden"
                animate="show"
            >
                {/* Posts Card */}
                <motion.div className="acs-stat-card" variants={item}>
                    <div className="acs-stat-header">
                        <div className="acs-stat-icon" style={{ background: 'rgba(99, 102, 241, 0.1)', color: '#6366f1' }}>
                            <FiFileText />
                        </div>
                        <FiActivity style={{ color: 'var(--acs-success)', fontSize: '1.25rem' }} />
                    </div>
                    <div className="acs-stat-value">
                        {usage?.posts.used || 0}
                    </div>
                    <div className="acs-stat-label">
                        {__('Posts générés ce mois', 'ai-content-studio')}
                    </div>
                    <div className="acs-stat-progress">
                        <div className="acs-progress-bar">
                            <motion.div
                                className="acs-progress-fill"
                                initial={{ width: 0 }}
                                animate={{ width: `${calculatePercentage(usage?.posts.used, usage?.posts.limit)}%` }}
                                transition={{ duration: 1, delay: 0.5 }}
                            />
                        </div>
                        <div className="acs-progress-text">
                            <span>{usage?.posts.used || 0} utilisés</span>
                            <span>{usage?.posts.limit === -1 ? '∞' : usage?.posts.limit} disponibles</span>
                        </div>
                    </div>
                </motion.div>

                {/* Articles Card */}
                <motion.div className="acs-stat-card" variants={item}>
                    <div className="acs-stat-header">
                        <div className="acs-stat-icon" style={{ background: 'rgba(16, 185, 129, 0.1)', color: '#10b981' }}>
                            <FiTrendingUp />
                        </div>
                        <FiZap style={{ color: 'var(--acs-warning)', fontSize: '1.25rem' }} />
                    </div>
                    <div className="acs-stat-value">
                        {usage?.articles.used || 0}
                    </div>
                    <div className="acs-stat-label">
                        {__('Articles de blog', 'ai-content-studio')}
                    </div>
                    <div className="acs-stat-progress">
                        <div className="acs-progress-bar">
                            <motion.div
                                className="acs-progress-fill"
                                style={{ background: 'var(--acs-gradient-success)' }}
                                initial={{ width: 0 }}
                                animate={{ width: `${calculatePercentage(usage?.articles.used, usage?.articles.limit)}%` }}
                                transition={{ duration: 1, delay: 0.6 }}
                            />
                        </div>
                        <div className="acs-progress-text">
                            <span>{usage?.articles.used || 0} utilisés</span>
                            <span>{usage?.articles.limit === -1 ? '∞' : usage?.articles.limit} disponibles</span>
                        </div>
                    </div>
                </motion.div>

                {/* Images Card */}
                <motion.div className="acs-stat-card" variants={item}>
                    <div className="acs-stat-header">
                        <div className="acs-stat-icon" style={{ background: 'rgba(236, 72, 153, 0.1)', color: '#ec4899' }}>
                            <FiImage />
                        </div>
                        <FiActivity style={{ color: 'var(--acs-info)', fontSize: '1.25rem' }} />
                    </div>
                    <div className="acs-stat-value">
                        {usage?.images.used || 0}
                    </div>
                    <div className="acs-stat-label">
                        {__('Images IA générées', 'ai-content-studio')}
                    </div>
                    <div className="acs-stat-progress">
                        <div className="acs-progress-bar">
                            <motion.div
                                className="acs-progress-fill"
                                style={{ background: 'var(--acs-gradient-secondary)' }}
                                initial={{ width: 0 }}
                                animate={{ width: `${calculatePercentage(usage?.images.used, usage?.images.limit)}%` }}
                                transition={{ duration: 1, delay: 0.7 }}
                            />
                        </div>
                        <div className="acs-progress-text">
                            <span>{usage?.images.used || 0} utilisées</span>
                            <span>{usage?.images.limit === -1 ? '∞' : usage?.images.limit} disponibles</span>
                        </div>
                    </div>
                </motion.div>
            </motion.div>

            {/* Action Cards */}
            <motion.div
                className="acs-dashboard-grid"
                style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(350px, 1fr))' }}
                variants={container}
                initial="hidden"
                animate="show"
            >
                {/* Current Plan */}
                <motion.div
                    className="acs-card"
                    variants={item}
                    style={{
                        background: `linear-gradient(135deg, ${currentPlanColor.from} 0%, ${currentPlanColor.to} 100%)`,
                        color: 'white',
                        border: 'none'
                    }}
                >
                    <div style={{ display: 'flex', alignItems: 'center', gap: '1rem', marginBottom: '1rem' }}>
                        <FiAward style={{ fontSize: '2rem' }} />
                        <div>
                            <div className="acs-text-sm" style={{ color: 'rgba(255,255,255,0.9)', opacity: 0.9 }}>
                                {__('Plan actuel', 'ai-content-studio')}
                            </div>
                            <div style={{ fontSize: '1.75rem', fontWeight: 700 }}>
                                {subscription?.plan_name || 'Free'}
                            </div>
                        </div>
                    </div>
                    <p style={{ color: 'rgba(255,255,255,0.9)', marginBottom: '1.5rem' }}>
                        {subscription?.plan === 'free' && __('Passez à un plan supérieur pour débloquer plus de fonctionnalités!', 'ai-content-studio')}
                        {subscription?.plan !== 'free' && __('Vous profitez de toutes les fonctionnalités de votre plan.', 'ai-content-studio')}
                    </p>
                    <button
                        className="acs-btn acs-btn-secondary"
                        style={{ width: '100%', background: 'white', color: currentPlanColor.from }}
                        onClick={() => window.location.href = '/shop'}
                    >
                        {subscription?.plan === 'free' ? __('Upgrader mon plan', 'ai-content-studio') : __('Gérer mon abonnement', 'ai-content-studio')}
                    </button>
                </motion.div>

                {/* Quick Actions */}
                <motion.div className="acs-card" variants={item}>
                    <h3 className="acs-heading-md" style={{ marginBottom: '1.5rem' }}>
                        🚀 {__('Actions rapides', 'ai-content-studio')}
                    </h3>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
                        <motion.button
                            className="acs-btn acs-btn-primary"
                            style={{ width: '100%', justifyContent: 'flex-start' }}
                            onClick={() => navigate('/generate')}
                            whileHover={{ scale: 1.02 }}
                            whileTap={{ scale: 0.98 }}
                        >
                            <FiPlus /> {__('Nouveau post social', 'ai-content-studio')}
                        </motion.button>
                        <motion.button
                            className="acs-btn acs-btn-ghost"
                            style={{ width: '100%', justifyContent: 'flex-start' }}
                            whileHover={{ scale: 1.02 }}
                            whileTap={{ scale: 0.98 }}
                        >
                            <FiTrendingUp /> {__('Générer un article de blog', 'ai-content-studio')}
                        </motion.button>
                        <motion.button
                            className="acs-btn acs-btn-ghost"
                            style={{ width: '100%', justifyContent: 'flex-start' }}
                            whileHover={{ scale: 1.02 }}
                            whileTap={{ scale: 0.98 }}
                        >
                            <FiImage /> {__('Créer une image IA', 'ai-content-studio')}
                        </motion.button>
                        <motion.button
                            className="acs-btn acs-btn-ghost"
                            style={{ width: '100%', justifyContent: 'flex-start' }}
                            whileHover={{ scale: 1.02 }}
                            whileTap={{ scale: 0.98 }}
                        >
                            <FiCalendar /> {__('Voir le calendrier', 'ai-content-studio')}
                        </motion.button>
                    </div>
                </motion.div>

                {/* Welcome Tips */}
                <motion.div
                    className="acs-card"
                    variants={item}
                    style={{ background: 'var(--acs-gradient-light)', border: '2px solid var(--acs-gray-200)' }}
                >
                    <h3 className="acs-heading-md" style={{ marginBottom: '1rem' }}>
                        💡 {__('Conseil du jour', 'ai-content-studio')}
                    </h3>
                    <p className="acs-text-body" style={{ marginBottom: '1rem' }}>
                        {__('Publiez vos posts aux heures de pointe pour maximiser l\'engagement. Les meilleurs moments sont généralement 10h-11h et 19h-20h.', 'ai-content-studio')}
                    </p>
                    <div className="acs-text-sm" style={{ fontStyle: 'italic', color: 'var(--acs-gray-500)' }}>
                        {__('Astuce basée sur les meilleures pratiques des réseaux sociaux', 'ai-content-studio')}
                    </div>
                </motion.div>
            </motion.div>

            {/* Recent Activity (Placeholder for future) */}
            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.8 }}
                style={{ marginTop: '2rem' }}
            >
                <div className="acs-card">
                    <h3 className="acs-heading-md" style={{ marginBottom: '1.5rem' }}>
                        📊 {__('Activité récente', 'ai-content-studio')}
                    </h3>
                    <div style={{ textAlign: 'center', padding: '3rem 1rem', color: 'var(--acs-gray-400)' }}>
                        <FiActivity style={{ fontSize: '3rem', marginBottom: '1rem' }} />
                        <p>{__('Vos posts récents apparaîtront ici', 'ai-content-studio')}</p>
                        <button
                            className="acs-btn acs-btn-primary"
                            onClick={() => navigate('/generate')}
                            style={{ marginTop: '1rem' }}
                        >
                            <FiPlus /> {__('Créer votre premier post', 'ai-content-studio')}
                        </button>
                    </div>
                </div>
            </motion.div>
        </div>
    );
}
