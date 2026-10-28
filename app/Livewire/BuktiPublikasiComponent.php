<?php

namespace App\Livewire;

use App\Models\Publikasijadwal;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;

class BuktiPublikasiComponent extends Component
{
    public $id;

    public function mount($id): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $this->id = $id;
    }

    public function render()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $pub = Publikasijadwal::with(['semester.tahunajaran', 'kelas', 'publisher'])->findOrFail($this->id);

        return view('livewire.bukti-publikasi-component', ['pub' => $pub]);
    }

    public function unduhPdf()
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
        $pub = Publikasijadwal::with(['semester.tahunajaran', 'kelas', 'publisher'])->findOrFail($this->id);
        $pdf = Pdf::loadView('livewire.bukti-publikasi-pdf', ['pub' => $pub]);

        return response()->streamDownload(fn () => print ($pdf->output()), 'bukti-publikasi-'.$pub->nomor.'.pdf');
    }
}
