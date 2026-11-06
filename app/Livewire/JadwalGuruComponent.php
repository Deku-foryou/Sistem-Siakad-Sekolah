<?php

namespace App\Livewire;

use App\Models\JadwalKelas;
use App\Services\JadwalSiswaService;
use App\Services\KonteksAkademikService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class JadwalGuruComponent extends Component
{
    public function render()
    {
        $user = Auth::user();
        abort_unless(in_array($user?->level, ['guru', 'superadmin'], true), 403);

        $konteks = KonteksAkademikService::ambil();
        $guru = $user?->level === 'guru' ? $user->guru()->first() : null;

        $jadwalPerHari = collect();

        if ($konteks->valid()) {
            $query = JadwalKelas::query()
                ->with([
                    'semester',
                    'jamhari.kelas',
                    'mapel',
                    'guru',
                ])
                ->where('semester_idsemester', $konteks->semester->idsemester);

            // Guru hanya melihat jadwal miliknya sendiri.
            if ($user?->level === 'guru') {
                if (! $guru) {
                    $jadwalPerHari = collect();
                } else {
                    $query->where('guru_idguru', $guru->idguru);
                    $jadwalPerHari = app(JadwalSiswaService::class)->perHari($query->get());
                }
            } else {
                // Superadmin melihat seluruh jadwal.
                $jadwalPerHari = app(JadwalSiswaService::class)->perHari($query->get());
            }
        }

        return view('livewire.jadwal-guru-component', [
            'user' => $user,
            'guru' => $guru,
            'konteks' => $konteks,
            'jadwalPerHari' => $jadwalPerHari,
            'namaHari' => \App\Support\Hari::NAMA,
        ]);
    }
}
