<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export PDF - {{ now()->format('Y-m-d H:i') }}</title>
</head>
<style>
    table tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    thead {
        background-color: #575e64;
        color: white;
    }

    th,
    td {
        padding: 5px;
    }
</style>

<body style="font-size: 10px;font-family:Arial, Helvetica, sans-serif">

    <div style="margin-bottom: 1px"> <i> Export data : {{ $title ?? '' }}</i></div>
    <div style="margin-bottom: 1px"> <i>Tanggal Unduh : {{ now()->format('Y-m-d H:i') }} - Diunduh Oleh : {{
            auth()->user()->name ?? '' }}</i> </div>


    @if($tglstart ?? '' && $tglend ?? '')
    <div style="margin-bottom: 3px;text-align: right"> <i>Range Data : {{ $tglstart }} - {{ $tglend }}</i></div>
    @endif

    @if($tahun ?? '' && $bulan ?? '')
    <div style="margin-bottom: 3px;text-align: right"> <i>Tahun : {{ $tahun }} - Bulan : {{ $bulan }}</i></div>
    @endif


    @if($tglnow ?? '')
    <div style="margin-bottom: 3px;text-align: right"> <i>Tanggal : {{ $tglnow }}</i></div>
    @endif

    <table border="1" style="border-collapse: collapse; width: 100%;">
        <thead>
            <tr>
                <th style="text-align: center; width: 10px">No</th>
                @foreach ($headers as $header)
                <th> {{ $header }} </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $key => $data)
            <tr>
                <td style="text-align: center;">{{ $key+1 }}</td>
                @foreach ($data as $value)
                <td> {{ $value }} </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>