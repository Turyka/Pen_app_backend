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
        
        // Remove the DROP TABLE and CREATE TABLE statements, keep only INSERTs
        $lines = explode("\n", $fileContent);
        $inserts = [];
        $currentInsert = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines, DROP TABLE, CREATE TABLE, and comment lines
            if (empty($line) || 
                strpos($line, 'DROP TABLE') === 0 || 
                strpos($line, 'CREATE TABLE') === 0 ||
                strpos($line, '--') === 0) {
                continue;
            }
            
            $currentInsert .= $line;
            
            // If line ends with semicolon, it's a complete statement
            if (substr($line, -1) === ';') {
                if (strpos($currentInsert, 'INSERT INTO') === 0) {
                    $inserts[] = $currentInsert;
                }
                $currentInsert = '';
            }
        }
        
        // If there's a remaining insert
        if (!empty($currentInsert) && strpos($currentInsert, 'INSERT INTO') === 0) {
            $inserts[] = $currentInsert;
        }
        
        $successful = 0;
        $errors = [];
        $skippedTables = ['migrations']; // Skip migrations table to avoid conflicts
        
        // First, get the current order of inserts to handle foreign keys
        // Process tables in order of dependencies (no foreign keys first)
        $tableOrder = [
            'users',           // No dependencies
            'napi_login',      // No foreign keys
            'kepfeltoltes',    // No foreign keys
            'hirek',           // No foreign keys
            'naptar',          // No foreign keys
            'kozlemeny',       // Depends on users (user_id)
            'facebook_posts',  // No foreign keys
            'tiktok_posts',    // No foreign keys
            'adat_eszkozok'    // No foreign keys
        ];
        
        // Group inserts by table
        $insertsByTable = [];
        foreach ($inserts as $insert) {
            if (preg_match('/INSERT INTO "(\w+)"/', $insert, $matches)) {
                $tableName = $matches[1];
                if (!in_array($tableName, $skippedTables)) {
                    $insertsByTable[$tableName][] = $insert;
                }
            }
        }
        
        // Process tables in the correct order
        foreach ($tableOrder as $tableName) {
            if (isset($insertsByTable[$tableName])) {
                foreach ($insertsByTable[$tableName] as $insert) {
                    // Parse the INSERT statement
                    if (preg_match('/INSERT INTO "(\w+)"\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/', $insert, $matches)) {
                        $tableName = $matches[1];
                        $columns = array_map(function($col) {
                            return trim($col, '" ');
                        }, explode(',', $matches[2]));
                        
                        $values = explode(',', $matches[3]);
                        
                        // Process each value
                        $processedValues = [];
                        $needsProcessing = false;
                        
                        foreach ($values as $index => $value) {
                            $value = trim($value);
                            $columnName = $columns[$index] ?? '';
                            $originalValue = $value;
                            
                            // Handle boolean columns
                            if (in_array($columnName, ['ertesites', 'kozlemenyErtesites', 'naptarErtesites'])) {
                                if ($value === "''" || $value === 'NULL' || $value === '0') {
                                    $value = 'false';
                                    $needsProcessing = true;
                                } elseif ($value === "'1'" || $value === '1') {
                                    $value = 'true';
                                    $needsProcessing = true;
                                }
                            }
                            
                            // Handle smallint columns
                            if ($columnName === 'type') {
                                if ($value === "''" || $value === 'NULL') {
                                    $value = '0';
                                    $needsProcessing = true;
                                } elseif ($value === "'1'" || $value === '1' || $value === 'true') {
                                    $value = '1';
                                    $needsProcessing = true;
                                } elseif ($value === "'0'" || $value === '0' || $value === 'false') {
                                    $value = '0';
                                    $needsProcessing = true;
                                }
                            }
                            
                            // Handle integer columns
                            if (in_array($columnName, ['user_id', 'batch'])) {
                                if ($value === "''" || $value === 'NULL') {
                                    $value = 'NULL';
                                    $needsProcessing = true;
                                } elseif ($value === 'true') {
                                    $value = '1';
                                    $needsProcessing = true;
                                } elseif ($value === 'false') {
                                    $value = '0';
                                    $needsProcessing = true;
                                }
                            }
                            
                            $processedValues[] = $value;
                        }
                        
                        // Only rebuild if we made changes
                        if ($needsProcessing) {
                            $processedInsert = 'INSERT INTO "' . $tableName . '" (' . implode(', ', array_map(function($col) {
                                return '"' . $col . '"';
                            }, $columns)) . ') VALUES (' . implode(', ', $processedValues) . ')';
                        } else {
                            $processedInsert = $insert;
                        }
                        
                        try {
                            DB::statement($processedInsert);
                            $successful++;
                        } catch (\Exception $e) {
                            $errorMessage = $e->getMessage();
                            
                            // Handle specific errors
                            if (str_contains($errorMessage, 'invalid input syntax for type boolean')) {
                                // Try to fix boolean columns by replacing empty strings
                                $fixedInsert = preg_replace("/'',/", "false,", $processedInsert);
                                $fixedInsert = preg_replace("/,''\)/", ",false)", $fixedInsert);
                                
                                try {
                                    DB::statement($fixedInsert);
                                    $successful++;
                                    continue;
                                } catch (\Exception $e2) {
                                    $errors[] = $errorMessage . "\nStatement: " . substr($insert, 0, 200) . "...";
                                }
                            } elseif (str_contains($errorMessage, 'duplicate key value violates unique constraint')) {
                                // Try to update instead of insert for duplicates
                                if (preg_match('/INSERT INTO "(\w+)"\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/', $insert, $dupMatches)) {
                                    $dupTable = $dupMatches[1];
                                    $dupColumns = array_map(function($col) {
                                        return trim($col, '" ');
                                    }, explode(',', $dupMatches[2]));
                                    $dupValues = explode(',', $dupMatches[3]);
                                    
                                    // Find the ID value
                                    $idIndex = array_search('id', $dupColumns);
                                    if ($idIndex !== false && isset($dupValues[$idIndex])) {
                                        $id = trim($dupValues[$idIndex], "'");
                                        
                                        // Build UPDATE statement
                                        $setParts = [];
                                        foreach ($dupColumns as $idx => $col) {
                                            if ($col !== 'id' && isset($dupValues[$idx])) {
                                                $setParts[] = '"' . $col . '" = ' . $dupValues[$idx];
                                            }
                                        }
                                        
                                        if (!empty($setParts)) {
                                            $updateSql = 'UPDATE "' . $dupTable . '" SET ' . implode(', ', $setParts) . ' WHERE "id" = ' . $id;
                                            try {
                                                DB::statement($updateSql);
                                                $successful++;
                                                continue;
                                            } catch (\Exception $e2) {
                                                // Update failed
                                            }
                                        }
                                    }
                                }
                            } elseif (!str_contains($errorMessage, 'duplicate') && !str_contains($errorMessage, 'already exists')) {
                                $errors[] = $errorMessage . "\nStatement: " . substr($insert, 0, 200) . "...";
                            }
                        }
                    } else {
                        // If parsing fails, try to execute the original insert
                        try {
                            DB::statement($insert);
                            $successful++;
                        } catch (\Exception $e) {
                            if (!str_contains($e->getMessage(), 'duplicate')) {
                                $errors[] = $e->getMessage() . "\nStatement: " . substr($insert, 0, 200) . "...";
                            }
                        }
                    }
                }
            }
        }

        // Reset sequences for all tables
        $tables = ['napi_login', 'kozlemeny', 'kepfeltoltes', 'facebook_posts', 'tiktok_posts', 'hirek', 'naptar', 'adat_eszkozok', 'users'];
        foreach ($tables as $tableName) {
            try {
                $maxId = DB::table($tableName)->max('id') ?? 0;
                DB::statement("ALTER SEQUENCE {$tableName}_id_seq RESTART WITH " . ($maxId + 1));
            } catch (\Exception $e) {
                // Ignore errors if table doesn't exist or has no sequence
            }
        }

        return response()->json([
            'success' => 'Data added from backup!',
            'inserts_successful' => $successful,
            'total_inserts' => count($inserts),
            'errors' => $errors
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Restore failed: ' . $e->getMessage()], 500);
    }
}
}