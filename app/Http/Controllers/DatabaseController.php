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
        
        // Handle UTF-8 encoding properly
        $fileContent = mb_convert_encoding($fileContent, 'UTF-8', 'UTF-8');
        
        // Replace backticks with double quotes
        $fileContent = str_replace("`", "\"", $fileContent);
        
        // Fix the specific issues
        $fileContent = preg_replace_callback(
            '/INSERT INTO[^;]+;/',
            function($match) {
                $statement = $match[0];
                
                // FIX 1: Convert boolean true/false to appropriate values based on column context
                
                // For ID columns - convert boolean to appropriate ID values
                $statement = preg_replace_callback(
                    '/INSERT INTO "(\w+)"\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/',
                    function($tableMatch) {
                        $tableName = $tableMatch[1];
                        $columns = array_map('trim', explode(',', $tableMatch[2]));
                        $values = array_map('trim', explode(',', $tableMatch[3]));
                        
                        // Process each value based on column name
                        foreach ($values as $index => &$value) {
                            $columnName = str_replace('"', '', $columns[$index] ?? '');
                            
                            // Handle ID columns (should be integers, not booleans)
                            if ($columnName === 'id' || $columnName === 'user_id' || $columnName === 'batch') {
                                if ($value === 'true') {
                                    // Find the next available ID or use 1 as default
                                    $value = '1';
                                } elseif ($value === 'false') {
                                    $value = 'NULL';
                                }
                            }
                            
                            // Handle boolean columns (should be true/false)
                            if ($columnName === 'ertesites' || $columnName === 'kozlemenyErtesites' || $columnName === 'naptarErtesites') {
                                if ($value === "''" || $value === 'NULL') {
                                    $value = 'false';
                                } elseif ($value === "'1'" || $value === '1') {
                                    $value = 'true';
                                } elseif ($value === "'0'" || $value === '0') {
                                    $value = 'false';
                                }
                            }
                            
                            // Handle type column in kozlemeny table (should be integer)
                            if ($tableName === 'kozlemeny' && $columnName === 'type') {
                                if ($value === 'false' || $value === 'true') {
                                    $value = $value === 'true' ? '1' : '0';
                                }
                            }
                            
                            // Fix empty strings for non-text columns
                            if ($value === "''" && !in_array($columnName, ['title', 'description', 'created', 'edited', 'url', 'image_url', 'fcm_token', 'device_id', 'password', 'name', 'teljes_nev', 'szak', 'titulus', 'remember_token'])) {
                                $value = 'NULL';
                            }
                            
                            // Fix apostrophe escaping
                            if (preg_match('/^\'(.+)\'$/s', $value, $matches)) {
                                $content = $matches[1];
                                $content = str_replace("\\'", "''", $content);
                                $content = str_replace('\\"', '"', $content);
                                $content = str_replace("\\r\\n", "\r\n", $content);
                                $content = str_replace("\\n", "\n", $content);
                                $content = str_replace("\\r", "\r", $content);
                                $content = str_replace("\\t", "\t", $content);
                                $content = str_replace("\\\\", "\\", $content);
                                $value = "'" . $content . "'";
                            }
                        }
                        
                        return 'INSERT INTO "' . $tableName . '" (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')';
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
        $skippedTables = ['migrations']; // Skip migrations table to avoid conflicts
        
        foreach ($inserts[0] as $insert) {
            // Skip migrations table
            if (preg_match('/INSERT INTO "migrations"/', $insert)) {
                continue;
            }
            
            try {
                DB::statement($insert);
                $successful++;
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                
                // Try specific fixes based on error type
                if (str_contains($errorMessage, 'Datatype mismatch') || str_contains($errorMessage, 'invalid input syntax')) {
                    
                    // Fix for ID columns being boolean
                    $fixedInsert = preg_replace('/VALUES\s*\(\s*true\s*,/', 'VALUES (1,', $insert);
                    $fixedInsert = preg_replace('/VALUES\s*\(\s*false\s*,/', 'VALUES (0,', $fixedInsert);
                    
                    // Fix for boolean columns
                    $fixedInsert = preg_replace('/,\s*true\s*,/', ', true,', $fixedInsert);
                    $fixedInsert = preg_replace('/,\s*false\s*,/', ', false,', $fixedInsert);
                    $fixedInsert = preg_replace('/,\s*true\s*\)/', ', true)', $fixedInsert);
                    $fixedInsert = preg_replace('/,\s*false\s*\)/', ', false)', $fixedInsert);
                    
                    // Fix for empty strings in boolean columns
                    $fixedInsert = preg_replace("/''/", 'false', $fixedInsert);
                    
                    try {
                        DB::statement($fixedInsert);
                        $successful++;
                        continue;
                    } catch (\Exception $e2) {
                        // If still failing, try to extract and fix the specific problematic value
                        if (preg_match('/column "([^"]+)" is of type ([^ ]+)/', $errorMessage, $typeMatches)) {
                            $problemColumn = $typeMatches[1];
                            $expectedType = $typeMatches[2];
                            
                            // Try to fix based on column and expected type
                            if ($expectedType === 'bigint' || $expectedType === 'integer') {
                                $fixedInsert = preg_replace_callback(
                                    '/VALUES\s*\(([^)]+)\)/',
                                    function($valueMatch) use ($problemColumn, $insert) {
                                        $values = explode(',', $valueMatch[1]);
                                        // Extract column names from the INSERT
                                        if (preg_match('/\(([^)]+)\)\s*VALUES/', $insert, $colMatch)) {
                                            $columns = array_map('trim', explode(',', $colMatch[1]));
                                            foreach ($columns as $idx => $col) {
                                                $col = trim($col, '" ');
                                                if ($col === $problemColumn && isset($values[$idx])) {
                                                    // Convert boolean to integer
                                                    if (trim($values[$idx]) === 'true') {
                                                        $values[$idx] = '1';
                                                    } elseif (trim($values[$idx]) === 'false') {
                                                        $values[$idx] = '0';
                                                    }
                                                }
                                            }
                                        }
                                        return 'VALUES (' . implode(', ', $values) . ')';
                                    },
                                    $fixedInsert
                                );
                                
                                try {
                                    DB::statement($fixedInsert);
                                    $successful++;
                                    continue;
                                } catch (\Exception $e3) {
                                    // Give up on this insert
                                }
                            }
                        }
                    }
                } elseif (str_contains($errorMessage, 'syntax error at or near')) {
                    // Fix apostrophe issues
                    $fixedInsert = preg_replace("/([^\\])'([^']*)'/", "$1''$2''", $insert);
                    try {
                        DB::statement($fixedInsert);
                        $successful++;
                        continue;
                    } catch (\Exception $e2) {
                        // Still failing, log the error
                    }
                } elseif (str_contains($errorMessage, 'not-null constraint')) {
                    // For not-null violations, try to set a default value
                    if (str_contains($errorMessage, 'url') && str_contains($insert, 'facebook_posts')) {
                        $fixedInsert = str_replace("NULL, 'https://", "'https://res.cloudinary.com/dummy.jpg', 'https://", $insert);
                        try {
                            DB::statement($fixedInsert);
                            $successful++;
                            continue;
                        } catch (\Exception $e2) {
                            // Still failing
                        }
                    }
                }
                
                // Log error if not duplicate
                if (!str_contains($errorMessage, 'duplicate') && !str_contains($errorMessage, 'already exists')) {
                    $errors[] = $errorMessage . "\nStatement: " . substr($insert, 0, 200) . "...";
                }
            }
        }

        return response()->json([
            'success' => 'Data added from backup!',
            'inserts_successful' => $successful,
            'total_inserts' => count($inserts[0]) - count($skippedTables), // Adjust for skipped tables
            'errors' => $errors
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Restore failed: ' . $e->getMessage()], 500);
    }
}
}