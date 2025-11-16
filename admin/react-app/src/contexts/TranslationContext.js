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
            // User not logged in or API error - use default French
            console.log('Using default language (French):', error.message);
            setCurrentLanguage('fr');
            setTranslations({});
        } finally {
            setLoading(false);
        }
    };

    const loadTranslations = async (langCode) => {
        try {
            console.log(`Loading translations for language: ${langCode}`);
            const response = await apiFetch({
                path: `/acs/v1/translations/${langCode}`,
            });

            console.log('Translation API response:', response);

            if (response.success && response.data.translations) {
                const translationsData = response.data.translations;
                console.log(`Loaded ${Object.keys(translationsData).length} translation keys for ${langCode}`);
                setTranslations(translationsData);
            } else {
                console.warn('No translations in response:', response);
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
        if (currentLanguage === 'fr') {
            return key;
        }

        // Check if translation exists
        if (!translations[key]) {
            // Log missing translations in dev mode
            if (process.env.NODE_ENV === 'development') {
                console.warn(`Missing translation for key: "${key}" in language: ${currentLanguage}`);
            }
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
