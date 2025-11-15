/**
 * Translation Script using Claude API
 *
 * This script translates the French base file (fr.json) to all 29 other languages
 * using Claude AI for high-quality, context-aware translations.
 *
 * Usage:
 *   ANTHROPIC_API_KEY=your_key node translate-with-claude.js
 *
 * Or to translate specific languages:
 *   ANTHROPIC_API_KEY=your_key node translate-with-claude.js en es de
 */

const fs = require('fs');
const path = require('path');
const https = require('https');

// Read API key from environment or WordPress config
const API_KEY = process.env.ANTHROPIC_API_KEY || process.env.CLAUDE_API_KEY;

if (!API_KEY) {
    console.error('❌ Error: ANTHROPIC_API_KEY environment variable not set');
    console.error('   Set it using: export ANTHROPIC_API_KEY=your_key_here');
    process.exit(1);
}

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

// Helper function to make API calls to Claude
function callClaudeAPI(prompt) {
    return new Promise((resolve, reject) => {
        const data = JSON.stringify({
            model: 'claude-sonnet-4-20250514',
            max_tokens: 16000,
            messages: [{
                role: 'user',
                content: prompt
            }]
        });

        const options = {
            hostname: 'api.anthropic.com',
            port: 443,
            path: '/v1/messages',
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'x-api-key': API_KEY,
                'anthropic-version': '2023-06-01',
                'Content-Length': data.length
            }
        };

        const req = https.request(options, (res) => {
            let body = '';

            res.on('data', (chunk) => {
                body += chunk;
            });

            res.on('end', () => {
                try {
                    const response = JSON.parse(body);
                    if (response.content && response.content[0] && response.content[0].text) {
                        resolve(response.content[0].text);
                    } else {
                        reject(new Error('Invalid API response format'));
                    }
                } catch (error) {
                    reject(error);
                }
            });
        });

        req.on('error', (error) => {
            reject(error);
        });

        req.write(data);
        req.end();
    });
}

// Translate the JSON structure
async function translateToLanguage(sourceJson, targetLang, targetLangName) {
    console.log(`\n🌍 Translating to ${targetLangName} (${targetLang})...`);

    const prompt = `You are a professional translator. Translate the following JSON object from French to ${targetLangName}.

IMPORTANT INSTRUCTIONS:
1. Preserve the exact JSON structure (all keys, nesting, arrays)
2. ONLY translate the VALUES, NEVER translate the keys
3. Maintain any special formatting (emojis, punctuation, HTML entities)
4. For UI text, use natural, native expressions that users would expect
5. Keep placeholder syntax intact (e.g., "Maximum: X characters" structure)
6. Return ONLY the translated JSON, no explanations or comments
7. Ensure the JSON is valid and can be parsed

Source JSON (in French):
${JSON.stringify(sourceJson, null, 2)}

Return the translated JSON in ${targetLangName}:`;

    try {
        const response = await callClaudeAPI(prompt);

        // Extract JSON from response (Claude might add formatting)
        let jsonMatch = response.match(/\{[\s\S]*\}/);
        if (!jsonMatch) {
            throw new Error('No JSON found in response');
        }

        const translatedJson = JSON.parse(jsonMatch[0]);
        return translatedJson;
    } catch (error) {
        console.error(`   ❌ Error translating to ${targetLang}:`, error.message);
        throw error;
    }
}

// Main translation function
async function translateAll() {
    // Read the French source file
    const frPath = path.join(__dirname, 'src', 'locales', 'fr.json');
    const frJson = JSON.parse(fs.readFileSync(frPath, 'utf8'));

    // Get target languages from command line or use all
    const args = process.argv.slice(2);
    const targetLangs = args.length > 0 ? args : Object.keys(languages);

    console.log(`\n🚀 Starting translation process for ${targetLangs.length} languages...\n`);

    let successCount = 0;
    let errorCount = 0;

    for (const langCode of targetLangs) {
        if (!languages[langCode]) {
            console.log(`⚠️  Skipping unknown language code: ${langCode}`);
            continue;
        }

        const langInfo = languages[langCode];

        try {
            const translated = await translateToLanguage(frJson, langCode, langInfo.nativeName);

            // Save the translated file
            const outputPath = path.join(__dirname, 'src', 'locales', `${langCode}.json`);
            fs.writeFileSync(outputPath, JSON.stringify(translated, null, 2), 'utf8');

            console.log(`   ✅ Successfully created ${langCode}.json`);
            successCount++;

            // Add a small delay to avoid rate limiting
            await new Promise(resolve => setTimeout(resolve, 1000));

        } catch (error) {
            console.error(`   ❌ Failed to translate ${langCode}`);
            errorCount++;
        }
    }

    console.log(`\n✨ Translation complete!`);
    console.log(`   ✅ Success: ${successCount} languages`);
    if (errorCount > 0) {
        console.log(`   ❌ Errors: ${errorCount} languages`);
    }
    console.log();
}

// Run the translation
translateAll().catch(error => {
    console.error('\n❌ Fatal error:', error);
    process.exit(1);
});
