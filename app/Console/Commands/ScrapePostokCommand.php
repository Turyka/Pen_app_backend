<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Postok;

class ScrapePostokCommand extends Command
{
    protected $signature = 'scrape:fbpannon';
    protected $description = 'Scrapes posts from the Pannon Nagykanizsa Facebook page (basic attempt)';

    public function handle()
    {
        $url = 'https://www.facebook.com/pannon.nagykanizsa';

        $client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'Accept-Language' => 'en-US,en;q=0.9',
            ],
        ]);

        try {
            $response = $client->get($url);
        } catch (\Exception $e) {
            $this->error('Failed to fetch Facebook page: ' . $e->getMessage());
            return;
        }

        $html = (string) $response->getBody();
        $crawler = new Crawler($html);

        // Try grabbing post texts inside <div role="article"> (very fragile, likely empty)
        $posts = $crawler->filter('div[role="article"]');

        if ($posts->count() === 0) {
            $this->warn('No posts found — likely requires JavaScript or login.');
            return;
        }

        $posts->each(function ($node) use ($url) {
            // Look for text in possible message container
            $message = $node->filter('div[data-ad-preview="message"]')->count()
                ? $node->filter('div[data-ad-preview="message"]')->text()
                : '(No post message found)';

            Postok::create([
                'title' => 'Facebook Post',
                'description' => $message,
                'url' => $url,
            ]);

            $this->info('Saved post: ' . substr($message, 0, 100));
        });

        $this->info('Scraping completed.');
    }
}
