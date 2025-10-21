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
        $response = Http::get('https://pen.uni-pannon.hu/hirek/');

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

        $cards = $xpath->query("//div[contains(@class, 'dpt-entry')]");
        $added = 0;
        $cardsArray = iterator_to_array($cards);
        $cardsArray = array_reverse($cardsArray);

        foreach ($cardsArray as $card) {
            $titleNode = $xpath->query(".//h3[contains(@class, 'dpt-title')]/a", $card);
            $title = $titleNode->length ? trim($titleNode[0]->nodeValue) : '';
            $link = $titleNode->length ? $titleNode[0]->getAttribute('href') : '';
            $imgNode = $xpath->query(".//img", $card);
            $image = $imgNode->length ? $imgNode[0]->getAttribute('data-dpt-src') : '';
            $dateNode = $xpath->query(".//div[contains(@class, 'dpt-date')]/time", $card);
            $date = $dateNode->length ? $dateNode[0]->getAttribute('datetime') : null;

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
