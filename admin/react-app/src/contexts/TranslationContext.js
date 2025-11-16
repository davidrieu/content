import { createContext, useContext, useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const TranslationContext = createContext();

export function TranslationProvider({ children }) {
    const [currentLanguage, setCurrentLanguage] = useState('fr');
    const [translations, setTranslations] = useState({});
    const [loading, setLoading] = useState(true);

    // Load user language and translations on mount
    useEffect(() => {
        loadUserLanguage();
    }, []);

    const loadUserLanguage = async () => {
        try {
            // Get user's language preference
            const response = await apiFetch({
                path: '/acs/v1/language',
            });

            if (response.success && response.data.language) {
                const langCode = response.data.language;
                setCurrentLanguage(langCode);

                // Load translations if not French (default)
                if (langCode !== 'fr') {
                    await loadTranslations(langCode);
                }
            }
        } catch (error) {
            console.error('Error loading language:', error);
        } finally {
            setLoading(false);
        }
    };

    const loadTranslations = async (langCode) => {
        try {
            const response = await apiFetch({
                path: `/acs/v1/translations/${langCode}`,
            });

            if (response.success && response.data.translations) {
                setTranslations(response.data.translations);
            }
        } catch (error) {
            console.error('Error loading translations:', error);
        }
    };

    const changeLanguage = async (langCode) => {
        try {
            const response = await apiFetch({
                path: '/acs/v1/language',
                method: 'POST',
                data: { language: langCode },
            });

            if (response.success) {
                setCurrentLanguage(langCode);

                // Load new translations
                if (langCode === 'fr') {
                    setTranslations({});
                } else {
                    await loadTranslations(langCode);
                }

                // Reload page to apply changes everywhere
                window.location.reload();
            }
        } catch (error) {
            console.error('Error changing language:', error);
        }
    };

    const t = (key) => {
        // If French or no translation exists, return original key
        if (currentLanguage === 'fr' || !translations[key]) {
            return key;
        }

        return translations[key];
    };

    const value = {
        currentLanguage,
        changeLanguage,
        t,
        loading,
        translations,
    };

    return (
        <TranslationContext.Provider value={value}>
            {children}
        </TranslationContext.Provider>
    );
}

export function useTranslation() {
    const context = useContext(TranslationContext);

    if (!context) {
        throw new Error('useTranslation must be used within a TranslationProvider');
    }

    return context;
}
