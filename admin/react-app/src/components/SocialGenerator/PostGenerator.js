import { useState } from '@wordpress/element';
import { FiZap, FiCopy, FiCheck } from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import { useTranslation } from '../../contexts/TranslationContext';

export default function PostGenerator({ profile }) {
    const { t } = useTranslation();
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
            setError(t('Veuillez entrer un sujet'));
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
                setError(response.error?.message || t('Erreur lors de la génération'));
            }
        } catch (err) {
            setError(err.message || t('Erreur lors de la génération'));
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
            <h1 className="acs-page-title">{t('Générateur de Posts')}</h1>

            {/* Generator Form */}
            <div className="acs-card">
                <div className="acs-card-header">
                    <h3 className="acs-card-title">{t('Créer un nouveau post')}</h3>
                </div>

                <div className="acs-form-group">
                    <label className="acs-form-label">{t('Plateforme')}</label>
                    <select className="acs-select" value={platform} onChange={(e) => setPlatform(e.target.value)}>
                        <option value="instagram">Instagram</option>
                        <option value="facebook">Facebook</option>
                        <option value="linkedin">LinkedIn</option>
                        <option value="twitter">X (Twitter)</option>
                    </select>
                </div>

                <div className="acs-form-group">
                    <label className="acs-form-label">{t('Sujet du post')}</label>
                    <textarea
                        className="acs-textarea"
                        placeholder={t('De quoi voulez-vous parler ?')}
                        value={topic}
                        onChange={(e) => setTopic(e.target.value)}
                        rows={4}
                    />
                </div>

                <div className="acs-form-group">
                    <label className="acs-form-label">{t('Ton de voix')}</label>
                    <select className="acs-select" value={tone} onChange={(e) => setTone(e.target.value)}>
                        <option value="professional">{t('Professionnel')}</option>
                        <option value="casual">{t('Décontracté')}</option>
                        <option value="enthusiastic">{t('Enthousiaste')}</option>
                        <option value="friendly">{t('Amical')}</option>
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
                            {t('Génération en cours...')}
                        </>
                    ) : (
                        <>
                            <FiZap /> {t('Générer 3 variantes')}
                        </>
                    )}
                </button>
            </div>

            {/* Generated Posts */}
            {generatedPosts.length > 0 && (
                <div style={{ marginTop: 'var(--acs-spacing-5)' }}>
                    <h2 className="acs-card-title mb-4">{t('Posts générés')}</h2>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(350px, 1fr))', gap: 'var(--acs-spacing-4)' }}>
                        {generatedPosts.map((post, index) => (
                            <div key={post.id || index} className="acs-post-card">
                                <div className="acs-post-header">
                                    <span className="acs-badge acs-badge-primary">
                                        {t('Variante')} {index + 1}
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
                                                <FiCheck /> {t('Copié!')}
                                            </>
                                        ) : (
                                            <>
                                                <FiCopy /> {t('Copier')}
                                            </>
                                        )}
                                    </button>
                                    <button className="acs-btn acs-btn-primary acs-btn-sm" style={{ flex: 1 }}>
                                        {t('Utiliser')}
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
                        {t('Remplissez le formulaire et cliquez sur Générer pour créer vos posts')}
                    </p>
                </div>
            )}
        </div>
    );
}
