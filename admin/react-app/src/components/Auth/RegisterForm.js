import { useState } from '@wordpress/element';
import { useTranslation } from '../../contexts/TranslationContext';
import { FiMail, FiLock, FiUser, FiUserPlus, FiCheck, FiGlobe } from 'react-icons/fi';

export default function RegisterForm({ onSwitchToLogin }) {
    const { t } = useTranslation();
    const [formData, setFormData] = useState({
        username: '',
        email: '',
        password: '',
        confirmPassword: '',
        language: 'fr',
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState(false);

    const handleChange = (e) => {
        setFormData({
            ...formData,
            [e.target.name]: e.target.value,
        });
        setError('');
    };

    const validateForm = () => {
        if (formData.password.length < 8) {
            setError(t('Le mot de passe doit contenir au moins 8 caractères'));
            return false;
        }

        if (formData.password !== formData.confirmPassword) {
            setError(t('Les mots de passe ne correspondent pas'));
            return false;
        }

        if (!formData.email.includes('@')) {
            setError(t('Veuillez entrer une adresse email valide'));
            return false;
        }

        return true;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!validateForm()) {
            return;
        }

        setLoading(true);
        setError('');

        try {
            const response = await fetch(window.acsData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'acs_register',
                    username: formData.username,
                    email: formData.email,
                    password: formData.password,
                    language: formData.language,
                    authNonce: window.acsData.authNonce,
                }),
            });

            const data = await response.json();

            if (data.success) {
                setSuccess(true);
                setTimeout(() => {
                    // Reload page to log in the new user
                    window.location.reload();
                }, 2000);
            } else {
                setError(data.data.message || t('Erreur lors de la création du compte'));
            }
        } catch (err) {
            setError(t('Erreur lors de la création du compte. Veuillez réessayer.'));
        } finally {
            setLoading(false);
        }
    };

    if (success) {
        return (
            <div className="acs-auth-container">
                <div className="acs-auth-card">
                    <div className="acs-auth-header">
                        <div className="acs-auth-icon success">
                            <FiCheck size={32} />
                        </div>
                        <h1 className="acs-auth-title">{t('Compte créé avec succès !')}</h1>
                        <p className="acs-auth-subtitle">
                            {t('Redirection en cours...')}
                        </p>
                    </div>
                </div>
            </div>
        );
    }

    const passwordStrength = () => {
        const password = formData.password;
        if (password.length === 0) return null;
        if (password.length < 8) return { level: 'weak', label: t('Faible') };
        if (password.length < 12) return { level: 'medium', label: t('Moyen') };
        return { level: 'strong', label: t('Fort') };
    };

    const strength = passwordStrength();

    return (
        <div className="acs-auth-container">
            <div className="acs-auth-card">
                <div className="acs-auth-header">
                    <div className="acs-auth-icon">
                        <FiUserPlus size={32} />
                    </div>
                    <h1 className="acs-auth-title">{t('Créer un compte')}</h1>
                    <p className="acs-auth-subtitle">
                        {t('Commencez à créer du contenu avec l\'IA')}
                    </p>
                </div>

                {error && (
                    <div className="acs-alert acs-alert-danger">
                        {error}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="acs-auth-form">
                    <div className="acs-form-group">
                        <label className="acs-form-label">
                            {t('Nom d\'utilisateur')}
                        </label>
                        <div className="acs-input-with-icon">
                            <FiUser className="acs-input-icon" />
                            <input
                                type="text"
                                name="username"
                                className="acs-form-control acs-with-icon"
                                value={formData.username}
                                onChange={handleChange}
                                required
                                autoFocus
                                placeholder={t('johndoe')}
                            />
                        </div>
                    </div>

                    <div className="acs-form-group">
                        <label className="acs-form-label">
                            {t('Adresse email')}
                        </label>
                        <div className="acs-input-with-icon">
                            <FiMail className="acs-input-icon" />
                            <input
                                type="email"
                                name="email"
                                className="acs-form-control acs-with-icon"
                                value={formData.email}
                                onChange={handleChange}
                                required
                                placeholder={t('votre@email.com')}
                            />
                        </div>
                    </div>

                    <div className="acs-form-group">
                        <label className="acs-form-label">
                            {t('Langue de l\'interface')}
                        </label>
                        <div className="acs-input-with-icon">
                            <FiGlobe className="acs-input-icon" />
                            <select
                                name="language"
                                className="acs-form-control acs-with-icon"
                                value={formData.language}
                                onChange={handleChange}
                                required
                            >
                                <option value="fr">Français</option>
                                <option value="en">English</option>
                                <option value="es">Español</option>
                                <option value="pt">Português</option>
                                <option value="de">Deutsch</option>
                                <option value="it">Italiano</option>
                                <option value="zh">中文 (Mandarin)</option>
                                <option value="ja">日本語</option>
                                <option value="ko">한국어</option>
                                <option value="ar">العربية</option>
                                <option value="ru">Русский</option>
                                <option value="hi">हिन्दी</option>
                                <option value="bn">বাংলা</option>
                                <option value="id">Bahasa Indonesia</option>
                                <option value="tr">Türkçe</option>
                                <option value="vi">Tiếng Việt</option>
                                <option value="pl">Polski</option>
                                <option value="uk">Українська</option>
                                <option value="nl">Nederlands</option>
                                <option value="th">ไทย</option>
                                <option value="sv">Svenska</option>
                                <option value="el">Ελληνικά</option>
                                <option value="cs">Čeština</option>
                                <option value="ro">Română</option>
                                <option value="hu">Magyar</option>
                                <option value="da">Dansk</option>
                                <option value="fi">Suomi</option>
                                <option value="no">Norsk</option>
                                <option value="he">עברית</option>
                                <option value="ca">Català</option>
                            </select>
                        </div>
                    </div>

                    <div className="acs-form-group">
                        <label className="acs-form-label">
                            {t('Mot de passe')}
                        </label>
                        <div className="acs-input-with-icon">
                            <FiLock className="acs-input-icon" />
                            <input
                                type="password"
                                name="password"
                                className="acs-form-control acs-with-icon"
                                value={formData.password}
                                onChange={handleChange}
                                required
                                placeholder={t('••••••••')}
                            />
                        </div>
                        {strength && (
                            <div className="acs-password-strength">
                                <div className={`acs-strength-bar ${strength.level}`}>
                                    <div className="acs-strength-fill"></div>
                                </div>
                                <span className={`acs-strength-label ${strength.level}`}>
                                    {strength.label}
                                </span>
                            </div>
                        )}
                    </div>

                    <div className="acs-form-group">
                        <label className="acs-form-label">
                            {t('Confirmer le mot de passe')}
                        </label>
                        <div className="acs-input-with-icon">
                            <FiLock className="acs-input-icon" />
                            <input
                                type="password"
                                name="confirmPassword"
                                className="acs-form-control acs-with-icon"
                                value={formData.confirmPassword}
                                onChange={handleChange}
                                required
                                placeholder={t('••••••••')}
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        className="acs-btn acs-btn-primary acs-btn-lg w-100"
                        disabled={loading}
                    >
                        {loading ? (
                            t('Création...')
                        ) : (
                            <>
                                <FiUserPlus />
                                {t('Créer mon compte')}
                            </>
                        )}
                    </button>
                </form>

                <div className="acs-auth-divider">
                    <span>{t('ou')}</span>
                </div>

                <button
                    onClick={onSwitchToLogin}
                    className="acs-btn acs-btn-outline-primary acs-btn-lg w-100"
                >
                    {t('J\'ai déjà un compte')}
                </button>

                <div className="acs-auth-terms">
                    <p className="acs-text-sm">
                        {t('En créant un compte, vous acceptez nos')}{' '}
                        <a href="#" className="acs-link">{t('Conditions d\'utilisation')}</a>
                        {' '}{t('et notre')}{' '}
                        <a href="#" className="acs-link">{t('Politique de confidentialité')}</a>
                    </p>
                </div>
            </div>
        </div>
    );
}
