import { useState, useEffect } from '@wordpress/element';
import { HashRouter, Routes, Route, Navigate } from 'react-router-dom';
import apiFetch from '@wordpress/api-fetch';
import OnboardingWizard from './components/Onboarding/OnboardingWizard';
import Dashboard from './components/Dashboard/Dashboard';
import PostGenerator from './components/SocialGenerator/PostGenerator';
import LoadingSpinner from './components/common/LoadingSpinner';

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
            <div className="acs-app">
                <Routes>
                    <Route path="/" element={<Dashboard profile={profile} />} />
                    <Route path="/generate" element={<PostGenerator profile={profile} />} />
                    <Route path="*" element={<Navigate to="/" replace />} />
                </Routes>
            </div>
        </HashRouter>
    );
}

export default App;
