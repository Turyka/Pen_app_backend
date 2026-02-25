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
            
            // FIX 1: Handle UTF-8 encoding properly
            $fileContent = mb_convert_encoding($fileContent, 'UTF-8', 'UTF-8');
            
            // FIX 2: Replace backticks with double quotes
            $fileContent = str_replace("`", "\"", $fileContent);
            
            // FIX 3: Fix boolean values (empty strings to NULL)
            $fileContent = preg_replace_callback(
                '/INSERT INTO[^;]+;/',
                function($match) {
                    $statement = $match[0];
                    
                    // Fix boolean fields - replace empty strings with NULL
                    $statement = preg_replace_callback(
                        '/VALUES\s*\(([^)]+)\)/',
                        function($valuesMatch) {
                            $values = $valuesMatch[1];
                            
                            // Split values carefully (this is a simple split, might need improvement for complex cases)
                            $valueArray = explode(',', $values);
                            foreach ($valueArray as &$value) {
                                $value = trim($value);
                                // If it's an empty string, convert to NULL
                                if ($value === "''") {
                                    $value = 'NULL';
                                }
                                // Fix MySQL boolean strings
                                if ($value === "'0'") {
                                    $value = 'false';
                                }
                                if ($value === "'1'") {
                                    $value = 'true';
                                }
                            }
                            return 'VALUES (' . implode(', ', $valueArray) . ')';
                        },
                        $statement
                    );
                    
                    // FIX 4: Fix apostrophe escaping (MySQL \' to PostgreSQL '')
                    $statement = preg_replace_callback(
                        "/'([^']*)'/",
                        function($quoteMatch) {
                            $content = $quoteMatch[1];
                            // Replace MySQL-style escaped quotes with PostgreSQL style
                            $content = str_replace("\\'", "''", $content);
                            $content = str_replace('\\"', '"', $content);
                            $content = str_replace("\\r\\n", "\r\n", $content);
                            $content = str_replace("\\n", "\n", $content);
                            $content = str_replace("\\r", "\r", $content);
                            $content = str_replace("\\t", "\t", $content);
                            $content = str_replace("\\\\", "\\", $content);
                            return "'" . $content . "'";
                        },
                        $statement
                    );
                    
                    return $statement;
                },
                $fileContent
            );
            
            // Extract all INSERT statements
            $inserts = [];
            preg_match_all('/INSERT INTO[^;]+;/', $fileContent, $inserts);
            
            $successful = 0;
            $errors = [];
            
            foreach ($inserts[0] as $insert) {
                try {
                    DB::statement($insert);
                    $successful++;
                } catch (\Exception $e) {
                    // Try one more fix for common errors
                    $errorMessage = $e->getMessage();
                    
                    if (str_contains($errorMessage, 'invalid input syntax for type boolean')) {
                        // Try to fix boolean columns by replacing empty strings with NULL
                        $fixedInsert = preg_replace("/'',/", "NULL,", $insert);
                        $fixedInsert = preg_replace("/,''\)/", ",NULL)", $fixedInsert);
                        
                        // Also try to fix '0' and '1' strings to boolean
                        $fixedInsert = preg_replace("/'0'/", "false", $fixedInsert);
                        $fixedInsert = preg_replace("/'1'/", "true", $fixedInsert);
                        
                        try {
                            DB::statement($fixedInsert);
                            $successful++;
                            continue;
                        } catch (\Exception $e2) {
                            // Still failing, try third approach - remove the problematic insert
                            if (str_contains($errorMessage, 'ertesites') || str_contains($errorMessage, 'boolean')) {
                                // Skip this insert if it's a boolean column issue
                                $errors[] = "Skipped boolean insert: " . substr($insert, 0, 100) . "...";
                                continue;
                            }
                        }
                    } elseif (str_contains($errorMessage, 'syntax error at or near')) {
                        // Try to fix apostrophe issues
                        $fixedInsert = preg_replace("/([^\\])'([^']*)'/", "$1''$2''", $insert);
                        try {
                            DB::statement($fixedInsert);
                            $successful++;
                            continue;
                        } catch (\Exception $e2) {
                            // Still failing, log the error
                        }
                    }
                    
                    // Ignore duplicate errors, log others
                    if (!str_contains($errorMessage, 'duplicate') && !str_contains($errorMessage, 'already exists')) {
                        $errors[] = $errorMessage . "\nStatement: " . substr($insert, 0, 200) . "...";
                    }
                }
            }

            return response()->json([
                'success' => 'Data added from backup!',
                'inserts_successful' => $successful,
                'total_inserts' => count($inserts[0]),
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Restore failed: ' . $e->getMessage()], 500);
        }
    }
}