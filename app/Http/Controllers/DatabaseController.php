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
                        if ($value === null) {
                            $values[] = 'NULL';
                        } elseif (is_bool($value)) {
                            $values[] = $value ? 'true' : 'false';
                        } else {
                            // Fix: Proper PostgreSQL escaping (double apostrophes)
                            $escaped = str_replace("'", "''", $value);
                            $values[] = "'" . $escaped . "'";
                        }
                    }
                    
                    $sqlContent .= "INSERT INTO \"{$tableName}\" (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
                }
                $sqlContent .= "\n";
            }
        }
        
        return $sqlContent;
    }

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

            // Download the SQL file
            $fileContent = file_get_contents($downloadUrl);
            
            // Convert MySQL syntax to PostgreSQL (if restoring from MySQL backup to PostgreSQL)
            $fileContent = str_replace("`", "\"", $fileContent); // Backticks to double quotes
            
            // FIX: Better apostrophe handling for PostgreSQL
            // First, temporarily mark escaped apostrophes
            $fileContent = str_replace("\\'", "{{APOS}}", $fileContent);
            // Then fix any remaining single apostrophes in strings
            $fileContent = preg_replace("/(?<=[^\\\\])'/", "''", $fileContent);
            // Restore the escaped apostrophes as double apostrophes
            $fileContent = str_replace("{{APOS}}", "''", $fileContent);
            
            // Get boolean columns info
            $booleanColumns = $this->getBooleanColumns();
            
            // Split into individual statements
            $statements = explode(';', $fileContent);
            
            $executed = 0;
            $errors = [];
            $successful = 0;
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    try {
                        // Process statement for boolean columns if it's an INSERT
                        if (strpos($statement, 'INSERT INTO') === 0) {
                            $statement = $this->processBooleanValues($statement, $booleanColumns);
                        }
                        
                        DB::statement($statement);
                        $executed++;
                        
                        // Count successful INSERTs separately
                        if (strpos($statement, 'INSERT INTO') === 0) {
                            $successful++;
                        }
                    } catch (\Exception $e) {
                        // Only log non-duplicate errors
                        if (!str_contains($e->getMessage(), 'duplicate') && 
                            !str_contains($e->getMessage(), 'already exists')) {
                            $errors[] = [
                                'statement' => substr($statement, 0, 100) . '...',
                                'error' => $e->getMessage()
                            ];
                        } else {
                            // Still count duplicates as successful since data exists
                            if (strpos($statement, 'INSERT INTO') === 0) {
                                $successful++;
                            }
                        }
                    }
                }
            }

            return response()->json([
                'success' => 'Database restored from backup!',
                'backup_used' => $backup['public_id'],
                'backup_date' => $backup['created_at'],
                'statements_executed' => $executed,
                'inserts_successful' => $successful,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Restore failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get all boolean columns from the database
     */
    private function getBooleanColumns()
    {
        try {
            $booleanColumns = [];
            
            // Only for PostgreSQL
            if (env('DB_CONNECTION') !== 'pgsql') {
                return $booleanColumns;
            }
            
            // Get all tables
            $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'");
            
            foreach ($tables as $table) {
                // Get boolean columns for each table
                $columns = DB::select("
                    SELECT column_name 
                    FROM information_schema.columns 
                    WHERE table_schema = 'public' 
                    AND table_name = ?
                    AND data_type IN ('boolean', 'bool')
                ", [$table->table_name]);
                
                foreach ($columns as $column) {
                    $booleanColumns[$table->table_name][] = $column->column_name;
                }
            }
            
            return $booleanColumns;
        } catch (\Exception $e) {
            // If we can't get boolean columns, return empty array
            return [];
        }
    }

    /**
     * Process INSERT statement to convert MySQL boolean values to PostgreSQL format
     */
    private function processBooleanValues($insert, $booleanColumns)
    {
        // Extract table name
        preg_match('/INSERT INTO "([^"]+)"/', $insert, $matches);
        if (empty($matches)) {
            return $insert;
        }
        $tableName = $matches[1];
        
        // If this table has boolean columns, process them
        if (isset($booleanColumns[$tableName])) {
            foreach ($booleanColumns[$tableName] as $boolColumn) {
                // Look for patterns like: "column_name", 'value'
                $pattern = '/"' . preg_quote($boolColumn, '/') . '",\s*\'([^\']*)\'/';
                
                $insert = preg_replace_callback($pattern, function($matches) use ($boolColumn) {
                    $value = $matches[1];
                    
                    // Convert MySQL-style boolean to PostgreSQL boolean
                    if ($value === '' || $value === '0' || $value === 'NULL') {
                        return '"' . $boolColumn . '", false';
                    } elseif ($value === '1') {
                        return '"' . $boolColumn . '", true';
                    }
                    return $matches[0];
                }, $insert);
            }
        }
        
        return $insert;
    }
}