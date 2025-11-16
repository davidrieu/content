import { useState, useEffect, useRef } from 'react';
import { FiGlobe, FiCheck } from 'react-icons/fi';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

export default function LanguageSwitcher() {
    const [isOpen, setIsOpen] = useState(false);
    const [languages, setLanguages] = useState([]);
    const [currentLanguage, setCurrentLanguage] = useState({
        code: 'fr',
        flag: '🇫🇷',
        native_name: 'Français'
    });
    const [loading, setLoading] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const dropdownRef = useRef(null);
    const searchInputRef = useRef(null);

    // Close dropdown when clicking outside
    useEffect(() => {
        function handleClickOutside(event) {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setIsOpen(false);
                setSearchQuery(''); // Reset search when closing
            }
        }

        if (isOpen) {
            document.addEventListener('mousedown', handleClickOutside);
            // Focus search input when dropdown opens
            setTimeout(() => {
                if (searchInputRef.current) {
                    searchInputRef.current.focus();
                }
            }, 100);
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
            console.log('LanguageSwitcher: Loading languages...');
            const response = await apiFetch({
                path: '/acs/v1/languages',
            });

            console.log('LanguageSwitcher: API Response:', response);

            if (response.success) {
                const allLangs = Object.values(response.data.languages);

                console.log('LanguageSwitcher: All languages:', allLangs);

                setLanguages(allLangs);
            } else {
                console.error('LanguageSwitcher: API returned success=false');
            }
        } catch (error) {
            console.error('LanguageSwitcher: Error loading languages:', error);
        }
    };

    const loadCurrentLanguage = async () => {
        try {
            const response = await apiFetch({
                path: '/acs/v1/language',
            });

            if (response.success && response.data.config) {
                setCurrentLanguage(response.data.config);
            }
        } catch (error) {
            console.error('Error loading current language:', error);
            // Keep default French language if error
        }
    };

    const handleLanguageChange = async (languageCode) => {
        if (loading || languageCode === currentLanguage?.code) return;

        setLoading(true);
        setIsOpen(false);
        setSearchQuery('');

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

    // Filter languages based on search query
    const filteredLanguages = languages.filter((lang) => {
        if (!searchQuery) return true;
        const query = searchQuery.toLowerCase();
        return (
            lang.native_name.toLowerCase().includes(query) ||
            lang.name.toLowerCase().includes(query) ||
            lang.code.toLowerCase().includes(query)
        );
    });

    return (
        <div className="acs-language-switcher" ref={dropdownRef}>
            <button
                className="acs-language-trigger"
                onClick={() => setIsOpen(!isOpen)}
                disabled={loading}
                aria-label={__('Changer de langue', 'ai-content-studio')}
                style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '6px',
                    padding: '8px 12px',
                    background: 'transparent',
                    border: 'none',
                    cursor: 'pointer',
                    borderRadius: '4px',
                    transition: 'all 0.2s'
                }}
            >
                <FiGlobe size={18} />
                <span style={{ fontSize: '1.25rem' }}>{currentLanguage.flag}</span>
            </button>

            {isOpen && (
                <div className="acs-language-dropdown">
                    <div className="acs-language-dropdown-header">
                        <FiGlobe size={16} />
                        <span>{__('Choisir une langue', 'ai-content-studio')}</span>
                    </div>

                    {/* Search Bar */}
                    <div className="acs-language-search">
                        <input
                            ref={searchInputRef}
                            type="text"
                            placeholder={__('Rechercher une langue...', 'ai-content-studio')}
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="acs-language-search-input"
                        />
                    </div>

                    {languages.length === 0 && (
                        <div style={{ padding: '20px', textAlign: 'center', color: '#999' }}>
                            Chargement des langues...
                        </div>
                    )}

                    {/* All Languages */}
                    {filteredLanguages.length > 0 ? (
                        <div className="acs-language-list acs-language-list-scrollable">
                            {filteredLanguages.map((lang) => (
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
                    ) : (
                        <div style={{ padding: '20px', textAlign: 'center', color: '#999' }}>
                            {__('Aucune langue trouvée', 'ai-content-studio')}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
