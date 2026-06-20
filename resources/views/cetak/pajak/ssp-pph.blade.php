@extends('cetak.layout', ['judul' => 'Surat Setoran Pajak — PPh'])

@section('isi')
    <h1 class="judul">Surat Setoran Pajak (SSP)</h1>
    <div class="nomor">{{ $hitung['jenis_pph'] ?: 'PPh' }}</div>

    <table class="kotak" style="margin-bottom:12px;">
        <tr><td style="width:35%">Nama Wajib Pajak</td><td>{{ $nota->nama_penyedia }}</td></tr>
        <tr><td>NPWP</td><td>{{ $nota->npwp_penyedia ?: '-' }}</td></tr>
        <tr><td>Alamat</td><td>{{ $nota->alamat_penyedia ?: '-' }}</td></tr>
        <tr><td>Uraian</td><td>{{ $nota->transaksi?->kegiatan }}</td></tr>
    </table>

    <table class="kotak">
        <tr><th style="width:35%">Kode Akun Pajak (MAP)</th><td>{{ $hitung['map_pph'] ?: '-' }}</td></tr>
        <tr><th>Kode Jenis Setoran (KJS)</th><td>{{ $hitung['kjs_pph'] ?: '-' }}</td></tr>
        <tr><th>Dasar Pengenaan Pajak (DPP)</th><td class="text-right">Rp {{ number_format($hitung['dpp'], 0, ',', '.') }}</td></tr>
        <tr>
            <th>Jumlah Setoran PPh</th>
            <td class="text-right">
                @if ($hitung['manual'])
                    <span style="letter-spacing:2px;">…………………………</span>
                @else
                    Rp {{ number_format($hitung['pph'], 0, ',', '.') }}
                @endif
            </td>
        </tr>
    </table>

    @if ($hitung['manual'])
        <p style="margin-top:8px; font-style:italic;">
            Catatan: nilai PPh Pasal 21 dihitung manual sesuai tabel PPh 21 (tarif progresif),
            diisi oleh bendahara. {{ $hitung['catatan'] }}
        </p>
    @endif

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
@endsection
