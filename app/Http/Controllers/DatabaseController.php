<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DatabaseController extends Controller
{
    private function checkAuth($request)
    {
        if ($request->query('titkos') !== env('API_SECRET')) {
            abort(response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 403));
        }
    }

    public function migrateRefresh(Request $request)
    {
        $this->checkAuth($request);

        try {
            Artisan::call('db:wipe', ['--force' => true]);
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);

            return response()->json([
                'message' => 'Database refreshed successfully!',
                'output' => Artisan::output()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Migration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function refreshAdatEszkozok(Request $request)
    {
        $this->checkAuth($request);

        DB::table('adat_eszkozok')->truncate();

        return response()->json(['message' => 'All data deleted']);
    }

    // 🔹 BACKUP
    public function backup(Request $request)
    {
        $this->checkAuth($request);

        $fileName = 'backup_' . now()->format('Y_m_d_His') . '.sql';
        $filePath = storage_path('app/' . $fileName);

        try {
            $sqlContent = "-- PostgreSQL Backup\n";
            $sqlContent .= "-- Created: " . now() . "\n\n";

            $sqlContent .= $this->generatePostgreSQLBackup();

            file_put_contents($filePath, $sqlContent);

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
            if (file_exists($filePath)) unlink($filePath);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // 🔥 FULL SAFE ESCAPE
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

        // escape \ és '
        $escaped = str_replace(
            ["\\", "'"],
            ["\\\\", "''"],
            $value
        );

        return "'{$escaped}'";
    }

    // 🔹 POSTGRES BACKUP
    private function generatePostgreSQLBackup()
    {
        $sql = "";

        $tables = DB::select("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_type = 'BASE TABLE'
        ");

        foreach ($tables as $table) {
            $tableName = $table->table_name;

            $sql .= "TRUNCATE TABLE \"{$tableName}\" RESTART IDENTITY CASCADE;\n";

            $rows = DB::table($tableName)->get();

            foreach ($rows as $row) {
                $columns = [];
                $values = [];

                foreach ($row as $column => $value) {
                    $columns[] = "\"{$column}\"";
                    $values[] = $this->escapeValue($value);
                }

                $sql .= "INSERT INTO \"{$tableName}\" (" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ");\n";
            }

            $sql .= "\n";
        }

        return $sql;
    }

    // 🔹 RESTORE (100% SAFE)
public function restoreNewest(Request $request)
{
    $this->checkAuth($request);

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
            return response()->json(['error' => 'No backup found'], 404);
        }

        $file = file_get_contents($result['resources'][0]['secure_url']);

        // 🔥 ESCAPE FIXEK
        $file = str_replace("\\'", "''", $file);
        $file = str_replace("\\\\", "\\\\\\\\", $file);

        // 🔥 NULL FIX (empty string -> NULL)
        $file = preg_replace("/,\s*''/", ", NULL", $file);

        // 🔥 BOOLEAN FIX
        $file = preg_replace("/'([01])'/", "$1", $file);

        // 🔥 SEQUENCE FIX (nagyon fontos)
        $file = preg_replace(
            "/DEFAULT nextval\\('[^']+'::regclass\\)/",
            "",
            $file
        );

        DB::beginTransaction();

        // 🔥 CONSTRAINT OFF
        DB::statement("SET session_replication_role = replica;");

        DB::unprepared($file);

        // 🔥 CONSTRAINT ON
        DB::statement("SET session_replication_role = DEFAULT;");

        // 🔥 SEQUENCE RESET
        $tables = DB::select("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'public'
        ");

        foreach ($tables as $table) {
            $tableName = $table->table_name;

            try {
                DB::statement("
                    SELECT setval(
                        pg_get_serial_sequence('\"{$tableName}\"', 'id'),
                        COALESCE(MAX(id), 1),
                        true
                    ) FROM \"{$tableName}\"
                ");
            } catch (\Exception $e) {
                // ignore ha nincs id
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Database restored successfully'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'error' => 'Restore failed',
            'details' => $e->getMessage()
        ], 500);
    }
}
}