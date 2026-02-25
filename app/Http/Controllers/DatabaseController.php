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
                
                // Fix the statement
                $statement = preg_replace_callback(
                    '/INSERT INTO "(\w+)"\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/',
                    function($tableMatch) {
                        $tableName = $tableMatch[1];
                        $columns = array_map('trim', explode(',', $tableMatch[2]));
                        $values = array_map('trim', explode(',', $tableMatch[3]));
                        
                        // Remove quotes from column names
                        $columns = array_map(function($col) {
                            return trim($col, '"');
                        }, $columns);
                        
                        $newColumns = [];
                        $newValues = [];
                        
                        // Process each column-value pair
                        foreach ($columns as $index => $column) {
                            $value = $values[$index] ?? 'NULL';
                            
                            // Handle ID columns - skip them to let PostgreSQL auto-generate
                            if ($column === 'id') {
                                continue; // Skip ID column entirely
                            }
                            
                            // Handle boolean columns
                            if (in_array($column, ['ertesites', 'kozlemenyErtesites', 'naptarErtesites', 'type'])) {
                                if ($value === "''" || $value === 'NULL' || $value === 'false' || $value === '0') {
                                    $value = 'false';
                                } elseif ($value === "'1'" || $value === '1' || $value === 'true') {
                                    $value = 'true';
                                }
                            }
                            
                            // Handle integer columns that might have been converted to boolean
                            if ($column === 'user_id' || $column === 'batch') {
                                if ($value === 'true') {
                                    $value = '1';
                                } elseif ($value === 'false') {
                                    $value = 'NULL';
                                }
                            }
                            
                            // Fix apostrophe escaping in string values
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
                            
                            $newColumns[] = '"' . $column . '"';
                            $newValues[] = $value;
                        }
                        
                        return 'INSERT INTO "' . $tableName . '" (' . implode(', ', $newColumns) . ') VALUES (' . implode(', ', $newValues) . ')';
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
        
        // First, reset sequences for all tables
        $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        foreach ($tables as $table) {
            $tableName = $table->tablename;
            try {
                // Get the max ID for each table
                if (Schema::hasColumn($tableName, 'id')) {
                    $maxId = DB::table($tableName)->max('id') ?? 0;
                    // Reset the sequence
                    DB::statement("ALTER SEQUENCE {$tableName}_id_seq RESTART WITH " . ($maxId + 1));
                }
            } catch (\Exception $e) {
                // Table might not have an ID column or sequence
            }
        }
        
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
                
                // Handle unique violation errors
                if (str_contains($errorMessage, 'duplicate key value violates unique constraint')) {
                    // Try to update instead of insert
                    if (preg_match('/INSERT INTO "(\w+)".*VALUES\s*\(([^)]+)\)/', $insert, $matches)) {
                        $tableName = $matches[1];
                        $values = array_map('trim', explode(',', $matches[2]));
                        
                        // Try to extract column names
                        if (preg_match('/\(([^)]+)\)\s*VALUES/', $insert, $colMatches)) {
                            $columns = array_map(function($col) {
                                return trim(trim($col), '"');
                            }, explode(',', $colMatches[1]));
                            
                            // Find the ID value
                            $idIndex = array_search('id', $columns);
                            if ($idIndex !== false && isset($values[$idIndex])) {
                                $id = trim($values[$idIndex], "'");
                                
                                // Build UPDATE statement
                                $setParts = [];
                                foreach ($columns as $idx => $col) {
                                    if ($col !== 'id' && isset($values[$idx])) {
                                        $setParts[] = '"' . $col . '" = ' . $values[$idx];
                                    }
                                }
                                
                                if (!empty($setParts)) {
                                    $updateSql = 'UPDATE "' . $tableName . '" SET ' . implode(', ', $setParts) . ' WHERE "id" = ' . $id;
                                    try {
                                        DB::statement($updateSql);
                                        $successful++;
                                        continue;
                                    } catch (\Exception $e2) {
                                        // Update failed, try insert with new ID
                                    }
                                }
                            }
                        }
                    }
                    
                    // If update fails, try insert with a new ID
                    if (preg_match('/INSERT INTO "(\w+)"\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/', $insert, $matches)) {
                        $tableName = $matches[1];
                        $columns = array_map('trim', explode(',', $matches[2]));
                        $values = array_map('trim', explode(',', $matches[3]));
                        
                        // Remove the ID column and its value
                        $newColumns = [];
                        $newValues = [];
                        
                        foreach ($columns as $idx => $col) {
                            $colName = trim($col, '"');
                            if ($colName !== 'id') {
                                $newColumns[] = $col;
                                $newValues[] = $values[$idx];
                            }
                        }
                        
                        if (!empty($newColumns)) {
                            $newInsert = 'INSERT INTO "' . $tableName . '" (' . implode(', ', $newColumns) . ') VALUES (' . implode(', ', $newValues) . ')';
                            try {
                                DB::statement($newInsert);
                                $successful++;
                                continue;
                            } catch (\Exception $e2) {
                                // Still failing, log the error
                            }
                        }
                    }
                }
                
                // Handle other errors
                if (!str_contains($errorMessage, 'duplicate') && !str_contains($errorMessage, 'already exists')) {
                    $errors[] = $errorMessage . "\nStatement: " . substr($insert, 0, 200) . "...";
                }
            }
        }

        // Reset sequences again after inserts
        foreach ($tables as $table) {
            $tableName = $table->tablename;
            try {
                if (Schema::hasColumn($tableName, 'id')) {
                    $maxId = DB::table($tableName)->max('id') ?? 0;
                    DB::statement("ALTER SEQUENCE {$tableName}_id_seq RESTART WITH " . ($maxId + 1));
                }
            } catch (\Exception $e) {
                // Ignore errors
            }
        }

        return response()->json([
            'success' => 'Data added from backup!',
            'inserts_successful' => $successful,
            'total_inserts' => count($inserts[0]) - count($skippedTables),
            'errors' => $errors
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Restore failed: ' . $e->getMessage()], 500);
    }
}
}