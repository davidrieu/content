import { useState } from '@wordpress/element';
import { Card, CardBody, Button, SelectControl, TextareaControl } from '@wordpress/components';
import { useNavigate } from 'react-router-dom';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

export default function PostGenerator({ profile }) {
    const navigate = useNavigate();
    const [platform, setPlatform] = useState('instagram');
    const [topic, setTopic] = useState('');
    const [tone, setTone] = useState('professional');
    const [language, setLanguage] = useState('fr');
    const [generating, setGenerating] = useState(false);
    const [generatedPosts, setGeneratedPosts] = useState([]);
    const [error, setError] = useState('');

    const handleGenerate = async () => {
        if (!topic.trim()) {
            setError(__('Veuillez entrer un sujet', 'ai-content-studio'));
            return;
        }

        setGenerating(true);
        setError('');

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

    return (
        <div className="acs-post-generator">
            <Button isSecondary onClick={() => navigate('/')}>
                ← {__('Retour au tableau de bord', 'ai-content-studio')}
            </Button>

            <h1>{__('Générateur de posts réseaux sociaux', 'ai-content-studio')}</h1>

            <Card>
                <CardBody>
                    <SelectControl
                        label={__('Plateforme', 'ai-content-studio')}
                        value={platform}
                        options={[
                            { value: 'instagram', label: 'Instagram' },
                            { value: 'facebook', label: 'Facebook' },
                            { value: 'linkedin', label: 'LinkedIn' },
                            { value: 'twitter', label: 'Twitter/X' },
                        ]}
                        onChange={setPlatform}
                    />

                    <TextareaControl
                        label={__('Sujet du post', 'ai-content-studio')}
                        value={topic}
                        onChange={setTopic}
                        placeholder={__('De quoi voulez-vous parler ?', 'ai-content-studio')}
                        rows={4}
                    />

                    <SelectControl
                        label={__('Ton', 'ai-content-studio')}
                        value={tone}
                        options={[
                            { value: 'professional', label: __('Professionnel', 'ai-content-studio') },
                            { value: 'casual', label: __('Décontracté', 'ai-content-studio') },
                            { value: 'enthusiastic', label: __('Enthousiaste', 'ai-content-studio') },
                            { value: 'friendly', label: __('Amical', 'ai-content-studio') },
                        ]}
                        onChange={setTone}
                    />

                    {error && <div className="acs-error">{error}</div>}

                    <Button isPrimary onClick={handleGenerate} disabled={generating}>
                        {generating ? __('Génération en cours...', 'ai-content-studio') : __('Générer 3 variantes', 'ai-content-studio')}
                    </Button>
                </CardBody>
            </Card>

            {generatedPosts.length > 0 && (
                <div className="acs-generated-posts">
                    <h2>{__('Posts générés', 'ai-content-studio')}</h2>
                    <div className="acs-posts-grid">
                        {generatedPosts.map((post, index) => (
                            <Card key={post.id || index}>
                                <CardBody>
                                    <h3>{__('Variante', 'ai-content-studio')} {index + 1}</h3>
                                    <div className="acs-post-content">{post.content}</div>
                                    {post.hashtags && post.hashtags.length > 0 && (
                                        <div className="acs-hashtags">
                                            {post.hashtags.map((tag, i) => (
                                                <span key={i} className="acs-hashtag">
                                                    {tag}
                                                </span>
                                            ))}
                                        </div>
                                    )}
                                </CardBody>
                            </Card>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
