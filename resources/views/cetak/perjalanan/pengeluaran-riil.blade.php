@extends('cetak.layout', ['judul' => 'Daftar Pengeluaran Riil'])

@section('isi')
    <h1 class="judul">Daftar Pengeluaran Riil</h1>
    <div class="nomor">SPD Nomor: {{ $st->nomor_surat }}</div>

    <p style="margin:8px 0;">
        Yang bertanda tangan di bawah ini menyatakan bahwa biaya transport dan/atau
        penginapan di bawah ini yang tidak dapat diperoleh bukti-bukti pengeluarannya,
        meliputi:
    </p>

    @php $total = 0; @endphp
    <table class="kotak">
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th>Nama</th>
                <th>Uraian</th>
                <th>Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($st->pegawai as $i => $p)
                @php
                    $jumlah = (int) ($p['uang_harian'] ?? 0) * $st->lama_hari
                            + (int) ($p['transport'] ?? 0)
                            + (int) ($p['penginapan'] ?? 0);
                    $total += $jumlah;
                @endphp
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $p['nama'] }}</td>
                    <td>Biaya perjalanan dinas {{ $st->tempat_tujuan }}</td>
                    <td class="text-right">{{ number_format($jumlah, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr>
                <th colspan="3" class="text-right">JUMLAH</th>
                <th class="text-right">{{ number_format($total, 0, ',', '.') }}</th>
            </tr>
        </tbody>
    </table>

    <p style="margin-top:10px;">
        Jumlah seluruhnya Rp {{ number_format($total, 0, ',', '.') }} telah dibayarkan
        seluruhnya dan menjadi tanggung jawab kami.
    </p>

    <table class="ttd">
        <tr>
            <td style="width:55%">
                Mengetahui/Menyetujui,<br>
                {{ config('satker.ppk.jabatan') }},<br><br><br><br>
                <strong><u>{{ $st->ttd_ppk ?: config('satker.ppk.nama') }}</u></strong><br>
                NIP. {{ $st->nip_ppk ?: config('satker.ppk.nip') }}
            </td>
            <td>
                {{ config('satker.kota') }}, {{ now()->translatedFormat('d F Y') }}<br>
                Yang membuat pernyataan,<br><br><br><br>
                <strong><u>{{ $st->pegawai[0]['nama'] ?? '' }}</u></strong><br>
                NIP. {{ $st->pegawai[0]['nip'] ?? '' }}
            </td>
        </tr>
    </table>
@endsection
