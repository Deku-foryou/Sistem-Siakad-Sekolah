<!DOCTYPE html>
<html><head><title>Bukti Publikasi</title></head><body>
<h1>Bukti Publikasi Jadwal Pelajaran</h1>
<p>Nomor: {{ $pub->nomor }}</p>
<p>Tahun Ajaran: {{ $pub->semester->tahunajaran->nama ?? '-' }}</p>
<p>Semester: {{ $pub->semester->nama ?? '-' }}</p>
<p>Kelas: {{ $pub->kelas->nama ?? '-' }}</p>
<p>Tanggal Publikasi: {{ $pub->published_at }}</p>
<p>Penerbit: {{ $pub->publisher->name ?? '-' }}</p>
<table border="1" cellpadding="4">
<tr><th>Hari</th><th>Jam Ke</th><th>Mapel</th><th>Guru</th></tr>
@foreach ($pub->snapshot as $row)
<tr><td>{{ $row['hari'] }}</td><td>{{ $row['jam_ke'] }}</td><td>{{ $row['mapel'] }}</td><td>{{ $row['guru'] }}</td></tr>
@endforeach
</table>
</body></html>
