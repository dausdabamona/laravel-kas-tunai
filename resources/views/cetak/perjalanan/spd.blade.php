@extends('cetak.layout', ['judul' => 'Surat Perjalanan Dinas'])

@section('isi')
    @php $p = $st->pegawai[0] ?? []; @endphp

    <h1 class="judul">Surat Perjalanan Dinas (SPD)</h1>
    <div class="nomor">Nomor: {{ $st->nomor_surat }}</div>

    <table class="kotak">
        <tr><td style="width:5%">1</td><td style="width:40%">Pejabat Pembuat Komitmen</td><td>{{ $st->ttd_ppk ?: config('satker.ppk.nama') }}</td></tr>
        <tr><td>2</td><td>Nama/NIP Pegawai yang melaksanakan perjalanan dinas</td><td>{{ $p['nama'] ?? '' }} / {{ $p['nip'] ?? '' }}</td></tr>
        <tr><td>3</td><td>Pangkat dan Golongan / Jabatan</td><td>{{ $p['pangkat'] ?? '' }} / {{ $p['jabatan'] ?? '' }}</td></tr>
        <tr><td>4</td><td>Maksud Perjalanan Dinas</td><td>{{ $st->maksud }}</td></tr>
        <tr><td>5</td><td>Alat angkut yang dipergunakan</td><td>{{ $st->angkutan ?: '-' }}</td></tr>
        <tr><td>6</td><td>Tempat berangkat / Tempat tujuan</td><td>{{ $st->tempat_berangkat }} / {{ $st->tempat_tujuan }}</td></tr>
        <tr><td>7</td><td>Lamanya / Tanggal berangkat / Tanggal harus kembali</td>
            <td>{{ $st->lama_hari }} hari / {{ $st->tgl_berangkat->translatedFormat('d M Y') }} / {{ $st->tgl_kembali->translatedFormat('d M Y') }}</td></tr>
        <tr><td>8</td><td>Pembebanan Anggaran (Akun)</td><td>{{ $st->akun ?: '-' }}</td></tr>
    </table>

    <table class="ttd">
        <tr>
            <td style="width:55%">&nbsp;</td>
            <td>
                Dikeluarkan di {{ config('satker.kota') }}<br>
                Pada tanggal {{ now()->translatedFormat('d F Y') }}<br>
                {{ config('satker.ppk.jabatan') }},<br><br><br><br>
                <strong><u>{{ $st->ttd_ppk ?: config('satker.ppk.nama') }}</u></strong><br>
                NIP. {{ $st->nip_ppk ?: config('satker.ppk.nip') }}
            </td>
        </tr>
    </table>

    {{-- Lembar pengesahan tiba/berangkat (bolak-balik) --}}
    <div style="margin-top:28px; border-top:1px dashed #999; padding-top:12px;">
        <table class="kotak">
            <tr>
                <td style="width:50%">
                    Tiba di: <strong>{{ $st->tempat_tujuan }}</strong><br>
                    Pada tanggal: {{ $st->tgl_berangkat->translatedFormat('d M Y') }}<br>
                    Kepala,<br><br><br>
                    (..............................)
                </td>
                <td>
                    Berangkat dari: <strong>{{ $st->tempat_tujuan }}</strong><br>
                    Ke: {{ $st->tempat_berangkat }}<br>
                    Pada tanggal: {{ $st->tgl_kembali->translatedFormat('d M Y') }}<br>
                    Kepala,<br><br><br>
                    (..............................)
                </td>
            </tr>
        </table>
    </div>
@endsection
