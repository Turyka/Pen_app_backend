<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScrapeFacebook extends Command
{
    protected $signature = 'facebook:scrape';
    protected $description = 'Scrape latest Facebook post';

    public function handle()
    {
        $this->info('Scraping Facebook...');
        
        // ---------- FULL PATH + Working directory ----------
        $pythonPath = public_path('scrape_facebook.py');
        $cmd = "cd " . public_path() . " && python3 scrape_facebook.py 2>&1";
        
        $this->info("Running: $cmd");
        $output = shell_exec($cmd);
        
        $this->info("Raw output: " . substr($output, 0, 200));
        Log::info('Facebook scrape raw', ['output' => $output]);
        
        $post = json_decode($output, true);
        
        if (!$post || empty($post['latest_1']['title'])) {
            $this->error('❌ No post scraped');
            Log::error('Facebook scrape failed', ['output' => $output]);
            return 1;
        }

        // ---------- SAVE ----------
        DB::table('facebook_posts')->updateOrInsert(
            ['url' => $post['latest_1']['url']],
            [
                'title' => $post['latest_1']['title'],
                'image_url' => $post['latest_1']['image_url'],
                'updated_at' => now()
            ]
        );

        $this->info("✅ Saved: " . substr($post['latest_1']['title'], 0, 80));
        return 0;
    }
}