<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Hir;
use DOMDocument;
use DOMXPath;

class ScrapeHirekCommand extends Command
{
    protected $signature = 'scrape:hirek';
    protected $description = 'Scrape news from PEN site and store them in the database';

    public function handle(): int
    {
        $response = Http::withOptions([
        'verify' => false
        ])->get('https://pen.uni-pannon.hu/hirek/');

        if (!$response->successful()) {
            $this->error('Sikertelen lehúzás');
            return Command::FAILURE;
        }
        
        $html = $response->body();
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        // UPDATED: New selector for current website structure
        $cards = $xpath->query("//div[contains(@class, 'wp-block-group') and .//figure[contains(@class, 'wp-block-post-featured-image')]]");
        
        $this->info("Found " . $cards->length . " cards on the page");
        
        $added = 0;
        $cardsArray = iterator_to_array($cards);
        $cardsArray = array_reverse($cardsArray);

        foreach ($cardsArray as $card) {
            // UPDATED: New selectors for current structure
            $titleNode = $xpath->query(".//h2[contains(@class, 'wp-block-post-title')]/a", $card);
            $title = $titleNode->length ? trim($titleNode[0]->nodeValue) : '';
            $link = $titleNode->length ? $titleNode[0]->getAttribute('href') : '';
            
            // UPDATED: Image now comes from src attribute, not data-dpt-src
            $imgNode = $xpath->query(".//figure[contains(@class,'wp-block-post-featured-image')]//img", $card);
            $image = $imgNode->length ? $imgNode[0]->getAttribute('src') : '';
            
            // UPDATED: Date now comes from time element with datetime attribute
            $dateNode = $xpath->query(".//time[@datetime]", $card);
            $date = $dateNode->length ? $dateNode[0]->getAttribute('datetime') : null;

            // Make URLs absolute if they're relative
            if ($link && strpos($link, 'http') !== 0) {
                $link = 'https://pen.uni-pannon.hu' . $link;
            }
            if ($image && strpos($image, 'http') !== 0) {
                $image = 'https://pen.uni-pannon.hu' . $image;
            }

            // Find existing news with same title
            $mar_van = Hir::where('title', $title)
              ->where('date', $date)
              ->first();

            if ($mar_van) {
                // If both image and date are the same → skip
                if ($mar_van->image === $image && $mar_van->date === $date) {
                    continue;
                }

                // If date or image differ → update them
                $mar_van->update([
                    'image' => $image,
                    'date' => $date,
                ]);

                $this->info("Frissítve: $title ($date)");
                continue;
            }

            if (Hir::where('link', $link)->exists()) {
                continue;
            }

            Hir::create([
                'title' => $title,
                'link' => $link,
                'image' => $image,
                'date' => $date,
            ]);

            $added++;
        }

        $this->info("Scraped $added new news items.");
        return Command::SUCCESS;
    }
}