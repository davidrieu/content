<?php
/**
 * Script de diagnostic pour le système de traduction
 *
 * UTILISATION :
 * 1. Uploader ce fichier à la racine de votre WordPress
 * 2. Accéder via : https://votre-site.com/debug-translations.php
 * 3. Analyser les résultats
 */

// Charger WordPress
require_once __DIR__ . '/wp-load.php';

// Sécurité : Seulement pour admins
if (!current_user_can('manage_options')) {
    die('Accès refusé');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnostic Traductions - AI Content Studio</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #2271b1; }
        h2 { color: #135e96; border-bottom: 2px solid #2271b1; padding-bottom: 10px; }
        .success { color: #00a32a; font-weight: bold; }
        .error { color: #d63638; font-weight: bold; }
        .warning { color: #dba617; font-weight: bold; }
        pre { background: #f0f0f0; padding: 15px; border-radius: 4px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #2271b1; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .key-value { display: grid; grid-template-columns: 200px 1fr; gap: 10px; }
        .key-value > div:first-child { font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔍 Diagnostic du Système de Traduction</h1>
    <p>Généré le : <?php echo date('Y-m-d H:i:s'); ?></p>

    <?php
    // Récupérer le répertoire de traductions
    $upload_dir = wp_upload_dir();
    $translations_dir = $upload_dir['basedir'] . '/ai-content-studio/languages';
    ?>

    <!-- SECTION 1 : Configuration -->
    <div class="section">
        <h2>1. Configuration Globale</h2>
        <div class="key-value">
            <div>Répertoire traductions :</div>
            <div><code><?php echo $translations_dir; ?></code></div>

            <div>Répertoire existe ?</div>
            <div class="<?php echo file_exists($translations_dir) ? 'success' : 'error'; ?>">
                <?php echo file_exists($translations_dir) ? '✅ OUI' : '❌ NON'; ?>
            </div>

            <div>Répertoire inscriptible ?</div>
            <div class="<?php echo is_writable($translations_dir) ? 'success' : 'error'; ?>">
                <?php echo is_writable($translations_dir) ? '✅ OUI' : '❌ NON'; ?>
            </div>

            <div>Clé API Claude configurée ?</div>
            <div class="<?php echo !empty(get_option('acs_claude_api_key')) ? 'success' : 'error'; ?>">
                <?php echo !empty(get_option('acs_claude_api_key')) ? '✅ OUI' : '❌ NON'; ?>
            </div>
        </div>
    </div>

    <!-- SECTION 2 : Fichiers de traduction -->
    <div class="section">
        <h2>2. Fichiers de Traduction Générés</h2>
        <?php
        if (file_exists($translations_dir)) {
            $files = glob($translations_dir . '/translations-*.json');
            if (!empty($files)) {
                echo '<table>';
                echo '<tr><th>Langue</th><th>Fichier</th><th>Taille</th><th>Clés</th><th>Échantillon</th></tr>';

                foreach ($files as $file) {
                    $basename = basename($file);
                    preg_match('/translations-([a-z]{2})\.json/', $basename, $matches);
                    $lang = $matches[1] ?? 'unknown';

                    $size = filesize($file);
                    $content = file_get_contents($file);
                    $data = json_decode($content, true);
                    $num_keys = is_array($data) ? count($data) : 0;

                    // Prendre 3 échantillons aléatoires
                    $sample = '';
                    if (is_array($data) && $num_keys > 0) {
                        $sample_keys = array_rand($data, min(3, $num_keys));
                        if (!is_array($sample_keys)) $sample_keys = [$sample_keys];

                        $sample .= '<ul style="margin:0;padding-left:20px;font-size:11px;">';
                        foreach ($sample_keys as $key) {
                            $french = $key;
                            $translated = $data[$key];
                            $sample .= '<li><strong>' . htmlspecialchars(substr($french, 0, 30)) . '</strong> → ' . htmlspecialchars(substr($translated, 0, 30)) . '</li>';
                        }
                        $sample .= '</ul>';
                    }

                    echo '<tr>';
                    echo '<td><strong>' . strtoupper($lang) . '</strong></td>';
                    echo '<td><code>' . $basename . '</code></td>';
                    echo '<td>' . number_format($size / 1024, 2) . ' KB</td>';
                    echo '<td class="' . ($num_keys > 400 ? 'success' : 'warning') . '">' . $num_keys . ' clés</td>';
                    echo '<td>' . $sample . '</td>';
                    echo '</tr>';
                }

                echo '</table>';
                echo '<p class="success">✅ ' . count($files) . ' fichier(s) de traduction trouvé(s)</p>';
            } else {
                echo '<p class="error">❌ Aucun fichier de traduction trouvé dans le répertoire</p>';
            }
        } else {
            echo '<p class="error">❌ Le répertoire de traductions n\'existe pas</p>';
        }
        ?>
    </div>

    <!-- SECTION 3 : Test API -->
    <div class="section">
        <h2>3. Test des Endpoints API</h2>
        <?php
        // Test pour quelques langues
        $test_langs = ['en', 'es', 'de', 'it'];

        echo '<table>';
        echo '<tr><th>Langue</th><th>Endpoint</th><th>Statut</th><th>Données</th></tr>';

        foreach ($test_langs as $lang) {
            $file = $translations_dir . "/translations-{$lang}.json";
            $exists = file_exists($file);

            if ($exists) {
                $content = file_get_contents($file);
                $data = json_decode($content, true);
                $num_keys = is_array($data) ? count($data) : 0;

                // Vérifier si c'est du vrai contenu traduit ou du français
                $sample_key = 'Bienvenue';
                $sample_value = $data[$sample_key] ?? '';

                $is_translated = $sample_value !== 'Bienvenue' && !empty($sample_value);

                echo '<tr>';
                echo '<td><strong>' . strtoupper($lang) . '</strong></td>';
                echo '<td><code>/acs/v1/translations/' . $lang . '</code></td>';
                echo '<td class="' . ($exists ? 'success' : 'error') . '">' . ($exists ? '✅ Existe' : '❌ Manquant') . '</td>';
                echo '<td>';
                echo '<div>' . $num_keys . ' clés</div>';
                echo '<div style="font-size:11px;margin-top:5px;">';
                echo '<strong>Test "Bienvenue":</strong> ';
                if ($is_translated) {
                    echo '<span class="success">✅ ' . htmlspecialchars($sample_value) . '</span>';
                } else {
                    echo '<span class="error">❌ ' . htmlspecialchars($sample_value) . ' (pas traduit!)</span>';
                }
                echo '</div>';
                echo '</td>';
                echo '</tr>';
            } else {
                echo '<tr>';
                echo '<td><strong>' . strtoupper($lang) . '</strong></td>';
                echo '<td><code>/acs/v1/translations/' . $lang . '</code></td>';
                echo '<td class="error">❌ Manquant</td>';
                echo '<td>Fichier non généré</td>';
                echo '</tr>';
            }
        }

        echo '</table>';
        ?>
    </div>

    <!-- SECTION 4 : Fichier Source -->
    <div class="section">
        <h2>4. Fichier Source (translations-en.json)</h2>
        <?php
        $source_file = WP_PLUGIN_DIR . '/ai-content-studio/languages/translations-en.json';
        if (file_exists($source_file)) {
            $source_content = file_get_contents($source_file);
            $source_data = json_decode($source_content, true);
            $source_keys = is_array($source_data) ? count($source_data) : 0;

            echo '<div class="key-value">';
            echo '<div>Fichier source :</div>';
            echo '<div><code>' . $source_file . '</code></div>';

            echo '<div>Nombre de clés :</div>';
            echo '<div class="success">' . $source_keys . ' clés</div>';

            echo '<div>Échantillon :</div>';
            echo '<div>';
            if ($source_keys > 0) {
                $sample_keys = array_rand($source_data, min(5, $source_keys));
                if (!is_array($sample_keys)) $sample_keys = [$sample_keys];

                echo '<ul style="margin:0;padding-left:20px;">';
                foreach ($sample_keys as $key) {
                    $french = $key;
                    $english = $source_data[$key];
                    echo '<li><strong>' . htmlspecialchars($french) . '</strong> → ' . htmlspecialchars($english) . '</li>';
                }
                echo '</ul>';
            }
            echo '</div>';
            echo '</div>';
        } else {
            echo '<p class="error">❌ Fichier source introuvable</p>';
        }
        ?>
    </div>

    <!-- SECTION 5 : Utilisateur actuel -->
    <div class="section">
        <h2>5. Utilisateur Actuel</h2>
        <?php
        $user = wp_get_current_user();
        $user_lang = get_user_meta($user->ID, 'acs_user_language', true);
        ?>
        <div class="key-value">
            <div>Utilisateur :</div>
            <div><?php echo $user->display_name; ?> (ID: <?php echo $user->ID; ?>)</div>

            <div>Langue préférée :</div>
            <div class="<?php echo !empty($user_lang) ? 'success' : 'warning'; ?>">
                <?php echo !empty($user_lang) ? strtoupper($user_lang) : 'Non définie (défaut: FR)'; ?>
            </div>
        </div>
    </div>

    <!-- SECTION 6 : Recommandations -->
    <div class="section">
        <h2>6. Recommandations</h2>
        <?php
        $issues = [];

        if (!file_exists($translations_dir)) {
            $issues[] = '❌ <strong>CRITIQUE:</strong> Le répertoire de traductions n\'existe pas. Allez dans AI Content Studio → Traductions et cliquez "Générer Toutes les Traductions"';
        }

        if (file_exists($translations_dir)) {
            $files = glob($translations_dir . '/translations-*.json');
            if (count($files) < 30) {
                $issues[] = '⚠️ <strong>IMPORTANT:</strong> Seulement ' . count($files) . ' langues sur 30 ont été générées. Générez les langues manquantes.';
            }

            // Vérifier si les fichiers contiennent du vrai contenu
            foreach (['es', 'de', 'it'] as $lang) {
                $file = $translations_dir . "/translations-{$lang}.json";
                if (file_exists($file)) {
                    $content = file_get_contents($file);
                    $data = json_decode($content, true);
                    if (isset($data['Bienvenue']) && $data['Bienvenue'] === 'Bienvenue') {
                        $issues[] = '❌ <strong>CRITIQUE:</strong> La langue <strong>' . strtoupper($lang) . '</strong> n\'est pas traduite (contient du français). RÉGÉNÉREZ cette langue.';
                    }
                }
            }
        }

        if (empty(get_option('acs_claude_api_key'))) {
            $issues[] = '❌ <strong>CRITIQUE:</strong> Clé API Claude non configurée. Impossible de générer les traductions.';
        }

        if (empty($issues)) {
            echo '<p class="success">✅ Aucun problème détecté ! Le système devrait fonctionner correctement.</p>';
        } else {
            echo '<ul>';
            foreach ($issues as $issue) {
                echo '<li>' . $issue . '</li>';
            }
            echo '</ul>';
        }
        ?>
    </div>

    <div class="section">
        <h2>7. Actions</h2>
        <p>Si vous voyez des problèmes ci-dessus :</p>
        <ol>
            <li>Allez dans <strong>AI Content Studio → Traductions</strong></li>
            <li>Cliquez sur <strong>"Générer Toutes les Traductions"</strong> (ou régénérez les langues problématiques individuellement)</li>
            <li>Attendez la fin de la génération (~30 minutes pour 30 langues)</li>
            <li>Rafraîchissez cette page pour vérifier</li>
            <li>Testez le changement de langue dans le plugin</li>
        </ol>
    </div>
</body>
</html>
<?php
// Supprimer ce fichier après diagnostic pour sécurité
// unlink(__FILE__);
?>
