@extends('cetak.layout', ['judul' => 'Tanda Terima Pengembalian'])

@section('isi')
    <h1 class="judul">Tanda Terima Pengembalian</h1>
    <div class="nomor">Transaksi: {{ $transaksi?->no }}</div>

    <p style="margin:16px 0;">Yang bertanda tangan di bawah ini, {{ config('satker.bendahara.jabatan') }}
        {{ config('satker.nama') }}, telah <strong>menerima pengembalian sisa uang muka</strong> dengan rincian:</p>

    <table style="margin:16px 0;">
        <tr>
            <td style="width:30%">Diterima dari</td>
            <td style="width:3%">:</td>
            <td>{{ $transaksi?->penjab ?: '...........................................' }}</td>
        </tr>
        <tr>
            <td style="vertical-align:top;">Jumlah</td>
            <td style="vertical-align:top;">:</td>
            <td><strong>Rp {{ number_format($p->jumlah, 0, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td style="vertical-align:top;">Terbilang</td>
            <td style="vertical-align:top;">:</td>
            <td><em style="text-transform:capitalize;">{{ \App\Support\Terbilang::rupiah($p->jumlah) }}</em></td>
        </tr>
        <tr>
            <td style="vertical-align:top;">Untuk kegiatan</td>
            <td style="vertical-align:top;">:</td>
            <td>{{ $transaksi?->kegiatan }}</td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td>:</td>
            <td>{{ $p->tanggal->translatedFormat('d F Y') }}</td>
        </tr>
        @if ($p->keterangan)
            <tr><td>Keterangan</td><td>:</td><td>{{ $p->keterangan }}</td></tr>
        @endif
    </table>

    <table class="ttd">
        <tr>
            <td style="width:50%" class="text-center">
                Yang menyerahkan,<br>
                Pelaksana / Penerima Uang Muka<br><br><br><br>
                <strong><u>{{ $transaksi?->penjab ?: '...........................' }}</u></strong>
            </td>
            <td style="width:50%" class="text-center">
                {{ config('satker.kota') }}, {{ $p->tanggal->translatedFormat('d F Y') }}<br>
                Yang menerima, {{ config('satker.bendahara.jabatan') }}<br><br><br><br>
                <strong><u>{{ config('satker.bendahara.nama') ?: '...........................' }}</u></strong><br>
                @if (config('satker.bendahara.nip')) NIP. {{ config('satker.bendahara.nip') }} @endif
            </td>
        </tr>
    </table>
@endsection
