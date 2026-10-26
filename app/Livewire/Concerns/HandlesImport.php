<?php

namespace App\Livewire\Concerns;

use App\Services\Import\ImportRunner;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use RuntimeException;
use Throwable;

trait HandlesImport
{
    use WithFileUploads;

    public $fileImport = null;

    public ?string $importUuid = null;

    public string $importJenis = '';

    public int $importOffset = 0;

    public int $importTotal = 0;

    public int $importBerhasil = 0;

    public int $importGagal = 0;

    public bool $importSelesai = false;

    public bool $importBerjalan = false;

    public array $importPeringatan = [];

    public string $importPesan = '';

    public function bukaImport(string $jenis): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        if ($jenis !== $this->jenisImportHalaman()) {
            abort(404);
        }
        if ($this->importUuid && (new ImportRunner)->aktif($this->importUuid)) {
            throw new RuntimeException('Selesaikan atau batalkan import yang sedang berjalan terlebih dahulu.');
        }

        $this->hapusFileSementara();
        $this->fileImport = null;
        $this->importUuid = null;
        $this->importJenis = $jenis;
        $this->importOffset = 0;
        $this->importTotal = 0;
        $this->importBerhasil = 0;
        $this->importGagal = 0;
        $this->importSelesai = false;
        $this->importBerjalan = false;
        $this->importPeringatan = [];
        $this->importPesan = '';

        $this->dispatch('show-import-modal');
    }

    public function mulaiImport(): array
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->importPesan = '';
        $this->validate([
            'fileImport' => ['required', 'file', 'max:2048', 'extensions:xlsx'],
        ], [
            'fileImport.required' => 'Pilih file XLSX terlebih dahulu.',
            'fileImport.max' => 'Ukuran file maksimal 2 MB.',
            'fileImport.extensions' => 'File harus memiliki ekstensi .xlsx.',
        ]);

        if ($this->importJenis !== $this->jenisImportHalaman()) {
            throw new RuntimeException('Jenis import tidak valid.');
        }

        $runner = new ImportRunner;
        $state = null;
        $upload = $this->fileImport;

        try {
            $path = $upload->getRealPath();
            if (! is_string($path) || $path === '') {
                throw new RuntimeException('File sementara Livewire tidak dapat dibaca.');
            }

            $state = $runner->mulai($path, $this->importJenis, Auth::user());
        } catch (Throwable $exception) {
            $this->importPesan = $exception->getMessage();
            throw $exception;
        } finally {
            try {
                $this->hapusFileSementara();
            } catch (Throwable $exception) {
                if (is_array($state) && ! ($state['selesai'] ?? false)) {
                    $runner->batalkan($state['uuid']);
                }
                $this->importPesan = $exception->getMessage();
                throw $exception;
            }
        }

        $this->importUuid = $state['uuid'];
        $this->perbaruiStateImport($state);
        $this->importBerjalan = ! $this->importSelesai;
        $this->importPesan = 'File berhasil dipindai dan siap diproses.';

        return $this->stateImport();
    }

    public function lanjutImport(): array
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        if (! $this->importUuid) {
            throw new RuntimeException('Import belum dimulai.');
        }

        try {
            $state = (new ImportRunner)->proses($this->importUuid);
        } catch (Throwable $exception) {
            $this->importPesan = $exception->getMessage();
            $this->importBerjalan = false;
            $this->importSelesai = true;
            throw $exception;
        }

        $this->perbaruiStateImport($state);
        $this->importBerjalan = ! $this->importSelesai;
        $this->importPesan = $this->importSelesai
            ? 'Import selesai.'
            : 'Import sedang diproses.';

        return $this->stateImport();
    }

    public function batalImport(): array
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        $this->hapusFileSementara();
        if ($this->importUuid) {
            $state = (new ImportRunner)->batalkan($this->importUuid);
            $this->perbaruiStateImport($state);
            $this->importPesan = $state['dibatalkan'] ? 'Import dibatalkan.' : 'Import sudah selesai.';
        } else {
            $this->importPesan = 'Import dibatalkan.';
        }

        $this->importBerjalan = false;
        $this->importSelesai = true;

        return $this->stateImport();
    }

    public function tutupImport(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);

        if ($this->importUuid && (new ImportRunner)->aktif($this->importUuid)) {
            throw new RuntimeException('Batalkan atau tunggu import selesai sebelum menutup dialog.');
        }

        $this->hapusFileSementara();
        $this->fileImport = null;
        $this->dispatch('close-import-modal');
    }

    private function perbaruiStateImport(array $state): void
    {
        $this->importUuid = $state['uuid'];
        $this->importJenis = $state['jenis'];
        $this->importOffset = (int) $state['offset'];
        $this->importTotal = (int) $state['total'];
        $this->importBerhasil = (int) $state['berhasil'];
        $this->importGagal = (int) $state['gagal'];
        $this->importSelesai = (bool) $state['selesai'];
        $this->importPeringatan = array_slice(array_map(
            static fn (string $warning): string => mb_substr($warning, 0, 180, 'UTF-8'),
            $state['peringatan'] ?? []
        ), 0, 5);
    }

    private function stateImport(): array
    {
        return [
            'uuid' => $this->importUuid,
            'jenis' => $this->importJenis,
            'offset' => $this->importOffset,
            'total' => $this->importTotal,
            'berhasil' => $this->importBerhasil,
            'gagal' => $this->importGagal,
            'selesai' => $this->importSelesai,
            'peringatan' => $this->importPeringatan,
            'pesan' => $this->importPesan,
        ];
    }

    private function jenisImportHalaman(): string
    {
        return match (static::class) {
            \App\Livewire\SiswaComponent::class => 'siswa',
            \App\Livewire\GuruComponent::class => 'guru',
            \App\Livewire\MapelComponent::class => 'mapel',
            \App\Livewire\KelasComponent::class => 'kelas',
            \App\Livewire\KelassiswaComponent::class => 'penempatan',
            default => '',
        };
    }

    private function hapusFileSementara(): void
    {
        if ($this->fileImport instanceof TemporaryUploadedFile && $this->fileImport->exists() && ! $this->fileImport->delete()) {
            throw new RuntimeException('File sementara Livewire tidak dapat dihapus.');
        }

        $this->fileImport = null;
    }
}
