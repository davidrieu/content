import { useState, useEffect } from '@wordpress/element';
import { HashRouter, Routes, Route, Navigate, useNavigate, useLocation } from 'react-router-dom';
import apiFetch from '@wordpress/api-fetch';
import Sidebar from './components/Layout/Sidebar';
import Topbar from './components/Layout/Topbar';
import OnboardingWizard from './components/Onboarding/OnboardingWizard';
import Dashboard from './components/Dashboard/Dashboard';
import PostGenerator from './components/SocialGenerator/PostGenerator';
import LoadingSpinner from './components/common/LoadingSpinner';

function AppContent({ profile }) {
    const navigate = useNavigate();
    const location = useLocation();

    const getPageTitle = () => {
        switch (location.pathname) {
            case '/': return 'Dashboard';
            case '/generate': return 'Générer des posts';
            case '/articles': return 'Articles de blog';
            case '/images': return 'Images IA';
            case '/calendar': return 'Calendrier';
            case '/trends': return 'Tendances';
            case '/settings': return 'Paramètres';
            default: return 'Dashboard';
        }
    };

    return (
        <div className="acs-app">
            <Sidebar currentPath={location.pathname} onNavigate={navigate} />
            <div className="acs-main-wrapper">
                <Topbar currentPage={getPageTitle()} profile={profile} />
                <div className="acs-content">
                    <Routes>
                        <Route path="/" element={<Dashboard profile={profile} />} />
                        <Route path="/generate" element={<PostGenerator profile={profile} />} />
                        <Route path="/articles" element={<PlaceholderPage title="Articles de blog" />} />
                        <Route path="/images" element={<PlaceholderPage title="Images IA" />} />
                        <Route path="/calendar" element={<PlaceholderPage title="Calendrier" />} />
                        <Route path="/trends" element={<PlaceholderPage title="Tendances" />} />
                        <Route path="/settings" element={<PlaceholderPage title="Paramètres" />} />
                        <Route path="*" element={<Navigate to="/" replace />} />
                    </Routes>
                </div>
            </div>
        </div>
    );
}

function PlaceholderPage({ title }) {
    return (
        <div className="acs-card" style={{ textAlign: 'center', padding: '4rem 2rem' }}>
            <h2 className="acs-card-title">{title}</h2>
            <p className="acs-text-muted">Cette section sera disponible prochainement.</p>
        </div>
    );
}

function App() {
    const [needsOnboarding, setNeedsOnboarding] = useState(true);
    const [loading, setLoading] = useState(true);
    const [profile, setProfile] = useState(null);

    useEffect(() => {
        checkOnboardingStatus();
    }, []);

    const checkOnboardingStatus = async () => {
        try {
            const response = await apiFetch({ path: '/acs/v1/profile' });
            if (response.success && response.data) {
                setProfile(response.data);
                setNeedsOnboarding(false);
            }
        } catch (error) {
            setNeedsOnboarding(true);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return <LoadingSpinner />;
    }

    if (needsOnboarding) {
        return (
            <OnboardingWizard
                onComplete={(newProfile) => {
                    setProfile(newProfile);
                    setNeedsOnboarding(false);
                }}
            />
        );
    }

    return (
        <HashRouter>
            <AppContent profile={profile} />
        </HashRouter>
    );
}

export default App;
