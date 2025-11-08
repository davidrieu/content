<?php
/**
 * SEO Analysis Service
 *
 * @package ACS\Services
 */

namespace ACS\Services;

if (!defined('ABSPATH')) {
    exit;
}

class SEO_Analyzer {

    /**
     * Analyze content for SEO
     *
     * @param string $content Article content
     * @param string $title Article title
     * @param string $keyword Focus keyword
     * @return array
     */
    public function analyze($content, $title, $keyword) {
        $score = 0;
        $checks = [];
        $suggestions = [];

        // 1. Keyword in title (20 points)
        if (stripos($title, $keyword) !== false) {
            $score += 20;
            $checks['keyword_in_title'] = true;
        } else {
            $checks['keyword_in_title'] = false;
            $suggestions[] = __('Ajoutez le mot-clé dans le titre', 'ai-content-studio');
        }

        // 2. Title length (10 points)
        $title_length = strlen($title);
        if ($title_length >= 30 && $title_length <= 60) {
            $score += 10;
            $checks['title_length'] = true;
        } else {
            $checks['title_length'] = false;
            $suggestions[] = sprintf(__('Longueur de titre recommandée : 30-60 caractères (actuel : %d)', 'ai-content-studio'), $title_length);
        }

        // 3. Content length (15 points)
        $word_count = str_word_count(strip_tags($content));
        if ($word_count >= 1000) {
            $score += 15;
            $checks['content_length'] = true;
        } else {
            $checks['content_length'] = false;
            $suggestions[] = sprintf(__('Contenu trop court : %d mots (minimum recommandé : 1000)', 'ai-content-studio'), $word_count);
        }

        // 4. Keyword density (15 points)
        $keyword_count = substr_count(strtolower($content), strtolower($keyword));
        $density = ($word_count > 0) ? ($keyword_count / $word_count) * 100 : 0;

        if ($density >= 0.5 && $density <= 2.5) {
            $score += 15;
            $checks['keyword_density'] = true;
        } else {
            $checks['keyword_density'] = false;
            $suggestions[] = sprintf(__('Densité de mot-clé : %.2f%% (recommandé : 0.5-2.5%%)', 'ai-content-studio'), $density);
        }

        // 5. Keyword in first paragraph (10 points)
        $first_paragraph = $this->get_first_paragraph($content);
        if (stripos($first_paragraph, $keyword) !== false) {
            $score += 10;
            $checks['keyword_in_intro'] = true;
        } else {
            $checks['keyword_in_intro'] = false;
            $suggestions[] = __('Ajoutez le mot-clé dans le premier paragraphe', 'ai-content-studio');
        }

        // 6. Headings structure (15 points)
        $h2_count = substr_count($content, '<h2>');
        $h3_count = substr_count($content, '<h3>');

        if ($h2_count >= 2 && $h2_count <= 6) {
            $score += 10;
            $checks['headings_structure'] = true;
        } else {
            $checks['headings_structure'] = false;
            $suggestions[] = __('Utilisez 2-6 titres H2 pour structurer le contenu', 'ai-content-studio');
        }

        // Keyword in headings (5 points)
        if (preg_match('/<h[2-3]>.*?' . preg_quote($keyword, '/') . '.*?<\/h[2-3]>/i', $content)) {
            $score += 5;
            $checks['keyword_in_headings'] = true;
        } else {
            $checks['keyword_in_headings'] = false;
            $suggestions[] = __('Incluez le mot-clé dans au moins un titre H2 ou H3', 'ai-content-studio');
        }

        // 7. Readability (15 points)
        $readability = $this->calculate_readability($content);
        if ($readability >= 60) {
            $score += 15;
            $checks['readability'] = true;
        } else {
            $checks['readability'] = false;
            $suggestions[] = __('Améliorez la lisibilité avec des phrases plus courtes', 'ai-content-studio');
        }

        return [
            'score' => $score,
            'checks' => $checks,
            'suggestions' => $suggestions,
            'metrics' => [
                'word_count' => $word_count,
                'keyword_count' => $keyword_count,
                'keyword_density' => $density,
                'title_length' => $title_length,
                'h2_count' => $h2_count,
                'h3_count' => $h3_count,
                'readability_score' => $readability,
            ],
        ];
    }

    /**
     * Get first paragraph
     *
     * @param string $content
     * @return string
     */
    private function get_first_paragraph($content) {
        preg_match('/<p>(.*?)<\/p>/s', $content, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Calculate readability (Flesch Reading Ease approximation)
     *
     * @param string $content
     * @return float
     */
    private function calculate_readability($content) {
        $text = strip_tags($content);
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $words = str_word_count($text);
        $syllables = $this->count_syllables($text);

        if (count($sentences) == 0 || $words == 0) {
            return 0;
        }

        $avg_sentence_length = $words / count($sentences);
        $avg_syllables_per_word = $syllables / $words;

        // Flesch Reading Ease formula
        $score = 206.835 - (1.015 * $avg_sentence_length) - (84.6 * $avg_syllables_per_word);

        return max(0, min(100, $score));
    }

    /**
     * Count syllables (approximate)
     *
     * @param string $text
     * @return int
     */
    private function count_syllables($text) {
        $words = str_word_count(strtolower($text), 1);
        $total_syllables = 0;

        foreach ($words as $word) {
            $syllables = preg_match_all('/[aeiouy]+/', $word);
            $total_syllables += max(1, $syllables);
        }

        return $total_syllables;
    }
}
