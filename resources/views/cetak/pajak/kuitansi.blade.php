@extends('cetak.layout', ['judul' => 'Kuitansi'])

@section('isi')
    <h1 class="judul">Kuitansi</h1>

    <table style="margin:16px 0;">
        <tr>
            <td style="width:25%">Sudah terima dari</td>
            <td style="width:3%">:</td>
            <td>{{ config('satker.bendahara.jabatan') }} {{ config('satker.nama') }}</td>
        </tr>
        <tr>
            <td style="vertical-align:top;">Uang sebanyak</td>
            <td style="vertical-align:top;">:</td>
            <td>
                <strong style="text-transform:capitalize;">{{ \App\Support\Terbilang::rupiah($nota->nominal) }}</strong>
            </td>
        </tr>
        <tr>
            <td style="vertical-align:top;">Untuk pembayaran</td>
            <td style="vertical-align:top;">:</td>
            <td>{{ $nota->transaksi?->kegiatan }}</td>
        </tr>
    </table>

    <table style="margin:18px 0;">
        <tr>
            <td style="width:45%; border:1px solid #000; padding:10px; font-size:14pt;">
                <strong>Rp {{ number_format($nota->nominal, 0, ',', '.') }}</strong>
            </td>
            <td>&nbsp;</td>
        </tr>
    </table>

    <table class="ttd">
        <tr>
            <td style="width:55%">&nbsp;</td>
            <td>
                {{ config('satker.kota') }}, {{ now()->translatedFormat('d F Y') }}<br>
                Yang menerima,<br><br><br><br>
                <strong><u>{{ $nota->nama_penyedia }}</u></strong><br>
                @if ($nota->npwp_penyedia) NPWP. {{ $nota->npwp_penyedia }} @endif
            </td>
        </tr>
    </table>
@endsection
