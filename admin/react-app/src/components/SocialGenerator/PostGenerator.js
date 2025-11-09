import { useState } from '@wordpress/element';
import { motion, AnimatePresence } from 'framer-motion';
import { useNavigate } from 'react-router-dom';
import {
    FiArrowLeft, FiZap, FiCopy, FiDownload, FiHeart,
    FiMessageCircle, FiShare2, FiCheck
} from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

const platformIcons = {
    instagram: '📸',
    facebook: '📘',
    linkedin: '💼',
    twitter: '𝕏',
    tiktok: '🎵',
    youtube: '📹'
};

export default function PostGenerator({ profile }) {
    const navigate = useNavigate();
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
                data: {
                    platform,
                    topic,
                    tone,
                    language,
                },
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
        <div className="acs-post-generator">
            {/* Header */}
            <motion.div
                initial={{ opacity: 0, y: -20 }}
                animate={{ opacity: 1, y: 0 }}
                style={{ marginBottom: '2rem' }}
            >
                <button
                    className="acs-btn acs-btn-ghost acs-btn-sm"
                    onClick={() => navigate('/')}
                    style={{ marginBottom: '1rem' }}
                >
                    <FiArrowLeft /> {__('Retour', 'ai-content-studio')}
                </button>
                <h1 className="acs-heading-xl">
                    ✨ {__('Générateur de Posts', 'ai-content-studio')}
                </h1>
                <p className="acs-text-body">
                    {__('Créez du contenu engageant pour vos réseaux sociaux en quelques secondes', 'ai-content-studio')}
                </p>
            </motion.div>

            {/* Generator Form */}
            <motion.div
                className="acs-generator-form"
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.1 }}
            >
                {/* Platform Selection */}
                <div className="acs-form-section">
                    <label className="acs-text-sm" style={{ fontWeight: 600, marginBottom: '0.75rem', display: 'block' }}>
                        🌐 {__('Plateforme', 'ai-content-studio')}
                    </label>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(140px, 1fr))', gap: '0.75rem' }}>
                        {[
                            { value: 'instagram', label: 'Instagram', icon: '📸' },
                            { value: 'facebook', label: 'Facebook', icon: '📘' },
                            { value: 'linkedin', label: 'LinkedIn', icon: '💼' },
                            { value: 'twitter', label: 'X (Twitter)', icon: '𝕏' },
                        ].map((p) => (
                            <motion.button
                                key={p.value}
                                className={`acs-checkbox-card ${platform === p.value ? 'checked' : ''}`}
                                onClick={() => setPlatform(p.value)}
                                whileHover={{ scale: 1.02 }}
                                whileTap={{ scale: 0.98 }}
                                style={{ cursor: 'pointer', textAlign: 'center' }}
                            >
                                <div style={{ fontSize: '1.5rem', marginBottom: '0.25rem' }}>{p.icon}</div>
                                <div style={{ fontWeight: 600, fontSize: '0.875rem' }}>{p.label}</div>
                            </motion.button>
                        ))}
                    </div>
                </div>

                {/* Topic Input */}
                <div className="acs-form-section">
                    <label className="acs-text-sm" style={{ fontWeight: 600, marginBottom: '0.5rem', display: 'block' }}>
                        💡 {__('De quoi voulez-vous parler?', 'ai-content-studio')}
                    </label>
                    <textarea
                        className="acs-textarea"
                        placeholder={__('Ex: Lancement de notre nouvelle collection été 2025, conseils pour améliorer la productivité, recette de smoothie détox...', 'ai-content-studio')}
                        value={topic}
                        onChange={(e) => setTopic(e.target.value)}
                        rows={4}
                        style={{ minHeight: '120px' }}
                    />
                </div>

                {/* Tone Selection */}
                <div className="acs-form-section">
                    <label className="acs-text-sm" style={{ fontWeight: 600, marginBottom: '0.75rem', display: 'block' }}>
                        🎭 {__('Ton de voix', 'ai-content-studio')}
                    </label>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(140px, 1fr))', gap: '0.75rem' }}>
                        {[
                            { value: 'professional', label: __('Professionnel', 'ai-content-studio'), icon: '💼' },
                            { value: 'casual', label: __('Décontracté', 'ai-content-studio'), icon: '😊' },
                            { value: 'enthusiastic', label: __('Enthousiaste', 'ai-content-studio'), icon: '🎉' },
                            { value: 'friendly', label: __('Amical', 'ai-content-studio'), icon: '🤝' },
                        ].map((t) => (
                            <motion.button
                                key={t.value}
                                className={`acs-checkbox-card ${tone === t.value ? 'checked' : ''}`}
                                onClick={() => setTone(t.value)}
                                whileHover={{ scale: 1.02 }}
                                whileTap={{ scale: 0.98 }}
                                style={{ cursor: 'pointer', padding: '0.75rem' }}
                            >
                                <div style={{ fontSize: '1.25rem', marginBottom: '0.25rem' }}>{t.icon}</div>
                                <div style={{ fontWeight: 600, fontSize: '0.875rem' }}>{t.label}</div>
                            </motion.button>
                        ))}
                    </div>
                </div>

                {/* Error Message */}
                <AnimatePresence>
                    {error && (
                        <motion.div
                            className="acs-alert acs-alert-error"
                            initial={{ opacity: 0, height: 0 }}
                            animate={{ opacity: 1, height: 'auto' }}
                            exit={{ opacity: 0, height: 0 }}
                        >
                            <span className="acs-alert-icon">⚠️</span>
                            <div>{error}</div>
                        </motion.div>
                    )}
                </AnimatePresence>

                {/* Generate Button */}
                <motion.button
                    className="acs-btn acs-btn-primary acs-btn-lg"
                    style={{ width: '100%', marginTop: '1rem' }}
                    onClick={handleGenerate}
                    disabled={generating || !topic.trim()}
                    whileHover={{ scale: generating ? 1 : 1.02 }}
                    whileTap={{ scale: generating ? 1 : 0.98 }}
                >
                    {generating ? (
                        <>
                            <div className="acs-spinner" style={{ width: '20px', height: '20px', borderWidth: '2px' }} />
                            {__('Génération en cours...', 'ai-content-studio')}
                        </>
                    ) : (
                        <>
                            <FiZap /> {__('Générer 3 variantes magiques', 'ai-content-studio')}
                        </>
                    )}
                </motion.button>
            </motion.div>

            {/* Generated Posts */}
            <AnimatePresence>
                {generatedPosts.length > 0 && (
                    <motion.div
                        className="acs-generated-posts"
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.2 }}
                    >
                        <h2 className="acs-heading-lg" style={{ marginBottom: '1.5rem' }}>
                            🎨 {__('Vos posts générés', 'ai-content-studio')}
                        </h2>
                        <div className="acs-posts-grid">
                            {generatedPosts.map((post, index) => (
                                <motion.div
                                    key={post.id || index}
                                    className="acs-post-card"
                                    initial={{ opacity: 0, scale: 0.9 }}
                                    animate={{ opacity: 1, scale: 1 }}
                                    transition={{ delay: index * 0.1 }}
                                >
                                    {/* Post Header */}
                                    <div className="acs-post-header">
                                        <div className="acs-variant-badge">
                                            {platformIcons[platform]} {__('Variante', 'ai-content-studio')} {index + 1}
                                        </div>
                                        <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center', color: 'var(--acs-gray-400)' }}>
                                            <FiHeart size={18} />
                                            <FiMessageCircle size={18} />
                                            <FiShare2 size={18} />
                                        </div>
                                    </div>

                                    {/* Post Content */}
                                    <div className="acs-post-content">
                                        {post.content}
                                    </div>

                                    {/* Hashtags */}
                                    {post.hashtags && post.hashtags.length > 0 && (
                                        <div className="acs-hashtags">
                                            {post.hashtags.map((tag, i) => (
                                                <motion.span
                                                    key={i}
                                                    className="acs-hashtag"
                                                    initial={{ opacity: 0, scale: 0.8 }}
                                                    animate={{ opacity: 1, scale: 1 }}
                                                    transition={{ delay: 0.3 + (i * 0.05) }}
                                                    whileHover={{ scale: 1.05 }}
                                                >
                                                    {tag}
                                                </motion.span>
                                            ))}
                                        </div>
                                    )}

                                    {/* Actions */}
                                    <div className="acs-post-actions">
                                        <motion.button
                                            className="acs-btn acs-btn-ghost acs-btn-sm"
                                            onClick={() => copyToClipboard(post.content + '\n\n' + (post.hashtags || []).join(' '), index)}
                                            whileHover={{ scale: 1.05 }}
                                            whileTap={{ scale: 0.95 }}
                                            style={{ flex: 1 }}
                                        >
                                            {copiedIndex === index ? (
                                                <>
                                                    <FiCheck style={{ color: 'var(--acs-success)' }} />
                                                    {__('Copié!', 'ai-content-studio')}
                                                </>
                                            ) : (
                                                <>
                                                    <FiCopy /> {__('Copier', 'ai-content-studio')}
                                                </>
                                            )}
                                        </motion.button>
                                        <motion.button
                                            className="acs-btn acs-btn-primary acs-btn-sm"
                                            whileHover={{ scale: 1.05 }}
                                            whileTap={{ scale: 0.95 }}
                                            style={{ flex: 1 }}
                                        >
                                            <FiDownload /> {__('Utiliser', 'ai-content-studio')}
                                        </motion.button>
                                    </div>
                                </motion.div>
                            ))}
                        </div>

                        {/* Generate More Button */}
                        <motion.div
                            style={{ textAlign: 'center', marginTop: '2rem' }}
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            transition={{ delay: 0.5 }}
                        >
                            <button
                                className="acs-btn acs-btn-ghost"
                                onClick={handleGenerate}
                                disabled={generating}
                            >
                                <FiZap /> {__('Générer 3 nouvelles variantes', 'ai-content-studio')}
                            </button>
                        </motion.div>
                    </motion.div>
                )}
            </AnimatePresence>

            {/* Empty State */}
            {!generating && generatedPosts.length === 0 && (
                <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ delay: 0.3 }}
                    style={{
                        textAlign: 'center',
                        padding: '4rem 2rem',
                        color: 'var(--acs-gray-400)',
                        marginTop: '2rem'
                    }}
                >
                    <FiZap style={{ fontSize: '4rem', marginBottom: '1rem', opacity: 0.3 }} />
                    <p className="acs-text-body">
                        {__('Remplissez le formulaire ci-dessus et cliquez sur "Générer" pour créer votre premier post!', 'ai-content-studio')}
                    </p>
                </motion.div>
            )}
        </div>
    );
}
