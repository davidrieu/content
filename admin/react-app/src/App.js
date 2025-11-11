import { useState, useEffect } from '@wordpress/element';
import { HashRouter, Routes, Route, Navigate, useNavigate, useLocation } from 'react-router-dom';
import apiFetch from '@wordpress/api-fetch';
import Sidebar from './components/Layout/Sidebar';
import Topbar from './components/Layout/Topbar';
import OnboardingWizard from './components/Onboarding/OnboardingWizard';
import Dashboard from './components/Dashboard/Dashboard';
import EnhancedPostGenerator from './components/SocialGenerator/EnhancedPostGenerator';
import ContentCalendar from './components/Calendar/ContentCalendar';
import StrategyGenerator from './components/Strategy/StrategyGenerator';
import ContentLibrary from './components/Library/ContentLibrary';
import BlogArticleGenerator from './components/Blog/BlogArticleGenerator';
import BlogLibrary from './components/Blog/BlogLibrary';
import LoginForm from './components/Auth/LoginForm';
import RegisterForm from './components/Auth/RegisterForm';
import LoadingSpinner from './components/common/LoadingSpinner';

function AppContent({ profile }) {
    const navigate = useNavigate();
    const location = useLocation();

    const getPageTitle = () => {
        switch (location.pathname) {
            case '/': return 'Dashboard';
            case '/generate': return 'Générer des posts';
            case '/calendar': return 'Calendrier Éditorial';
            case '/strategy': return 'Stratégie Automatique';
            case '/library': return 'Bibliothèque';
            case '/articles': return 'Articles de blog';
            case '/blog-library': return 'Bibliothèque d\'Articles';
            case '/images': return 'Images IA';
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
                        <Route path="/generate" element={<EnhancedPostGenerator profile={profile} />} />
                        <Route path="/calendar" element={<ContentCalendar profile={profile} />} />
                        <Route path="/strategy" element={<StrategyGenerator profile={profile} />} />
                        <Route path="/library" element={<ContentLibrary profile={profile} />} />
                        <Route path="/articles" element={<BlogArticleGenerator profile={profile} />} />
                        <Route path="/blog-library" element={<BlogLibrary profile={profile} />} />
                        <Route path="/images" element={<PlaceholderPage title="Images IA" />} />
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
    const [isAuthenticated, setIsAuthenticated] = useState(false);
    const [showRegister, setShowRegister] = useState(false);
    const [needsOnboarding, setNeedsOnboarding] = useState(true);
    const [loading, setLoading] = useState(true);
    const [profile, setProfile] = useState(null);

    useEffect(() => {
        checkAuthAndOnboarding();
    }, []);

    const checkAuthAndOnboarding = async () => {
        // Check if acsData is available
        if (!window.acsData) {
            console.error('ACS Error - window.acsData is not defined!');
            setIsAuthenticated(false);
            setLoading(false);
            return;
        }

        // Check if user is logged in
        const currentUserId = window.acsData.currentUser;

        console.log('ACS Debug - Full acsData:', window.acsData);
        console.log('ACS Debug - currentUserId:', currentUserId, 'type:', typeof currentUserId);

        // Strict check for logged out users (0, "0", null, undefined, false)
        if (!currentUserId || currentUserId === 0 || currentUserId === "0" || currentUserId === false) {
            console.log('ACS Debug - User not authenticated, showing login form');
            setIsAuthenticated(false);
            setLoading(false);
            return;
        }

        console.log('ACS Debug - User authenticated, checking onboarding status');
        setIsAuthenticated(true);

        // Check onboarding status
        try {
            const response = await apiFetch({ path: '/acs/v1/profile' });
            console.log('ACS Debug - Profile response:', response);
            if (response.success && response.data) {
                setProfile(response.data);
                setNeedsOnboarding(false);
            }
        } catch (error) {
            console.log('ACS Debug - Profile error (needs onboarding):', error);
            setNeedsOnboarding(true);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return <LoadingSpinner />;
    }

    // Show login/register if not authenticated
    if (!isAuthenticated) {
        if (showRegister) {
            return (
                <RegisterForm
                    onSwitchToLogin={() => setShowRegister(false)}
                />
            );
        }
        return (
            <LoginForm
                onLogin={() => setIsAuthenticated(true)}
                onSwitchToRegister={() => setShowRegister(true)}
            />
        );
    }

    // Show onboarding if needed
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

    // Show main app
    return (
        <HashRouter>
            <AppContent profile={profile} />
        </HashRouter>
    );
}

export default App;
