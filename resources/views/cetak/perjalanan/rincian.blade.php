@extends('cetak.layout', ['judul' => 'Rincian Biaya Perjalanan Dinas'])

@section('isi')
    <h1 class="judul">Rincian Biaya Perjalanan Dinas</h1>
    <div class="nomor">Lampiran SPD Nomor: {{ $st->nomor_surat }}</div>

    @php $total = 0; @endphp

    <table class="kotak">
        <thead>
            <tr>
                <th style="width:4%">No</th>
                <th>Nama</th>
                <th>Uang Harian × Hari</th>
                <th>Transport</th>
                <th>Penginapan</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($st->pegawai as $i => $p)
                @php
                    $uangHarian = (int) ($p['uang_harian'] ?? 0) * $st->lama_hari;
                    $transport = (int) ($p['transport'] ?? 0);
                    $penginapan = (int) ($p['penginapan'] ?? 0);
                    $subtotal = $uangHarian + $transport + $penginapan;
                    $total += $subtotal;
                @endphp
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $p['nama'] }}</td>
                    <td class="text-right">{{ number_format($p['uang_harian'] ?? 0, 0, ',', '.') }} × {{ $st->lama_hari }} = {{ number_format($uangHarian, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($transport, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($penginapan, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr>
                <th colspan="5" class="text-right">TOTAL</th>
                <th class="text-right">{{ number_format($total, 0, ',', '.') }}</th>
            </tr>
        </tbody>
    </table>

    <table class="ttd">
        <tr>
            <td style="width:55%">
                Mengetahui,<br>
                {{ config('satker.ppk.jabatan') }},<br><br><br><br>
                <strong><u>{{ $st->ttd_ppk ?: config('satker.ppk.nama') }}</u></strong><br>
                NIP. {{ $st->nip_ppk ?: config('satker.ppk.nip') }}
            </td>
            <td>
                {{ config('satker.kota') }}, {{ now()->translatedFormat('d F Y') }}<br>
                Pelaksana,<br><br><br><br>
                <strong><u>{{ $st->pegawai[0]['nama'] ?? '' }}</u></strong><br>
                NIP. {{ $st->pegawai[0]['nip'] ?? '' }}
            </td>
        </tr>
    </table>
@endsection
