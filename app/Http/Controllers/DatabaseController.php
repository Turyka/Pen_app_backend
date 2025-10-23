<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\DB;

class DatabaseController extends Controller
{
    // 🔹 Backup database and upload to Cloudinary
public function backup()
{
    $fileName = 'backup_' . now()->format('Y_m_d_His') . '.sql';
    $filePath = storage_path('app/' . $fileName);

    try {
        $sqlContent = "-- Real Database Backup\n";
        $sqlContent .= "-- Created: " . now()->toDateTimeString() . "\n";
        $sqlContent .= "-- Database: " . env('DB_DATABASE') . "\n\n";
        
        // Get all table names
        $tables = DB::select('SHOW TABLES');
        $dbName = env('DB_DATABASE');
        $firstTable = (array)$tables[0];
        $propertyName = array_keys($firstTable)[0];
        
        foreach ($tables as $table) {
            $tableArray = (array)$table;
            $tableName = $tableArray[$propertyName];
            
            // Get table structure
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $createTableArray = (array)$createTable[0];
            $createStatement = $createTableArray['Create Table'];
            
            $sqlContent .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $sqlContent .= $createStatement . ";\n\n";
            
            // Get all data from the table
            $rows = DB::table($tableName)->get();
            
            if ($rows->count() > 0) {
                foreach ($rows as $row) {
                    $columns = [];
                    $values = [];
                    
                    foreach ($row as $column => $value) {
                        $columns[] = "`{$column}`";
                        $values[] = $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                    }
                    
                    $sqlContent .= "INSERT INTO `{$tableName}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
                }
                $sqlContent .= "\n";
            }
        }

        // Save to file
        file_put_contents($filePath, $sqlContent);

        // Upload to Cloudinary
        $cloudinary = new Cloudinary(env('CLOUDINARY_URL'));
        $result = $cloudinary->uploadApi()->upload($filePath, [
            'folder' => 'adatbazis',
            'resource_type' => 'raw',
        ]);

        unlink($filePath);

        return response()->json([
            'success' => 'Real database backup saved to Cloudinary!',
            'download_url' => $result['secure_url'],
            'public_id' => $result['public_id']
        ]);

    } catch (\Exception $e) {
        if (file_exists($filePath)) unlink($filePath);
        return response()->json(['error' => 'Failed: ' . $e->getMessage()], 500);
    }
}

    // 🔹 Download and restore database from Cloudinary
public function restoreNewest()
{
    try {
        // Initialize Cloudinary
        $cloudinary = new Cloudinary(env('CLOUDINARY_URL'));
        
        // Get ALL backups from adatbazis folder
        $result = $cloudinary->adminApi()->assets([
            'type' => 'upload',
            'prefix' => 'adatbazis',
            'resource_type' => 'raw',
            'max_results' => 100
        ]);

        if (empty($result['resources'])) {
            return response()->json(['error' => 'No backups found in adatbazis folder'], 404);
        }

        // Sort backups by creation date manually (newest first)
        $backups = $result['resources'];
        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        $newestBackup = $backups[0];
        $public_id = $newestBackup['public_id'];
        $downloadUrl = $newestBackup['secure_url'];

        // Download the SQL file
        $fileContent = file_get_contents($downloadUrl);
        
        // Split SQL file into individual queries
        $queries = array_filter(array_map('trim', 
            preg_split('/;\s*$/m', $fileContent)
        ));

        // Execute each query
        $executedQueries = 0;
        $errors = [];

        foreach ($queries as $query) {
            if (!empty(trim($query))) {
                try {
                    DB::statement($query);
                    $executedQueries++;
                } catch (\Exception $e) {
                    $errors[] = "Query failed: " . $e->getMessage();
                }
            }
        }

        return response()->json([
            'success' => 'Database restored from newest backup!',
            'backup_used' => $public_id,
            'backup_created' => $newestBackup['created_at'],
            'queries_executed' => $executedQueries,
            'errors' => $errors
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Restore failed: ' . $e->getMessage()], 500);
    }
}

    // 🔹 List all available backups from Cloudinary
    public function listBackups()
    {
        $cloudinary = new Cloudinary(env('CLOUDINARY_URL'));

        $result = $cloudinary->adminApi()->assets([
            'type' => 'upload',
            'prefix' => 'database_backups',
            'resource_type' => 'raw',
            'max_results' => 50
        ]);

        $backups = collect($result['resources'])->map(function($backup) {
            return [
                'public_id' => $backup['public_id'],
                'url' => $backup['secure_url'],
                'created_at' => $backup['created_at'],
                'size' => $backup['bytes']
            ];
        });
        dd($backups);
        return response()->json(['backups' => $backups]);
    }
}