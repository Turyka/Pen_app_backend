<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseController extends Controller
{
    /**
     * Backup database to Cloudinary
     */
    public function backup()
    {
        try {
            // Get all data from your tables
            $tables = [
                'users', 'hirek', 'naptar', 'postok', 'adat_eszkozok',
                'napi_login', 'kozlemeny', 'kepfeltoltes', 'facebook_posts', 
                'tiktok_posts', 'sessions', 'password_resets'
            ];
            
            $data = [];
            foreach ($tables as $table) {
                // Check if table exists
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    $data[$table] = DB::table($table)->get();
                }
            }
            
            // Add metadata
            $backup = [
                'created_at' => now()->toDateTimeString(),
                'database' => env('DB_DATABASE'),
                'data' => $data
            ];
            
            // Convert to JSON
            $fileName = 'backup_' . now()->format('Y_m_d_His') . '.json';
            $filePath = storage_path('app/' . $fileName);
            file_put_contents($filePath, json_encode($backup));
            
            // Upload to Cloudinary
            $cloudinary = new Cloudinary(env('CLOUDINARY_URL'));
            $result = $cloudinary->uploadApi()->upload($filePath, [
                'folder' => 'database_backups',
                'resource_type' => 'raw'
            ]);
            
            // Delete local file
            unlink($filePath);
            
            return response()->json([
                'success' => true,
                'message' => 'Backup completed!',
                'file' => $fileName,
                'url' => $result['secure_url']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Backup failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Restore newest backup from Cloudinary
     */
    public function restoreNewest()
    {
        try {
            // Get newest backup from Cloudinary
            $cloudinary = new Cloudinary(env('CLOUDINARY_URL'));
            
            $result = $cloudinary->adminApi()->assets([
                'type' => 'upload',
                'prefix' => 'database_backups',
                'resource_type' => 'raw',
                'max_results' => 1,
                'sort_by' => [['created_at' => 'desc']]
            ]);

            if (empty($result['resources'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No backups found'
                ], 404);
            }

            // Download backup
            $backup = $result['resources'][0];
            $jsonContent = file_get_contents($backup['secure_url']);
            $backupData = json_decode($jsonContent, true);
            
            if (!$backupData || !isset($backupData['data'])) {
                throw new \Exception('Invalid backup file');
            }
            
            // Start transaction
            DB::beginTransaction();
            
            try {
                // Clear existing data (except migrations table)
                foreach ($backupData['data'] as $table => $rows) {
                    if ($table !== 'migrations') {
                        DB::table($table)->truncate();
                    }
                }
                
                // Insert backup data
                foreach ($backupData['data'] as $table => $rows) {
                    foreach ($rows as $row) {
                        DB::table($table)->insert((array)$row);
                    }
                }
                
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Database restored from: ' . $backup['public_id'],
                    'backup_date' => $backupData['created_at'] ?? 'unknown'
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('Restore failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Migrate refresh - wipe all tables and run migrations+seeds
     */
    public function migrateRefresh()
    {
        try {
            Artisan::call('db:wipe', ['--force' => true]);
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);
            
            return response()->json([
                'success' => true,
                'message' => 'Database refreshed successfully!',
                'output' => Artisan::output()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage()
            ], 500);
        }
    }
}