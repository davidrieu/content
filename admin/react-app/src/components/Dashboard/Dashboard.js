import { useState, useEffect } from '@wordpress/element';
import { Card, CardBody, Button } from '@wordpress/components';
import { useNavigate } from 'react-router-dom';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

export default function Dashboard({ profile }) {
    const navigate = useNavigate();
    const [subscription, setSubscription] = useState(null);
    const [usage, setUsage] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        try {
            const [subResponse, usageResponse] = await Promise.all([
                apiFetch({ path: '/acs/v1/subscription' }),
                apiFetch({ path: '/acs/v1/subscription/usage' }),
            ]);

            if (subResponse.success) setSubscription(subResponse.data);
            if (usageResponse.success) setUsage(usageResponse.data);
        } catch (error) {
            console.error('Error loading data:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return <div>{__('Chargement...', 'ai-content-studio')}</div>;
    }

    return (
        <div className="acs-dashboard">
            <h1>{__('Tableau de bord', 'ai-content-studio')}</h1>

            <div className="acs-dashboard-grid">
                <Card>
                    <CardBody>
                        <h2>{__('Bienvenue', 'ai-content-studio')} {profile?.business_name}!</h2>
                        <p>
                            {__('Plan actuel:', 'ai-content-studio')} <strong>{subscription?.plan_name}</strong>
                        </p>
                    </CardBody>
                </Card>

                <Card>
                    <CardBody>
                        <h3>{__('Usage ce mois-ci', 'ai-content-studio')}</h3>
                        {usage && (
                            <div>
                                <div className="acs-usage-item">
                                    <span>{__('Posts:', 'ai-content-studio')}</span>
                                    <strong>
                                        {usage.posts.used} / {usage.posts.limit === -1 ? '∞' : usage.posts.limit}
                                    </strong>
                                </div>
                                <div className="acs-usage-item">
                                    <span>{__('Articles:', 'ai-content-studio')}</span>
                                    <strong>
                                        {usage.articles.used} / {usage.articles.limit === -1 ? '∞' : usage.articles.limit}
                                    </strong>
                                </div>
                                <div className="acs-usage-item">
                                    <span>{__('Images:', 'ai-content-studio')}</span>
                                    <strong>
                                        {usage.images.used} / {usage.images.limit === -1 ? '∞' : usage.images.limit}
                                    </strong>
                                </div>
                            </div>
                        )}
                    </CardBody>
                </Card>

                <Card>
                    <CardBody>
                        <h3>{__('Actions rapides', 'ai-content-studio')}</h3>
                        <Button isPrimary onClick={() => navigate('/generate')}>
                            {__('Générer un post', 'ai-content-studio')}
                        </Button>
                    </CardBody>
                </Card>
            </div>
        </div>
    );
}
