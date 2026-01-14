<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class InstaGroupCrawler
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            ]
        ]);
    }

    public function crawlProfile(string $username, int $limit = 10): array
    {
        try {
            $profileUrl = "https://www.instagram.com/{$username}/";
            $response = $this->client->get($profileUrl);
            $html = (string) $response->getBody();

            // Extract JSON data from Instagram's window._sharedData or window.__additionalData
            $posts = $this->extractPostsFromHtml($html);

            // Filter and limit to top N posts
            $posts = array_slice($posts, 0, $limit);

            Log::info("Successfully crawled {$username}", ['count' => count($posts)]);

            return $posts;

        } catch (RequestException $e) {
            Log::error("Instagram crawl failed for {$username}", [
                'error' => $e->getMessage(),
                'status' => $e->getResponse()?->getStatusCode() ?? 'unknown'
            ]);
            return [];
        } catch (\Exception $e) {
            Log::error("Unexpected error crawling Instagram {$username}", ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function extractPostsFromHtml(string $html): array
    {
        $posts = [];

        // Method 1: Try window._sharedData (older format)
        if (preg_match('/window\._sharedData\s*=\s*({.+?});<\/script>/s', $html, $matches)) {
            $data = json_decode($matches[1], true);
            return $this->parseSharedData($data);
        }

        // Method 2: Try window.__additionalData (newer format with LDF - Lightweight Data Format)
        if (preg_match('/window\.__additionalDataLoaded\([^)]+,\s*({.+?})\);/s', $html, $matches)) {
            $data = json_decode($matches[1], true);
            return $this->parseAdditionalData($data);
        }

        // Method 3: Fallback - extract from script tags with JSON
        if (preg_match_all('/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $ldJsonMatches)) {
            foreach ($ldJsonMatches[1] as $json) {
                $item = json_decode($json, true);
                if (isset($item['@type']) && $item['@type'] === 'ImageObject') {
                    $posts[] = $this->formatPostFromLdJson($item);
                }
            }
        }

        return $posts;
    }

    private function parseSharedData(array $data): array
    {
        $posts = [];
        
        if (isset($data['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'])) {
            foreach ($data['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'] as $edge) {
                $node = $edge['node'];
                $posts[] = $this->formatPost($node);
            }
        }

        return $posts;
    }

    private function parseAdditionalData(array $data): array
    {
        $posts = [];
        
        if (isset($data['profilePage'][0]['data']['user']['edge_owner_to_timeline_media']['edges'])) {
            foreach ($data['profilePage'][0]['data']['user']['edge_owner_to_timeline_media']['edges'] as $edge) {
                $posts[] = $this->formatPost($edge['node']);
            }
        }

        return $posts;
    }

    private function formatPost(array $node): array
    {
        $thumbnail = $node['thumbnail_src'] ?? null;
        $firstImage = null;

        // Get first image from carousel if it's a multi-image post
        if (isset($node['edge_sidecar_to_children']['edges'][0]['node']['display_url'])) {
            $firstImage = $node['edge_sidecar_to_children']['edges'][0]['node']['display_url'];
        } elseif (isset($node['display_url'])) {
            $firstImage = $node['display_url'];
        } elseif ($thumbnail) {
            $firstImage = $thumbnail;
        }

        return [
            'url_path' => "/p/{$node['shortcode']}/",
            'full_url' => "https://www.instagram.com/p/{$node['shortcode']}/",
            'title' => $node['edge_media_to_caption']['edges'][0]['node']['text'] ?? 'No caption',
            'image_url' => $firstImage,
            'thumbnail' => $thumbnail,
            'likes' => $node['edge_media_preview_like']['count'] ?? 0,
            'timestamp' => $node['taken_at_timestamp'] ?? null,
            'is_video' => $node['is_video'] ?? false,
        ];
    }

    private function formatPostFromLdJson(array $item): array
    {
        return [
            'url_path' => parse_url($item['url'] ?? '', PHP_URL_PATH),
            'full_url' => $item['url'] ?? '',
            'title' => $item['caption'] ?? 'No caption',
            'image_url' => $item['contentUrl'] ?? $item['thumbnailUrl'] ?? null,
            'thumbnail' => $item['thumbnailUrl'] ?? null,
            'likes' => 0,
            'timestamp' => null,
            'is_video' => false,
        ];
    }
}