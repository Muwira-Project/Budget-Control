<?php

namespace App\Support;

use PDO;
use RuntimeException;
use Spatie\DbDumper\Databases\Sqlite;

/**
 * SQLite dumper that copies the database file directly instead of
 * shelling out to the `sqlite3` CLI binary (which is not always
 * available on Windows development machines).
 */
class SqliteFileDumper extends Sqlite
{
    public function dumpToFile(string $dumpFile): void
    {
        $target = str_replace("'", "''", $dumpFile);

        try {
            $pdo = new PDO('sqlite:'.$this->dbName);
            $pdo->exec("VACUUM INTO '{$target}'");
            $pdo = null;
        } catch (\Throwable) {
            @copy($this->dbName, $dumpFile);
        }

        if (! file_exists($dumpFile) || filesize($dumpFile) === 0) {
            throw new RuntimeException('SQLite backup file could not be created.');
        }
    }
}
