import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { FiMail, FiLock, FiLogIn, FiUser } from 'react-icons/fi';

export default function LoginForm({ onLogin, onSwitchToRegister }) {
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
                    nonce: window.acsData.nonce,
                }),
            });

            const data = await response.json();

            if (data.success) {
                // Reload page to refresh user session
                window.location.reload();
            } else {
                setError(data.data.message || __('Erreur de connexion', 'ai-content-studio'));
            }
        } catch (err) {
            setError(__('Erreur de connexion. Veuillez réessayer.', 'ai-content-studio'));
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
                    <h1 className="acs-auth-title">{__('Connexion', 'ai-content-studio')}</h1>
                    <p className="acs-auth-subtitle">
                        {__('Connectez-vous pour accéder à AI Content Studio', 'ai-content-studio')}
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
                            {__('Nom d\'utilisateur ou Email', 'ai-content-studio')}
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
                    </div>

                    <div className="acs-form-footer">
                        <a href={window.acsData.homeUrl + '/wp-login.php?action=lostpassword'} className="acs-link">
                            {__('Mot de passe oublié ?', 'ai-content-studio')}
                        </a>
                    </div>

                    <button
                        type="submit"
                        className="acs-btn acs-btn-primary acs-btn-lg w-100"
                        disabled={loading}
                    >
                        {loading ? (
                            __('Connexion...', 'ai-content-studio')
                        ) : (
                            <>
                                <FiLogIn />
                                {__('Se connecter', 'ai-content-studio')}
                            </>
                        )}
                    </button>
                </form>

                <div className="acs-auth-divider">
                    <span>{__('ou', 'ai-content-studio')}</span>
                </div>

                <button
                    onClick={onSwitchToRegister}
                    className="acs-btn acs-btn-outline-primary acs-btn-lg w-100"
                >
                    <FiUser />
                    {__('Créer un compte', 'ai-content-studio')}
                </button>
            </div>
        </div>
    );
}
