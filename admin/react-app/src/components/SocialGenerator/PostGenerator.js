import { useState } from '@wordpress/element';
import { FiZap, FiCopy, FiCheck } from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

export default function PostGenerator({ profile }) {
    const [platform, setPlatform] = useState('instagram');
    const [topic, setTopic] = useState('');
    const [tone, setTone] = useState('professional');
    const [language, setLanguage] = useState('fr');
    const [generating, setGenerating] = useState(false);
    const [generatedPosts, setGeneratedPosts] = useState([]);
    const [error, setError] = useState('');
    const [copiedIndex, setCopiedIndex] = useState(null);

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
                data: { platform, topic, tone, language },
            });

            if (response.success) {
                setGeneratedPosts(response.data);
            } else {
                setError(response.error?.message || __('Erreur lors de la génération', 'ai-content-studio'));
            }
        } catch (err) {
            setError(err.message || __('Erreur lors de la génération', 'ai-content-studio'));
        } finally {
            setGenerating(false);
        }
    };

    const copyToClipboard = (content, index) => {
        navigator.clipboard.writeText(content);
        setCopiedIndex(index);
        setTimeout(() => setCopiedIndex(null), 2000);
    };

    return (
        <div>
            <h1 className="acs-page-title">{__('Générateur de Posts', 'ai-content-studio')}</h1>

            {/* Generator Form */}
            <div className="acs-card">
                <div className="acs-card-header">
                    <h3 className="acs-card-title">{__('Créer un nouveau post', 'ai-content-studio')}</h3>
                </div>

                <div className="acs-form-group">
                    <label className="acs-form-label">{__('Plateforme', 'ai-content-studio')}</label>
                    <select className="acs-select" value={platform} onChange={(e) => setPlatform(e.target.value)}>
                        <option value="instagram">Instagram</option>
                        <option value="facebook">Facebook</option>
                        <option value="linkedin">LinkedIn</option>
                        <option value="twitter">X (Twitter)</option>
                    </select>
                </div>

                <div className="acs-form-group">
                    <label className="acs-form-label">{__('Sujet du post', 'ai-content-studio')}</label>
                    <textarea
                        className="acs-textarea"
                        placeholder={__('De quoi voulez-vous parler ?', 'ai-content-studio')}
                        value={topic}
                        onChange={(e) => setTopic(e.target.value)}
                        rows={4}
                    />
                </div>

                <div className="acs-form-group">
                    <label className="acs-form-label">{__('Ton de voix', 'ai-content-studio')}</label>
                    <select className="acs-select" value={tone} onChange={(e) => setTone(e.target.value)}>
                        <option value="professional">{__('Professionnel', 'ai-content-studio')}</option>
                        <option value="casual">{__('Décontracté', 'ai-content-studio')}</option>
                        <option value="enthusiastic">{__('Enthousiaste', 'ai-content-studio')}</option>
                        <option value="friendly">{__('Amical', 'ai-content-studio')}</option>
                    </select>
                </div>

                {error && (
                    <div className="acs-alert acs-alert-danger">
                        {error}
                    </div>
                )}

                <button
                    className="acs-btn acs-btn-primary acs-btn-lg w-100"
                    onClick={handleGenerate}
                    disabled={generating || !topic.trim()}
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

            {/* Generated Posts */}
            {generatedPosts.length > 0 && (
                <div style={{ marginTop: 'var(--acs-spacing-5)' }}>
                    <h2 className="acs-card-title mb-4">{__('Posts générés', 'ai-content-studio')}</h2>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(350px, 1fr))', gap: 'var(--acs-spacing-4)' }}>
                        {generatedPosts.map((post, index) => (
                            <div key={post.id || index} className="acs-post-card">
                                <div className="acs-post-header">
                                    <span className="acs-badge acs-badge-primary">
                                        {__('Variante', 'ai-content-studio')} {index + 1}
                                    </span>
                                </div>

                                <div className="acs-post-content">
                                    {post.content}
                                </div>

                                {post.hashtags && post.hashtags.length > 0 && (
                                    <div className="acs-hashtags">
                                        {post.hashtags.map((tag, i) => (
                                            <span key={i} className="acs-hashtag">
                                                {tag}
                                            </span>
                                        ))}
                                    </div>
                                )}

                                <div style={{ marginTop: 'var(--acs-spacing-3)', display: 'flex', gap: 'var(--acs-spacing-2)' }}>
                                    <button
                                        className="acs-btn acs-btn-outline-primary acs-btn-sm"
                                        onClick={() => copyToClipboard(post.content + '\n\n' + (post.hashtags || []).join(' '), index)}
                                        style={{ flex: 1 }}
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
                                    <button className="acs-btn acs-btn-primary acs-btn-sm" style={{ flex: 1 }}>
                                        {__('Utiliser', 'ai-content-studio')}
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Empty State */}
            {!generating && generatedPosts.length === 0 && (
                <div className="acs-card" style={{ marginTop: 'var(--acs-spacing-4)', textAlign: 'center', padding: '3rem 2rem' }}>
                    <FiZap size={48} style={{ color: 'var(--acs-gray-300)', marginBottom: '1rem' }} />
                    <p className="acs-text-muted">
                        {__('Remplissez le formulaire et cliquez sur Générer pour créer vos posts', 'ai-content-studio')}
                    </p>
                </div>
            )}
        </div>
    );
}
