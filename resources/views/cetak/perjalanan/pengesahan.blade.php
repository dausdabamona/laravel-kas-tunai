@extends('cetak.layout', ['judul' => 'Pengesahan / Visum Perjalanan Dinas'])

@section('isi')
    <h1 class="judul">Pengesahan Perjalanan Dinas</h1>
    <div class="nomor">SPD Nomor: {{ $st->nomor_surat }}</div>

    <p style="margin:8px 0;">
        Telah melaksanakan perjalanan dinas ke <strong>{{ $st->tempat_tujuan }}</strong>
        dengan maksud <em>{{ $st->maksud }}</em> selama {{ $st->lama_hari }} hari
        ({{ $st->tgl_berangkat->translatedFormat('d M Y') }} s.d. {{ $st->tgl_kembali->translatedFormat('d M Y') }}).
    </p>

    <table class="kotak" style="margin-top:8px;">
        <thead>
            <tr><th style="width:5%">No</th><th>Nama / NIP</th><th>Jabatan</th><th>Keterangan</th></tr>
        </thead>
        <tbody>
            @foreach ($st->pegawai as $i => $p)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $p['nama'] }}<br><small>NIP. {{ $p['nip'] }}</small></td>
                    <td>{{ $p['jabatan'] }}</td>
                    <td>Telah melaksanakan</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="ttd">
        <tr>
            <td style="width:55%">
                Pejabat yang mengesahkan,<br>
                {{ config('satker.ppk.jabatan') }},<br><br><br><br>
                <strong><u>{{ $st->ttd_ppk ?: config('satker.ppk.nama') }}</u></strong><br>
                NIP. {{ $st->nip_ppk ?: config('satker.ppk.nip') }}
            </td>
            <td>
                {{ config('satker.kota') }}, {{ now()->translatedFormat('d F Y') }}<br>
                Yang melaksanakan,<br><br><br><br>
                <strong><u>{{ $st->pegawai[0]['nama'] ?? '' }}</u></strong><br>
                NIP. {{ $st->pegawai[0]['nip'] ?? '' }}
            </td>
        </tr>
    </table>
@endsection
