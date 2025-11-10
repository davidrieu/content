import { useState, useEffect, useRef } from '@wordpress/element';
import { FiZap, FiCopy, FiCheck, FiSave, FiCalendar, FiHeart, FiAlertTriangle, FiCheckCircle } from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { CONTENT_TYPES, POST_TEMPLATES, PLATFORM_SPECS } from '../../data/contentTemplates';

export default function EnhancedPostGenerator({ profile }) {
    const [platform, setPlatform] = useState('instagram');
    const [contentType, setContentType] = useState('educational');
    const [template, setTemplate] = useState('');
    const [topic, setTopic] = useState('');
    const [tone, setTone] = useState('professional');
    const [language, setLanguage] = useState('fr');
    const [generating, setGenerating] = useState(false);
    const [generatedPosts, setGeneratedPosts] = useState([]);
    const [error, setError] = useState('');
    const [copiedIndex, setCopiedIndex] = useState(null);
    const [selectedPostForSave, setSelectedPostForSave] = useState(null);

    // Ref pour l'auto-scroll
    const generatedPostsRef = useRef(null);

    // Filtrer les templates disponibles selon le type de contenu sélectionné
    const availableTemplates = Object.values(POST_TEMPLATES).filter(
        (t) => t.type === contentType && t.platforms.includes(platform)
    );

    // Auto-sélectionner le premier template quand on change de type
    useEffect(() => {
        if (availableTemplates.length > 0 && !template) {
            setTemplate(availableTemplates[0].id);
        }
    }, [contentType, platform]);

    const selectedTemplate = POST_TEMPLATES[template];
    const platformSpec = PLATFORM_SPECS[platform];

    // Calculer si un post dépasse la limite
    const isOverLimit = (content, hashtags) => {
        const fullContent = content + '\n\n' + (hashtags || []).join(' ');
        return fullContent.length > platformSpec.max_length;
    };

    // Calculer le pourcentage d'utilisation de la limite
    const getLimitPercentage = (content, hashtags) => {
        const fullContent = content + '\n\n' + (hashtags || []).join(' ');
        return Math.min((fullContent.length / platformSpec.max_length) * 100, 100);
    };

    const handleGenerate = async () => {
        if (!topic.trim()) {
            setError(__('Veuillez entrer un sujet', 'ai-content-studio'));
            return;
        }

        setGenerating(true);
        setError('');
        setGeneratedPosts([]);

        try {
            const response = await apiFetch({
                path: '/acs/v1/posts/generate',
                method: 'POST',
                data: {
                    platform,
                    topic,
                    tone,
                    language,
                    content_type: contentType,
                    template_id: template,
                    max_length: platformSpec.max_length, // IMPORTANT: Passer la limite au backend
                    profile_data: {
                        sector: profile?.sector || '',
                        target_audience: profile?.target_audience || '',
                        business_name: profile?.business_name || '',
                    },
                },
            });

            if (response.success) {
                setGeneratedPosts(response.data);
                // Auto-scroll vers les posts générés après un court délai
                setTimeout(() => {
                    generatedPostsRef.current?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }, 100);
            } else {
                setError(response.error?.message || __('Erreur lors de la génération', 'ai-content-studio'));
            }
        } catch (err) {
            console.error('Generation error:', err);

            // Extract error message from response - structure: { data: { error: { code, message } } }
            let errorMessage = __('Erreur lors de la génération', 'ai-content-studio');
            let errorCode = '';

            if (err.data && err.data.error) {
                errorMessage = err.data.error.message || err.data.error;
                errorCode = err.data.error.code || '';
            } else if (err.message) {
                errorMessage = err.message;
            }

            // Messages d'erreur plus clairs basés sur le code d'erreur
            if (errorCode === 'no_profile' || errorMessage.includes('profil business')) {
                errorMessage = '📝 Vous devez d\'abord créer votre profil business via l\'assistant de démarrage ou dans les Paramètres.';
            } else if (errorCode === 'limit_reached' || errorMessage.includes('limite')) {
                errorMessage = '📊 ' + errorMessage;
            } else if (errorMessage.includes('API key') || errorMessage.includes('api_key') || errorMessage.includes('no_api_key')) {
                errorMessage = '❌ Clé API Claude non configurée. Contactez l\'administrateur.';
            } else if (errorMessage.includes('401') || errorMessage.includes('403')) {
                errorMessage = '🔑 Clé API invalide ou expirée. Contactez l\'administrateur.';
            } else if (errorMessage.includes('429')) {
                errorMessage = '⏰ Limite d\'utilisation atteinte. Attendez quelques minutes ou passez à un plan supérieur.';
            } else if (errorMessage.includes('timeout')) {
                errorMessage = '⏱️ Délai d\'attente dépassé. Réessayez dans quelques instants.';
            }

            setError(errorMessage);
        } finally {
            setGenerating(false);
        }
    };

    const copyToClipboard = (content, hashtags, index) => {
        const fullContent = content + '\n\n' + (hashtags || []).join(' ');
        navigator.clipboard.writeText(fullContent);
        setCopiedIndex(index);
        setTimeout(() => setCopiedIndex(null), 2000);
    };

    const saveToCalendar = async (post, index) => {
        setSelectedPostForSave(index);
        // TODO: Ouvrir un modal pour choisir la date de planification
        alert('Fonctionnalité de planification à venir!');
        setTimeout(() => setSelectedPostForSave(null), 1000);
    };

    const saveToLibrary = async (post, index) => {
        try {
            await apiFetch({
                path: '/acs/v1/library/save',
                method: 'POST',
                data: {
                    content: post.content,
                    hashtags: post.hashtags,
                    platform,
                    category: contentType,
                },
            });
            alert(__('Post sauvegardé dans votre bibliothèque!', 'ai-content-studio'));
        } catch (err) {
            alert(__('Erreur lors de la sauvegarde', 'ai-content-studio'));
        }
    };

    return (
        <div>
            <div style={{ marginBottom: 'var(--acs-spacing-4)' }}>
                <h1 className="acs-page-title">{__('Générateur de Posts Intelligent', 'ai-content-studio')}</h1>
                <p className="acs-text-muted">
                    {__('Créez du contenu engageant avec des templates professionnels adaptés à vos objectifs', 'ai-content-studio')}
                </p>
            </div>

            {/* Generator Form */}
            <div className="acs-card">
                <div className="acs-card-header">
                    <h3 className="acs-card-title">{__('Paramètres de génération', 'ai-content-studio')}</h3>
                </div>

                {/* Plateforme - Toutes les 8 plateformes */}
                <div className="acs-form-group">
                    <label className="acs-form-label">{__('Plateforme', 'ai-content-studio')}</label>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(110px, 1fr))', gap: 'var(--acs-spacing-3)' }}>
                        {Object.keys(PLATFORM_SPECS).map((p) => {
                            const spec = PLATFORM_SPECS[p];
                            return (
                                <div
                                    key={p}
                                    onClick={() => setPlatform(p)}
                                    style={{
                                        padding: 'var(--acs-spacing-3)',
                                        border: `2px solid ${platform === p ? spec.color : 'var(--acs-gray-200)'}`,
                                        borderRadius: 'var(--acs-radius-lg)',
                                        textAlign: 'center',
                                        cursor: 'pointer',
                                        transition: 'var(--acs-transition)',
                                        background: platform === p ? `${spec.color}10` : 'var(--acs-white)',
                                    }}
                                >
                                    <div style={{ fontSize: '1.5rem', marginBottom: 'var(--acs-spacing-1)' }}>
                                        {spec.emoji}
                                    </div>
                                    <div style={{ fontSize: 'var(--acs-font-size-sm)', fontWeight: 600, textTransform: 'capitalize' }}>
                                        {p === 'linkedin' ? 'LinkedIn' : p.charAt(0).toUpperCase() + p.slice(1)}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>

                {/* Type de contenu */}
                <div className="acs-form-group">
                    <label className="acs-form-label">{__('Type de contenu', 'ai-content-studio')}</label>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 'var(--acs-spacing-3)' }}>
                        {Object.values(CONTENT_TYPES).map((type) => (
                            <div
                                key={type.id}
                                onClick={() => {
                                    setContentType(type.id);
                                    setTemplate(''); // Reset template
                                }}
                                style={{
                                    padding: 'var(--acs-spacing-3)',
                                    border: `2px solid ${contentType === type.id ? type.color : 'var(--acs-gray-200)'}`,
                                    borderRadius: 'var(--acs-radius-lg)',
                                    cursor: 'pointer',
                                    transition: 'var(--acs-transition)',
                                    background: contentType === type.id ? `${type.color}15` : 'var(--acs-white)',
                                }}
                            >
                                <div style={{ display: 'flex', alignItems: 'center', gap: 'var(--acs-spacing-2)', marginBottom: 'var(--acs-spacing-1)' }}>
                                    <span style={{ fontSize: '1.25rem' }}>{type.icon}</span>
                                    <span style={{ fontWeight: 600, fontSize: 'var(--acs-font-size-base)' }}>{type.label}</span>
                                </div>
                                <p style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)', margin: 0 }}>{type.description}</p>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Template */}
                {availableTemplates.length > 0 && (
                    <div className="acs-form-group">
                        <label className="acs-form-label">{__('Template', 'ai-content-studio')}</label>
                        <select className="acs-select" value={template} onChange={(e) => setTemplate(e.target.value)}>
                            <option value="">{__('-- Sélectionner un template --', 'ai-content-studio')}</option>
                            {availableTemplates.map((t) => (
                                <option key={t.id} value={t.id}>
                                    {t.name} - {t.description}
                                </option>
                            ))}
                        </select>

                        {/* Info template sélectionné */}
                        {selectedTemplate && (
                            <div
                                style={{
                                    marginTop: 'var(--acs-spacing-3)',
                                    padding: 'var(--acs-spacing-3)',
                                    background: 'var(--acs-gray-50)',
                                    borderRadius: 'var(--acs-radius)',
                                    border: '1px solid var(--acs-gray-200)',
                                }}
                            >
                                <div style={{ fontWeight: 600, marginBottom: 'var(--acs-spacing-2)', color: 'var(--acs-gray-900)' }}>
                                    Structure du template :
                                </div>
                                <ul style={{ margin: 0, paddingLeft: 'var(--acs-spacing-4)', color: 'var(--acs-gray-700)' }}>
                                    {selectedTemplate.structure.map((item, i) => (
                                        <li key={i} style={{ fontSize: 'var(--acs-font-size-sm)', marginBottom: 'var(--acs-spacing-1)' }}>
                                            {item}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </div>
                )}

                {/* Sujet */}
                <div className="acs-form-group">
                    <label className="acs-form-label">{__('Sujet du post', 'ai-content-studio')}</label>
                    <textarea
                        className="acs-textarea"
                        placeholder={__('Exemple: 5 astuces pour améliorer sa productivité au travail', 'ai-content-studio')}
                        value={topic}
                        onChange={(e) => setTopic(e.target.value)}
                        rows={3}
                    />
                    <div style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)', marginTop: 'var(--acs-spacing-1)' }}>
                        💡 {__('Plus votre sujet est précis, meilleur sera le résultat', 'ai-content-studio')}
                    </div>
                </div>

                {/* Ton et Language */}
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 'var(--acs-spacing-4)' }}>
                    <div className="acs-form-group">
                        <label className="acs-form-label">{__('Ton de voix', 'ai-content-studio')}</label>
                        <select className="acs-select" value={tone} onChange={(e) => setTone(e.target.value)}>
                            <option value="professional">{__('Professionnel', 'ai-content-studio')}</option>
                            <option value="casual">{__('Décontracté', 'ai-content-studio')}</option>
                            <option value="enthusiastic">{__('Enthousiaste', 'ai-content-studio')}</option>
                            <option value="friendly">{__('Amical', 'ai-content-studio')}</option>
                            <option value="inspiring">{__('Inspirant', 'ai-content-studio')}</option>
                            <option value="educational">{__('Éducatif', 'ai-content-studio')}</option>
                        </select>
                    </div>

                    <div className="acs-form-group">
                        <label className="acs-form-label">{__('Langue', 'ai-content-studio')}</label>
                        <select className="acs-select" value={language} onChange={(e) => setLanguage(e.target.value)}>
                            <optgroup label="🌍 Europe">
                                <option value="en">🇬🇧 English</option>
                                <option value="fr">🇫🇷 Français</option>
                                <option value="es">🇪🇸 Español</option>
                                <option value="de">🇩🇪 Deutsch</option>
                                <option value="it">🇮🇹 Italiano</option>
                                <option value="pt">🇵🇹 Português</option>
                                <option value="ru">🇷🇺 Русский</option>
                                <option value="pl">🇵🇱 Polski</option>
                                <option value="nl">🇳🇱 Nederlands</option>
                                <option value="tr">🇹🇷 Türkçe</option>
                                <option value="uk">🇺🇦 Українська</option>
                                <option value="el">🇬🇷 Ελληνικά</option>
                            </optgroup>
                            <optgroup label="🌏 Asie">
                                <option value="zh">🇨🇳 中文 (Mandarin)</option>
                                <option value="hi">🇮🇳 हिन्दी (Hindi)</option>
                                <option value="ar">🇸🇦 العربية (Arabic)</option>
                                <option value="ja">🇯🇵 日本語 (Japanese)</option>
                                <option value="ko">🇰🇷 한국어 (Korean)</option>
                                <option value="vi">🇻🇳 Tiếng Việt (Vietnamese)</option>
                                <option value="id">🇮🇩 Bahasa Indonesia</option>
                                <option value="th">🇹🇭 ไทย (Thai)</option>
                            </optgroup>
                            <optgroup label="🌎 Amériques">
                                <option value="pt-br">🇧🇷 Português (Brasil)</option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                {/* Platform specs info - Amélioré */}
                {platformSpec && (
                    <div
                        style={{
                            padding: 'var(--acs-spacing-4)',
                            background: 'linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05))',
                            borderRadius: 'var(--acs-radius-lg)',
                            border: '1px solid rgba(99, 102, 241, 0.2)',
                            marginBottom: 'var(--acs-spacing-4)',
                        }}
                    >
                        <div style={{ display: 'flex', alignItems: 'center', gap: 'var(--acs-spacing-3)' }}>
                            <div style={{ fontSize: '2rem' }}>{platformSpec.emoji}</div>
                            <div style={{ flex: 1 }}>
                                <div style={{ fontWeight: 600, marginBottom: 'var(--acs-spacing-1)', color: 'var(--acs-gray-900)' }}>
                                    Spécifications {platform.charAt(0).toUpperCase() + platform.slice(1)}
                                </div>
                                <div style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-700)' }}>
                                    📏 Longueur optimale: <strong>{platformSpec.optimal_length}</strong>
                                    {' '}• Maximum: <strong>{platformSpec.max_length} caractères</strong>
                                </div>
                                <div style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)', marginTop: 'var(--acs-spacing-1)' }}>
                                    ✨ {platformSpec.features.join(' • ')}
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {error && <div className="acs-alert acs-alert-danger">{error}</div>}

                <button
                    className="acs-btn acs-btn-primary acs-btn-lg w-100"
                    onClick={handleGenerate}
                    disabled={generating || !topic.trim() || !template}
                >
                    {generating ? (
                        <>
                            <div className="acs-spinner" style={{ width: '20px', height: '20px', borderWidth: '2px' }} />
                            {__('Génération en cours...', 'ai-content-studio')}
                        </>
                    ) : (
                        <>
                            <FiZap /> {__('Générer 3 variantes', 'ai-content-studio')}
                        </>
                    )}
                </button>
            </div>

            {/* Generated Posts - Design amélioré */}
            {generatedPosts.length > 0 && (
                <div ref={generatedPostsRef} style={{ marginTop: 'var(--acs-spacing-5)' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 'var(--acs-spacing-4)' }}>
                        <div>
                            <h2 className="acs-card-title" style={{ marginBottom: 'var(--acs-spacing-1)' }}>
                                {__('Posts générés', 'ai-content-studio')} ✨
                            </h2>
                            <p style={{ fontSize: 'var(--acs-font-size-sm)', color: 'var(--acs-gray-600)', margin: 0 }}>
                                Sélectionnez le post qui correspond le mieux à votre style
                            </p>
                        </div>
                        <span className="acs-badge acs-badge-success">
                            {generatedPosts.length} variantes
                        </span>
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(380px, 1fr))', gap: 'var(--acs-spacing-4)' }}>
                        {generatedPosts.map((post, index) => {
                            const fullContent = post.content + '\n\n' + (post.hashtags || []).join(' ');
                            const charCount = fullContent.length;
                            const isOver = isOverLimit(post.content, post.hashtags);
                            const percentage = getLimitPercentage(post.content, post.hashtags);

                            return (
                                <div
                                    key={post.id || index}
                                    style={{
                                        background: 'var(--acs-white)',
                                        borderRadius: 'var(--acs-radius-lg)',
                                        padding: 'var(--acs-spacing-4)',
                                        boxShadow: 'var(--acs-shadow)',
                                        border: `2px solid ${isOver ? 'var(--acs-danger)' : 'var(--acs-gray-100)'}`,
                                        transition: 'var(--acs-transition)',
                                    }}
                                >
                                    {/* En-tête du post */}
                                    <div style={{
                                        display: 'flex',
                                        justifyContent: 'space-between',
                                        alignItems: 'center',
                                        marginBottom: 'var(--acs-spacing-3)',
                                        paddingBottom: 'var(--acs-spacing-3)',
                                        borderBottom: '1px solid var(--acs-gray-100)',
                                    }}>
                                        <span style={{
                                            background: 'linear-gradient(135deg, var(--acs-primary), var(--acs-secondary))',
                                            color: 'var(--acs-white)',
                                            padding: '4px 12px',
                                            borderRadius: '12px',
                                            fontSize: 'var(--acs-font-size-sm)',
                                            fontWeight: 600,
                                        }}>
                                            {__('Variante', 'ai-content-studio')} {index + 1}
                                        </span>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 'var(--acs-spacing-2)' }}>
                                            {isOver ? (
                                                <FiAlertTriangle style={{ color: 'var(--acs-danger)', fontSize: '1.25rem' }} />
                                            ) : (
                                                <FiCheckCircle style={{ color: 'var(--acs-success)', fontSize: '1.25rem' }} />
                                            )}
                                            <span style={{
                                                fontSize: 'var(--acs-font-size-sm)',
                                                color: isOver ? 'var(--acs-danger)' : 'var(--acs-gray-600)',
                                                fontWeight: isOver ? 600 : 400,
                                            }}>
                                                {charCount} / {platformSpec.max_length}
                                            </span>
                                        </div>
                                    </div>

                                    {/* Barre de progression de la limite */}
                                    <div style={{ marginBottom: 'var(--acs-spacing-3)' }}>
                                        <div className="acs-progress" style={{ height: '6px' }}>
                                            <div
                                                className={`acs-progress-bar ${isOver ? 'danger' : percentage > 80 ? 'warning' : ''}`}
                                                style={{ width: `${percentage}%` }}
                                            />
                                        </div>
                                        {isOver && (
                                            <div style={{
                                                fontSize: 'var(--acs-font-size-sm)',
                                                color: 'var(--acs-danger)',
                                                marginTop: 'var(--acs-spacing-1)',
                                                fontWeight: 500,
                                            }}>
                                                ⚠️ Ce post dépasse la limite de {platformSpec.max_length} caractères pour {platform}
                                            </div>
                                        )}
                                    </div>

                                    {/* Contenu du post */}
                                    <div style={{
                                        background: 'var(--acs-gray-50)',
                                        padding: 'var(--acs-spacing-4)',
                                        borderRadius: 'var(--acs-radius)',
                                        marginBottom: 'var(--acs-spacing-3)',
                                        lineHeight: 1.7,
                                        color: 'var(--acs-gray-800)',
                                        borderLeft: `3px solid ${platformSpec.color}`,
                                        whiteSpace: 'pre-wrap',
                                        wordWrap: 'break-word',
                                    }}>
                                        {post.content}
                                    </div>

                                    {/* Hashtags */}
                                    {post.hashtags && post.hashtags.length > 0 && (
                                        <div style={{
                                            display: 'flex',
                                            flexWrap: 'wrap',
                                            gap: 'var(--acs-spacing-2)',
                                            marginBottom: 'var(--acs-spacing-3)',
                                        }}>
                                            {post.hashtags.map((tag, i) => (
                                                <span
                                                    key={i}
                                                    style={{
                                                        background: `${platformSpec.color}15`,
                                                        color: platformSpec.color,
                                                        padding: '4px 10px',
                                                        borderRadius: '12px',
                                                        fontSize: 'var(--acs-font-size-sm)',
                                                        fontWeight: 500,
                                                    }}
                                                >
                                                    {tag}
                                                </span>
                                            ))}
                                        </div>
                                    )}

                                    {/* Boutons d'action */}
                                    <div style={{
                                        display: 'grid',
                                        gridTemplateColumns: '1fr 1fr',
                                        gap: 'var(--acs-spacing-2)',
                                        marginBottom: 'var(--acs-spacing-2)',
                                    }}>
                                        <button
                                            className="acs-btn acs-btn-outline-primary acs-btn-sm"
                                            onClick={() => copyToClipboard(post.content, post.hashtags, index)}
                                        >
                                            {copiedIndex === index ? (
                                                <>
                                                    <FiCheck /> {__('Copié!', 'ai-content-studio')}
                                                </>
                                            ) : (
                                                <>
                                                    <FiCopy /> {__('Copier', 'ai-content-studio')}
                                                </>
                                            )}
                                        </button>
                                        <button
                                            className="acs-btn acs-btn-outline-primary acs-btn-sm"
                                            onClick={() => saveToLibrary(post, index)}
                                        >
                                            <FiHeart /> {__('Favoris', 'ai-content-studio')}
                                        </button>
                                    </div>
                                    <button
                                        className="acs-btn acs-btn-primary acs-btn-sm w-100"
                                        onClick={() => saveToCalendar(post, index)}
                                    >
                                        <FiCalendar /> {__('Planifier ce post', 'ai-content-studio')}
                                    </button>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* Empty State */}
            {!generating && generatedPosts.length === 0 && (
                <div className="acs-card" style={{ marginTop: 'var(--acs-spacing-4)', textAlign: 'center', padding: '3rem 2rem' }}>
                    <FiZap size={48} style={{ color: 'var(--acs-gray-300)', marginBottom: '1rem' }} />
                    <h3 style={{ marginBottom: 'var(--acs-spacing-2)' }}>{__('Prêt à créer du contenu ?', 'ai-content-studio')}</h3>
                    <p className="acs-text-muted">
                        {__('Choisissez un type de contenu, un template et un sujet pour générer des posts professionnels', 'ai-content-studio')}
                    </p>
                </div>
            )}
        </div>
    );
}
