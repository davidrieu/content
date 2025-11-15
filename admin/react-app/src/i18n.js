import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import LanguageDetector from 'i18next-browser-languagedetector';

// Import all translation files
import fr from './locales/fr.json';
import en from './locales/en.json';
import es from './locales/es.json';
import de from './locales/de.json';
import it from './locales/it.json';
import pt from './locales/pt.json';
import nl from './locales/nl.json';
import pl from './locales/pl.json';
import ru from './locales/ru.json';
import ja from './locales/ja.json';
import zh from './locales/zh.json';
import ar from './locales/ar.json';
import ko from './locales/ko.json';
import tr from './locales/tr.json';
import hi from './locales/hi.json';
import sv from './locales/sv.json';
import da from './locales/da.json';
import no from './locales/no.json';
import fi from './locales/fi.json';
import cs from './locales/cs.json';
import ro from './locales/ro.json';
import el from './locales/el.json';
import th from './locales/th.json';
import vi from './locales/vi.json';
import id from './locales/id.json';
import ms from './locales/ms.json';
import he from './locales/he.json';
import uk from './locales/uk.json';
import bn from './locales/bn.json';
import fa from './locales/fa.json';

const resources = {
    fr: { translation: fr },
    en: { translation: en },
    es: { translation: es },
    de: { translation: de },
    it: { translation: it },
    pt: { translation: pt },
    nl: { translation: nl },
    pl: { translation: pl },
    ru: { translation: ru },
    ja: { translation: ja },
    zh: { translation: zh },
    ar: { translation: ar },
    ko: { translation: ko },
    tr: { translation: tr },
    hi: { translation: hi },
    sv: { translation: sv },
    da: { translation: da },
    no: { translation: no },
    fi: { translation: fi },
    cs: { translation: cs },
    ro: { translation: ro },
    el: { translation: el },
    th: { translation: th },
    vi: { translation: vi },
    id: { translation: id },
    ms: { translation: ms },
    he: { translation: he },
    uk: { translation: uk },
    bn: { translation: bn },
    fa: { translation: fa },
};

// Get user's selected language from WordPress or localStorage
const getUserLanguage = () => {
    // First check if there's a user language setting in acsData (from WordPress)
    if (window.acsData && window.acsData.userLanguage) {
        return window.acsData.userLanguage;
    }

    // Then check localStorage
    const storedLanguage = localStorage.getItem('acs-language');
    if (storedLanguage) {
        return storedLanguage;
    }

    // Finally, fall back to browser language or default to French
    const browserLang = navigator.language.split('-')[0];
    return resources[browserLang] ? browserLang : 'fr';
};

i18n
    .use(LanguageDetector)
    .use(initReactI18next)
    .init({
        resources,
        lng: getUserLanguage(),
        fallbackLng: 'fr',
        debug: false,

        interpolation: {
            escapeValue: false, // React already escapes values
        },

        react: {
            useSuspense: false,
        },
    });

// Export a function to change language
export const changeLanguage = (lang) => {
    if (resources[lang]) {
        i18n.changeLanguage(lang);
        localStorage.setItem('acs-language', lang);

        // Also update the HTML dir attribute for RTL languages
        document.documentElement.dir = ['ar', 'he', 'fa'].includes(lang) ? 'rtl' : 'ltr';

        return true;
    }
    return false;
};

// Initialize RTL on load
if (['ar', 'he', 'fa'].includes(i18n.language)) {
    document.documentElement.dir = 'rtl';
}

export default i18n;
