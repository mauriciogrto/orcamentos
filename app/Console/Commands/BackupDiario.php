<?php

namespace App\Console\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupDiario extends Command
{
    protected $signature = 'backup:diario {--keep=30 : Quantidade de dias para manter backups antigos}';

    protected $description = 'Gera backup do banco de dados e dos arquivos públicos do sistema';

    public function handle(): int
    {
        $timestamp = now()->format('Y-m-d_His');
        $backupDir = storage_path('app/backups');
        $tempDir = $backupDir.'/tmp-'.$timestamp;
        $zipPath = $backupDir.'/backup-'.$timestamp.'.zip';

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        try {
            $databaseDump = $tempDir.'/database.sql';
            $this->dumpDatabase($databaseDump);
            $this->createZip($zipPath, $databaseDump);
            $this->pruneOldBackups((int) $this->option('keep'));
        } finally {
            $this->removeDirectory($tempDir);
        }

        $this->info('Backup gerado com sucesso: '.$zipPath);

        return self::SUCCESS;
    }

    private function dumpDatabase(string $dumpPath): void
    {
        $connectionName = config('database.default');
        $connection = config("database.connections.{$connectionName}");

        if (in_array($connectionName, ['mysql', 'mariadb'], true)) {
            $this->dumpMysql($connection, $dumpPath);

            return;
        }

        if ($connectionName === 'sqlite') {
            $database = $connection['database'] ?? null;

            if (! $database || $database === ':memory:' || ! is_file($database)) {
                throw new RuntimeException('Banco SQLite não encontrado para backup.');
            }

            copy($database, $dumpPath);

            return;
        }

        throw new RuntimeException("Backup não implementado para a conexão {$connectionName}.");
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    private function dumpMysql(array $connection, string $dumpPath): void
    {
        $binary = $this->mysqldumpBinary();
        $arguments = [
            $binary,
            '--host='.$connection['host'],
            '--port='.$connection['port'],
            '--user='.$connection['username'],
            '--default-character-set='.($connection['charset'] ?? 'utf8mb4'),
            '--single-transaction',
            '--quick',
            '--result-file='.$dumpPath,
        ];

        if (($connection['password'] ?? '') !== '') {
            $arguments[] = '--password='.$connection['password'];
        }

        $arguments[] = $connection['database'];

        $process = new Process($arguments);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Falha ao gerar dump MySQL: '.$process->getErrorOutput());
        }
    }

    private function mysqldumpBinary(): string
    {
        $configured = env('MYSQLDUMP_BINARY');

        if ($configured && is_file($configured)) {
            return $configured;
        }

        $xampp = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';

        return is_file($xampp) ? $xampp : 'mysqldump';
    }

    private function createZip(string $zipPath, string $databaseDump): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Não foi possível criar o arquivo de backup.');
        }

        $zip->addFile($databaseDump, 'database.sql');

        $publicStorage = storage_path('app/public');
        if (is_dir($publicStorage)) {
            $this->addDirectoryToZip($zip, $publicStorage, 'storage_public');
        }

        $zip->close();
    }

    private function addDirectoryToZip(ZipArchive $zip, string $directory, string $prefix): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $realPath = $file->getRealPath();
            if (! $realPath) {
                continue;
            }

            $relativePath = $prefix.'/'.str_replace('\\', '/', substr($realPath, strlen($directory) + 1));

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($realPath, $relativePath);
            }
        }
    }

    private function pruneOldBackups(int $keepDays): void
    {
        if ($keepDays <= 0) {
            return;
        }

        $limit = now()->subDays($keepDays);

        foreach (glob(storage_path('app/backups/backup-*.zip')) ?: [] as $file) {
            if (filemtime($file) < $limit->timestamp) {
                @unlink($file);
            }
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }

        rmdir($directory);
    }
}
