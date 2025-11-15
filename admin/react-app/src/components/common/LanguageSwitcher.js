import { useState, useRef, useEffect } from '@wordpress/element';
import { useTranslation } from 'react-i18next';
import { FiGlobe, FiCheck } from 'react-icons/fi';
import { changeLanguage } from '../../i18n';

// Language configurations with flags and names
const languages = [
    { code: 'fr', name: 'Français', flag: '🇫🇷', popular: true },
    { code: 'en', name: 'English', flag: '🇬🇧', popular: true },
    { code: 'es', name: 'Español', flag: '🇪🇸', popular: true },
    { code: 'de', name: 'Deutsch', flag: '🇩🇪', popular: true },
    { code: 'it', name: 'Italiano', flag: '🇮🇹', popular: true },
    { code: 'pt', name: 'Português', flag: '🇵🇹', popular: true },
    { code: 'nl', name: 'Nederlands', flag: '🇳🇱', popular: false },
    { code: 'pl', name: 'Polski', flag: '🇵🇱', popular: false },
    { code: 'ru', name: 'Русский', flag: '🇷🇺', popular: true },
    { code: 'ja', name: '日本語', flag: '🇯🇵', popular: true },
    { code: 'zh', name: '中文', flag: '🇨🇳', popular: true },
    { code: 'ar', name: 'العربية', flag: '🇸🇦', popular: true },
    { code: 'ko', name: '한국어', flag: '🇰🇷', popular: false },
    { code: 'tr', name: 'Türkçe', flag: '🇹🇷', popular: false },
    { code: 'hi', name: 'हिन्दी', flag: '🇮🇳', popular: false },
    { code: 'sv', name: 'Svenska', flag: '🇸🇪', popular: false },
    { code: 'da', name: 'Dansk', flag: '🇩🇰', popular: false },
    { code: 'no', name: 'Norsk', flag: '🇳🇴', popular: false },
    { code: 'fi', name: 'Suomi', flag: '🇫🇮', popular: false },
    { code: 'cs', name: 'Čeština', flag: '🇨🇿', popular: false },
    { code: 'ro', name: 'Română', flag: '🇷🇴', popular: false },
    { code: 'el', name: 'Ελληνικά', flag: '🇬🇷', popular: false },
    { code: 'th', name: 'ไทย', flag: '🇹🇭', popular: false },
    { code: 'vi', name: 'Tiếng Việt', flag: '🇻🇳', popular: false },
    { code: 'id', name: 'Bahasa Indonesia', flag: '🇮🇩', popular: false },
    { code: 'ms', name: 'Bahasa Melayu', flag: '🇲🇾', popular: false },
    { code: 'he', name: 'עברית', flag: '🇮🇱', popular: false },
    { code: 'uk', name: 'Українська', flag: '🇺🇦', popular: false },
    { code: 'bn', name: 'বাংলা', flag: '🇧🇩', popular: false },
    { code: 'fa', name: 'فارسی', flag: '🇮🇷', popular: false },
];

export default function LanguageSwitcher({ showLabel = true, compact = false }) {
    const { i18n } = useTranslation();
    const [isOpen, setIsOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const dropdownRef = useRef(null);

    const currentLanguage = languages.find(lang => lang.code === i18n.language) || languages[0];

    // Close dropdown when clicking outside
    useEffect(() => {
        function handleClickOutside(event) {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setIsOpen(false);
                setSearchTerm('');
            }
        }

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const handleLanguageChange = (langCode) => {
        const success = changeLanguage(langCode);
        if (success) {
            setIsOpen(false);
            setSearchTerm('');

            // Optionally save to user settings via API
            if (window.acsData && window.acsData.userId) {
                // Save to WordPress user meta
                wp.apiFetch({
                    path: '/acs/v1/settings',
                    method: 'POST',
                    data: {
                        user_language: langCode
                    }
                }).catch(err => console.error('Failed to save language preference:', err));
            }
        }
    };

    // Filter languages based on search
    const filteredLanguages = languages.filter(lang =>
        lang.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        lang.code.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const popularLanguages = filteredLanguages.filter(lang => lang.popular);
    const otherLanguages = filteredLanguages.filter(lang => !lang.popular);

    if (compact) {
        return (
            <div className="acs-language-switcher-compact" ref={dropdownRef}>
                <button
                    onClick={() => setIsOpen(!isOpen)}
                    className="acs-lang-button-compact"
                    title={currentLanguage.name}
                >
                    <span className="acs-lang-flag">{currentLanguage.flag}</span>
                </button>

                {isOpen && (
                    <div className="acs-lang-dropdown">
                        <div className="acs-lang-search">
                            <input
                                type="text"
                                placeholder="Search languages..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="acs-lang-search-input"
                            />
                        </div>

                        {popularLanguages.length > 0 && (
                            <>
                                <div className="acs-lang-section-title">Popular</div>
                                {popularLanguages.map(lang => (
                                    <button
                                        key={lang.code}
                                        onClick={() => handleLanguageChange(lang.code)}
                                        className={`acs-lang-option ${lang.code === i18n.language ? 'active' : ''}`}
                                    >
                                        <span className="acs-lang-flag">{lang.flag}</span>
                                        <span className="acs-lang-name">{lang.name}</span>
                                        {lang.code === i18n.language && <FiCheck size={16} />}
                                    </button>
                                ))}
                            </>
                        )}

                        {otherLanguages.length > 0 && (
                            <>
                                <div className="acs-lang-section-title">All Languages</div>
                                {otherLanguages.map(lang => (
                                    <button
                                        key={lang.code}
                                        onClick={() => handleLanguageChange(lang.code)}
                                        className={`acs-lang-option ${lang.code === i18n.language ? 'active' : ''}`}
                                    >
                                        <span className="acs-lang-flag">{lang.flag}</span>
                                        <span className="acs-lang-name">{lang.name}</span>
                                        {lang.code === i18n.language && <FiCheck size={16} />}
                                    </button>
                                ))}
                            </>
                        )}

                        {filteredLanguages.length === 0 && (
                            <div className="acs-lang-no-results">No languages found</div>
                        )}
                    </div>
                )}
            </div>
        );
    }

    return (
        <div className="acs-language-switcher" ref={dropdownRef}>
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="acs-lang-button"
            >
                <FiGlobe size={18} />
                {showLabel && (
                    <>
                        <span className="acs-lang-current-name">{currentLanguage.name}</span>
                        <span className="acs-lang-current-flag">{currentLanguage.flag}</span>
                    </>
                )}
            </button>

            {isOpen && (
                <div className="acs-lang-dropdown">
                    <div className="acs-lang-search">
                        <input
                            type="text"
                            placeholder="Search languages..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="acs-lang-search-input"
                            autoFocus
                        />
                    </div>

                    {popularLanguages.length > 0 && (
                        <>
                            <div className="acs-lang-section-title">Popular</div>
                            {popularLanguages.map(lang => (
                                <button
                                    key={lang.code}
                                    onClick={() => handleLanguageChange(lang.code)}
                                    className={`acs-lang-option ${lang.code === i18n.language ? 'active' : ''}`}
                                >
                                    <span className="acs-lang-flag">{lang.flag}</span>
                                    <span className="acs-lang-name">{lang.name}</span>
                                    {lang.code === i18n.language && <FiCheck size={16} />}
                                </button>
                            ))}
                        </>
                    )}

                    {otherLanguages.length > 0 && (
                        <>
                            <div className="acs-lang-section-title">All Languages</div>
                            <div className="acs-lang-scrollable">
                                {otherLanguages.map(lang => (
                                    <button
                                        key={lang.code}
                                        onClick={() => handleLanguageChange(lang.code)}
                                        className={`acs-lang-option ${lang.code === i18n.language ? 'active' : ''}`}
                                    >
                                        <span className="acs-lang-flag">{lang.flag}</span>
                                        <span className="acs-lang-name">{lang.name}</span>
                                        {lang.code === i18n.language && <FiCheck size={16} />}
                                    </button>
                                ))}
                            </div>
                        </>
                    )}

                    {filteredLanguages.length === 0 && (
                        <div className="acs-lang-no-results">No languages found</div>
                    )}
                </div>
            )}
        </div>
    );
}
