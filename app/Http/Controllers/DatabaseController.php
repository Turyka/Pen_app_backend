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

        // Download the SQL file
        $fileContent = file_get_contents($downloadUrl);
        
        // Split into individual statements
        $statements = explode(";\n", $fileContent);
        
        $successful = 0;
        $totalInserts = 0;
        $errors = [];
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            // Only process INSERT statements
            if (stripos($statement, 'INSERT INTO') === 0) {
                $totalInserts++;
                
                try {
                    // Convert MySQL syntax to PostgreSQL
                    
                    // 1. Replace backticks with double quotes
                    $statement = preg_replace('/`([^`]*)`/', '"$1"', $statement);
                    
                    // 2. Fix boolean values - convert empty strings to proper boolean
                    $statement = preg_replace_callback(
                        '/VALUES\s*\(([^)]+)\)/i',
                        function($matches) {
                            $values = $matches[1];
                            // Split values but respect quoted strings
                            $valueArray = $this->parseValues($values);
                            
                            foreach ($valueArray as &$value) {
                                $value = trim($value);
                                
                                // Handle empty strings for boolean columns
                                if ($value === "''") {
                                    $value = 'NULL';
                                }
                                
                                // Fix escaped apostrophes - MySQL uses \', PostgreSQL uses ''
                                $value = str_replace("\\'", "''", $value);
                            }
                            
                            return 'VALUES (' . implode(', ', $valueArray) . ')';
                        },
                        $statement
                    );
                    
                    // 3. Remove any trailing semicolon for execution
                    $statement = rtrim($statement, ';');
                    
                    DB::statement($statement);
                    $successful++;
                    
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage() . "\nStatement: " . substr($statement, 0, 200) . "...";
                }
            }
        }

        return response()->json([
            'success' => 'Data added from backup!',
            'inserts_successful' => $successful,
            'total_inserts' => $totalInserts,
            'errors' => $errors
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Restore failed: ' . $e->getMessage()], 500);
    }
}

/**
 * Parse CSV values while respecting quoted strings
 */
private function parseValues($valuesString)
{
    $values = [];
    $current = '';
    $inQuotes = false;
    $quoteChar = null;
    $len = strlen($valuesString);
    
    for ($i = 0; $i < $len; $i++) {
        $char = $valuesString[$i];
        
        if (($char === "'" || $char === '"') && ($i === 0 || $valuesString[$i-1] !== '\\')) {
            if (!$inQuotes) {
                $inQuotes = true;
                $quoteChar = $char;
                $current .= $char;
            } elseif ($quoteChar === $char) {
                $inQuotes = false;
                $quoteChar = null;
                $current .= $char;
            } else {
                $current .= $char;
            }
        } elseif ($char === ',' && !$inQuotes) {
            $values[] = $current;
            $current = '';
        } else {
            $current .= $char;
        }
    }
    
    if (!empty($current)) {
        $values[] = $current;
    }
    
    return $values;
}

}