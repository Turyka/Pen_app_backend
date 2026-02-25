<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseController extends Controller
{

/* =========================
   🔥 COMMON ESCAPE FUNCTION
========================= */

private function escapeValue($value)
{
    if (is_bool($value)) {
        return $value ? 'TRUE' : 'FALSE';
    }

    if ($value === null || $value === '') {
        return 'NULL';
    }

    if (is_numeric($value)) {
        return $value;
    }

    $escaped = str_replace(
        ["\\", "'"],
        ["\\\\", "''"],
        $value
    );

    return "'{$escaped}'";
}


/* =========================
   🔥 BACKUP (POSTGRESQL)
========================= */

private function generatePostgreSQLBackup()
{
    $sqlContent = "SET client_encoding = 'UTF8';\n\n";

    $tables = DB::select("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_type = 'BASE TABLE'
    ");

    foreach ($tables as $table) {
        $tableName = $table->table_name;

        // Drop
        $sqlContent .= "DROP TABLE IF EXISTS \"{$tableName}\" CASCADE;\n";

        // Columns
        $columns = DB::select("
            SELECT 
                column_name,
                data_type,
                is_nullable,
                column_default,
                character_maximum_length
            FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND table_name = '{$tableName}'
            ORDER BY ordinal_position
        ");

        $sqlContent .= "CREATE TABLE \"{$tableName}\" (\n";

        $defs = [];

        foreach ($columns as $col) {
            $def = "\"{$col->column_name}\" {$col->data_type}";

            if ($col->character_maximum_length) {
                $def .= "({$col->character_maximum_length})";
            }

            if ($col->is_nullable === 'NO') {
                $def .= " NOT NULL";
            }

            if ($col->column_default) {
                $def .= " DEFAULT {$col->column_default}";
            }

            $defs[] = $def;
        }

        $sqlContent .= implode(",\n", $defs) . "\n);\n\n";

        // DATA
        $rows = DB::table($tableName)->get();

        foreach ($rows as $row) {
            $cols = [];
            $vals = [];

            foreach ($row as $col => $val) {
                $cols[] = "\"{$col}\"";
                $vals[] = $this->escapeValue($val);
            }

            $sqlContent .= "INSERT INTO \"{$tableName}\" (" .
                implode(',', $cols) .
                ") VALUES (" .
                implode(',', $vals) .
                ");\n";
        }

        $sqlContent .= "\n";
    }

    return $sqlContent;
}


/* =========================
   🔥 BACKUP ENDPOINT
========================= */

public function backup(Request $request)
{
    if ($request->query('titkos') !== env('API_SECRET')) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $fileName = 'backup_' . now()->format('Y_m_d_His') . '.sql';
    $filePath = storage_path('app/' . $fileName);

    try {
        $sql = $this->generatePostgreSQLBackup();

        file_put_contents($filePath, $sql);

        $cloudinary = new Cloudinary(env('CLOUDINARY_URL'));

        $result = $cloudinary->uploadApi()->upload($filePath, [
            'folder' => 'adatbazis',
            'resource_type' => 'raw',
        ]);

        unlink($filePath);

        return response()->json([
            'success' => true,
            'url' => $result['secure_url']
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


/* =========================
   🔥 RESTORE
========================= */

public function restoreNewest(Request $request)
{
    if ($request->query('titkos') !== env('API_SECRET')) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

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

        $url = $result['resources'][0]['secure_url'];
        $sql = file_get_contents($url);

        DB::beginTransaction();

        // 🔥 SPLIT SAFE
        $queries = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($queries as $query) {
            try {
                DB::statement($query);
            } catch (\Exception $e) {
                // csak logoljuk
                Log::error($e->getMessage());
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Restore kész'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}

}