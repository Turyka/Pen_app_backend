<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseController extends Controller
{
public function migrateRefresh()
{
    try {
        // Force drop all tables first
        Artisan::call('db:wipe', ['--force' => true]);
        
        // Then run migrations and seeds
        $migrateStatus = Artisan::call('migrate', ['--force' => true]);
        $seedStatus = Artisan::call('db:seed', ['--force' => true]);
        
        $output = Artisan::output();

        return response()->json([
            'message' => 'Database refreshed successfully!',
            'migrate_status' => $migrateStatus,
            'seed_status' => $seedStatus,
            'output' => $output
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
                    if (is_bool($value)) {
    $values[] = $value ? '1' : '0';
} elseif ($value === null) {
    $values[] = 'NULL';
} elseif ($value === '') {
    $values[] = 'NULL';
} else {
    $values[] = "'" . addslashes($value) . "'";
}
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
        
        // Drop table
        $sqlContent .= "DROP TABLE IF EXISTS \"{$tableName}\" CASCADE;\n";
        
        // Get table structure using information_schema instead of pg_get_tabledef
        $columns = DB::select("
            SELECT 
                column_name,
                data_type,
                is_nullable,
                column_default,
                character_maximum_length
            FROM information_schema.columns 
            WHERE table_schema = 'public' AND table_name = '{$tableName}'
            ORDER BY ordinal_position
        ");
        
        // Build CREATE TABLE statement manually
        $sqlContent .= "CREATE TABLE \"{$tableName}\" (\n";
        
        $columnDefinitions = [];
        foreach ($columns as $column) {
            $definition = "\"{$column->column_name}\" {$column->data_type}";
            
            // Add length for character types
            if ($column->character_maximum_length) {
                $definition .= "({$column->character_maximum_length})";
            }
            
            // Handle nullable
            if ($column->is_nullable === 'NO') {
                $definition .= " NOT NULL";
            }
            
            // Handle default values
            if ($column->column_default) {
                $definition .= " DEFAULT {$column->column_default}";
            }
            
            $columnDefinitions[] = $definition;
        }
        
        $sqlContent .= implode(",\n", $columnDefinitions) . "\n);\n\n";
        
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

public function restoreNewest()
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

        // Download the SQL file with proper encoding detection
        $fileContent = file_get_contents($downloadUrl);
        
        // Fix encoding issues - ensure it's UTF-8
        if (mb_detect_encoding($fileContent, 'UTF-8', true) === false) {
            $fileContent = utf8_encode($fileContent);
        }
        
        // Convert MySQL syntax to PostgreSQL
        $fileContent = str_replace("`", "\"", $fileContent); // Backticks to double quotes
        
        // Fix apostrophes - handle them properly without breaking UTF-8
        // We need to be careful with this to not break emojis
        $fileContent = preg_replace('/(?<!\\\\)\\\\\'/', "''", $fileContent);
        
        // Drop all tables and recreate schema FIRST
        DB::statement('DROP SCHEMA public CASCADE');
        DB::statement('CREATE SCHEMA public');
        
        // Split into individual statements, being careful with UTF-8
        $statements = preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $fileContent);
        
        $executed = 0;
        $errors = [];
        $successful = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            // Ensure the statement is valid UTF-8
            $statement = mb_convert_encoding($statement, 'UTF-8', 'UTF-8');
            
            try {
                DB::statement($statement);
                $executed++;
                
                // Count successful INSERTs
                if (strpos(strtoupper($statement), 'INSERT INTO') === 0) {
                    $successful++;
                }
            } catch (\Exception $e) {
                // Check if it's a duplicate error
                if (str_contains($e->getMessage(), 'duplicate') || 
                    str_contains($e->getMessage(), 'already exists')) {
                    // Count duplicates as successful
                    if (strpos(strtoupper($statement), 'INSERT INTO') === 0) {
                        $successful++;
                    }
                    $executed++;
                } 
                // Check if it's an encoding error
                elseif (str_contains($e->getMessage(), 'Malformed UTF-8')) {
                    // Try to clean the statement
                    $cleaned = $this->cleanUtf8String($statement);
                    try {
                        DB::statement($cleaned);
                        $executed++;
                        if (strpos(strtoupper($statement), 'INSERT INTO') === 0) {
                            $successful++;
                        }
                    } catch (\Exception $e2) {
                        $errors[] = [
                            'type' => 'UTF-8 Error',
                            'error' => $e->getMessage(),
                            'statement' => substr($statement, 0, 200)
                        ];
                    }
                } else {
                    $errors[] = [
                        'type' => 'SQL Error',
                        'error' => $e->getMessage(),
                        'statement' => substr($statement, 0, 200)
                    ];
                }
            }
        }

        return response()->json([
            'success' => 'Database fully restored from backup!',
            'backup_used' => $backup['public_id'],
            'backup_date' => $backup['created_at'],
            'statements_executed' => $executed,
            'inserts_successful' => $successful,
            'total_inserts' => substr_count($fileContent, 'INSERT INTO'),
            'errors' => $errors
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Restore failed: ' . $e->getMessage()], 500);
    }
}

/**
 * Helper function to clean UTF-8 strings
 */
private function cleanUtf8String($string)
{
    // Remove any invalid UTF-8 sequences
    $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    
    // Remove any remaining invalid characters
    $string = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
    
    return $string;
}

}