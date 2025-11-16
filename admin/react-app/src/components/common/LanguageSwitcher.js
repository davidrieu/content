import { useState, useEffect, useRef } from 'react';
import { FiGlobe, FiCheck } from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

export default function LanguageSwitcher() {
    const [isOpen, setIsOpen] = useState(false);
    const [languages, setLanguages] = useState([]);
    const [popularLanguages, setPopularLanguages] = useState([]);
    const [currentLanguage, setCurrentLanguage] = useState(null);
    const [loading, setLoading] = useState(false);
    const dropdownRef = useRef(null);

    // Close dropdown when clicking outside
    useEffect(() => {
        function handleClickOutside(event) {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        }

        if (isOpen) {
            document.addEventListener('mousedown', handleClickOutside);
            return () => document.removeEventListener('mousedown', handleClickOutside);
        }
    }, [isOpen]);

    // Load languages and current user language
    useEffect(() => {
        loadLanguages();
        loadCurrentLanguage();
    }, []);

    const loadLanguages = async () => {
        try {
            const response = await apiFetch({
                path: '/acs/v1/languages',
            });

            if (response.success) {
                setLanguages(Object.values(response.data.languages));
                setPopularLanguages(Object.values(response.data.popular));
            }
        } catch (error) {
            console.error('Error loading languages:', error);
        }
    };

    const loadCurrentLanguage = async () => {
        try {
            const response = await apiFetch({
                path: '/acs/v1/language',
            });

            if (response.success) {
                setCurrentLanguage(response.data.config);
            }
        } catch (error) {
            console.error('Error loading current language:', error);
        }
    };

    const handleLanguageChange = async (languageCode) => {
        if (loading || languageCode === currentLanguage?.code) return;

        setLoading(true);

        try {
            const response = await apiFetch({
                path: '/acs/v1/language',
                method: 'POST',
                data: {
                    language: languageCode,
                },
            });

            if (response.success) {
                // Reload page to apply new language
                window.location.reload();
            }
        } catch (error) {
            console.error('Error changing language:', error);
            setLoading(false);
        }
    };

    if (!currentLanguage) {
        return null;
    }

    return (
        <div className="acs-language-switcher" ref={dropdownRef}>
            <button
                className="acs-language-trigger"
                onClick={() => setIsOpen(!isOpen)}
                disabled={loading}
                aria-label={__('Changer de langue', 'ai-content-studio')}
            >
                <FiGlobe size={18} />
                <span className="acs-current-lang">{currentLanguage.flag}</span>
            </button>

            {isOpen && (
                <div className="acs-language-dropdown">
                    <div className="acs-language-dropdown-header">
                        <FiGlobe size={16} />
                        <span>{__('Choisir une langue', 'ai-content-studio')}</span>
                    </div>

                    {/* Popular Languages */}
                    {popularLanguages.length > 0 && (
                        <>
                            <div className="acs-language-section-title">
                                {__('Langues populaires', 'ai-content-studio')}
                            </div>
                            <div className="acs-language-list">
                                {popularLanguages.map((lang) => (
                                    <button
                                        key={lang.code}
                                        className={`acs-language-item ${
                                            currentLanguage.code === lang.code ? 'active' : ''
                                        }`}
                                        onClick={() => handleLanguageChange(lang.code)}
                                        disabled={loading}
                                    >
                                        <span className="acs-language-flag">{lang.flag}</span>
                                        <span className="acs-language-name">{lang.native_name}</span>
                                        {currentLanguage.code === lang.code && (
                                            <FiCheck className="acs-language-check" size={16} />
                                        )}
                                    </button>
                                ))}
                            </div>
                        </>
                    )}

                    {/* All Languages */}
                    <div className="acs-language-section-title">
                        {__('Toutes les langues', 'ai-content-studio')}
                    </div>
                    <div className="acs-language-list acs-language-list-scrollable">
                        {languages
                            .filter((lang) => !lang.popular)
                            .map((lang) => (
                                <button
                                    key={lang.code}
                                    className={`acs-language-item ${
                                        currentLanguage.code === lang.code ? 'active' : ''
                                    }`}
                                    onClick={() => handleLanguageChange(lang.code)}
                                    disabled={loading}
                                >
                                    <span className="acs-language-flag">{lang.flag}</span>
                                    <span className="acs-language-name">{lang.native_name}</span>
                                    {currentLanguage.code === lang.code && (
                                        <FiCheck className="acs-language-check" size={16} />
                                    )}
                                </button>
                            ))}
                    </div>
                </div>
            )}
        </div>
    );
}
