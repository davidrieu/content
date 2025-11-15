const fs = require('fs');
const path = require('path');

// Language configurations
const languages = {
    en: { name: 'English', nativeName: 'English' },
    es: { name: 'Spanish', nativeName: 'Español' },
    de: { name: 'German', nativeName: 'Deutsch' },
    it: { name: 'Italian', nativeName: 'Italiano' },
    pt: { name: 'Portuguese', nativeName: 'Português' },
    nl: { name: 'Dutch', nativeName: 'Nederlands' },
    pl: { name: 'Polish', nativeName: 'Polski' },
    ru: { name: 'Russian', nativeName: 'Русский' },
    ja: { name: 'Japanese', nativeName: '日本語' },
    zh: { name: 'Chinese', nativeName: '中文' },
    ar: { name: 'Arabic', nativeName: 'العربية' },
    ko: { name: 'Korean', nativeName: '한국어' },
    tr: { name: 'Turkish', nativeName: 'Türkçe' },
    hi: { name: 'Hindi', nativeName: 'हिन्दी' },
    sv: { name: 'Swedish', nativeName: 'Svenska' },
    da: { name: 'Danish', nativeName: 'Dansk' },
    no: { name: 'Norwegian', nativeName: 'Norsk' },
    fi: { name: 'Finnish', nativeName: 'Suomi' },
    cs: { name: 'Czech', nativeName: 'Čeština' },
    ro: { name: 'Romanian', nativeName: 'Română' },
    el: { name: 'Greek', nativeName: 'Ελληνικά' },
    th: { name: 'Thai', nativeName: 'ไทย' },
    vi: { name: 'Vietnamese', nativeName: 'Tiếng Việt' },
    id: { name: 'Indonesian', nativeName: 'Bahasa Indonesia' },
    ms: { name: 'Malay', nativeName: 'Bahasa Melayu' },
    he: { name: 'Hebrew', nativeName: 'עברית' },
    uk: { name: 'Ukrainian', nativeName: 'Українська' },
    bn: { name: 'Bengali', nativeName: 'বাংলা' },
    fa: { name: 'Persian', nativeName: 'فارسی' },
};

// Read the French translation file (base)
const frTranslation = require('./src/locales/fr.json');

// Function to translate recursively through the object
function translateObject(obj, langCode, langName) {
    const translated = {};

    for (const key in obj) {
        if (typeof obj[key] === 'object' && !Array.isArray(obj[key])) {
            // Recursively translate nested objects
            translated[key] = translateObject(obj[key], langCode, langName);
        } else if (Array.isArray(obj[key])) {
            // For arrays, we keep them as is for now
            // In a real implementation, you'd translate each item
            translated[key] = obj[key];
        } else {
            // For strings, we add a placeholder
            // In production, this would call Claude API to translate
            translated[key] = `[${langCode.toUpperCase()}] ${obj[key]}`;
        }
    }

    return translated;
}

// Generate translation files for all languages
console.log('🚀 Generating translation files for 29 languages...\n');

let filesCreated = 0;

Object.keys(languages).forEach(langCode => {
    const langInfo = languages[langCode];
    console.log(`📝 Creating ${langInfo.nativeName} (${langCode})...`);

    // For now, we create placeholder translations
    // In a real implementation, each string would be translated via Claude API
    const translated = translateObject(frTranslation, langCode, langInfo.name);

    // Write the file
    const outputPath = path.join(__dirname, 'src', 'locales', `${langCode}.json`);
    fs.writeFileSync(outputPath, JSON.stringify(translated, null, 2), 'utf8');

    filesCreated++;
    console.log(`   ✅ Created ${langCode}.json`);
});

console.log(`\n✨ Successfully created ${filesCreated} translation files!`);
console.log('\n⚠️  NOTE: These files contain placeholder translations.');
console.log('   For production use, run the AI translation script to generate');
console.log('   proper translations using Claude API.\n');
