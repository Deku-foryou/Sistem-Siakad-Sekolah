@section('title', 'Bukti Publikasi Jadwal')

<div class="container-fluid mb-5 px-4" style="margin-top:20px">
    <h3 class="fw-bold mb-3">Bukti Publikasi Jadwal Pelajaran</h3>
    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <p><strong>Nomor:</strong> {{ $pub->nomor }}</p>
        <p><strong>Tahun Ajaran:</strong> {{ $pub->semester->tahunajaran->nama ?? '-' }}</p>
        <p><strong>Semester:</strong> {{ $pub->semester->nama ?? '-' }}</p>
        <p><strong>Kelas:</strong> {{ $pub->kelas->nama ?? '-' }}</p>
        <p><strong>Tanggal Publikasi:</strong> {{ $pub->published_at }}</p>
        <p><strong>Penerbit:</strong> {{ $pub->publisher->name ?? '-' }}</p>
        <p><strong>Status:</strong> {{ $pub->status }}</p>
        <button class="btn btn-primary" wire:click="unduhPdf">Unduh PDF</button>
    </div></div>
    <div class="table-responsive"><table class="table table-bordered">
        <thead><tr><th>Hari</th><th>Jam Ke</th><th>Mapel</th><th>Guru</th></tr></thead>
        <tbody>
        @foreach ($pub->snapshot as $row)
            <tr><td>{{ $row['hari'] }}</td><td>{{ $row['jam_ke'] }}</td><td>{{ $row['mapel'] }}</td><td>{{ $row['guru'] }}</td></tr>
        @endforeach
        </tbody>
    </table></div>
</div>
