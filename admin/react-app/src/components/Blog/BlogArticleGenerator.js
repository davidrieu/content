import { useState, useRef, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useLocation } from 'react-router-dom';
import { FiFileText, FiEdit, FiImage, FiSave, FiLoader, FiCheckCircle } from 'react-icons/fi';
import PricingModal from '../common/PricingModal';

export default function BlogArticleGenerator() {
    const location = useLocation();
    const [subject, setSubject] = useState('');
    const [suggestedTitle, setSuggestedTitle] = useState('');
    const [keywords, setKeywords] = useState([]);
    const [mainKeyword, setMainKeyword] = useState('');
    const [firstPerson, setFirstPerson] = useState(false);
    const [length, setLength] = useState('2000-3000');
    const [language, setLanguage] = useState('fr');

    // Initialiser le sujet depuis location.state (navigation depuis BlogStrategy)
    useEffect(() => {
        if (location.state?.subject) {
            setSubject(location.state.subject);
            // Nettoyer le state pour éviter de le réutiliser
            window.history.replaceState({}, document.title);
        }
    }, [location.state]);

    const [generatingSuggestions, setGeneratingSuggestions] = useState(false);
    const [generatingArticle, setGeneratingArticle] = useState(false);
    const [articleContent, setArticleContent] = useState('');
    const [articleGenerated, setArticleGenerated] = useState(false);

    const [seoTitle, setSeoTitle] = useState('');
    const [metaDescription, setMetaDescription] = useState('');
    const [seoUrl, setSeoUrl] = useState('');
    const [generatingSeo, setGeneratingSeo] = useState(false);

    const [savingArticle, setSavingArticle] = useState(false);
    const [saved, setSaved] = useState(false);
    const [showCompletionModal, setShowCompletionModal] = useState(false);
    const [showPricingModal, setShowPricingModal] = useState(false);
    const [pricingTriggerType, setPricingTriggerType] = useState('limit_reached');

    const contentRef = useRef(null);

    // Vérifier le quota au chargement de la page
    useEffect(() => {
        const checkQuota = async () => {
            try {
                const response = await apiFetch({
                    path: '/acs/v1/subscription/usage',
                    method: 'GET',
                });

                if (response.success && response.data) {
                    const { articles } = response.data;
                    // Si limit_reached ou si utilisé >= limite
                    if (articles.limit !== -1 && articles.used >= articles.limit) {
                        setPricingTriggerType('article_limit');
                        setShowPricingModal(true);
                    }
                }
            } catch (err) {
                console.log('Could not check quota:', err);
            }
        };

        checkQuota();
    }, []); // Exécuter une seule fois au montage

    // Auto-scroll pendant la génération
    useEffect(() => {
        if (generatingArticle && contentRef.current) {
            contentRef.current.scrollTop = contentRef.current.scrollHeight;
        }
    }, [articleContent, generatingArticle]);

    // Générer le titre et les mots-clés
    const generateSuggestions = async () => {
        if (!subject.trim()) {
            alert(__('Veuillez entrer un sujet', 'ai-content-studio'));
            return;
        }

        setGeneratingSuggestions(true);
        try {
            const response = await apiFetch({
                path: '/acs/v1/blog/generate-suggestions',
                method: 'POST',
                data: {
                    subject: subject.trim(),
                    language: language,
                },
            });

            if (response.success) {
                setSuggestedTitle(response.data.title);
                setKeywords(response.data.keywords);
                setMainKeyword(response.data.keywords[0]); // Premier mot-clé = principal
            } else {
                alert(response.message || __('Erreur lors de la génération', 'ai-content-studio'));
            }
        } catch (error) {
            console.error('Error generating suggestions:', error);
            alert(__('Erreur lors de la génération des suggestions', 'ai-content-studio'));
        } finally {
            setGeneratingSuggestions(false);
        }
    };

    // Générer l'article avec streaming
    const generateArticle = async () => {
        if (!suggestedTitle || keywords.length === 0) {
            alert(__('Veuillez d\'abord générer le titre et les mots-clés', 'ai-content-studio'));
            return;
        }

        setGeneratingArticle(true);
        setArticleContent('');
        setArticleGenerated(false);
        setSaved(false);

        try {
            const response = await fetch(window.acsData.apiUrl + '/blog/generate-article', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.acsData.nonce,
                },
                body: JSON.stringify({
                    title: suggestedTitle,
                    keywords: keywords,
                    main_keyword: mainKeyword,
                    first_person: firstPerson,
                    length: length,
                    language: language,
                }),
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { value, done } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop(); // Garde la dernière ligne incomplète

                for (const line of lines) {
                    if (line.startsWith('data: ')) {
                        try {
                            const data = JSON.parse(line.slice(6));

                            if (data.type === 'content') {
                                setArticleContent(prev => prev + data.content);
                            } else if (data.type === 'done') {
                                setArticleGenerated(true);
                                setShowCompletionModal(true); // Show popup when done
                            } else if (data.type === 'error') {
                                // Vérifier si c'est une erreur de limite
                                if (data.code === 'limit_reached' || (data.message && data.message.includes('limite'))) {
                                    setPricingTriggerType('article_limit');
                                    setShowPricingModal(true);
                                }
                                throw new Error(data.message);
                            }
                        } catch (e) {
                            console.error('Error parsing SSE:', e);
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error generating article:', error);
            // Vérifier si c'est une erreur de limite
            if (error.message && (error.message.includes('limite') || error.message.includes('limit'))) {
                setPricingTriggerType('article_limit');
                setShowPricingModal(true);
            }
            alert(__('Erreur lors de la génération de l\'article', 'ai-content-studio') + ': ' + error.message);
        } finally {
            setGeneratingArticle(false);
        }
    };

    // Générer les meta SEO
    const generateSeoMeta = async () => {
        if (!articleContent) {
            alert(__('Veuillez d\'abord générer l\'article', 'ai-content-studio'));
            return;
        }

        setGeneratingSeo(true);
        try {
            const response = await apiFetch({
                path: '/acs/v1/blog/generate-seo',
                method: 'POST',
                data: {
                    title: suggestedTitle,
                    content: articleContent,
                    main_keyword: mainKeyword,
                    language: language,
                },
            });

            if (response.success) {
                setSeoTitle(response.data.seo_title);
                setMetaDescription(response.data.meta_description);
                setSeoUrl(response.data.url_slug);
                // Auto-sauvegarder après génération SEO
                await saveArticle();
            } else {
                alert(response.message || __('Erreur lors de la génération SEO', 'ai-content-studio'));
            }
        } catch (error) {
            console.error('Error generating SEO:', error);
            alert(__('Erreur lors de la génération des meta SEO', 'ai-content-studio'));
        } finally {
            setGeneratingSeo(false);
        }
    };

    // Sauvegarder l'article
    const saveArticle = async () => {
        if (!articleContent) {
            console.warn('Save attempt failed: No article content');
            alert(__('Aucun article à sauvegarder', 'ai-content-studio'));
            return;
        }

        const saveData = {
            subject: subject,
            title: suggestedTitle,
            content: articleContent,
            keywords: keywords,
            main_keyword: mainKeyword,
            first_person: firstPerson,
            length: length,
            language: language,
            seo_title: seoTitle || '',
            meta_description: metaDescription || '',
            url_slug: seoUrl || '',
        };

        console.log('Saving article with data:', saveData);

        setSavingArticle(true);
        try {
            const response = await apiFetch({
                path: '/acs/v1/blog/save-article',
                method: 'POST',
                data: saveData,
            });

            console.log('Save response:', response);

            if (response.success) {
                setSaved(true);
                console.log('Article saved successfully with ID:', response.data?.id);
                setTimeout(() => setSaved(false), 3000);
            } else {
                console.error('Save failed:', response.message);
                alert(response.message || __('Erreur lors de la sauvegarde', 'ai-content-studio'));
            }
        } catch (error) {
            console.error('Error saving article:', error);
            console.error('Error details:', error.message, error.stack);
            alert(__('Erreur lors de la sauvegarde de l\'article', 'ai-content-studio'));
        } finally {
            setSavingArticle(false);
        }
    };

    // Format content with proper HTML tags for headings and images
    const formatArticleContent = (content) => {
        if (!content) return '';

        // Convert markdown-style headings to HTML
        let formatted = content;

        // H1 (# Title)
        formatted = formatted.replace(/^# (.+)$/gm, '<h1>$1</h1>');

        // H2 (## Title)
        formatted = formatted.replace(/^## (.+)$/gm, '<h2>$1</h2>');

        // H3 (### Title)
        formatted = formatted.replace(/^### (.+)$/gm, '<h3>$1</h3>');

        // Images from Unsplash: [IMAGE: url]
        formatted = formatted.replace(/\[IMAGE:\s*([^\]]+)\]/g, '<img src="$1" alt="Article illustration" class="acs-article-image" />');

        // Replace line breaks with <br />
        formatted = formatted.replace(/\n/g, '<br />');

        return formatted;
    };

    return (
        <div className="acs-blog-generator">
            <div className="acs-page-header">
                <h1><FiFileText /> {__('Générateur d\'Articles de Blog', 'ai-content-studio')}</h1>
                <p className="acs-text-muted">
                    {__('Générez des articles de blog optimisés SEO avec Claude AI', 'ai-content-studio')}
                </p>
            </div>

            <div className="acs-blog-generator-grid">
                {/* Formulaire de gauche */}
                <div className="acs-blog-form-section">
                    <div className="acs-card">
                        <h2 className="acs-card-title">
                            {__('1. Sujet de l\'article', 'ai-content-studio')}
                        </h2>

                        <div className="acs-form-group">
                            <label>{__('Langue de l\'article', 'ai-content-studio')}</label>
                            <select
                                className="acs-form-control"
                                value={language}
                                onChange={(e) => setLanguage(e.target.value)}
                                disabled={generatingSuggestions || generatingArticle}
                            >
                                <option value="fr">{__('Français', 'ai-content-studio')}</option>
                                <option value="en">{__('Anglais', 'ai-content-studio')}</option>
                                <option value="es">{__('Espagnol', 'ai-content-studio')}</option>
                                <option value="pt">{__('Portugais', 'ai-content-studio')}</option>
                                <option value="de">{__('Allemand', 'ai-content-studio')}</option>
                                <option value="it">{__('Italien', 'ai-content-studio')}</option>
                                <option value="zh">{__('Chinois (Mandarin)', 'ai-content-studio')}</option>
                                <option value="ja">{__('Japonais', 'ai-content-studio')}</option>
                                <option value="ko">{__('Coréen', 'ai-content-studio')}</option>
                                <option value="ar">{__('Arabe', 'ai-content-studio')}</option>
                                <option value="ru">{__('Russe', 'ai-content-studio')}</option>
                                <option value="hi">{__('Hindi', 'ai-content-studio')}</option>
                                <option value="bn">{__('Bengali', 'ai-content-studio')}</option>
                                <option value="id">{__('Indonésien', 'ai-content-studio')}</option>
                                <option value="tr">{__('Turc', 'ai-content-studio')}</option>
                                <option value="vi">{__('Vietnamien', 'ai-content-studio')}</option>
                                <option value="pl">{__('Polonais', 'ai-content-studio')}</option>
                                <option value="uk">{__('Ukrainien', 'ai-content-studio')}</option>
                                <option value="nl">{__('Néerlandais', 'ai-content-studio')}</option>
                                <option value="th">{__('Thaï', 'ai-content-studio')}</option>
                                <option value="sv">{__('Suédois', 'ai-content-studio')}</option>
                                <option value="el">{__('Grec', 'ai-content-studio')}</option>
                                <option value="cs">{__('Tchèque', 'ai-content-studio')}</option>
                                <option value="ro">{__('Roumain', 'ai-content-studio')}</option>
                                <option value="hu">{__('Hongrois', 'ai-content-studio')}</option>
                                <option value="da">{__('Danois', 'ai-content-studio')}</option>
                                <option value="fi">{__('Finnois', 'ai-content-studio')}</option>
                                <option value="no">{__('Norvégien', 'ai-content-studio')}</option>
                                <option value="he">{__('Hébreu', 'ai-content-studio')}</option>
                                <option value="ca">{__('Catalan', 'ai-content-studio')}</option>
                            </select>
                        </div>

                        <div className="acs-form-group">
                            <label>{__('Sujet principal', 'ai-content-studio')}</label>
                            <input
                                type="text"
                                className="acs-form-control"
                                placeholder={__('Ex: Comment améliorer le SEO de son site web', 'ai-content-studio')}
                                value={subject}
                                onChange={(e) => setSubject(e.target.value)}
                                disabled={generatingSuggestions || generatingArticle}
                            />
                        </div>

                        <button
                            className="acs-btn acs-btn-primary w-100"
                            onClick={generateSuggestions}
                            disabled={generatingSuggestions || generatingArticle || !subject.trim()}
                        >
                            {generatingSuggestions ? (
                                <>
                                    <div className="acs-spinner" style={{ width: '16px', height: '16px', borderWidth: '2px' }} />
                                    {__('Génération...', 'ai-content-studio')}
                                </>
                            ) : (
                                <>
                                    <FiEdit /> {__('Générer Titre & Mots-clés', 'ai-content-studio')}
                                </>
                            )}
                        </button>

                        {suggestedTitle && (
                            <div className="acs-suggestion-result">
                                <div className="acs-form-group">
                                    <label>{__('Titre suggéré', 'ai-content-studio')}</label>
                                    <input
                                        type="text"
                                        className="acs-form-control"
                                        value={suggestedTitle}
                                        onChange={(e) => setSuggestedTitle(e.target.value)}
                                    />
                                </div>

                                <div className="acs-form-group">
                                    <label>{__('Mots-clés (5)', 'ai-content-studio')}</label>
                                    <div className="acs-keywords-list">
                                        {keywords.map((keyword, index) => (
                                            <span
                                                key={index}
                                                className={`acs-keyword-tag ${index === 0 ? 'primary' : ''}`}
                                            >
                                                {keyword}
                                                {index === 0 && <small> (principal)</small>}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {suggestedTitle && (
                        <div className="acs-card">
                            <h2 className="acs-card-title">
                                {__('2. Options de rédaction', 'ai-content-studio')}
                            </h2>

                            <div className="acs-form-group">
                                <label className="acs-checkbox-label">
                                    <input
                                        type="checkbox"
                                        checked={firstPerson}
                                        onChange={(e) => setFirstPerson(e.target.checked)}
                                        disabled={generatingArticle}
                                    />
                                    {__('Écrire à la première personne', 'ai-content-studio')}
                                </label>
                            </div>

                            <div className="acs-form-group">
                                <label>{__('Longueur de l\'article', 'ai-content-studio')}</label>
                                <select
                                    className="acs-form-control"
                                    value={length}
                                    onChange={(e) => setLength(e.target.value)}
                                    disabled={generatingArticle}
                                >
                                    <option value="1000-2000">{__('1000-2000 mots', 'ai-content-studio')}</option>
                                    <option value="2000-3000">{__('2000-3000 mots', 'ai-content-studio')}</option>
                                    <option value="3000-4000">{__('3000-4000 mots', 'ai-content-studio')}</option>
                                    <option value="4000-5000">{__('4000-5000 mots', 'ai-content-studio')}</option>
                                </select>
                            </div>

                            <button
                                className="acs-btn acs-btn-primary acs-btn-lg w-100"
                                onClick={generateArticle}
                                disabled={generatingArticle}
                            >
                                {generatingArticle ? (
                                    <>
                                        <div className="acs-spinner" style={{ width: '20px', height: '20px', borderWidth: '2px' }} />
                                        {__('Génération en cours...', 'ai-content-studio')}
                                    </>
                                ) : (
                                    <>
                                        <FiFileText /> {__('Générer l\'Article', 'ai-content-studio')}
                                    </>
                                )}
                            </button>
                        </div>
                    )}

                    {articleGenerated && (
                        <div className="acs-card">
                            <h2 className="acs-card-title">
                                {__('3. Meta SEO', 'ai-content-studio')}
                            </h2>

                            {!seoTitle ? (
                                <button
                                    className="acs-btn acs-btn-secondary w-100"
                                    onClick={generateSeoMeta}
                                    disabled={generatingSeo}
                                >
                                    {generatingSeo ? (
                                        <>
                                            <div className="acs-spinner" style={{ width: '16px', height: '16px', borderWidth: '2px' }} />
                                            {__('Génération...', 'ai-content-studio')}
                                        </>
                                    ) : (
                                        <>
                                            <FiEdit /> {__('Générer Titre, Meta & URL', 'ai-content-studio')}
                                        </>
                                    )}
                                </button>
                            ) : (
                                <>
                                    <div className="acs-form-group">
                                        <label>{__('Titre SEO', 'ai-content-studio')}</label>
                                        <input
                                            type="text"
                                            className="acs-form-control"
                                            value={seoTitle}
                                            onChange={(e) => setSeoTitle(e.target.value)}
                                        />
                                        <small className="acs-text-muted">
                                            {seoTitle.length} / 60 caractères
                                        </small>
                                    </div>

                                    <div className="acs-form-group">
                                        <label>{__('Meta Description', 'ai-content-studio')}</label>
                                        <textarea
                                            className="acs-textarea"
                                            rows="3"
                                            value={metaDescription}
                                            onChange={(e) => setMetaDescription(e.target.value)}
                                        />
                                        <small className="acs-text-muted">
                                            {metaDescription.length} / 160 caractères
                                        </small>
                                    </div>

                                    <div className="acs-form-group">
                                        <label>{__('URL (slug)', 'ai-content-studio')}</label>
                                        <input
                                            type="text"
                                            className="acs-form-control"
                                            value={seoUrl}
                                            onChange={(e) => setSeoUrl(e.target.value)}
                                        />
                                    </div>
                                </>
                            )}
                        </div>
                    )}

                    {articleGenerated && (
                        <button
                            className="acs-btn acs-btn-success acs-btn-lg w-100"
                            onClick={saveArticle}
                            disabled={savingArticle || saved}
                        >
                            {savingArticle ? (
                                <>
                                    <div className="acs-spinner" style={{ width: '20px', height: '20px', borderWidth: '2px' }} />
                                    {__('Sauvegarde...', 'ai-content-studio')}
                                </>
                            ) : saved ? (
                                <>
                                    <FiCheckCircle /> {__('Article Sauvegardé !', 'ai-content-studio')}
                                </>
                            ) : (
                                <>
                                    <FiSave /> {__('Sauvegarder l\'Article', 'ai-content-studio')}
                                </>
                            )}
                        </button>
                    )}
                </div>

                {/* Prévisualisation à droite */}
                <div className="acs-blog-preview-section">
                    <div className="acs-card acs-preview-card">
                        <h2 className="acs-card-title">
                            {__('Aperçu de l\'article', 'ai-content-studio')}
                        </h2>

                        {!articleContent && !generatingArticle && (
                            <div className="acs-empty-state">
                                <FiFileText size={48} />
                                <p>{__('L\'article apparaîtra ici en temps réel lors de la génération', 'ai-content-studio')}</p>
                            </div>
                        )}

                        {(articleContent || generatingArticle) && (
                            <div className="acs-article-preview" ref={contentRef}>
                                {suggestedTitle && (
                                    <h1 className="acs-preview-title">{suggestedTitle}</h1>
                                )}

                                <div
                                    className="acs-preview-content"
                                    dangerouslySetInnerHTML={{ __html: formatArticleContent(articleContent) }}
                                />

                                {generatingArticle && (
                                    <div className="acs-generating-indicator">
                                        <FiLoader className="acs-spin" />
                                        <span>{__('Génération en cours...', 'ai-content-studio')}</span>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Completion Modal */}
            {showCompletionModal && (
                <div className="acs-modal-overlay" onClick={() => setShowCompletionModal(false)}>
                    <div className="acs-modal acs-completion-modal" onClick={(e) => e.stopPropagation()}>
                        <div className="acs-modal-header">
                            <FiCheckCircle size={48} style={{ color: 'var(--acs-success)' }} />
                            <h2>{__('Article Terminé !', 'ai-content-studio')}</h2>
                        </div>
                        <div className="acs-modal-body">
                            <p>{__('Votre article a été généré avec succès.', 'ai-content-studio')}</p>
                            <p className="acs-text-muted">
                                {__('Vous pouvez maintenant générer les meta SEO et sauvegarder l\'article.', 'ai-content-studio')}
                            </p>
                        </div>
                        <div className="acs-modal-footer">
                            <button
                                className="acs-btn acs-btn-primary"
                                onClick={() => setShowCompletionModal(false)}
                            >
                                {__('Continuer', 'ai-content-studio')}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Pricing Modal */}
            <PricingModal
                isOpen={showPricingModal}
                onClose={() => setShowPricingModal(false)}
                currentPlan={'free_trial'}
                triggerType={pricingTriggerType}
            />
        </div>
    );
}
