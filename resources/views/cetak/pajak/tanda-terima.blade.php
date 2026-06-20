@extends('cetak.layout', ['judul' => 'Tanda Terima Uang Muka'])

@section('isi')
    <h1 class="judul">Tanda Terima Uang Muka Kerja</h1>
    <div class="nomor">Nomor: {{ $transaksi->no }}</div>

    <p style="margin:16px 0;">Yang bertanda tangan di bawah ini menyatakan telah menerima uang muka kerja dari
        {{ config('satker.bendahara.jabatan') }} {{ config('satker.nama') }}, dengan rincian sebagai berikut:</p>

    <table style="margin:16px 0;">
        <tr>
            <td style="width:28%">Diterima oleh</td>
            <td style="width:3%">:</td>
            <td>{{ $transaksi->penjab ?: '...........................................' }}</td>
        </tr>
        <tr>
            <td style="vertical-align:top;">Jumlah uang</td>
            <td style="vertical-align:top;">:</td>
            <td><strong>Rp {{ number_format($uangMuka, 0, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td style="vertical-align:top;">Terbilang</td>
            <td style="vertical-align:top;">:</td>
            <td><em style="text-transform:capitalize;">{{ \App\Support\Terbilang::rupiah($uangMuka) }}</em></td>
        </tr>
        <tr>
            <td style="vertical-align:top;">Untuk kegiatan</td>
            <td style="vertical-align:top;">:</td>
            <td>{{ $transaksi->kegiatan }}</td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td>:</td>
            <td>{{ $transaksi->tanggal->translatedFormat('d F Y') }}</td>
        </tr>
    </table>

    <p style="margin:16px 0; font-size:11pt;">Uang muka tersebut wajib dipertanggungjawabkan dengan bukti/nota yang sah.
        Apabila terdapat sisa, wajib disetorkan kembali ke kas; apabila kurang, akan ditambahkan oleh bendahara sesuai bukti.</p>

    <table class="ttd">
        <tr>
            <td style="width:50%" class="text-center">
                Yang menyerahkan,<br>
                {{ config('satker.bendahara.jabatan') }}<br><br><br><br>
                <strong><u>{{ config('satker.bendahara.nama') ?: '...........................' }}</u></strong><br>
                @if (config('satker.bendahara.nip')) NIP. {{ config('satker.bendahara.nip') }} @endif
            </td>
            <td style="width:50%" class="text-center">
                {{ config('satker.kota') }}, {{ $transaksi->tanggal->translatedFormat('d F Y') }}<br>
                Yang menerima,<br><br><br><br>
                <strong><u>{{ $transaksi->penjab ?: '...........................' }}</u></strong>
            </td>
        </tr>
    </table>
@endsection
