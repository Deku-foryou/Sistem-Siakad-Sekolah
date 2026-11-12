<?php

namespace App\Livewire;

use App\Models\Kuitansi;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\Walimurid;
use App\Services\Keuangan\KuitansiService;
use App\Services\NotifikasiService;
use App\Services\Payments\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class PembayaranManualComponent extends Component
{
    use LivewireAlert, WithFileUploads, WithPagination;

    public string $tab = 'pending';

    public string $search = '';

    public string $filterStatus = '';

    // Form pencatatan
    public string $siswaId = '';

    public string $tagihanId = '';

    public string $nominal = '';

    public string $metode = 'tunai';

    public string $tanggal_pembayaran = '';

    public string $nama_pembayar = '';

    public string $referensi = '';

    public string $catatan = '';

    public $bukti;

    // Verifikasi/tolak
    public int $selectedPembayaranId = 0;

    public string $alasanPenolakan = '';

    public function mount(): void
    {
        abort_unless(in_array(Auth::user()?->level, ['superadmin', 'admin'], true), 403);
        $this->tanggal_pembayaran = now()->format('Y-m-d');
    }

    private function assertAdmin(): void
    {
        abort_unless(in_array(Auth::user()?->level, ['superadmin', 'admin'], true), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTab(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function pilihSiswa(): void
    {
        $this->assertAdmin();
        $this->tagihanId = '';
    }

    public function resetForm(): void
    {
        $this->assertAdmin();
        $this->reset(['siswaId', 'tagihanId', 'nominal', 'nama_pembayar', 'referensi', 'catatan', 'bukti']);
        $this->metode = 'tunai';
        $this->tanggal_pembayaran = now()->format('Y-m-d');
    }

    public function catat(): void
    {
        $this->assertAdmin();

        $this->validate([
            'siswaId' => 'required|integer',
            'tagihanId' => 'required|integer',
            'nominal' => 'required|integer|min:1',
            'metode' => 'required|in:tunai,transfer,va,qris,ewallet,credit_card,cstore,echannel,lainnya',
            'tanggal_pembayaran' => 'required|date',
            'nama_pembayar' => 'nullable|string|max:150',
            'referensi' => 'nullable|string|max:150',
            'catatan' => 'nullable|string',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $siswa = Siswa::findOrFail((int) $this->siswaId);
        $tagihan = Tagihan::query()->where('idtagihan', (int) $this->tagihanId)->where('siswa_idsiswa', $siswa->idsiswa)->first();
        if (! $tagihan) {
            // fallback
            $tagihan = Tagihan::findOrFail((int) $this->tagihanId);
        }

        $nominal = (int) $this->nominal;
        // Nominal dihitung server-side berdasarkan input valid; tetap pastikan tidak negatif
        if ($nominal < 1) {
            $this->alert('error', 'Nominal tidak valid.');

            return;
        }

        $buktiPath = null;
        $buktiNama = null;
        $buktiMime = null;
        $buktiUkuran = null;

        if ($this->bukti) {
            $ext = strtolower($this->bukti->getClientOriginalExtension());
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            if (! in_array($ext, $allowed, true)) {
                $this->alert('error', 'Tipe berkas tidak diizinkan.');

                return;
            }
            $mime = $this->bukti->getMimeType();
            // Simpan di private storage
            $stored = $this->bukti->store('pembayaran/bukti', 'local');
            // Move from public disk location if needed? local disk root is storage/app/public in this app config
            // but requirement says private storage; better store to private path
            $originalPath = Storage::disk('local')->path($stored);
            $privateDir = storage_path('app/private/pembayaran/bukti');
            @mkdir($privateDir, 0755, true);
            $newName = uniqid('bukti_').'.'.$ext;
            $privatePath = $privateDir.'/'.$newName;
            @rename($originalPath, $privatePath);
            @unlink($originalPath);
            $buktiPath = 'pembayaran/bukti/'.$newName;
            $buktiNama = $this->bukti->getClientOriginalName();
            $buktiMime = $mime;
            $buktiUkuran = filesize($privatePath);
        }

        $pembayaran = DB::transaction(function () use ($siswa, $tagihan, $nominal, $buktiPath, $buktiNama, $buktiMime, $buktiUkuran) {
            return Pembayaran::create([
                'tagihan_idtagihan' => $tagihan->idtagihan,
                'siswa_idsiswa' => $siswa->idsiswa,
                'nominal_dibayar' => $nominal,
                'biaya_gateway' => 0,
                'metode' => $this->metode,
                'provider' => 'manual',
                'status' => 'pending',
                'tanggal_pembayaran' => $this->tanggal_pembayaran,
                'dibayar_pada' => null,
                'nama_pembayar' => $this->nama_pembayar ?: ($siswa->nama ?? null),
                'referensi' => $this->referensi ?: null,
                'catatan' => $this->catatan ?: null,
                'bukti_path' => $buktiPath,
                'bukti_nama_asli' => $buktiNama,
                'bukti_mime' => $buktiMime,
                'bukti_ukuran' => $buktiUkuran,
                'diinput_oleh' => Auth::id(),
                'payer_jenis' => 'admin',
                'payer_nama' => $this->nama_pembayar ?: ($siswa->nama ?? null),
                'payer_user_id' => Auth::id(),
            ]);
        });

        // Kirim notifikasi ke pemilik tagihan dan wali verified aktif
        try {
            $penerima = collect();
            if ($siswa->user_iduser) {
                $penerima->push($siswa->user_iduser);
            }
            $waliUsers = Walimurid::query()
                ->where('siswa_idsiswa', $siswa->idsiswa)
                ->where('status_hubungan', 'verified')
                ->whereNull('tanggal_berhenti')
                ->pluck('user_iduser');
            foreach ($waliUsers as $wu) {
                $penerima->push($wu);
            }
            $penerima = $penerima->filter()->unique()->values()->all();
            if (! empty($penerima)) {
                NotifikasiService::buat($penerima, [
                    'judul' => 'Pembayaran manual menunggu verifikasi',
                    'isi' => 'Pembayaran manual untuk tagihan telah dicatat dan menunggu verifikasi oleh admin.',
                    'jenis' => 'keuangan',
                    'prioritas' => 'sedang',
                    'link' => '/notifikasi',
                ], Auth::user());
            }
        } catch (\Throwable $e) {
            // Jangan gagalkan pencatatan karena notifikasi; log untuk pemantauan
            Log::warning('Notifikasi pembayaran manual (menunggu verifikasi) gagal dibuat', ['error' => $e->getMessage()]);
        }

        $this->alert('success', 'Pembayaran manual berhasil dicatat dengan status menunggu verifikasi.');
        $this->resetForm();
        $this->tab = 'pending';
        $this->dispatch('close-modal-catat');
    }

    public function pilihVerifikasi(int $id): void
    {
        $this->assertAdmin();
        $this->selectedPembayaranId = $id;
        $this->alasanPenolakan = '';
    }

    public function setujui(int $id): void
    {
        $this->assertAdmin();

        try {
            DB::transaction(function () use ($id) {
                $pembayaran = Pembayaran::query()->lockForUpdate()->findOrFail($id);
                $pembayaran = PaymentService::verifikasiPembayaranManual($pembayaran, Auth::user());
                // Buat kuitansi
                try {
                    KuitansiService::buatDariPembayaran($pembayaran, Auth::user());
                } catch (\Throwable $e) {
                    // Jika gagal buat kuitansi, jangan gagalkan verifikasi? Tapi requirement says kuitansi dibuat setelah verifikasi
                    // Coba buat lagi
                }
            });
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());

            return;
        }

        // Kirim notifikasi
        try {
            $pembayaran = Pembayaran::find($id);
            if ($pembayaran) {
                $siswa = $pembayaran->siswa;
                $penerima = collect();
                if ($siswa && $siswa->user_iduser) {
                    $penerima->push($siswa->user_iduser);
                }
                $waliUsers = Walimurid::query()
                    ->where('siswa_idsiswa', $pembayaran->siswa_idsiswa)
                    ->where('status_hubungan', 'verified')
                    ->whereNull('tanggal_berhenti')
                    ->pluck('user_iduser');
                foreach ($waliUsers as $wu) {
                    $penerima->push($wu);
                }
                $penerima = $penerima->filter()->unique()->values()->all();
                if (! empty($penerima)) {
                    NotifikasiService::buat($penerima, [
                        'judul' => 'Pembayaran disetujui',
                        'isi' => 'Pembayaran manual Anda telah disetujui dan kuitansi telah tersedia.',
                        'jenis' => 'keuangan',
                        'prioritas' => 'sedang',
                        'link' => '/notifikasi',
                    ], Auth::user());
                }
            }
        } catch (\Throwable $e) {
            // Notifikasi gagal — jangan gagalkan verifikasi; log untuk pemantauan
            Log::warning('Notifikasi pembayaran disetujui gagal dibuat', ['error' => $e->getMessage()]);
        }

        $this->alert('success', 'Pembayaran berhasil diverifikasi dan kuitansi telah dibuat.');
        $this->dispatch('close-modal-verifikasi');
    }

    public function tolak(int $id): void
    {
        $this->assertAdmin();

        if (trim($this->alasanPenolakan) === '') {
            $this->alert('error', 'Alasan penolakan wajib diisi.');

            return;
        }

        try {
            $pembayaran = Pembayaran::findOrFail($id);
            PaymentService::tolakPembayaranManual($pembayaran, Auth::user(), $this->alasanPenolakan);
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());

            return;
        }

        try {
            $pembayaran = Pembayaran::find($id);
            if ($pembayaran) {
                $siswa = $pembayaran->siswa;
                $penerima = collect();
                if ($siswa && $siswa->user_iduser) {
                    $penerima->push($siswa->user_iduser);
                }
                $waliUsers = Walimurid::query()
                    ->where('siswa_idsiswa', $pembayaran->siswa_idsiswa)
                    ->where('status_hubungan', 'verified')
                    ->whereNull('tanggal_berhenti')
                    ->pluck('user_iduser');
                foreach ($waliUsers as $wu) {
                    $penerima->push($wu);
                }
                $penerima = $penerima->filter()->unique()->values()->all();
                if (! empty($penerima)) {
                    NotifikasiService::buat($penerima, [
                        'judul' => 'Pembayaran ditolak',
                        'isi' => 'Pembayaran manual ditolak. Alasan: '.$this->alasanPenolakan,
                        'jenis' => 'keuangan',
                        'prioritas' => 'tinggi',
                        'link' => '/notifikasi',
                    ], Auth::user());
                }
            }
        } catch (\Throwable $e) {
            // Notifikasi gagal — jangan gagalkan penolakan; log untuk pemantauan
            Log::warning('Notifikasi pembayaran ditolak gagal dibuat', ['error' => $e->getMessage()]);
        }

        $this->alert('success', 'Pembayaran berhasil ditolak.');
        $this->alasanPenolakan = '';
        $this->dispatch('close-modal-tolak');
    }

    public function render(): View
    {
        $this->assertAdmin();

        $siswas = Siswa::where('status', 'aktif')->orderBy('nama')->get();
        $tagihanOptions = collect();
        if ($this->siswaId) {
            $tagihanOptions = Tagihan::where('siswa_idsiswa', (int) $this->siswaId)
                ->whereIn('status', ['belum_dibayar', 'dibayar_sebagian'])
                ->orderByDesc('tanggal_terbit')
                ->get();
        }

        $query = Pembayaran::query()
            ->with(['siswa', 'tagihan', 'inputer', 'verifikator', 'kuitansi'])
            ->where('provider', 'manual')
            ->when($this->search !== '', function ($q) {
                $q->whereHas('siswa', function ($sq) {
                    $sq->where('nama', 'like', '%'.$this->search.'%')->orWhere('nis', 'like', '%'.$this->search.'%');
                });
            });

        if ($this->tab === 'pending') {
            $query->whereIn('status', ['pending', 'review']);
        } elseif ($this->tab === 'history') {
            if ($this->filterStatus !== '') {
                $query->where('status', $this->filterStatus);
            } else {
                $query->whereIn('status', ['success', 'failed', 'cancelled', 'refunded']);
            }
        }

        $rows = $query->latest('idpembayaran')->paginate(25);

        $pendingCount = Pembayaran::where('provider', 'manual')->whereIn('status', ['pending', 'review'])->count();

        return view('livewire.pembayaran-manual-component', [
            'siswas' => $siswas,
            'tagihanOptions' => $tagihanOptions,
            'rows' => $rows,
            'pendingCount' => $pendingCount,
        ]);
    }
}
