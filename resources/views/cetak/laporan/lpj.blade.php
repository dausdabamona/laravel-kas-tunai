@extends('cetak.layout', ['judul' => 'Laporan Pertanggungjawaban'])

@section('isi')
    <h1 class="judul">Laporan Pertanggungjawaban Bendahara</h1>
    <div class="nomor">
        Periode {{ \Illuminate\Support\Carbon::parse($dari)->translatedFormat('d M Y') }}
        s.d. {{ \Illuminate\Support\Carbon::parse($sampai)->translatedFormat('d M Y') }}
    </div>

    <table class="kotak">
        <thead>
            <tr>
                <th>Sumber</th>
                <th>Saldo Awal</th>
                <th>Penerimaan (Debet)</th>
                <th>Pengeluaran (Kredit)</th>
                <th>Saldo Akhir</th>
            </tr>
        </thead>
        <tbody>
            @php $totalAkhir = 0; @endphp
            @foreach ($rekap as $kunci => $r)
                @php $totalAkhir += $r['saldo_akhir']; @endphp
                <tr>
                    <td>{{ \App\Enums\Sumber::from($kunci)->label() }}</td>
                    <td class="text-right">{{ number_format($r['saldo_awal'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($r['debet'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($r['kredit'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($r['saldo_akhir'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr>
                <th colspan="4" class="text-right">TOTAL SALDO AKHIR</th>
                <th class="text-right">{{ number_format($totalAkhir, 0, ',', '.') }}</th>
            </tr>
        </tbody>
    </table>

    <table class="ttd">
        <tr>
            <td style="width:55%">
                Mengetahui,<br>
                {{ config('satker.ppk.jabatan') }},<br><br><br><br>
                <strong><u>{{ config('satker.ppk.nama') ?: '..............................' }}</u></strong><br>
                NIP. {{ config('satker.ppk.nip') }}
            </td>
            <td>
                {{ config('satker.kota') }}, {{ now()->translatedFormat('d F Y') }}<br>
                {{ config('satker.bendahara.jabatan') }},<br><br><br><br>
                <strong><u>{{ config('satker.bendahara.nama') ?: '..............................' }}</u></strong><br>
                NIP. {{ config('satker.bendahara.nip') }}
            </td>
        </tr>
    </table>
@endsection
