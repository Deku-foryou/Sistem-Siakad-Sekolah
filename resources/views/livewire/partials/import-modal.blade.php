@php
    $jenisUntukTemplate = $importJenis ?: 'siswa';
@endphp

<div
    x-data="{
        running: @entangle('importBerjalan').live,
        state: {
            uuid: @js($importUuid),
            total: @js($importTotal),
            offset: @js($importOffset),
            berhasil: @js($importBerhasil),
            gagal: @js($importGagal),
            selesai: @js($importSelesai),
            peringatan: @js($importPeringatan),
        },
        error: '',
        init() {
            this.unloadHandler = (event) => {
                if (! this.running) return;
                event.preventDefault();
                event.returnValue = '';
            };
            window.addEventListener('beforeunload', this.unloadHandler);
        },
        destroy() {
            window.removeEventListener('beforeunload', this.unloadHandler);
        },
        apply(state) {
            this.state = state;
            this.error = '';
        },
        percent() {
            return this.state.total
                ? Math.min(100, Math.round((this.state.berhasil + this.state.gagal) * 100 / this.state.total))
                : 0;
        },
        async start() {
            this.error = '';
            this.running = true;
            try {
                let state = await $wire.mulaiImport();
                this.apply(state);
                while (! state.selesai) {
                    state = await $wire.lanjutImport();
                    this.apply(state);
                }
                this.running = false;
            } catch (error) {
                this.error = $wire.importPesan || error?.message || 'Import gagal diproses.';
                this.running = false;
            }
        },
        async cancel() {
            this.error = '';
            try {
                const state = await $wire.batalImport();
                this.apply(state);
                this.running = false;
            } catch (error) {
                this.error = $wire.importPesan || error?.message || 'Import tidak dapat dibatalkan.';
            }
        }
    }"
>
    <div wire:ignore.self class="modal fade" id="importModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="importModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalTitle">Import {{ ucfirst($jenisUntukTemplate) }}</h5>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Gunakan template sesuai jenis data. File XLSX maksimal 2 MB.</p>
                    <a class="btn btn-outline-primary btn-sm mb-3" href="{{ route('import.template', ['jenis' => $jenisUntukTemplate]) }}">
                        <i class="fa-solid fa-download me-1"></i>Unduh template
                    </a>

                    <div class="mb-3">
                        <label for="fileImport" class="form-label">File XLSX</label>
                        <input id="fileImport" type="file" class="form-control" wire:model="fileImport" accept=".xlsx">
                        @error('fileImport') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        <div wire:loading wire:target="fileImport" class="small text-muted mt-1">Mengunggah file...</div>
                    </div>

                    <div x-show="state.total > 0" x-cloak class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Progres import</span>
                            <span x-text="`${state.berhasil + state.gagal} / ${state.total}`"></span>
                        </div>
                        <div class="progress" role="progressbar" :aria-valuenow="percent()" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-success" :style="`width: ${percent()}%`"></div>
                        </div>
                        <div class="small text-muted mt-1" x-text="`${state.berhasil} berhasil, ${state.gagal} gagal`"></div>
                    </div>

                    <div x-show="state.selesai && state.total > 0" x-cloak class="alert alert-info py-2 mb-3">
                        Import selesai: <strong x-text="`${state.berhasil} berhasil, ${state.gagal} gagal`"></strong>
                    </div>

                    <div x-show="state.peringatan?.length" x-cloak class="alert alert-warning py-2 mb-3">
                        <strong>Peringatan</strong>
                        <ul class="mb-0 ps-3">
                            <template x-for="(warning, index) in (state.peringatan || [])" :key="index">
                                <li x-text="warning"></li>
                            </template>
                        </ul>
                    </div>

                    <div x-show="state.gagal > 0 && state.uuid" x-cloak class="mb-3">
                        <a class="link-danger" :href="`{{ url('import/gagal') }}/${encodeURIComponent(state.uuid)}`">
                            <i class="fa-solid fa-file-excel me-1"></i>Unduh baris gagal
                        </a>
                    </div>

                    <div x-show="error" x-cloak class="alert alert-danger py-2 mb-0" role="alert" x-text="error"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" @click="start()" :disabled="running || ! $wire.fileImport">
                        <span x-show="!running"><i class="fa-solid fa-upload me-1"></i>Mulai import</span>
                        <span x-show="running" x-cloak><i class="fa-solid fa-spinner fa-spin me-1"></i>Memproses...</span>
                    </button>
                    <button type="button" class="btn btn-warning" x-show="running" @click="cancel()" :disabled="!running">
                        Batalkan import
                    </button>
                    <button type="button" class="btn btn-secondary" wire:click="tutupImport" :disabled="running">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('show-import-modal', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('importModal')).show();
    });
    window.addEventListener('close-import-modal', () => {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('importModal')).hide();
    });
</script>
