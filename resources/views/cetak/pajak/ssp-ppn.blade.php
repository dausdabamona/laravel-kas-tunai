@extends('cetak.layout', ['judul' => 'Surat Setoran Pajak — PPN'])

@section('isi')
    <h1 class="judul">Surat Setoran Pajak (SSP)</h1>
    <div class="nomor">PPN Dalam Negeri</div>

    @if ($hitung['ppn'] <= 0)
        <p style="font-style:italic;">Transaksi ini tidak dikenai PPN (di bawah ambang atau bukan objek PPN).</p>
    @else
        <table class="kotak" style="margin-bottom:12px;">
            <tr><td style="width:35%">Nama Wajib Pajak</td><td>{{ $nota->nama_penyedia }}</td></tr>
            <tr><td>NPWP</td><td>{{ $nota->npwp_penyedia ?: '-' }}</td></tr>
            <tr><td>Alamat</td><td>{{ $nota->alamat_penyedia ?: '-' }}</td></tr>
            <tr><td>Uraian</td><td>{{ $nota->transaksi?->kegiatan }}</td></tr>
        </table>

        <table class="kotak">
            <tr><th style="width:35%">Kode Akun Pajak (MAP)</th><td>{{ config('pajak.ppn_ssp.map') }}</td></tr>
            <tr><th>Kode Jenis Setoran (KJS)</th><td>{{ config('pajak.ppn_ssp.kjs') }}</td></tr>
            <tr><th>Dasar Pengenaan Pajak (DPP)</th><td class="text-right">Rp {{ number_format($hitung['dpp'], 0, ',', '.') }}</td></tr>
            <tr><th>Jumlah Setoran PPN</th><td class="text-right">Rp {{ number_format($hitung['ppn'], 0, ',', '.') }}</td></tr>
        </table>

        <table class="ttd">
            <tr>
                <td style="width:55%">&nbsp;</td>
                <td>
                    {{ config('satker.kota') }}, {{ now()->translatedFormat('d F Y') }}<br>
                    {{ config('satker.bendahara.jabatan') }},<br><br><br><br>
                    <strong><u>{{ config('satker.bendahara.nama') ?: '..............................' }}</u></strong><br>
                    NIP. {{ config('satker.bendahara.nip') }}
                </td>
            </tr>
        </table>
    @endif
@endsection
