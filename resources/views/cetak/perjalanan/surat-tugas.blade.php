@extends('cetak.layout', ['judul' => 'Surat Tugas'])

@section('isi')
    <h1 class="judul">Surat Tugas</h1>
    <div class="nomor">Nomor: {{ $st->nomor_surat }}</div>

    <table style="margin-bottom:12px;">
        <tr><td style="width:30%">Dasar</td><td style="width:3%">:</td><td>{{ $st->dasar ?: '-' }}</td></tr>
    </table>

    <p style="margin:6px 0;">Menugaskan kepada:</p>

    <table class="kotak" style="margin-bottom:12px;">
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th>Nama / NIP</th>
                <th>Pangkat / Gol.</th>
                <th>Jabatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($st->pegawai as $i => $p)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $p['nama'] }}<br><small>NIP. {{ $p['nip'] }}</small></td>
                    <td>{{ $p['pangkat'] }} / {{ $p['golongan'] }}</td>
                    <td>{{ $p['jabatan'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="margin-bottom:8px;">
        <tr><td style="width:30%">Untuk</td><td style="width:3%">:</td><td>{{ $st->maksud }}</td></tr>
        <tr><td>Tempat Tujuan</td><td>:</td><td>{{ $st->tempat_tujuan }}</td></tr>
        <tr><td>Lama Perjalanan</td><td>:</td><td>{{ $st->lama_hari }} hari ({{ $st->tgl_berangkat->translatedFormat('d M Y') }} s.d. {{ $st->tgl_kembali->translatedFormat('d M Y') }})</td></tr>
        <tr><td>Angkutan</td><td>:</td><td>{{ $st->angkutan ?: '-' }}</td></tr>
    </table>

    <table class="ttd">
        <tr>
            <td style="width:60%">&nbsp;</td>
            <td>
                {{ config('satker.kota') }}, {{ now()->translatedFormat('d F Y') }}<br>
                {{ config('satker.ppk.jabatan') }},<br><br><br><br>
                <strong><u>{{ $st->ttd_ppk ?: config('satker.ppk.nama') }}</u></strong><br>
                NIP. {{ $st->nip_ppk ?: config('satker.ppk.nip') }}
            </td>
        </tr>
    </table>
@endsection
