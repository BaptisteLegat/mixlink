<?php

namespace App\Service\Export\SoundCloud;

use App\Entity\Provider;
use RuntimeException;

class SoundCloudTrackSearcher
{
    private const string API_BASE_URL = 'https://api.soundcloud.com';
    private const int MIN_SCORE_THRESHOLD = 15;
    private const int MIN_PARTIAL_LENGTH = 4;
    private const int REMIX_SCORE_DIVISOR = 3;
    private const int BOTH_CONTAIN_SCORE = 20;

    public function __construct(
        private SoundCloudApiClient $apiClient,
    ) {
    }

    public function searchTrack(Provider $provider, string $title, string $artists): ?int
    {
        $cleanTitle = $this->cleanSearchTerm($title);
        $cleanArtists = $this->cleanSearchTerm($artists);

        // For complex titles like "PATT (Party All The Time)", also try with parentheses content
        $titleWithParentheses = $this->extractTitleWithParentheses($title);

        $searchQueries = [
            $cleanTitle.' '.$cleanArtists,
            $titleWithParentheses.' '.$cleanArtists,
            $cleanTitle,
            $titleWithParentheses,
            $cleanArtists.' '.$cleanTitle,
            $cleanTitle.' '.$this->extractMainArtist($cleanArtists),
            $titleWithParentheses.' '.$this->extractMainArtist($cleanArtists),
        ];

        $searchQueries = array_values(array_unique($searchQueries));

        foreach ($searchQueries as $searchQuery) {
            try {
                /** @var array<int, array<string, mixed>> $data */
                $data = $this->apiClient->makeRequest(
                    $provider,
                    'GET',
                    self::API_BASE_URL.'/tracks',
                    [
                        'query' => [
                            'q' => $searchQuery,
                            'limit' => 15,
                            'filter' => 'public',
                            'order' => 'hotness',
                        ],
                    ]
                );

                if (!empty($data)) {
                    $bestMatch = $this->findBestMatch($data, $cleanTitle, $cleanArtists, $titleWithParentheses);
                    if (null !== $bestMatch) {
                        return $bestMatch;
                    }
                }
            } catch (RuntimeException $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $tracks
     */
    private function findBestMatch(array $tracks, string $title, string $artists, string $titleWithParentheses = ''): ?int
    {
        $titleLower = strtolower($title);
        $artistsLower = strtolower($artists);
        $titleWithParenthesesLower = strtolower($titleWithParentheses);

        $bestScore = 0;
        $bestTrackId = null;

        foreach ($tracks as $track) {
            if (!isset($track['id']) || !is_int($track['id'])) {
                continue;
            }

            $trackTitle = strtolower((string) ($track['title'] ?? ''));
            $trackUser = strtolower((string) ($track['user']['username'] ?? ''));

            $isRemix = $this->isRemixOrCover($trackTitle);

            $score = $this->calculateMatchScore($trackTitle, $trackUser, $titleLower, $artistsLower);

            if (!empty($titleWithParenthesesLower) && $titleWithParenthesesLower !== $titleLower) {
                $scoreWithParentheses = $this->calculateMatchScore($trackTitle, $trackUser, $titleWithParenthesesLower, $artistsLower);
                $score = max($score, $scoreWithParentheses);
            }

            if ($isRemix) {
                $score = (int) ($score / self::REMIX_SCORE_DIVISOR);
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestTrackId = $track['id'];
            }
        }

        if ($bestScore >= self::MIN_SCORE_THRESHOLD) {
            return $bestTrackId;
        }

        return null;
    }

    private function isRemixOrCover(string $trackTitle): bool
    {
        // Check for remix keywords that are NOT in parentheses/brackets
        $generalRemixKeywords = [
            'mashup', 'cover', 'vs', 'version', 'rework', 'flip', 'dub',
            'instrumental', 'karaoke', 'acoustic', 'live', 'extended', 'radio edit', 'club mix',
        ];

        foreach ($generalRemixKeywords as $keyword) {
            if (str_contains($trackTitle, $keyword)) {
                return true;
            }
        }

        // Check for specific remix keywords ONLY in parentheses
        if (preg_match('/\([^)]*(remix|edit|mix|vip|bootleg)[^)]*\)/i', $trackTitle)) {
            return true;
        }

        // Check for specific remix keywords ONLY in brackets
        if (preg_match('/\[[^\]]*(remix|edit|mix|vip|bootleg)[^\]]*\]/i', $trackTitle)) {
            return true;
        }

        // Special case: if "remix" appears at the end (likely in artist name), it's still a remix
        if (preg_match('/\bremix\s*$/i', $trackTitle)) {
            return true;
        }

        return false;
    }

    private function calculateMatchScore(string $trackTitle, string $trackUser, string $searchTitle, string $searchArtists): int
    {
        $score = 0;

        $score += $this->calculateExactMatchScore($trackTitle, $trackUser, $searchTitle, $searchArtists);
        $score += $this->calculatePartialMatchScore($trackTitle, $trackUser, $searchTitle, $searchArtists);
        $score += $this->calculateBothContainScore($trackTitle, $trackUser, $searchTitle, $searchArtists);
        $score += $this->calculatePrefixMatchScore($trackTitle, $trackUser, $searchTitle, $searchArtists);

        return $score;
    }

    private function calculateExactMatchScore(string $trackTitle, string $trackUser, string $searchTitle, string $searchArtists): int
    {
        $score = 0;

        if ($trackTitle === $searchTitle) {
            $score += 100;
        }

        if ($trackUser === $searchArtists) {
            $score += 50;
        }

        return $score;
    }

    private function calculatePartialMatchScore(string $trackTitle, string $trackUser, string $searchTitle, string $searchArtists): int
    {
        $score = 0;

        if (str_contains($trackTitle, $searchTitle)) {
            $score += 40;
        }

        if (str_contains($trackUser, $searchArtists)) {
            $score += 30;
        }

        return $score;
    }

    private function calculateBothContainScore(string $trackTitle, string $trackUser, string $searchTitle, string $searchArtists): int
    {
        if (str_contains($trackTitle, $searchTitle) && str_contains($trackUser, $searchArtists)) {
            return self::BOTH_CONTAIN_SCORE;
        }

        return 0;
    }

    private function calculatePrefixMatchScore(string $trackTitle, string $trackUser, string $searchTitle, string $searchArtists): int
    {
        $score = 0;

        if (strlen($searchTitle) >= self::MIN_PARTIAL_LENGTH && str_contains($trackTitle, substr($searchTitle, 0, self::MIN_PARTIAL_LENGTH))) {
            $score += 15;
        }

        if (strlen($searchArtists) >= self::MIN_PARTIAL_LENGTH && str_contains($trackUser, substr($searchArtists, 0, self::MIN_PARTIAL_LENGTH))) {
            $score += 10;
        }

        return $score;
    }

    private function extractTitleWithParentheses(string $title): string
    {
        // If there are parentheses, extract the content and combine with the main part
        if (preg_match('/^([^(]+)\s*\(([^)]+)\)/', $title, $matches)) {
            $parenthesesPart = trim($matches[2]);

            return $parenthesesPart;
        }

        return $title;
    }

    private function cleanSearchTerm(string $term): string
    {
        $term = preg_replace('/\s*\([^)]*\)/', '', $term) ?? '';
        $term = preg_replace('/\s*feat\.?\s*/i', ' ', $term) ?? '';
        $term = preg_replace('/\s*ft\.?\s*/i', ' ', $term) ?? '';
        $term = preg_replace('/\s*featuring\s*/i', ' ', $term) ?? '';
        $term = preg_replace('/\s*\(feat\.?\s*[^)]*\)/i', '', $term) ?? '';

        return trim($term);
    }

    private function extractMainArtist(string $artists): string
    {
        // Assuming the main artist is the first one listed, split by commas
        $mainArtist = explode(',', $artists)[0];

        return trim($mainArtist);
    }
}
