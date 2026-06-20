@extends('cetak.layout', ['judul' => 'Tanda Terima Penambahan'])

@section('isi')
    <h1 class="judul">Tanda Terima Penambahan Kekurangan</h1>
    <div class="nomor">Transaksi: {{ $transaksi?->no }}</div>

    <p style="margin:16px 0;">Telah diserahkan oleh {{ config('satker.bendahara.jabatan') }}
        {{ config('satker.nama') }} <strong>tambahan kekurangan dana belanja</strong> dengan rincian:</p>

    <table style="margin:16px 0;">
        <tr>
            <td style="width:30%">Diserahkan kepada</td>
            <td style="width:3%">:</td>
            <td>{{ $transaksi?->penjab ?: '...........................................' }}</td>
        </tr>
        <tr>
            <td style="vertical-align:top;">Jumlah</td>
            <td style="vertical-align:top;">:</td>
            <td><strong>Rp {{ number_format($t->jumlah, 0, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td style="vertical-align:top;">Terbilang</td>
            <td style="vertical-align:top;">:</td>
            <td><em style="text-transform:capitalize;">{{ \App\Support\Terbilang::rupiah($t->jumlah) }}</em></td>
        </tr>
        <tr>
            <td style="vertical-align:top;">Untuk kegiatan</td>
            <td style="vertical-align:top;">:</td>
            <td>{{ $transaksi?->kegiatan }}</td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td>:</td>
            <td>{{ $t->tanggal->translatedFormat('d F Y') }}</td>
        </tr>
        @if ($t->keterangan)
            <tr><td>Keterangan</td><td>:</td><td>{{ $t->keterangan }}</td></tr>
        @endif
    </table>

    <table class="ttd">
        <tr>
            <td style="width:50%" class="text-center">
                {{ config('satker.kota') }}, {{ $t->tanggal->translatedFormat('d F Y') }}<br>
                Yang menyerahkan, {{ config('satker.bendahara.jabatan') }}<br><br><br><br>
                <strong><u>{{ config('satker.bendahara.nama') ?: '...........................' }}</u></strong><br>
                @if (config('satker.bendahara.nip')) NIP. {{ config('satker.bendahara.nip') }} @endif
            </td>
            <td style="width:50%" class="text-center">
                Yang menerima,<br>
                Pelaksana<br><br><br><br>
                <strong><u>{{ $transaksi?->penjab ?: '...........................' }}</u></strong>
            </td>
        </tr>
    </table>
@endsection
