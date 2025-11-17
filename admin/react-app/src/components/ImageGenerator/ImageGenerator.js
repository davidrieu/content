import React, { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import {
    FiImage, FiDownload, FiInstagram, FiFacebook, FiLinkedin,
    FiTwitter, FiFileText, FiSquare, FiMaximize, FiSmartphone,
    FiZap, FiCreditCard
} from 'react-icons/fi';
import { useTranslation } from '../../contexts/TranslationContext';

const ImageGenerator = () => {
    const { t } = useTranslation();
    const [prompt, setPrompt] = useState('');
    const [title, setTitle] = useState('');
    const [selectedFormat, setSelectedFormat] = useState('instagram_post');
    const [quality, setQuality] = useState('standard');
    const [style, setStyle] = useState('vivid');
    const [formats, setFormats] = useState([]);
    const [loading, setLoading] = useState(false);
    const [generatedImage, setGeneratedImage] = useState(null);
    const [error, setError] = useState('');
    const [subscription, setSubscription] = useState(null);
    const [showPricingModal, setShowPricingModal] = useState(false);

    useEffect(() => {
        loadFormats();
        loadSubscription();
    }, []);

    const loadFormats = async () => {
        try {
            const response = await apiFetch({
                path: '/acs/v1/images/formats',
                method: 'GET',
            });
            if (response.success) {
                setFormats(response.data.formats);
            }
        } catch (err) {
            console.error('Error loading formats:', err);
        }
    };

    const loadSubscription = async () => {
        try {
            const response = await apiFetch({
                path: '/acs/v1/subscription',
                method: 'GET',
            });
            if (response.success) {
                setSubscription(response.data);
            }
        } catch (err) {
            console.error('Error loading subscription:', err);
        }
    };

    const handleGenerate = async () => {
        if (!prompt.trim()) {
            setError(t('Veuillez entrer une description pour votre image.'));
            return;
        }

        setError('');
        setLoading(true);
        setGeneratedImage(null);

        try {
            const response = await apiFetch({
                path: '/acs/v1/images/generate',
                method: 'POST',
                data: {
                    prompt: prompt.trim(),
                    format: selectedFormat,
                    quality,
                    style,
                    title: title.trim(),
                },
            });

            if (response.success) {
                setGeneratedImage(response.data.image);
                setPrompt('');
                setTitle('');

                // Refresh subscription info
                await loadSubscription();
            } else {
                if (response.error && response.error.code === 'limit_reached') {
                    setShowPricingModal(true);
                }
                setError(response.error?.message || t('Une erreur est survenue.'));
            }
        } catch (err) {
            console.error('Generation error:', err);
            if (err.code === 'limit_reached') {
                setShowPricingModal(true);
            }
            setError(err.message || t('Erreur lors de la génération de l\'image.'));
        } finally {
            setLoading(false);
        }
    };

    const handleDownload = async () => {
        if (!generatedImage) return;

        try {
            // Si on a un media_id WordPress, télécharger depuis WordPress
            if (generatedImage.media_id) {
                const wpUrl = `${window.location.origin}/wp-content/uploads/`;
                // Construire l'URL WordPress en récupérant depuis l'API
                const response = await apiFetch({
                    path: `/wp/v2/media/${generatedImage.media_id}`,
                    method: 'GET',
                });

                if (response && response.source_url) {
                    // Créer un lien de téléchargement
                    const link = document.createElement('a');
                    link.href = response.source_url;
                    link.download = `${generatedImage.title || 'image'}.png`;
                    link.target = '_blank';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    return;
                }
            }

            // Fallback : essayer de télécharger depuis l'URL directe
            const response = await fetch(generatedImage.url, { mode: 'cors' });
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${generatedImage.title || 'image'}.png`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        } catch (err) {
            console.error('Download error:', err);
            // Fallback final : ouvrir dans un nouvel onglet
            window.open(generatedImage.url, '_blank');
        }
    };

    const getFormatIcon = (iconName) => {
        const icons = {
            instagram: FiInstagram,
            facebook: FiFacebook,
            linkedin: FiLinkedin,
            twitter: FiTwitter,
            'file-text': FiFileText,
            image: FiImage,
            square: FiSquare,
            maximize: FiMaximize,
            smartphone: FiSmartphone,
        };
        const Icon = icons[iconName] || FiImage;
        return <Icon />;
    };

    const formatsByCategory = formats.reduce((acc, format) => {
        if (!acc[format.category]) {
            acc[format.category] = [];
        }
        acc[format.category].push(format);
        return acc;
    }, {});

    const categoryNames = {
        social: t('Réseaux Sociaux'),
        blog: t('Blog & Articles'),
        custom: t('Formats Personnalisés'),
    };

    // Check if user can generate images
    const canGenerate = subscription && (
        subscription.plan === 'business' ||
        (subscription.limits?.images_per_month > 0 &&
         (subscription.usage?.images_this_month || 0) < subscription.limits?.images_per_month)
    );

    // Check if it's a free trial limitation
    const isFreeTrial = subscription && subscription.plan === 'free_trial';

    // Check if limit is reached (not free trial but quota exhausted)
    const isLimitReached = subscription && !isFreeTrial &&
        subscription.limits?.images_per_month > 0 &&
        (subscription.usage?.images_this_month || 0) >= subscription.limits?.images_per_month;

    return (
        <div className="acs-image-generator">
            <div className="acs-page-header">
                <div className="acs-page-header-content">
                    <h1 className="acs-page-title">
                        <FiImage /> {t('Générateur d\'Images IA')}
                    </h1>
                    <p className="acs-page-subtitle">
                        {t('Créez des visuels époustouflants avec RUNNWRITE AI pour vos réseaux sociaux et articles de blog')}
                    </p>
                </div>
                {subscription && (
                    <div className="acs-usage-badge">
                        <FiZap />
                        <span>
                            {subscription.plan === 'business'
                                ? t('Illimité')
                                : `${subscription.usage?.images_this_month || 0}/${subscription.limits?.images_per_month || 0}`
                            }
                        </span>
                        <small>{t('Images ce mois')}</small>
                    </div>
                )}
            </div>

            <div className="acs-image-generator-grid">
                {/* Left Column - Form */}
                <div className="acs-generator-form">
                    <div className="acs-card">
                        <h3 className="acs-card-title">{t('Configuration')}</h3>

                        {/* Prompt */}
                        <div className="acs-form-group">
                            <label className="acs-label">
                                {t('Description de l\'image')}
                                <span className="acs-required">*</span>
                            </label>
                            <textarea
                                className="acs-textarea"
                                rows="6"
                                placeholder={t('Décrivez l\'image que vous souhaitez générer. Soyez précis et détaillé pour de meilleurs résultats.\n\nExemple : Un paysage de montagne au coucher du soleil, avec des nuages orangés, style photographie professionnelle, lumière cinématographique, haute qualité, ultra détaillé.')}
                                value={prompt}
                                onChange={(e) => setPrompt(e.target.value)}
                                maxLength={4000}
                            />
                            <small className="acs-form-help">
                                {prompt.length}/4000 {t('caractères')}
                            </small>
                        </div>

                        {/* Title */}
                        <div className="acs-form-group">
                            <label className="acs-label">
                                {t('Titre de l\'image (optionnel)')}
                            </label>
                            <input
                                type="text"
                                className="acs-input"
                                placeholder={t('Mon image générée')}
                                value={title}
                                onChange={(e) => setTitle(e.target.value)}
                            />
                        </div>

                        {/* Format Selection */}
                        <div className="acs-form-group">
                            <label className="acs-label">
                                {t('Format')}
                                <span className="acs-required">*</span>
                            </label>

                            {Object.entries(formatsByCategory).map(([category, categoryFormats]) => (
                                <div key={category} className="acs-format-category">
                                    <h4 className="acs-format-category-title">
                                        {categoryNames[category] || category}
                                    </h4>
                                    <div className="acs-format-grid">
                                        {categoryFormats.map((format) => (
                                            <div
                                                key={format.id}
                                                className={`acs-format-card ${selectedFormat === format.id ? 'active' : ''}`}
                                                onClick={() => setSelectedFormat(format.id)}
                                            >
                                                <div className="acs-format-icon">
                                                    {getFormatIcon(format.icon)}
                                                </div>
                                                <div className="acs-format-info">
                                                    <div className="acs-format-name">{format.name}</div>
                                                    <div className="acs-format-size">{format.size}</div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Quality */}
                        <div className="acs-form-group">
                            <label className="acs-label">{t('Qualité')}</label>
                            <div className="acs-radio-group">
                                <label className="acs-radio-label">
                                    <input
                                        type="radio"
                                        name="quality"
                                        value="standard"
                                        checked={quality === 'standard'}
                                        onChange={(e) => setQuality(e.target.value)}
                                    />
                                    <span>{t('Standard')}</span>
                                    <small>{t('Rapide et économique')}</small>
                                </label>
                                <label className="acs-radio-label">
                                    <input
                                        type="radio"
                                        name="quality"
                                        value="hd"
                                        checked={quality === 'hd'}
                                        onChange={(e) => setQuality(e.target.value)}
                                    />
                                    <span>{t('HD')}</span>
                                    <small>{t('Meilleure qualité, plus de détails')}</small>
                                </label>
                            </div>
                        </div>

                        {/* Style */}
                        <div className="acs-form-group">
                            <label className="acs-label">{t('Style')}</label>
                            <div className="acs-radio-group">
                                <label className="acs-radio-label">
                                    <input
                                        type="radio"
                                        name="style"
                                        value="vivid"
                                        checked={style === 'vivid'}
                                        onChange={(e) => setStyle(e.target.value)}
                                    />
                                    <span>{t('Vivid')}</span>
                                    <small>{t('Couleurs vives et contrastées')}</small>
                                </label>
                                <label className="acs-radio-label">
                                    <input
                                        type="radio"
                                        name="style"
                                        value="natural"
                                        checked={style === 'natural'}
                                        onChange={(e) => setStyle(e.target.value)}
                                    />
                                    <span>{t('Natural')}</span>
                                    <small>{t('Style naturel et réaliste')}</small>
                                </label>
                            </div>
                        </div>

                        {error && (
                            <div className="acs-alert acs-alert-error">
                                {error}
                            </div>
                        )}

                        {isFreeTrial && (
                            <div className="acs-alert acs-alert-warning">
                                <FiCreditCard />
                                <div>
                                    <strong>{t('Fonctionnalité Premium')}</strong>
                                    <p>{t('La génération d\'images n\'est pas disponible avec le plan Free Trial. Passez à un plan payant pour accéder à cette fonctionnalité.')}</p>
                                </div>
                            </div>
                        )}

                        {isLimitReached && (
                            <div className="acs-alert acs-alert-warning">
                                <FiCreditCard />
                                <div>
                                    <strong>{t('Limite atteinte')}</strong>
                                    <p>{t('Vous avez utilisé toutes vos images pour ce mois')} ({subscription.limits?.images_per_month} {t('images')}). {t('Passez à un plan supérieur pour continuer.')}</p>
                                </div>
                            </div>
                        )}

                        <button
                            className={`acs-btn acs-btn-primary acs-btn-lg ${loading ? 'acs-btn-loading' : ''}`}
                            onClick={handleGenerate}
                            disabled={loading || !prompt.trim() || !canGenerate}
                        >
                            {loading ? (
                                <>
                                    <div className="acs-spinner"></div>
                                    {t('Génération en cours...')}
                                </>
                            ) : (
                                <>
                                    <FiZap />
                                    {t('Générer l\'image')}
                                </>
                            )}
                        </button>
                    </div>
                </div>

                {/* Right Column - Preview */}
                <div className="acs-generator-preview">
                    <div className="acs-card">
                        <h3 className="acs-card-title">{t('Aperçu')}</h3>

                        {generatedImage ? (
                            <div className="acs-image-preview">
                                <div className="acs-image-container">
                                    <img
                                        src={generatedImage.url}
                                        alt={generatedImage.title}
                                        className="acs-generated-image"
                                    />
                                </div>

                                <div className="acs-image-info">
                                    <h4>{generatedImage.title}</h4>
                                    <div className="acs-image-meta">
                                        <span className="acs-badge">{generatedImage.size}</span>
                                        <span className="acs-badge">{generatedImage.quality}</span>
                                        <span className="acs-badge">{generatedImage.style}</span>
                                    </div>
                                    <p className="acs-image-prompt">{generatedImage.prompt}</p>
                                </div>

                                <div className="acs-image-actions">
                                    <button
                                        className="acs-btn acs-btn-secondary"
                                        onClick={handleDownload}
                                    >
                                        <FiDownload />
                                        {t('Télécharger')}
                                    </button>
                                    <a
                                        href={generatedImage.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="acs-btn acs-btn-outline-primary"
                                    >
                                        <FiImage />
                                        {t('Voir en grand')}
                                    </a>
                                </div>
                            </div>
                        ) : (
                            <div className="acs-empty-state">
                                <FiImage size={64} />
                                <h3>{t('Aucune image générée')}</h3>
                                <p>
                                    {t('Remplissez le formulaire et cliquez sur "Générer l\'image" pour créer votre visuel.')}
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Tips */}
                    <div className="acs-card acs-tips-card">
                        <h4>💡 {t('Conseils pour de meilleurs résultats')}</h4>
                        <ul className="acs-tips-list">
                            <li>
                                <strong>{t('Soyez précis :')}</strong> {t('Décrivez les détails importants (couleurs, style, ambiance)')}
                            </li>
                            <li>
                                <strong>{t('Mentionnez le style :')}</strong> {t('"photographie professionnelle", "illustration minimaliste", "art numérique"')}
                            </li>
                            <li>
                                <strong>{t('Ajoutez le contexte :')}</strong> {t('"pour Instagram", "pour article de blog", "fond blanc"')}
                            </li>
                            <li>
                                <strong>{t('Utilisez des adjectifs :')}</strong> {t('"lumineux", "dynamique", "épuré", "moderne"')}
                            </li>
                            <li>
                                <strong>{t('Qualité HD :')}</strong> {t('Utilisez HD pour des images destinées à l\'impression ou grandes tailles')}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {/* Pricing Modal */}
            {showPricingModal && (
                <div className="acs-modal-overlay" onClick={() => setShowPricingModal(false)}>
                    <div className="acs-modal" onClick={(e) => e.stopPropagation()}>
                        <div className="acs-modal-header">
                            <h2>{t('Limite d\'images atteinte')}</h2>
                            <button
                                className="acs-modal-close"
                                onClick={() => setShowPricingModal(false)}
                            >
                                ×
                            </button>
                        </div>
                        <div className="acs-modal-body">
                            <p>{t('Vous avez atteint la limite d\'images pour votre plan actuel.')}</p>
                            <p>{t('Passez à un plan supérieur pour générer plus d\'images !')}</p>
                            <div className="acs-pricing-quick">
                                <div className="acs-pricing-option">
                                    <h4>Starter</h4>
                                    <div className="acs-price">29€<small>/mois</small></div>
                                    <p>{t('25 images/mois')}</p>
                                </div>
                                <div className="acs-pricing-option">
                                    <h4>Professional</h4>
                                    <div className="acs-price">59€<small>/mois</small></div>
                                    <p>{t('150 images/mois')}</p>
                                </div>
                                <div className="acs-pricing-option">
                                    <h4>Business</h4>
                                    <div className="acs-price">149€<small>/mois</small></div>
                                    <p>{t('Images illimitées')}</p>
                                </div>
                            </div>
                        </div>
                        <div className="acs-modal-footer">
                            <button
                                className="acs-btn acs-btn-outline-primary"
                                onClick={() => setShowPricingModal(false)}
                            >
                                {t('Plus tard')}
                            </button>
                            <button
                                className="acs-btn acs-btn-primary"
                                onClick={() => window.location.hash = '#/settings'}
                            >
                                {t('Voir les plans')}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default ImageGenerator;
