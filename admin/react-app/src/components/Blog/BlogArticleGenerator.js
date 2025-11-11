import { useState, useRef, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { FiFileText, FiEdit, FiImage, FiSave, FiLoader, FiCheckCircle } from 'react-icons/fi';

export default function BlogArticleGenerator() {
    const [subject, setSubject] = useState('');
    const [suggestedTitle, setSuggestedTitle] = useState('');
    const [keywords, setKeywords] = useState([]);
    const [mainKeyword, setMainKeyword] = useState('');
    const [firstPerson, setFirstPerson] = useState(false);
    const [length, setLength] = useState('2000-3000');
    const [includeImages, setIncludeImages] = useState(false);

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

    const contentRef = useRef(null);

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
                    include_images: includeImages,
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
                            } else if (data.type === 'error') {
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
            alert(__('Erreur lors de la génération de l\'article', 'ai-content-studio'));
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
                },
            });

            if (response.success) {
                setSeoTitle(response.data.seo_title);
                setMetaDescription(response.data.meta_description);
                setSeoUrl(response.data.url_slug);
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
            alert(__('Aucun article à sauvegarder', 'ai-content-studio'));
            return;
        }

        setSavingArticle(true);
        try {
            const response = await apiFetch({
                path: '/acs/v1/blog/save-article',
                method: 'POST',
                data: {
                    subject: subject,
                    title: suggestedTitle,
                    content: articleContent,
                    keywords: keywords,
                    main_keyword: mainKeyword,
                    first_person: firstPerson,
                    length: length,
                    seo_title: seoTitle,
                    meta_description: metaDescription,
                    url_slug: seoUrl,
                },
            });

            if (response.success) {
                setSaved(true);
                setTimeout(() => setSaved(false), 3000);
            } else {
                alert(response.message || __('Erreur lors de la sauvegarde', 'ai-content-studio'));
            }
        } catch (error) {
            console.error('Error saving article:', error);
            alert(__('Erreur lors de la sauvegarde de l\'article', 'ai-content-studio'));
        } finally {
            setSavingArticle(false);
        }
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

                            <div className="acs-form-group">
                                <label className="acs-checkbox-label">
                                    <input
                                        type="checkbox"
                                        checked={includeImages}
                                        onChange={(e) => setIncludeImages(e.target.checked)}
                                        disabled={generatingArticle}
                                    />
                                    {__('Inclure des images du web (avec crédits)', 'ai-content-studio')}
                                </label>
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
                                    dangerouslySetInnerHTML={{ __html: articleContent.replace(/\n/g, '<br />') }}
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
        </div>
    );
}
