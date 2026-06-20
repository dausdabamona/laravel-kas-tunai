<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * Backup terjadwal: dump DB + arsip storage privat ke disk 'backup',
 * dengan retensi 14 hari. Infra murni — tanpa dependensi tambahan.
 */
class JalankanBackup extends Command
{
    protected $signature = 'backup:jalankan';

    protected $description = 'Backup database + lampiran privat ke disk backup (retensi 14 hari)';

    public function handle(): int
    {
        $disk = Storage::disk('backup');
        $nama = 'backup-'.now()->format('Y-m-d-His').'.zip';
        $absZip = $disk->path($nama);
        @mkdir(dirname($absZip), 0775, true);

        $zip = new ZipArchive;
        $zip->open($absZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Manifest menjamin arsip tak kosong (ZipArchive tak menulis arsip nol entri).
        $zip->addFromString('manifest.txt', 'Backup Kas Tunai — '.now()->toDateTimeString());

        $this->tambahDatabase($zip);
        $this->tambahLampiran($zip);

        $zip->close();

        $this->retensi($disk);

        $this->info("Backup dibuat: {$nama}");

        return self::SUCCESS;
    }

    private function tambahDatabase(ZipArchive $zip): void
    {
        $conn = config('database.default');
        $driver = config("database.connections.{$conn}.driver");

        if ($driver === 'sqlite') {
            $db = config("database.connections.{$conn}.database");
            if (is_string($db) && $db !== ':memory:' && is_file($db)) {
                $zip->addFile($db, 'database.sqlite');
            }

            return;
        }

        if ($driver === 'mysql') {
            $cfg = config("database.connections.{$conn}");
            $proc = new Process([
                'mysqldump', '-h', (string) $cfg['host'], '-P', (string) $cfg['port'],
                '-u', (string) $cfg['username'], '-p'.(string) $cfg['password'], (string) $cfg['database'],
            ]);
            $proc->run();
            if ($proc->isSuccessful()) {
                $zip->addFromString('database.sql', $proc->getOutput());
            }
        }
    }

    private function tambahLampiran(ZipArchive $zip): void
    {
        $privat = Storage::disk('privat');

        foreach ($privat->allFiles() as $file) {
            $zip->addFromString('privat/'.$file, $privat->get($file));
        }
    }

    private function retensi(Filesystem $disk): void
    {
        $ambang = now()->subDays(14)->startOfDay();

        foreach ($disk->files() as $file) {
            if (preg_match('/backup-(\d{4}-\d{2}-\d{2})-/', $file, $m)
                && Carbon::parse($m[1])->lt($ambang)) {
                $disk->delete($file);
            }
        }
    }
}
