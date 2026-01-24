<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class TiktokScraper
{
    public function scrape(): array
    {
        try {
            // Path to your Python script
            $pythonScriptPath = base_path('scrape_tiktok.py');
            
            // Check if script exists
            if (!file_exists($pythonScriptPath)) {
                Log::error('Python script not found at: ' . $pythonScriptPath);
                return [];
            }
            
            // Execute Python script WITHOUT username parameter
            // Your script will use "pannonegyetem" as default
            $process = new Process(['python3', $pythonScriptPath]);
            $process->setTimeout(60);
            $process->run();
            
            if (!$process->isSuccessful()) {
                Log::error('Python script failed: ' . $process->getErrorOutput());
                return [];
            }
            
            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();
            
            // Log any errors
            if (!empty($errorOutput)) {
                Log::warning('TikTok scraper stderr: ' . $errorOutput);
            }
            
            // Parse JSON
            $result = json_decode($output, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Invalid JSON from Python script: ' . substr($output, 0, 500));
                return [];
            }
            
            // Filter out invalid entries
            return array_filter($result ?? [], function($video) {
                return !empty($video['url']);
            });
            
        } catch (Exception $e) {
            Log::error('TikTok scraping failed: ' . $e->getMessage());
            return [];
        }
    }
}