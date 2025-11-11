import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { FiFileText, FiEdit, FiTrash2, FiEye, FiCalendar, FiTag, FiGlobe } from 'react-icons/fi';

export default function BlogLibrary() {
    const [articles, setArticles] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selectedArticle, setSelectedArticle] = useState(null);
    const [showModal, setShowModal] = useState(false);

    // Charger les articles
    useEffect(() => {
        loadArticles();
    }, []);

    const loadArticles = async () => {
        setLoading(true);
        try {
            const response = await apiFetch({
                path: '/acs/v1/blog/articles',
                method: 'GET',
            });

            if (response.success) {
                setArticles(response.data);
            }
        } catch (error) {
            console.error('Error loading articles:', error);
        } finally {
            setLoading(false);
        }
    };

    // Supprimer un article
    const deleteArticle = async (articleId) => {
        if (!confirm(__('Êtes-vous sûr de vouloir supprimer cet article ?', 'ai-content-studio'))) {
            return;
        }

        try {
            const response = await apiFetch({
                path: `/acs/v1/blog/articles/${articleId}`,
                method: 'DELETE',
            });

            if (response.success) {
                loadArticles(); // Recharger la liste
            } else {
                alert(response.message || __('Erreur lors de la suppression', 'ai-content-studio'));
            }
        } catch (error) {
            console.error('Error deleting article:', error);
            alert(__('Erreur lors de la suppression', 'ai-content-studio'));
        }
    };

    // Afficher un article
    const viewArticle = (article) => {
        setSelectedArticle(article);
        setShowModal(true);
    };

    // Formater le contenu pour l'affichage
    const formatContent = (content) => {
        if (!content) return '';

        let formatted = content;

        // H2
        formatted = formatted.replace(/^## (.+)$/gm, '<h2>$1</h2>');

        // H3
        formatted = formatted.replace(/^### (.+)$/gm, '<h3>$1</h3>');

        // Line breaks
        formatted = formatted.replace(/\n/g, '<br />');

        return formatted;
    };

    // Map des langues - Complet
    const languageNames = {
        fr: 'Français', en: 'English', es: 'Español', pt: 'Português',
        de: 'Deutsch', it: 'Italiano', zh: '中文 (Mandarin)', ja: '日本語',
        ko: '한국어', ar: 'العربية', ru: 'Русский', hi: 'हिन्दी',
        bn: 'বাংলা', id: 'Bahasa Indonesia', tr: 'Türkçe', vi: 'Tiếng Việt',
        pl: 'Polski', uk: 'Українська', nl: 'Nederlands', th: 'ไทย',
        sv: 'Svenska', el: 'Ελληνικά', cs: 'Čeština', ro: 'Română',
        hu: 'Magyar', da: 'Dansk', fi: 'Suomi', no: 'Norsk',
        he: 'עברית', ca: 'Català'
    };

    if (loading) {
        return (
            <div className="acs-content">
                <div className="acs-loading-overlay">
                    <div className="acs-spinner"></div>
                </div>
            </div>
        );
    }

    return (
        <div className="acs-blog-library">
            <div className="acs-page-header">
                <h1><FiFileText /> {__('Bibliothèque d\'Articles', 'ai-content-studio')}</h1>
                <p className="acs-text-muted">
                    {__('Retrouvez tous vos articles de blog générés', 'ai-content-studio')}
                </p>
            </div>

            {articles.length === 0 ? (
                <div className="acs-card" style={{ textAlign: 'center', padding: '4rem 2rem' }}>
                    <FiFileText size={48} style={{ color: 'var(--acs-gray-400)', marginBottom: 'var(--acs-spacing-4)' }} />
                    <h2>{__('Aucun article', 'ai-content-studio')}</h2>
                    <p className="acs-text-muted">
                        {__('Vous n\'avez pas encore généré d\'articles de blog.', 'ai-content-studio')}
                    </p>
                </div>
            ) : (
                <div className="acs-articles-grid">
                    {articles.map((article) => (
                        <div key={article.id} className="acs-article-card">
                            <div className="acs-article-card-header">
                                <h3 className="acs-article-card-title">{article.title}</h3>
                                <div className="acs-article-meta">
                                    <span className="acs-article-meta-item">
                                        <FiGlobe size={14} />
                                        {languageNames[article.language] || article.language}
                                    </span>
                                    <span className="acs-article-meta-item">
                                        <FiFileText size={14} />
                                        {article.word_count} {__('mots', 'ai-content-studio')}
                                    </span>
                                    <span className="acs-article-meta-item">
                                        <FiCalendar size={14} />
                                        {new Date(article.created_at).toLocaleDateString()}
                                    </span>
                                </div>
                            </div>

                            <div className="acs-article-card-body">
                                {article.subject && (
                                    <p className="acs-article-subject">
                                        <strong>{__('Sujet:', 'ai-content-studio')}</strong> {article.subject}
                                    </p>
                                )}

                                {article.keywords && article.keywords.length > 0 && (
                                    <div className="acs-article-keywords">
                                        <FiTag size={14} />
                                        {article.keywords.slice(0, 3).map((keyword, index) => (
                                            <span key={index} className="acs-keyword-badge">
                                                {keyword}
                                            </span>
                                        ))}
                                        {article.keywords.length > 3 && (
                                            <span className="acs-keyword-badge">
                                                +{article.keywords.length - 3}
                                            </span>
                                        )}
                                    </div>
                                )}

                                {article.seo_title && (
                                    <div className="acs-article-seo">
                                        <small className="acs-text-muted">
                                            <strong>SEO:</strong> {article.seo_title}
                                        </small>
                                    </div>
                                )}
                            </div>

                            <div className="acs-article-card-footer">
                                <button
                                    className="acs-btn acs-btn-sm acs-btn-outline-primary"
                                    onClick={() => viewArticle(article)}
                                >
                                    <FiEye /> {__('Voir', 'ai-content-studio')}
                                </button>
                                <button
                                    className="acs-btn acs-btn-sm"
                                    onClick={() => deleteArticle(article.id)}
                                    style={{ color: 'var(--acs-danger)' }}
                                >
                                    <FiTrash2 /> {__('Supprimer', 'ai-content-studio')}
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Modal pour voir l'article */}
            {showModal && selectedArticle && (
                <div className="acs-modal-overlay" onClick={() => setShowModal(false)}>
                    <div className="acs-modal acs-article-modal" onClick={(e) => e.stopPropagation()}>
                        <div className="acs-modal-header">
                            <h2>{selectedArticle.title}</h2>
                            <button
                                className="acs-modal-close"
                                onClick={() => setShowModal(false)}
                            >
                                ×
                            </button>
                        </div>

                        <div className="acs-modal-body acs-article-modal-body">
                            <div className="acs-article-meta-section">
                                <div className="acs-article-meta-row">
                                    <span><FiGlobe /> <strong>{__('Langue:', 'ai-content-studio')}</strong> {languageNames[selectedArticle.language]}</span>
                                    <span><FiFileText /> <strong>{__('Mots:', 'ai-content-studio')}</strong> {selectedArticle.word_count}</span>
                                    <span><FiCalendar /> <strong>{__('Créé le:', 'ai-content-studio')}</strong> {new Date(selectedArticle.created_at).toLocaleString()}</span>
                                </div>

                                {selectedArticle.keywords && selectedArticle.keywords.length > 0 && (
                                    <div className="acs-article-keywords-full">
                                        <strong><FiTag /> {__('Mots-clés:', 'ai-content-studio')}</strong>
                                        {selectedArticle.keywords.map((keyword, index) => (
                                            <span key={index} className="acs-keyword-badge">
                                                {keyword}
                                            </span>
                                        ))}
                                    </div>
                                )}

                                {selectedArticle.seo_title && (
                                    <div className="acs-article-seo-section">
                                        <h4>{__('Meta SEO', 'ai-content-studio')}</h4>
                                        <p><strong>{__('Titre SEO:', 'ai-content-studio')}</strong> {selectedArticle.seo_title}</p>
                                        {selectedArticle.meta_description && (
                                            <p><strong>{__('Description:', 'ai-content-studio')}</strong> {selectedArticle.meta_description}</p>
                                        )}
                                        {selectedArticle.url_slug && (
                                            <p><strong>{__('URL:', 'ai-content-studio')}</strong> {selectedArticle.url_slug}</p>
                                        )}
                                    </div>
                                )}
                            </div>

                            <div className="acs-article-content-section">
                                <h3>{__('Contenu de l\'article', 'ai-content-studio')}</h3>
                                <div
                                    className="acs-article-content-preview"
                                    dangerouslySetInnerHTML={{ __html: formatContent(selectedArticle.content) }}
                                />
                            </div>
                        </div>

                        <div className="acs-modal-footer">
                            <button
                                className="acs-btn acs-btn-primary"
                                onClick={() => setShowModal(false)}
                            >
                                {__('Fermer', 'ai-content-studio')}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
