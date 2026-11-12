<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\AkunService;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Modul admin — Kelola Akun Wali (desain final §8.1 P1, §8.8, §9).
 * Hanya superadmin & admin sekolah yang memegang menu `wali-akun`.
 * Sandi sementara hanya tampil sekali (flash session), tidak pernah disimpan di state Livewire publik.
 */
class WaliAkunComponent extends Component
{
    use WithPagination;

    public $name = '';

    public $username = '';

    public $email = '';

    public $alasanAkun = '';

    public $search = '';

    public $perpage = 10;

    public $filterStatus = '';

    // Aksi per-baris
    public $targetId = null;

    public $aksi = null; // 'reset' | 'nonaktif' | 'aktif'

    public $alasanAksi = '';

    /**
     * Sandi sementara hanya hidup pada instance komponen selama satu request.
     * Private → tidak diserialisasi ke JSON Livewire; tidak masuk DB/log/session.
     */
    protected $sandiTampil = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'username' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_.-]+$/', 'unique:users,username'],
        'email' => 'nullable|string|email|max:255|unique:users,email',
    ];

    public function boot(): void
    {
        AkunService::pastikanBerwenangKelolaAkun(auth()->user());
    }

    public function updating($name, $value): void
    {
        if (in_array($name, ['search', 'perpage', 'filterStatus'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $query = User::query()->where('level', 'wali');

        if ($this->filterStatus === 'aktif') {
            $query->where('status_akun', 'aktif');
        } elseif ($this->filterStatus === 'nonaktif') {
            $query->where('status_akun', 'nonaktif');
        }

        if ($this->search !== '') {
            $q = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $this->search).'%';
            $query->where(function ($w) use ($q) {
                $w->where('username', 'like', $q)
                    ->orWhere('name', 'like', $q)
                    ->orWhere('email', 'like', $q);
            });
        }

        $users = $query->orderBy('name')->paginate($this->perpage);

        return view('livewire.wali-akun-component', [
            'users' => $users,
            'sandiTampil' => $this->sandiTampil,
        ]);
    }

    // ----------------------------- Buat akun -----------------------------

    public function simpan(): void
    {
        $this->validate();

        [$user, $sandi] = AkunService::buatAkunWali(
            auth()->user(),
            $this->name,
            $this->username,
            $this->email !== '' ? $this->email : null,
            $this->alasanAkun !== '' ? $this->alasanAkun : null
        );

        $this->sandiTampil = $sandi;
        $this->resetForm();
        $this->resetPage();
        $this->dispatch('close-add-akun-modal');
    }

    // ----------------------------- Nonaktif/aktif ------------------------

    public function bukaAksi(int $targetId, string $aksi): void
    {
        abort_unless(in_array($aksi, ['reset', 'nonaktif', 'aktif'], true), 404);
        $target = User::findOrFail($targetId);
        abort_unless($target->level === 'wali', 403);

        $this->targetId = $targetId;
        $this->aksi = $aksi;
        $this->alasanAksi = '';
    }

    public function batalAksi(): void
    {
        $this->reset('targetId', 'aksi', 'alasanAksi');
    }

    public function simpanAksi(): void
    {
        if (trim($this->alasanAksi) === '') {
            $this->addError('alasanAksi', 'Alasan wajib diisi.');

            return;
        }

        $target = User::findOrFail($this->targetId);

        switch ($this->aksi) {
            case 'reset':
                $sandi = AkunService::resetSandiSementara(auth()->user(), $target, $this->alasanAksi);
                $this->sandiTampil = $sandi;
                $this->dispatch('close-sandi-modal');
                break;

            case 'nonaktif':
                AkunService::setStatusAkun(auth()->user(), $target, 'nonaktif', $this->alasanAksi);
                $this->dispatch('close-status-modal');
                break;

            case 'aktif':
                AkunService::setStatusAkun(auth()->user(), $target, 'aktif', $this->alasanAksi);
                $this->dispatch('close-status-modal');
                break;

            default:
                abort(404);
        }

        $this->reset('targetId', 'aksi', 'alasanAksi');
        $this->resetPage();
    }

    public function resetForm(): void
    {
        $this->reset('name', 'username', 'email', 'alasanAkun');
        $this->resetValidation();
    }

    // ----------------------------- Internal ------------------------------

    public function dismissSandi(): void
    {
        $this->sandiTampil = null;
    }
}
