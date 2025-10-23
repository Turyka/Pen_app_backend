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
        // First drop all tables to avoid the "relation already exists" error
        $tables = DB::select("SELECT tablename as table_name FROM pg_tables WHERE schemaname = 'public'");
        
        // Disable foreign key checks
        DB::statement('SET session_replication_role = replica;');
        
        foreach ($tables as $table) {
            DB::statement('DROP TABLE IF EXISTS "' . $table->table_name . '" CASCADE;');
        }
        
        // Re-enable foreign key checks
        DB::statement('SET session_replication_role = origin;');
        
        // Now run migrate:refresh
        $migrateStatus = Artisan::call('migrate:refresh', ['--force' => true]);
        $migrateOutput = Artisan::output();

        $seedStatus = Artisan::call('db:seed', ['--force' => true]);
        $seedOutput = Artisan::output();

        return response()->json([
            'message' => 'Database migrated and seeded successfully.',
            'migrate_status' => $migrateStatus,
            'seed_status' => $seedStatus,
            'migrate_output' => $migrateOutput,
            'seed_output' => $seedOutput,
            'tables_dropped' => count($tables)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred during migration refresh.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    // 🔹 Backup database and upload to Cloudinary
public function backup()
{
    $fileName = 'backup_' . now()->format('Y_m_d_His') . '.sql';
    $filePath = storage_path('app/' . $fileName);

    try {
        $sqlContent = "-- Real Database Backup\n";
        $sqlContent .= "-- Created: " . now()->toDateTimeString() . "\n";
        $sqlContent .= "-- Database: " . env('DB_DATABASE') . "\n";
        $sqlContent .= "-- Driver: " . env('DB_CONNECTION') . "\n\n";
        
        $driver = env('DB_CONNECTION');
        
        if ($driver === 'mysql') {
            $sqlContent .= $this->generateMySQLBackup();
        } elseif ($driver === 'pgsql') {
            $sqlContent .= $this->generatePostgreSQLBackup();
        } else {
            throw new \Exception("Unsupported database driver: " . $driver);
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
            'public_id' => $result['public_id'],
            'driver' => $driver
        ]);

    } catch (\Exception $e) {
        if (file_exists($filePath)) unlink($filePath);
        return response()->json(['error' => 'Failed: ' . $e->getMessage()], 500);
    }
}

private function generateMySQLBackup()
{
    $sqlContent = "";
    
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
    
    return $sqlContent;
}

private function generatePostgreSQLBackup()
{
    $sqlContent = "";
    
    // Get all table names
    $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'");
    
    foreach ($tables as $table) {
        $tableName = $table->table_name;
        
        // Get table structure
        $createTable = DB::select("SELECT pg_get_tabledef('{$tableName}') as create_statement");
        $createStatement = $createTable[0]->create_statement;
        
        $sqlContent .= "DROP TABLE IF EXISTS \"{$tableName}\" CASCADE;\n";
        $sqlContent .= $createStatement . ";\n\n";
        
        // Get all data from the table
        $rows = DB::table($tableName)->get();
        
        if ($rows->count() > 0) {
            foreach ($rows as $row) {
                $columns = [];
                $values = [];
                
                foreach ($row as $column => $value) {
                    $columns[] = "\"{$column}\"";
                    $values[] = $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                }
                
                $sqlContent .= "INSERT INTO \"{$tableName}\" (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
            }
            $sqlContent .= "\n";
        }
    }
    
    return $sqlContent;
}

    // 🔹 Download and restore database from Cloudinary
public function restoreNewest()
{
    try {
        // Temporarily disable session handling
        config(['session.driver' => 'array']);

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
        
        // Convert syntax based on current database driver
        $driver = env('DB_CONNECTION');
        if ($driver === 'pgsql') {
            $fileContent = $this->convertToPostgreSQL($fileContent);
        }
        
        // Split SQL file into individual queries
        $queries = array_filter(array_map('trim', 
            preg_split('/;/', $fileContent)
        ));

        // Execute each query
        $executedQueries = 0;
        $errors = [];

        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query) && !str_starts_with($query, '--')) {
                try {
                    DB::statement($query);
                    $executedQueries++;
                } catch (\Exception $e) {
                    // Ignore "table doesn't exist" errors during DROP TABLE
                    if (!str_contains($e->getMessage(), 'does not exist') && 
                        !str_contains($e->getMessage(), 'Base table or view not found')) {
                        $errors[] = "Query failed: " . substr($query, 0, 100) . "... - " . $e->getMessage();
                    }
                }
            }
        }

        return response()->json([
            'success' => 'Database restored from newest backup!',
            'backup_used' => $public_id,
            'backup_created' => $newestBackup['created_at'],
            'queries_executed' => $executedQueries,
            'total_queries' => count($queries),
            'driver' => $driver,
            'errors' => $errors
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Restore failed: ' . $e->getMessage()], 500);
    }
}

private function convertToPostgreSQL($sqlContent)
{
    // Convert MySQL syntax to PostgreSQL
    $converted = $sqlContent;
    
    // Remove backticks and convert to quotes
    $converted = preg_replace('/`([^`]*)`/', '"$1"', $converted);
    
    // Remove MySQL-specific syntax
    $converted = preg_replace('/\bAUTO_INCREMENT\b/', '', $converted);
    $converted = preg_replace('/\bENGINE=InnoDB\b/', '', $converted);
    $converted = preg_replace('/\bDEFAULT CHARSET=[^;]*/', '', $converted);
    $converted = preg_replace('/\bCOLLATE=[^;]*/', '', $converted);
    $converted = preg_replace('/\bUNSIGNED\b/', '', $converted);
    
    return $converted;
}

}