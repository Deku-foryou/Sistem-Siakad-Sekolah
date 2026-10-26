<?php

namespace App\Http\Controllers;

use App\Services\Import\GagalGenerator;
use App\Services\Import\TemplateGenerator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportDownloadController extends Controller
{
    public function template(string $jenis): BinaryFileResponse
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        abort_unless(in_array($jenis, ['siswa', 'guru', 'mapel', 'kelas', 'penempatan'], true), 404);

        $path = TemplateGenerator::buat($jenis);
        $this->pastikanDalamFolderImports($path);

        return response()->download($path, 'template-'.$jenis.'.xlsx')->deleteFileAfterSend(true);
    }

    public function gagal(string $uuid): BinaryFileResponse
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        abort_unless(! str_contains($uuid, '..'), 404);

        $path = GagalGenerator::buat($uuid);
        $this->pastikanDalamFolderImports($path);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    private function pastikanDalamFolderImports(string $path): void
    {
        $directory = realpath(storage_path('app/private/imports'));
        $resolved = realpath($path);
        abort_unless(
            $directory !== false
            && $resolved !== false
            && str_starts_with($resolved, $directory.DIRECTORY_SEPARATOR),
            404
        );
    }
}
