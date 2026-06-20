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

    {{-- Rekonsiliasi uang muka + rincian pengembalian/penambahan --}}
    <h3 style="font-size:12pt; margin:14px 0 4px;">Rekonsiliasi Uang Muka</h3>
    <table class="kotak">
        <tr><td style="width:60%">Uang Muka Diserahkan</td><td class="text-right">Rp {{ number_format($rekon['uang_muka'], 0, ',', '.') }}</td></tr>
        <tr><td>Total Nota (pertanggungjawaban)</td><td class="text-right">Rp {{ number_format($rekon['total_nota'], 0, ',', '.') }}</td></tr>
        <tr><td>Total Pengembalian (sisa dikembalikan)</td><td class="text-right">Rp {{ number_format($rekon['total_pengembalian'], 0, ',', '.') }}</td></tr>
        <tr><td>Total Penambahan (kekurangan ditambah bendahara)</td><td class="text-right">Rp {{ number_format($rekon['total_tambahan'], 0, ',', '.') }}</td></tr>
        <tr>
            <th class="text-right">Selisih ({{ $rekon['status']->label() }})</th>
            <th class="text-right">Rp {{ number_format(abs($rekon['selisih']), 0, ',', '.') }}</th>
        </tr>
    </table>

    @if ($daftarPengembalian->isNotEmpty())
        <p style="margin:10px 0 2px; font-weight:bold;">Rincian Pengembalian (sisa uang muka dikembalikan ke kas)</p>
        <table class="kotak">
            <thead><tr><th style="width:5%">No</th><th>Tanggal</th><th>Keterangan</th><th>Jumlah</th></tr></thead>
            <tbody>
                @foreach ($daftarPengembalian as $p)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>{{ $p->tanggal->translatedFormat('d F Y') }}</td>
                        <td>{{ $p->keterangan ?: 'Pengembalian sisa belanja' }}</td>
                        <td class="text-right">Rp {{ number_format($p->jumlah, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($daftarTambahan->isNotEmpty())
        <p style="margin:10px 0 2px; font-weight:bold;">Rincian Penambahan (kekurangan dibayar bendahara)</p>
        <table class="kotak">
            <thead><tr><th style="width:5%">No</th><th>Tanggal</th><th>Keterangan</th><th>Jumlah</th></tr></thead>
            <tbody>
                @foreach ($daftarTambahan as $t)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>{{ $t->tanggal->translatedFormat('d F Y') }}</td>
                        <td>{{ $t->keterangan ?: 'Penambahan kekurangan belanja' }}</td>
                        <td class="text-right">Rp {{ number_format($t->jumlah, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Lampiran bukti foto BERPASANGAN per nota (ditanam base64 utk Save-as-PDF) --}}
    @php
        $adaFoto = collect($fotoNota)->contains(fn ($c) => $c->isNotEmpty())
            || collect($fotoBarang)->contains(fn ($c) => $c->isNotEmpty());
    @endphp
    @if ($adaFoto)
        <h3 style="font-size:12pt; margin:16px 0 4px;">Lampiran Bukti Foto (Nota &amp; Barang)</h3>

        @foreach ($daftarNota as $n)
            @php
                $fNota = $fotoNota[$n->id] ?? collect();
                $fBarang = $fotoBarang[$n->id] ?? collect();
            @endphp
            @if ($fNota->isNotEmpty() || $fBarang->isNotEmpty())
                <p style="margin:10px 0 2px; font-weight:bold;">{{ $n->urutan }}. {{ $n->nama_penyedia }} — Rp {{ number_format($n->nominal, 0, ',', '.') }}</p>
                <table style="width:100%; page-break-inside:avoid;"><tr>
                    <td style="width:50%; vertical-align:top;">
                        <span style="font-size:10pt;">Foto Nota:</span><br>
                        @foreach ($fNota as $f)
                            @php $uri = $dataUri($f); @endphp
                            @if ($uri)
                                <img src="{{ $uri }}" style="max-width:46%; max-height:200px; margin:3px; border:1px solid #999; vertical-align:top;" alt="nota" />
                            @endif
                        @endforeach
                        @if ($fNota->isEmpty()) <span style="color:#999; font-size:10pt;">—</span> @endif
                    </td>
                    <td style="width:50%; vertical-align:top;">
                        <span style="font-size:10pt;">Foto Barang:</span><br>
                        @foreach ($fBarang as $f)
                            @php $uri = $dataUri($f); @endphp
                            @if ($uri)
                                <img src="{{ $uri }}" style="max-width:46%; max-height:200px; margin:3px; border:1px solid #999; vertical-align:top;" alt="barang" />
                            @endif
                        @endforeach
                        @if ($fBarang->isEmpty()) <span style="color:#999; font-size:10pt;">—</span> @endif
                    </td>
                </tr></table>
            @endif
        @endforeach
    @endif

    <table class="ttd" style="page-break-inside:avoid;">
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
