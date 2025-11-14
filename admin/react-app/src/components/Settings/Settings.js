import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    FiUser,
    FiGlobe,
    FiCreditCard,
    FiShield,
    FiDownload,
    FiAlertCircle,
    FiCheck,
    FiSave
} from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import PricingModal from '../common/PricingModal';
import './Settings.css';

export default function Settings({ profile }) {
    const [activeTab, setActiveTab] = useState('account');
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState({ type: '', text: '' });
    const [showPricingModal, setShowPricingModal] = useState(false);

    // Account settings
    const [accountData, setAccountData] = useState({
        email: '',
        first_name: '',
        last_name: '',
    });

    // Preferences settings
    const [preferences, setPreferences] = useState({
        default_language: 'fr',
        default_tone: 'professional',
        use_emojis: true,
        use_hashtags: true,
        content_length: 'medium',
    });

    // Subscription data
    const [subscriptionData, setSubscriptionData] = useState({
        plan: 'free_trial',
        status: 'active',
        usage: {
            posts: 0,
            articles: 0,
        },
        limits: {
            posts_per_month: 1,
            articles_per_month: 1,
        },
    });

    useEffect(() => {
        loadSettings();
    }, []);

    const loadSettings = async () => {
        setLoading(true);
        try {
            // Load account info
            const user = await apiFetch({
                path: '/wp/v2/users/me',
                method: 'GET',
            });

            setAccountData({
                email: user.email || '',
                first_name: user.first_name || '',
                last_name: user.last_name || '',
            });

            // Load preferences
            try {
                const prefsResponse = await apiFetch({
                    path: '/acs/v1/settings/preferences',
                    method: 'GET',
                });

                if (prefsResponse.success && prefsResponse.data) {
                    setPreferences(prev => ({
                        ...prev,
                        ...prefsResponse.data,
                    }));
                }
            } catch (err) {
                console.log('No preferences found, using defaults');
            }

            // Load subscription data
            if (profile?.subscription_plan) {
                try {
                    const subResponse = await apiFetch({
                        path: '/acs/v1/subscription/status',
                        method: 'GET',
                    });

                    if (subResponse.success) {
                        setSubscriptionData(subResponse.data);
                    }
                } catch (err) {
                    console.log('Could not load subscription data');
                }
            }
        } catch (err) {
            console.error('Error loading settings:', err);
            showMessage('error', __('Erreur lors du chargement des paramètres', 'ai-content-studio'));
        } finally {
            setLoading(false);
        }
    };

    const showMessage = (type, text) => {
        setMessage({ type, text });
        setTimeout(() => setMessage({ type: '', text: '' }), 5000);
    };

    const saveAccountSettings = async () => {
        setSaving(true);
        try {
            await apiFetch({
                path: '/wp/v2/users/me',
                method: 'POST',
                data: accountData,
            });

            showMessage('success', __('Informations du compte mises à jour', 'ai-content-studio'));
        } catch (err) {
            showMessage('error', __('Erreur lors de la sauvegarde', 'ai-content-studio'));
        } finally {
            setSaving(false);
        }
    };

    const savePreferences = async () => {
        setSaving(true);
        try {
            const response = await apiFetch({
                path: '/acs/v1/settings/preferences',
                method: 'POST',
                data: preferences,
            });

            if (response.success) {
                showMessage('success', __('Préférences sauvegardées', 'ai-content-studio'));
            }
        } catch (err) {
            showMessage('error', __('Erreur lors de la sauvegarde', 'ai-content-studio'));
        } finally {
            setSaving(false);
        }
    };

    const exportData = async () => {
        try {
            const response = await apiFetch({
                path: '/acs/v1/settings/export',
                method: 'GET',
            });

            if (response.success && response.data) {
                // Create download
                const dataStr = JSON.stringify(response.data, null, 2);
                const dataBlob = new Blob([dataStr], { type: 'application/json' });
                const url = URL.createObjectURL(dataBlob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `acs-data-export-${new Date().toISOString().split('T')[0]}.json`;
                link.click();
                URL.revokeObjectURL(url);

                showMessage('success', __('Données exportées avec succès', 'ai-content-studio'));
            }
        } catch (err) {
            showMessage('error', __('Erreur lors de l\'export', 'ai-content-studio'));
        }
    };

    const tabs = [
        { id: 'account', icon: FiUser, label: __('Compte', 'ai-content-studio') },
        { id: 'preferences', icon: FiGlobe, label: __('Préférences', 'ai-content-studio') },
        { id: 'subscription', icon: FiCreditCard, label: __('Abonnement', 'ai-content-studio') },
        { id: 'data', icon: FiDownload, label: __('Données', 'ai-content-studio') },
    ];

    const toneOptions = [
        { value: 'professional', label: __('Professionnel', 'ai-content-studio') },
        { value: 'casual', label: __('Décontracté', 'ai-content-studio') },
        { value: 'friendly', label: __('Amical', 'ai-content-studio') },
        { value: 'enthusiastic', label: __('Enthousiaste', 'ai-content-studio') },
        { value: 'authoritative', label: __('Autoritaire', 'ai-content-studio') },
    ];

    const lengthOptions = [
        { value: 'short', label: __('Court', 'ai-content-studio') },
        { value: 'medium', label: __('Moyen', 'ai-content-studio') },
        { value: 'long', label: __('Long', 'ai-content-studio') },
    ];

    const languageOptions = [
        { value: 'fr', label: 'Français' },
        { value: 'en', label: 'English' },
        { value: 'es', label: 'Español' },
        { value: 'de', label: 'Deutsch' },
        { value: 'it', label: 'Italiano' },
        { value: 'pt', label: 'Português' },
    ];

    if (loading) {
        return (
            <div className="acs-settings-loading">
                <div className="acs-spinner" />
                <p>{__('Chargement des paramètres...', 'ai-content-studio')}</p>
            </div>
        );
    }

    return (
        <div className="acs-settings">
            <div className="acs-settings-header">
                <h1 className="acs-settings-title">{__('Paramètres', 'ai-content-studio')}</h1>
                <p className="acs-settings-subtitle">
                    {__('Gérez vos préférences et paramètres de compte', 'ai-content-studio')}
                </p>
            </div>

            {message.text && (
                <div className={`acs-settings-message acs-settings-message-${message.type}`}>
                    {message.type === 'success' ? <FiCheck /> : <FiAlertCircle />}
                    <span>{message.text}</span>
                </div>
            )}

            <div className="acs-settings-container">
                {/* Tabs Navigation */}
                <div className="acs-settings-tabs">
                    {tabs.map(tab => (
                        <button
                            key={tab.id}
                            className={`acs-settings-tab ${activeTab === tab.id ? 'active' : ''}`}
                            onClick={() => setActiveTab(tab.id)}
                        >
                            <tab.icon size={20} />
                            <span>{tab.label}</span>
                        </button>
                    ))}
                </div>

                {/* Tab Content */}
                <div className="acs-settings-content">
                    {/* Account Tab */}
                    {activeTab === 'account' && (
                        <div className="acs-settings-section">
                            <h2 className="acs-settings-section-title">
                                {__('Informations du compte', 'ai-content-studio')}
                            </h2>

                            <div className="acs-form-group">
                                <label className="acs-form-label">
                                    {__('Adresse email', 'ai-content-studio')}
                                </label>
                                <input
                                    type="email"
                                    className="acs-form-control"
                                    value={accountData.email}
                                    onChange={(e) => setAccountData({ ...accountData, email: e.target.value })}
                                />
                            </div>

                            <div className="acs-form-row">
                                <div className="acs-form-group">
                                    <label className="acs-form-label">
                                        {__('Prénom', 'ai-content-studio')}
                                    </label>
                                    <input
                                        type="text"
                                        className="acs-form-control"
                                        value={accountData.first_name}
                                        onChange={(e) => setAccountData({ ...accountData, first_name: e.target.value })}
                                    />
                                </div>

                                <div className="acs-form-group">
                                    <label className="acs-form-label">
                                        {__('Nom', 'ai-content-studio')}
                                    </label>
                                    <input
                                        type="text"
                                        className="acs-form-control"
                                        value={accountData.last_name}
                                        onChange={(e) => setAccountData({ ...accountData, last_name: e.target.value })}
                                    />
                                </div>
                            </div>

                            <button
                                className="acs-btn acs-btn-primary"
                                onClick={saveAccountSettings}
                                disabled={saving}
                            >
                                <FiSave />
                                {saving ? __('Sauvegarde...', 'ai-content-studio') : __('Sauvegarder', 'ai-content-studio')}
                            </button>
                        </div>
                    )}

                    {/* Preferences Tab */}
                    {activeTab === 'preferences' && (
                        <div className="acs-settings-section">
                            <h2 className="acs-settings-section-title">
                                {__('Préférences de génération', 'ai-content-studio')}
                            </h2>

                            <div className="acs-form-group">
                                <label className="acs-form-label">
                                    {__('Langue par défaut', 'ai-content-studio')}
                                </label>
                                <select
                                    className="acs-form-control"
                                    value={preferences.default_language}
                                    onChange={(e) => setPreferences({ ...preferences, default_language: e.target.value })}
                                >
                                    {languageOptions.map(opt => (
                                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                                    ))}
                                </select>
                            </div>

                            <div className="acs-form-group">
                                <label className="acs-form-label">
                                    {__('Ton par défaut', 'ai-content-studio')}
                                </label>
                                <select
                                    className="acs-form-control"
                                    value={preferences.default_tone}
                                    onChange={(e) => setPreferences({ ...preferences, default_tone: e.target.value })}
                                >
                                    {toneOptions.map(opt => (
                                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                                    ))}
                                </select>
                            </div>

                            <div className="acs-form-group">
                                <label className="acs-form-label">
                                    {__('Longueur de contenu par défaut', 'ai-content-studio')}
                                </label>
                                <select
                                    className="acs-form-control"
                                    value={preferences.content_length}
                                    onChange={(e) => setPreferences({ ...preferences, content_length: e.target.value })}
                                >
                                    {lengthOptions.map(opt => (
                                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                                    ))}
                                </select>
                            </div>

                            <div className="acs-form-group">
                                <label className="acs-settings-checkbox">
                                    <input
                                        type="checkbox"
                                        checked={preferences.use_emojis}
                                        onChange={(e) => setPreferences({ ...preferences, use_emojis: e.target.checked })}
                                    />
                                    <span>{__('Utiliser des emojis dans le contenu généré', 'ai-content-studio')}</span>
                                </label>
                            </div>

                            <div className="acs-form-group">
                                <label className="acs-settings-checkbox">
                                    <input
                                        type="checkbox"
                                        checked={preferences.use_hashtags}
                                        onChange={(e) => setPreferences({ ...preferences, use_hashtags: e.target.checked })}
                                    />
                                    <span>{__('Suggérer des hashtags automatiquement', 'ai-content-studio')}</span>
                                </label>
                            </div>

                            <button
                                className="acs-btn acs-btn-primary"
                                onClick={savePreferences}
                                disabled={saving}
                            >
                                <FiSave />
                                {saving ? __('Sauvegarde...', 'ai-content-studio') : __('Sauvegarder les préférences', 'ai-content-studio')}
                            </button>
                        </div>
                    )}

                    {/* Subscription Tab */}
                    {activeTab === 'subscription' && (
                        <div className="acs-settings-section">
                            <h2 className="acs-settings-section-title">
                                {__('Votre abonnement', 'ai-content-studio')}
                            </h2>

                            <div className="acs-subscription-card">
                                <div className="acs-subscription-header">
                                    <div>
                                        <h3 className="acs-subscription-plan">
                                            {subscriptionData.plan === 'free_trial' && __('Essai Gratuit', 'ai-content-studio')}
                                            {subscriptionData.plan === 'starter' && __('Starter', 'ai-content-studio')}
                                            {subscriptionData.plan === 'professional' && __('Professional', 'ai-content-studio')}
                                            {subscriptionData.plan === 'business' && __('Business', 'ai-content-studio')}
                                        </h3>
                                        <p className="acs-subscription-status">
                                            {subscriptionData.status === 'active' && __('Actif', 'ai-content-studio')}
                                        </p>
                                    </div>
                                    <button
                                        className="acs-btn acs-btn-primary"
                                        onClick={() => setShowPricingModal(true)}
                                    >
                                        {__('Changer de plan', 'ai-content-studio')}
                                    </button>
                                </div>

                                <div className="acs-usage-stats">
                                    <div className="acs-usage-item">
                                        <span className="acs-usage-label">{__('Posts générés ce mois', 'ai-content-studio')}</span>
                                        <span className="acs-usage-value">
                                            {subscriptionData.usage.posts} / {subscriptionData.limits.posts_per_month === -1 ? '∞' : subscriptionData.limits.posts_per_month}
                                        </span>
                                        <div className="acs-usage-bar">
                                            <div
                                                className="acs-usage-bar-fill"
                                                style={{
                                                    width: subscriptionData.limits.posts_per_month === -1
                                                        ? '0%'
                                                        : `${Math.min((subscriptionData.usage.posts / subscriptionData.limits.posts_per_month) * 100, 100)}%`
                                                }}
                                            />
                                        </div>
                                    </div>

                                    <div className="acs-usage-item">
                                        <span className="acs-usage-label">{__('Articles générés ce mois', 'ai-content-studio')}</span>
                                        <span className="acs-usage-value">
                                            {subscriptionData.usage.articles} / {subscriptionData.limits.articles_per_month === -1 ? '∞' : subscriptionData.limits.articles_per_month}
                                        </span>
                                        <div className="acs-usage-bar">
                                            <div
                                                className="acs-usage-bar-fill"
                                                style={{
                                                    width: subscriptionData.limits.articles_per_month === -1
                                                        ? '0%'
                                                        : `${Math.min((subscriptionData.usage.articles / subscriptionData.limits.articles_per_month) * 100, 100)}%`
                                                }}
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Data Tab */}
                    {activeTab === 'data' && (
                        <div className="acs-settings-section">
                            <h2 className="acs-settings-section-title">
                                {__('Gestion des données', 'ai-content-studio')}
                            </h2>

                            <div className="acs-data-card">
                                <div className="acs-data-card-icon">
                                    <FiDownload size={24} />
                                </div>
                                <div className="acs-data-card-content">
                                    <h3>{__('Exporter mes données', 'ai-content-studio')}</h3>
                                    <p>{__('Téléchargez toutes vos données en format JSON (profil, posts, articles, stratégies)', 'ai-content-studio')}</p>
                                </div>
                                <button
                                    className="acs-btn acs-btn-outline-primary"
                                    onClick={exportData}
                                >
                                    <FiDownload />
                                    {__('Exporter', 'ai-content-studio')}
                                </button>
                            </div>

                            <div className="acs-data-card acs-data-card-danger">
                                <div className="acs-data-card-icon">
                                    <FiAlertCircle size={24} />
                                </div>
                                <div className="acs-data-card-content">
                                    <h3>{__('Zone de danger', 'ai-content-studio')}</h3>
                                    <p>{__('Actions irréversibles concernant votre compte', 'ai-content-studio')}</p>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {showPricingModal && (
                <PricingModal
                    onClose={() => setShowPricingModal(false)}
                    currentPlan={subscriptionData.plan}
                    trigger="settings"
                />
            )}
        </div>
    );
}
