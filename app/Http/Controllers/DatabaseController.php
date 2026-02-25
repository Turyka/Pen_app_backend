<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DatabaseController extends Controller
{
    public function migrateRefresh()
    {
        try {
            Artisan::call('db:wipe', ['--force' => true]);
            $migrateStatus = Artisan::call('migrate', ['--force' => true]);
            $seedStatus = Artisan::call('db:seed', ['--force' => true]);
            
            return response()->json([
                'message' => 'Database refreshed successfully!',
                'migrate_status' => $migrateStatus,
                'seed_status' => $seedStatus,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Migration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function refreshAdatEszkozok()
    {
        DB::table('adat_eszkozok')->truncate();
        return response()->json(['message' => 'All data from adat_eszkozok has been deleted']);
    }

    // 🔹 Backup database and upload to Cloudinary
    public function backup()
    {
        $fileName = 'backup_' . now()->format('Y_m_d_His') . '.sql';
        $filePath = storage_path('app/' . $fileName);

        try {
            $driver = env('DB_CONNECTION');
            
            if ($driver === 'mysql') {
                $command = sprintf(
                    'mysqldump -h %s -u %s %s %s > %s',
                    env('DB_HOST'),
                    env('DB_USERNAME'),
                    env('DB_PASSWORD') ? '-p' . env('DB_PASSWORD') : '',
                    env('DB_DATABASE'),
                    $filePath
                );
            } elseif ($driver === 'pgsql') {
                putenv("PGPASSWORD=" . env('DB_PASSWORD'));
                $command = sprintf(
                    'pg_dump -h %s -U %s -d %s -f %s',
                    env('DB_HOST'),
                    env('DB_USERNAME'),
                    env('DB_DATABASE'),
                    $filePath
                );
            } else {
                throw new \Exception("Unsupported database driver: " . $driver);
            }
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new \Exception("Backup command failed");
            }

            // Upload to Cloudinary
            $cloudinary = new Cloudinary(env('CLOUDINARY_URL'));
            $result = $cloudinary->uploadApi()->upload($filePath, [
                'folder' => 'adatbazis',
                'resource_type' => 'raw',
            ]);

            unlink($filePath);
            
            if ($driver === 'pgsql') {
                putenv("PGPASSWORD");
            }

            return response()->json([
                'success' => 'Complete database backup saved to Cloudinary!',
                'download_url' => $result['secure_url'],
                'public_id' => $result['public_id'],
                'driver' => $driver,
                'filename' => $fileName
            ]);

        } catch (\Exception $e) {
            if (file_exists($filePath)) unlink($filePath);
            if ($driver === 'pgsql') putenv("PGPASSWORD");
            return response()->json(['error' => 'Backup failed: ' . $e->getMessage()], 500);
        }
    }

    // 🔹 Restore the newest backup from Cloudinary
    public function restoreNewest(Request $request)
    {
        try {
            // Initialize Cloudinary
            $cloudinary = new Cloudinary(env('CLOUDINARY_URL'));
            
            // Get the newest backup
            $result = $cloudinary->adminApi()->assets([
                'type' => 'upload',
                'prefix' => 'adatbazis',
                'resource_type' => 'raw',
                'max_results' => 1,
                'sort_by' => [['created_at' => 'desc']]
            ]);

            if (empty($result['resources'])) {
                return response()->json(['error' => 'No backups found'], 404);
            }

            $backup = $result['resources'][0];
            $downloadUrl = $backup['secure_url'];
            $tempFile = storage_path('app/restore_' . time() . '.sql');

            // Download the backup
            file_put_contents($tempFile, file_get_contents($downloadUrl));
            
            $driver = env('DB_CONNECTION');
            
            // Drop all tables first
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS = 0');
                $tables = DB::select('SHOW TABLES');
                $dbName = env('DB_DATABASE');
                $firstTable = (array)$tables[0];
                $propertyName = array_keys($firstTable)[0];
                
                foreach ($tables as $table) {
                    $tableArray = (array)$table;
                    DB::statement('DROP TABLE IF EXISTS `' . $tableArray[$propertyName] . '`');
                }
                DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            } elseif ($driver === 'pgsql') {
                DB::statement('DROP SCHEMA public CASCADE');
                DB::statement('CREATE SCHEMA public');
            }

            // Restore from backup
            if ($driver === 'mysql') {
                $command = sprintf(
                    'mysql -h %s -u %s %s %s < %s 2>&1',
                    env('DB_HOST'),
                    env('DB_USERNAME'),
                    env('DB_PASSWORD') ? '-p' . env('DB_PASSWORD') : '',
                    env('DB_DATABASE'),
                    $tempFile
                );
            } elseif ($driver === 'pgsql') {
                putenv("PGPASSWORD=" . env('DB_PASSWORD'));
                $command = sprintf(
                    'psql -h %s -U %s -d %s -f %s 2>&1',
                    env('DB_HOST'),
                    env('DB_USERNAME'),
                    env('DB_DATABASE'),
                    $tempFile
                );
            }
            
            exec($command, $output, $returnCode);
            
            unlink($tempFile);
            
            if ($driver === 'pgsql') {
                putenv("PGPASSWORD");
            }

            if ($returnCode !== 0) {
                throw new \Exception("Restore command failed: " . implode("\n", $output));
            }

            return response()->json([
                'success' => 'Database completely restored from backup!',
                'backup_used' => $backup['public_id'],
                'backup_date' => $backup['created_at'],
                'driver' => $driver
            ]);

        } catch (\Exception $e) {
            if (isset($tempFile) && file_exists($tempFile)) unlink($tempFile);
            if (isset($driver) && $driver === 'pgsql') putenv("PGPASSWORD");
            return response()->json(['error' => 'Restore failed: ' . $e->getMessage()], 500);
        }
    }

    // 🔹 Alternative restore method using PHP (if shell_exec is disabled)
    public function restoreNewestPHP(Request $request)
    {
        try {
            $cloudinary = new Cloudinary(env('CLOUDINARY_URL'));
            
            $result = $cloudinary->adminApi()->assets([
                'type' => 'upload',
                'prefix' => 'adatbazis',
                'resource_type' => 'raw',
                'max_results' => 1,
                'sort_by' => [['created_at' => 'desc']]
            ]);

            if (empty($result['resources'])) {
                return response()->json(['error' => 'No backups found'], 404);
            }

            $backup = $result['resources'][0];
            $downloadUrl = $backup['secure_url'];
            $fileContent = file_get_contents($downloadUrl);
            
            // Drop all tables
            DB::statement('DROP SCHEMA public CASCADE');
            DB::statement('CREATE SCHEMA public');
            
            // Execute the SQL
            $statements = explode(';', $fileContent);
            $executed = 0;
            $errors = [];
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    try {
                        DB::statement($statement);
                        $executed++;
                    } catch (\Exception $e) {
                        // Ignore duplicate errors
                        if (!str_contains($e->getMessage(), 'duplicate') && 
                            !str_contains($e->getMessage(), 'already exists')) {
                            $errors[] = $e->getMessage();
                        }
                    }
                }
            }

            return response()->json([
                'success' => 'Database restored using PHP method!',
                'backup_used' => $backup['public_id'],
                'statements_executed' => $executed,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Restore failed: ' . $e->getMessage()], 500);
        }
    }
}