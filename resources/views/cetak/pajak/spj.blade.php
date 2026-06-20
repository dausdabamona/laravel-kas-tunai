@extends('cetak.layout', ['judul' => 'Surat Pertanggungjawaban (SPJ)'])

@section('isi')
    <h1 class="judul">Surat Pertanggungjawaban Belanja</h1>
    <div class="nomor">{{ $transaksi->no }} — {{ $transaksi->tanggal->translatedFormat('d F Y') }}</div>

    <table style="margin-bottom:8px;">
        <tr><td style="width:25%">Kegiatan</td><td style="width:3%">:</td><td>{{ $transaksi->kegiatan }}</td></tr>
        <tr><td>Status SPJ</td><td>:</td><td><strong>{{ $transaksi->status_spj->label() }}</strong></td></tr>
    </table>

    <h3 style="font-size:12pt; margin:12px 0 4px;">Rincian Nota</h3>
    <table class="kotak">
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th>Penyedia / NPWP</th>
                <th>Nominal</th>
                <th>Pajak (PPh / PPN)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pajak as $baris)
                @php $n = $baris['nota']; $h = $baris['hitung']; @endphp
                <tr>
                    <td class="text-center">{{ $n->urutan }}</td>
                    <td>{{ $n->nama_penyedia }}<br><small>{{ $n->npwp_penyedia ?: 'tanpa NPWP' }}</small></td>
                    <td class="text-right">Rp {{ number_format($n->nominal, 0, ',', '.') }}</td>
                    <td>
                        {{ $h['jenis_pph'] ?: 'tanpa PPh' }}
                        @if ($h['manual'])
                            (manual)
                        @elseif ($h['pph'] > 0)
                            : Rp {{ number_format($h['pph'], 0, ',', '.') }}
                        @endif
                        @if ($h['ppn'] > 0)
                            <br>PPN: Rp {{ number_format($h['ppn'], 0, ',', '.') }}
                        @endif
                    </td>
                </tr>
            @endforeach
            <tr>
                <th colspan="2" class="text-right">TOTAL NOTA</th>
                <th class="text-right">Rp {{ number_format($totalNota, 0, ',', '.') }}</th>
                <th></th>
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
