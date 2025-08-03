<?php

namespace App\Service\Export\SoundCloud;

use App\Entity\Provider;
use RuntimeException;

class SoundCloudPlaylistManager
{
    private const string API_BASE_URL = 'https://api.soundcloud.com';
    private const string CREATE_PLAYLIST_URL = '/playlists';

    public function __construct(
        private SoundCloudApiClient $apiClient,
    ) {
    }

    /**
     * @return array{id: int, permalink_url: string}
     */
    public function createPlaylist(Provider $provider, string $playlistName): array
    {
        $data = $this->apiClient->makeRequest(
            $provider,
            'POST',
            self::API_BASE_URL.self::CREATE_PLAYLIST_URL,
            [
                'json' => [
                    'playlist' => [
                        'title' => $playlistName,
                        'description' => 'Created with mixlink',
                        'sharing' => 'private',
                    ],
                ],
            ]
        );

        /** @var array{id: int, permalink_url: string} */
        return $data;
    }

    public function addTrackToPlaylist(Provider $provider, int $playlistId, int $trackId): void
    {
        $maxRetries = 3;
        $retryDelay = 1; // 1 second between each retry

        for ($attempt = 1; $attempt <= $maxRetries; ++$attempt) {
            try {
                // SoundCloud requires GET + PUT approach with complete playlist data
                $playlistData = $this->apiClient->makeRequest(
                    $provider,
                    'GET',
                    self::API_BASE_URL.'/playlists/'.$playlistId
                );

                /** @var array<int, array<string, mixed>> $currentTracks */
                $currentTracks = $playlistData['tracks'] ?? [];

                // Clean tracks to only keep the ID (SoundCloud API requirement)
                $cleanTracks = [];
                foreach ($currentTracks as $track) {
                    if (isset($track['id'])) {
                        $cleanTracks[] = ['id' => $track['id']];
                    }
                }

                // Add the new track to the existing tracks array
                $cleanTracks[] = ['id' => $trackId];

                $putData = [
                    'playlist' => [
                        'title' => $playlistData['title'] ?? 'mixlink Playlist',
                        'description' => $playlistData['description'] ?? 'Created with mixlink',
                        'sharing' => $playlistData['sharing'] ?? 'private',
                        'tracks' => $cleanTracks,
                    ],
                ];

                $this->apiClient->makeRequest(
                    $provider,
                    'PUT',
                    self::API_BASE_URL.'/playlists/'.$playlistId,
                    [
                        'json' => $putData,
                    ]
                );

                return;
            } catch (RuntimeException $e) {
                // If it's the last attempt, rethrow the exception
                if ($attempt === $maxRetries) {
                    throw $e;
                }

                // Otherwise, wait before retrying
                sleep($retryDelay);

                // Increase the delay for the next retry (exponential backoff)
                $retryDelay *= 2;
            }
        }
    }
}
