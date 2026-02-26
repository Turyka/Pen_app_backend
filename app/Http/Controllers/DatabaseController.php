<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DatabaseController extends Controller
{
    /**
     * Backup database to Cloudinary
     */
    public function backup()
    {
        try {
            // All your tables in order
            $tables = [
                'users', 'hirek', 'naptar', 'postok', 'adat_eszkozok',
                'napi_login', 'kozlemeny', 'kepfeltoltes', 'facebook_posts', 
                'tiktok_posts', 'sessions', 'password_resets'
            ];
            
            $data = [];
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $data[$table] = DB::table($table)->get()->map(function ($item) {
                        return (array)$item;
                    });
                }
            }
            
            // Add metadata
            $backup = [
                'created_at' => now()->toDateTimeString(),
                'database' => env('DB_DATABASE'),
                'data' => $data
            ];
            
            // Create JSON file
            $fileName = 'backup_' . now()->format('Y_m_d_His') . '.json';
            $filePath = storage_path('app/' . $fileName);
            file_put_contents($filePath, json_encode($backup, JSON_PRETTY_PRINT));
            
            // Upload to Cloudinary
            $cloudinary = new Cloudinary(env('CLOUDINARY_URL'));
            $result = $cloudinary->uploadApi()->upload($filePath, [
                'folder' => 'database_backups',
                'resource_type' => 'raw',
                'public_id' => pathinfo($fileName, PATHINFO_FILENAME)
            ]);
            
            // Delete local file
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Backup completed successfully!',
                'file' => $fileName,
                'url' => $result['secure_url'],
                'size' => round($result['bytes'] / 1024, 2) . ' KB'
            ]);
            
        } catch (\Exception $e) {
            // Clean up if file exists
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            
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
            // Initialize Cloudinary
            $cloudinary = new Cloudinary(env('CLOUDINARY_URL'));
            
            // Get the newest backup from database_backups folder
            $result = $cloudinary->adminApi()->assets([
                'type' => 'upload',
                'prefix' => 'database_backups',
                'resource_type' => 'raw',
                'max_results' => 10,
                'sort_by' => [['created_at' => 'desc']]
            ]);

            if (empty($result['resources'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No backups found in Cloudinary'
                ], 404);
            }

            // Get the newest backup (first in the list)
            $backup = $result['resources'][0];
            $downloadUrl = $backup['secure_url'];
            $backupDate = $backup['created_at'];
            $backupName = $backup['public_id'];
            
            // Download the backup file
            $jsonContent = file_get_contents($downloadUrl);
            if ($jsonContent === false) {
                throw new \Exception('Failed to download backup file');
            }
            
            $backupData = json_decode($jsonContent, true);
            
            if (!$backupData || !isset($backupData['data'])) {
                throw new \Exception('Invalid backup file format');
            }
            
            // Start transaction
            DB::beginTransaction();
            
            try {
                // Temporarily disable foreign key checks
                DB::statement('SET CONSTRAINTS ALL DEFERRED;');
                
                // CORRECT ORDER: Delete in reverse order (children first, parents last)
                $deleteOrder = [
                    'sessions',           // Depends on users
                    'password_resets',    // Independent
                    'napi_login',         // Independent
                    'kozlemeny',          // Depends on users (user_id)
                    'adat_eszkozok',      // Independent
                    'tiktok_posts',       // Independent
                    'facebook_posts',     // Independent
                    'postok',             // Independent
                    'kepfeltoltes',       // Independent
                    'naptar',             // Independent
                    'hirek',              // Independent
                    'users'               // Parent - delete last
                ];
                
                // Clear all tables
                foreach ($deleteOrder as $table) {
                    if (isset($backupData['data'][$table]) && Schema::hasTable($table)) {
                        DB::table($table)->truncate();
                    }
                }
                
                // CORRECT ORDER: Insert in proper order (parents first, children last)
                $insertOrder = [
                    'users',              // Parent table - insert FIRST
                    'hirek',              // Independent
                    'naptar',             // Independent
                    'kepfeltoltes',       // Independent
                    'postok',             // Independent
                    'facebook_posts',     // Independent
                    'tiktok_posts',       // Independent
                    'adat_eszkozok',      // Independent
                    'kozlemeny',          // Depends on users - insert AFTER users
                    'napi_login',         // Independent
                    'password_resets',    // Independent
                    'sessions'            // Depends on users - insert LAST
                ];
                
                // Insert data in correct order
                foreach ($insertOrder as $table) {
                    if (isset($backupData['data'][$table]) && !empty($backupData['data'][$table]) && Schema::hasTable($table)) {
                        foreach ($backupData['data'][$table] as $row) {
                            $rowArray = (array)$row;
                            
                            // Handle users table specially to ensure passwords work
                            if ($table === 'users' && isset($rowArray['password'])) {
                                // Keep the password hash exactly as it is
                                // No need to re-hash
                            }
                            
                            // Insert the row
                            DB::table($table)->insert($rowArray);
                        }
                    }
                }
                
                // Reset sequences for all tables
                foreach ($insertOrder as $table) {
                    if (isset($backupData['data'][$table]) && !empty($backupData['data'][$table]) && Schema::hasTable($table)) {
                        // Get the maximum ID for this table
                        $maxId = DB::table($table)->max('id');
                        if ($maxId) {
                            // Reset the sequence to the max ID + 1
                            DB::statement("SELECT setval('{$table}_id_seq', {$maxId});");
                        }
                    }
                }
                
                // Re-enable foreign key checks
                DB::statement('SET CONSTRAINTS ALL IMMEDIATE;');
                
                DB::commit();
                
                // Count total records restored
                $totalRecords = 0;
                foreach ($backupData['data'] as $table => $rows) {
                    $totalRecords += count($rows);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Database restored successfully!',
                    'backup_file' => $backupName,
                    'backup_date' => $backupDate,
                    'tables_restored' => count($backupData['data']),
                    'total_records' => $totalRecords
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                // Make sure to re-enable constraints even on error
                DB::statement('SET CONSTRAINTS ALL IMMEDIATE;');
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
            // Run database wipe
            Artisan::call('db:wipe', ['--force' => true]);
            $wipeOutput = Artisan::output();
            
            // Run migrations
            Artisan::call('migrate', ['--force' => true]);
            $migrateOutput = Artisan::output();
            
            // Run seeders
            Artisan::call('db:seed', ['--force' => true]);
            $seedOutput = Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => 'Database refreshed successfully!',
                'wipe_output' => $wipeOutput,
                'migrate_output' => $migrateOutput,
                'seed_output' => $seedOutput
            ]);
            
        } catch (\Exception $e) {
            Log::error('Migration refresh failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Optional: List all backups (useful for debugging)
     */
    public function listBackups()
    {
        try {
            $cloudinary = new Cloudinary(env('CLOUDINARY_URL'));
            
            $result = $cloudinary->adminApi()->assets([
                'type' => 'upload',
                'prefix' => 'database_backups',
                'resource_type' => 'raw',
                'max_results' => 50,
                'sort_by' => [['created_at' => 'desc']]
            ]);
            
            $backups = [];
            foreach ($result['resources'] as $resource) {
                $backups[] = [
                    'public_id' => $resource['public_id'],
                    'url' => $resource['secure_url'],
                    'created_at' => $resource['created_at'],
                    'size' => round($resource['bytes'] / 1024, 2) . ' KB'
                ];
            }
            
            return response()->json([
                'success' => true,
                'backups' => $backups
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list backups: ' . $e->getMessage()
            ], 500);
        }
    }
}
