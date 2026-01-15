<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScrapeTiktok extends Command
{
    protected $signature = 'tiktok:scrape {profile}';
    protected $description = 'Scrape latest 10 TikTok videos from a profile';

    public function handle()
    {
        $profile = $this->argument('profile');
        $this->info("Scraping TikTok profile: $profile");

        $pythonPath = public_path('scrape_tiktok.py');
        $cmd = sprintf('cd %s && python3 %s', public_path(), basename($pythonPath));

        $output = shell_exec($cmd);
        $data = json_decode($output, true);

        if (!$data || $data['status'] !== 'success' || empty($data['posts'])) {
            $this->error("Scraping failed");
            Log::error('TikTok scrape failed', ['output' => $output]);
            return 1;
        }

        $saved = 0;
        foreach ($data['posts'] as $post) {
            if (!$post['url']) continue;

            // Skip duplicates
            $exists = DB::table('tiktok_posts')->where('url', $post['url'])->exists();
            if ($exists) continue;

            DB::table('tiktok_posts')->insert([
                'url' => $post['url'],
                'image_url' => $post['image_url'] ?? '',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $saved++;
        }

        $this->info("✅ Scraping complete. New posts saved: $saved");
        return 0;
    }
}
