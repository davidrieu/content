import { useState } from '@wordpress/element';
import { useTranslation } from '../../contexts/TranslationContext';
import { FiMail, FiLock, FiLogIn, FiUser } from 'react-icons/fi';

export default function LoginForm({ onLogin, onSwitchToRegister }) {
    const { t } = useTranslation();
    const [formData, setFormData] = useState({
        username: '',
        password: '',
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const handleChange = (e) => {
        setFormData({
            ...formData,
            [e.target.name]: e.target.value,
        });
        setError('');
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const response = await fetch(window.acsData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'acs_login',
                    username: formData.username,
                    password: formData.password,
                    authNonce: window.acsData.authNonce,
                }),
            });

            const data = await response.json();

            if (data.success) {
                // Reload page to refresh user session
                window.location.reload();
            } else {
                setError(data.data.message || t('Erreur de connexion'));
            }
        } catch (err) {
            setError(t('Erreur de connexion. Veuillez réessayer.'));
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="acs-auth-container">
            <div className="acs-auth-card">
                <div className="acs-auth-header">
                    <div className="acs-auth-icon">
                        <FiLogIn size={32} />
                    </div>
                    <h1 className="acs-auth-title">{t('Connexion')}</h1>
                    <p className="acs-auth-subtitle">
                        {t('Connectez-vous pour accéder à AI Content Studio')}
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
                            {t('Nom d\'utilisateur ou Email')}
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
                                placeholder={t('votre@email.com')}
                            />
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
                    </div>

                    <div className="acs-form-footer">
                        <a href={window.acsData.homeUrl + '/wp-login.php?action=lostpassword'} className="acs-link">
                            {t('Mot de passe oublié ?')}
                        </a>
                    </div>

                    <button
                        type="submit"
                        className="acs-btn acs-btn-primary acs-btn-lg w-100"
                        disabled={loading}
                    >
                        {loading ? (
                            t('Connexion...')
                        ) : (
                            <>
                                <FiLogIn />
                                {t('Se connecter')}
                            </>
                        )}
                    </button>
                </form>

                <div className="acs-auth-divider">
                    <span>{t('ou')}</span>
                </div>

                <button
                    onClick={onSwitchToRegister}
                    className="acs-btn acs-btn-outline-primary acs-btn-lg w-100"
                >
                    <FiUser />
                    {t('Créer un compte')}
                </button>
            </div>
        </div>
    );
}
