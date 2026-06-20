@extends('cetak.layout', ['judul' => 'Kuitansi Perjalanan Dinas'])

@php
    $r = $data['ringkas'];
    $pegawai = $st->pegawai[$pegawaiIndex] ?? [];
    $nomor = 'KT-'.$st->id.'-'.($pegawaiIndex + 1).'/'.$st->tgl_berangkat->format('Y');
@endphp

@section('isi')
    <h1 class="judul">K U I T A N S I</h1>
    <div class="nomor">Nomor: {{ $nomor }}</div>

    <table class="kotak" style="margin-top:8px;">
        <tr>
            <td style="width:28%">Sudah diterima dari</td>
            <td style="width:3%">:</td>
            <td>
                {{ config('satker.bendahara.jabatan') }} {{ config('satker.nama') }}
                <em>({{ config('satker.bendahara.nama') ?: '—' }})</em>
            </td>
        </tr>
        <tr>
            <td>Uang sejumlah</td>
            <td>:</td>
            <td><strong>Rp {{ number_format($r['total'], 0, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td style="vertical-align:top;">Untuk pembayaran</td>
            <td style="vertical-align:top;">:</td>
            <td>{{ $st->maksud }} pada kegiatan {{ $st->transaksi?->kegiatan ?? $st->maksud }} (SPD {{ $st->nomor_surat }})</td>
        </tr>
    </table>

    <p style="margin:10px 0; font-style:italic;">
        Terbilang : {{ \App\Support\Terbilang::rupiah($r['total']) }}
    </p>

    <table class="ttd">
        <tr>
            <td style="width:34%" class="text-center">
                Penerima,<br>
                {{ config('satker.kota') }}, {{ $st->tgl_kembali->translatedFormat('d F Y') }}<br><br><br><br>
                <strong><u>{{ $data['nama'] }}</u></strong><br>
                NIP. {{ $pegawai['nip'] ?? '' }}
            </td>
            <td style="width:33%" class="text-center">
                {{ config('satker.bendahara.jabatan') }},<br><br><br><br><br>
                <strong><u>{{ config('satker.bendahara.nama') ?: '...........................' }}</u></strong><br>
                NIP. {{ config('satker.bendahara.nip') }}
            </td>
            <td style="width:33%" class="text-center">
                Mengetahui,<br>
                {{ config('satker.ppk.jabatan') }}<br><br><br><br>
                <strong><u>{{ $st->ttd_ppk ?: config('satker.ppk.nama') }}</u></strong><br>
                NIP. {{ $st->nip_ppk ?: config('satker.ppk.nip') }}
            </td>
        </tr>
    </table>
@endsection
