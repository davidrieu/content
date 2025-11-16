import { useState, useEffect } from '@wordpress/element';
import { useTranslation } from '../../contexts/TranslationContext';
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
    const { t } = useTranslation();
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

    // Password change
    const [passwordData, setPasswordData] = useState({
        current_password: '',
        new_password: '',
        confirm_password: '',
    });
    const [passwordError, setPasswordError] = useState('');

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

            // Auto-select user's registration language from profile
            if (profile?.languages && Array.isArray(profile.languages) && profile.languages.length > 0) {
                const userLanguage = profile.languages[0]; // First language is the primary one
                setPreferences(prev => ({
                    ...prev,
                    default_language: userLanguage,
                }));
            }

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
            showMessage('error', t('Erreur lors du chargement des paramètres'));
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

            showMessage('success', t('Informations du compte mises à jour'));
        } catch (err) {
            showMessage('error', t('Erreur lors de la sauvegarde'));
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
                showMessage('success', t('Préférences sauvegardées'));
            }
        } catch (err) {
            showMessage('error', t('Erreur lors de la sauvegarde'));
        } finally {
            setSaving(false);
        }
    };

    const changePassword = async () => {
        setPasswordError('');

        // Validation
        if (!passwordData.current_password || !passwordData.new_password || !passwordData.confirm_password) {
            setPasswordError(t('Tous les champs sont requis'));
            return;
        }

        if (passwordData.new_password.length < 8) {
            setPasswordError(t('Le nouveau mot de passe doit contenir au moins 8 caractères'));
            return;
        }

        if (passwordData.new_password !== passwordData.confirm_password) {
            setPasswordError(t('Les mots de passe ne correspondent pas'));
            return;
        }

        setSaving(true);
        try {
            const response = await apiFetch({
                path: '/acs/v1/settings/password',
                method: 'POST',
                data: {
                    current_password: passwordData.current_password,
                    new_password: passwordData.new_password,
                },
            });

            if (response.success) {
                showMessage('success', t('Mot de passe modifié avec succès'));
                setPasswordData({
                    current_password: '',
                    new_password: '',
                    confirm_password: '',
                });
            }
        } catch (err) {
            setPasswordError(err.message || t('Mot de passe actuel incorrect'));
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

                showMessage('success', t('Données exportées avec succès'));
            }
        } catch (err) {
            showMessage('error', t('Erreur lors de l\'export'));
        }
    };

    const tabs = [
        { id: 'account', icon: FiUser, label: t('Compte') },
        { id: 'preferences', icon: FiGlobe, label: t('Préférences') },
        { id: 'subscription', icon: FiCreditCard, label: t('Abonnement') },
        { id: 'data', icon: FiDownload, label: t('Données') },
    ];

    const toneOptions = [
        { value: 'professional', label: t('Professionnel') },
        { value: 'casual', label: t('Décontracté') },
        { value: 'friendly', label: t('Amical') },
        { value: 'enthusiastic', label: t('Enthousiaste') },
        { value: 'authoritative', label: t('Autoritaire') },
    ];

    const lengthOptions = [
        { value: 'short', label: t('Court') },
        { value: 'medium', label: t('Moyen') },
        { value: 'long', label: t('Long') },
    ];

    const languageOptions = [
        { value: 'fr', label: 'Français' },
        { value: 'en', label: 'English' },
        { value: 'es', label: 'Español' },
        { value: 'pt', label: 'Português' },
        { value: 'de', label: 'Deutsch' },
        { value: 'it', label: 'Italiano' },
        { value: 'zh', label: '中文 (Mandarin)' },
        { value: 'ja', label: '日本語' },
        { value: 'ko', label: '한국어' },
        { value: 'ar', label: 'العربية' },
        { value: 'ru', label: 'Русский' },
        { value: 'hi', label: 'हिन्दी' },
        { value: 'bn', label: 'বাংলা' },
        { value: 'id', label: 'Bahasa Indonesia' },
        { value: 'tr', label: 'Türkçe' },
        { value: 'vi', label: 'Tiếng Việt' },
        { value: 'pl', label: 'Polski' },
        { value: 'uk', label: 'Українська' },
        { value: 'nl', label: 'Nederlands' },
        { value: 'th', label: 'ไทย' },
        { value: 'sv', label: 'Svenska' },
        { value: 'el', label: 'Ελληνικά' },
        { value: 'cs', label: 'Čeština' },
        { value: 'ro', label: 'Română' },
        { value: 'hu', label: 'Magyar' },
        { value: 'da', label: 'Dansk' },
        { value: 'fi', label: 'Suomi' },
        { value: 'no', label: 'Norsk' },
        { value: 'he', label: 'עברית' },
        { value: 'ca', label: 'Català' },
    ];

    if (loading) {
        return (
            <div className="acs-settings-loading">
                <div className="acs-spinner" />
                <p>{t('Chargement des paramètres...')}</p>
            </div>
        );
    }

    return (
        <div className="acs-settings">
            <div className="acs-settings-header">
                <h1 className="acs-settings-title">{t('Paramètres')}</h1>
                <p className="acs-settings-subtitle">
                    {t('Gérez vos préférences et paramètres de compte')}
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
                                {t('Informations du compte')}
                            </h2>

                            <div className="acs-form-group">
                                <label className="acs-form-label">
                                    {t('Adresse email')}
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
                                        {t('Prénom')}
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
                                        {t('Nom')}
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
                                {saving ? t('Sauvegarde...') : t('Sauvegarder')}
                            </button>

                            {/* Password Change Section */}
                            <div className="acs-settings-divider" />

                            <h2 className="acs-settings-section-title">
                                <FiShield style={{ marginRight: '8px' }} />
                                {t('Changer le mot de passe')}
                            </h2>

                            {passwordError && (
                                <div className="acs-settings-message acs-settings-message-error">
                                    <FiAlertCircle />
                                    <span>{passwordError}</span>
                                </div>
                            )}

                            <div className="acs-form-group">
                                <label className="acs-form-label">
                                    {t('Mot de passe actuel')}
                                </label>
                                <input
                                    type="password"
                                    className="acs-form-control"
                                    value={passwordData.current_password}
                                    onChange={(e) => setPasswordData({ ...passwordData, current_password: e.target.value })}
                                />
                            </div>

                            <div className="acs-form-row">
                                <div className="acs-form-group">
                                    <label className="acs-form-label">
                                        {t('Nouveau mot de passe')}
                                    </label>
                                    <input
                                        type="password"
                                        className="acs-form-control"
                                        value={passwordData.new_password}
                                        onChange={(e) => setPasswordData({ ...passwordData, new_password: e.target.value })}
                                        placeholder={t('Minimum 8 caractères')}
                                    />
                                </div>

                                <div className="acs-form-group">
                                    <label className="acs-form-label">
                                        {t('Confirmer le mot de passe')}
                                    </label>
                                    <input
                                        type="password"
                                        className="acs-form-control"
                                        value={passwordData.confirm_password}
                                        onChange={(e) => setPasswordData({ ...passwordData, confirm_password: e.target.value })}
                                    />
                                </div>
                            </div>

                            <button
                                className="acs-btn acs-btn-secondary"
                                onClick={changePassword}
                                disabled={saving}
                            >
                                <FiShield />
                                {saving ? t('Modification...') : t('Changer le mot de passe')}
                            </button>
                        </div>
                    )}

                    {/* Preferences Tab */}
                    {activeTab === 'preferences' && (
                        <div className="acs-settings-section">
                            <h2 className="acs-settings-section-title">
                                {t('Préférences de génération')}
                            </h2>

                            <div className="acs-form-group">
                                <label className="acs-form-label">
                                    {t('Langue par défaut')}
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
                                    {t('Ton par défaut')}
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
                                    {t('Longueur de contenu par défaut')}
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
                                    <span>{t('Utiliser des emojis dans le contenu généré')}</span>
                                </label>
                            </div>

                            <div className="acs-form-group">
                                <label className="acs-settings-checkbox">
                                    <input
                                        type="checkbox"
                                        checked={preferences.use_hashtags}
                                        onChange={(e) => setPreferences({ ...preferences, use_hashtags: e.target.checked })}
                                    />
                                    <span>{t('Suggérer des hashtags automatiquement')}</span>
                                </label>
                            </div>

                            <button
                                className="acs-btn acs-btn-primary"
                                onClick={savePreferences}
                                disabled={saving}
                            >
                                <FiSave />
                                {saving ? t('Sauvegarde...') : t('Sauvegarder les préférences')}
                            </button>
                        </div>
                    )}

                    {/* Subscription Tab */}
                    {activeTab === 'subscription' && (
                        <div className="acs-settings-section">
                            <h2 className="acs-settings-section-title">
                                {t('Votre abonnement')}
                            </h2>

                            <div className="acs-subscription-card">
                                <div className="acs-subscription-header">
                                    <div>
                                        <h3 className="acs-subscription-plan">
                                            {subscriptionData.plan === 'free_trial' && t('Essai Gratuit')}
                                            {subscriptionData.plan === 'starter' && t('Starter')}
                                            {subscriptionData.plan === 'professional' && t('Professional')}
                                            {subscriptionData.plan === 'business' && t('Business')}
                                        </h3>
                                        <p className="acs-subscription-status">
                                            {subscriptionData.status === 'active' && t('Actif')}
                                        </p>
                                    </div>
                                    <button
                                        className="acs-btn acs-btn-primary"
                                        onClick={() => setShowPricingModal(true)}
                                    >
                                        {t('Changer de plan')}
                                    </button>
                                </div>

                                <div className="acs-usage-stats">
                                    <div className="acs-usage-item">
                                        <span className="acs-usage-label">{t('Posts générés ce mois')}</span>
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
                                        <span className="acs-usage-label">{t('Articles générés ce mois')}</span>
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
                                {t('Gestion des données')}
                            </h2>

                            <div className="acs-data-card">
                                <div className="acs-data-card-icon">
                                    <FiDownload size={24} />
                                </div>
                                <div className="acs-data-card-content">
                                    <h3>{t('Exporter mes données')}</h3>
                                    <p>{t('Téléchargez toutes vos données en format JSON (profil, posts, articles, stratégies)')}</p>
                                </div>
                                <button
                                    className="acs-btn acs-btn-outline-primary"
                                    onClick={exportData}
                                >
                                    <FiDownload />
                                    {t('Exporter')}
                                </button>
                            </div>

                            <div className="acs-data-card acs-data-card-danger">
                                <div className="acs-data-card-icon">
                                    <FiAlertCircle size={24} />
                                </div>
                                <div className="acs-data-card-content">
                                    <h3>{t('Zone de danger')}</h3>
                                    <p>{t('Actions irréversibles concernant votre compte')}</p>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            <PricingModal
                isOpen={showPricingModal}
                onClose={() => setShowPricingModal(false)}
                currentPlan={subscriptionData.plan}
                triggerType="upgrade"
            />
        </div>
    );
}
