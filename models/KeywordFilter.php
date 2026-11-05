<?php

class KeywordFilter
{
    private array $blockedWords;
    private array $blockedPhrases;

    public function __construct()
    {
        $config = require __DIR__ . "/../includes/blocked_keywords.php";

        $this->blockedWords = $config["blocked_words"] ?? [];
        $this->blockedPhrases = $config["blocked_phrases"] ?? [];
    }

    public function containsBlockedContent(string $text): bool
    {
        $text = strtolower($text);;

        foreach ($this->blockedWords as $word) {
            $word = strtolower($word);

            $pattern = '/(?<![\p{L}\p{N}_])'
                . preg_quote($word, '/')
                . '(?![\p{L}\p{N}_])/u';

            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        $normalizedText = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);
        $normalizedText = trim(preg_replace('/\s+/u', ' ', $normalizedText));

        foreach ($this->blockedPhrases as $phrase) {
            $phrase = strtolower($phrase);
            $phrase = preg_quote($phrase, '/');
            $phrase = str_replace('\ ', '\s+', $phrase);

            if (preg_match('/(?<![\p{L}\p{N}_])' . $phrase . '(?![\p{L}\p{N}_])/u', $normalizedText)) {
                return true;
            }
        }

        return false;
    }
}
