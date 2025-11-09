import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { FiMail, FiLock, FiUser, FiUserPlus, FiCheck } from 'react-icons/fi';

export default function RegisterForm({ onSwitchToLogin }) {
    const [formData, setFormData] = useState({
        username: '',
        email: '',
        password: '',
        confirmPassword: '',
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
            setError(__('Le mot de passe doit contenir au moins 8 caractères', 'ai-content-studio'));
            return false;
        }

        if (formData.password !== formData.confirmPassword) {
            setError(__('Les mots de passe ne correspondent pas', 'ai-content-studio'));
            return false;
        }

        if (!formData.email.includes('@')) {
            setError(__('Veuillez entrer une adresse email valide', 'ai-content-studio'));
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
                    nonce: window.acsData.nonce,
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
                setError(data.data.message || __('Erreur lors de la création du compte', 'ai-content-studio'));
            }
        } catch (err) {
            setError(__('Erreur lors de la création du compte. Veuillez réessayer.', 'ai-content-studio'));
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
                        <h1 className="acs-auth-title">{__('Compte créé avec succès !', 'ai-content-studio')}</h1>
                        <p className="acs-auth-subtitle">
                            {__('Redirection en cours...', 'ai-content-studio')}
                        </p>
                    </div>
                </div>
            </div>
        );
    }

    const passwordStrength = () => {
        const password = formData.password;
        if (password.length === 0) return null;
        if (password.length < 8) return { level: 'weak', label: __('Faible', 'ai-content-studio') };
        if (password.length < 12) return { level: 'medium', label: __('Moyen', 'ai-content-studio') };
        return { level: 'strong', label: __('Fort', 'ai-content-studio') };
    };

    const strength = passwordStrength();

    return (
        <div className="acs-auth-container">
            <div className="acs-auth-card">
                <div className="acs-auth-header">
                    <div className="acs-auth-icon">
                        <FiUserPlus size={32} />
                    </div>
                    <h1 className="acs-auth-title">{__('Créer un compte', 'ai-content-studio')}</h1>
                    <p className="acs-auth-subtitle">
                        {__('Commencez à créer du contenu avec l\'IA', 'ai-content-studio')}
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
                            {__('Nom d\'utilisateur', 'ai-content-studio')}
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
                                placeholder={__('johndoe', 'ai-content-studio')}
                            />
                        </div>
                    </div>

                    <div className="acs-form-group">
                        <label className="acs-form-label">
                            {__('Adresse email', 'ai-content-studio')}
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
                                placeholder={__('votre@email.com', 'ai-content-studio')}
                            />
                        </div>
                    </div>

                    <div className="acs-form-group">
                        <label className="acs-form-label">
                            {__('Mot de passe', 'ai-content-studio')}
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
                                placeholder={__('••••••••', 'ai-content-studio')}
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
                            {__('Confirmer le mot de passe', 'ai-content-studio')}
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
                                placeholder={__('••••••••', 'ai-content-studio')}
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        className="acs-btn acs-btn-primary acs-btn-lg w-100"
                        disabled={loading}
                    >
                        {loading ? (
                            __('Création...', 'ai-content-studio')
                        ) : (
                            <>
                                <FiUserPlus />
                                {__('Créer mon compte', 'ai-content-studio')}
                            </>
                        )}
                    </button>
                </form>

                <div className="acs-auth-divider">
                    <span>{__('ou', 'ai-content-studio')}</span>
                </div>

                <button
                    onClick={onSwitchToLogin}
                    className="acs-btn acs-btn-outline-primary acs-btn-lg w-100"
                >
                    {__('J\'ai déjà un compte', 'ai-content-studio')}
                </button>

                <div className="acs-auth-terms">
                    <p className="acs-text-sm">
                        {__('En créant un compte, vous acceptez nos', 'ai-content-studio')}{' '}
                        <a href="#" className="acs-link">{__('Conditions d\'utilisation', 'ai-content-studio')}</a>
                        {' '}{__('et notre', 'ai-content-studio')}{' '}
                        <a href="#" className="acs-link">{__('Politique de confidentialité', 'ai-content-studio')}</a>
                    </p>
                </div>
            </div>
        </div>
    );
}
