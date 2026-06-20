@extends('cetak.layout', ['judul' => 'Buku Kas Umum'])

@section('isi')
    <h1 class="judul">Buku Kas Umum — {{ $sumber->label() }}</h1>
    <div class="nomor">
        Periode {{ \Illuminate\Support\Carbon::parse($dari)->translatedFormat('d M Y') }}
        s.d. {{ \Illuminate\Support\Carbon::parse($sampai)->translatedFormat('d M Y') }}
    </div>

    <table class="kotak">
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th style="width:12%">Tanggal</th>
                <th>Uraian</th>
                <th>Debet</th>
                <th>Kredit</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td></td><td></td>
                <td><em>Saldo awal periode</em></td>
                <td></td><td></td>
                <td class="text-right">{{ number_format($saldoAwal, 0, ',', '.') }}</td>
            </tr>
            @foreach ($baris as $t)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $t->tanggal->translatedFormat('d/m/Y') }}</td>
                    <td>{{ $t->kegiatan }}</td>
                    <td class="text-right">{{ $t->debet ? number_format($t->debet, 0, ',', '.') : '' }}</td>
                    <td class="text-right">{{ $t->kredit ? number_format($t->kredit, 0, ',', '.') : '' }}</td>
                    <td class="text-right">{{ number_format($t->saldo_berjalan, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="ttd">
        <tr>
            <td style="width:55%">
                Mengetahui,<br>
                {{ config('satker.ppk.jabatan') }},<br><br><br><br>
                <strong><u>{{ config('satker.ppk.nama') ?: '..............................' }}</u></strong><br>
                NIP. {{ config('satker.ppk.nip') }}
            </td>
            <td>
                {{ config('satker.kota') }}, {{ now()->translatedFormat('d F Y') }}<br>
                {{ config('satker.bendahara.jabatan') }},<br><br><br><br>
                <strong><u>{{ config('satker.bendahara.nama') ?: '..............................' }}</u></strong><br>
                NIP. {{ config('satker.bendahara.nip') }}
            </td>
        </tr>
    </table>
@endsection
