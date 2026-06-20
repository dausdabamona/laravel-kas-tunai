@extends('cetak.layout', ['judul' => 'Rincian Biaya Perjalanan Dinas'])

@php
    use App\Enums\KomponenBiaya;
    use App\Enums\PembayarPd;
    use App\Enums\Sumber;

    $items = $data['items'];
    $r = $data['ringkas'];
    $pegawai = $st->pegawai[$pegawaiIndex] ?? [];
@endphp

@section('isi')
    <h1 class="judul">Rincian Biaya Perjalanan Dinas</h1>
    <div class="nomor">
        Lampiran SPD Nomor: {{ $st->nomor_surat }} Tanggal {{ ($st->tanggal_surat ?? $st->tgl_berangkat)->translatedFormat('d F Y') }}<br>
        a.n. {{ $data['nama'] }}
    </div>

    <table class="kotak">
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th style="width:48%">Perincian Biaya</th>
                <th style="width:22%">Jumlah</th>
                <th style="width:25%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $i => $item)
                @php
                    $satuan = match ($item->komponen) {
                        KomponenBiaya::UangHarian => 'hari',
                        KomponenBiaya::Fullboard, KomponenBiaya::Penginapan => 'malam',
                        default => 'unit',
                    };

                    if (in_array($item->komponen, [KomponenBiaya::UangHarian, KomponenBiaya::Fullboard], true)) {
                        $perincian = $item->komponen->label().' '.$item->qty.' '.$satuan.' × Rp '.number_format($item->harga_satuan, 0, ',', '.');
                    } elseif (in_array($item->komponen, [KomponenBiaya::TransportDarat, KomponenBiaya::TransportUdara], true)) {
                        $perincian = 'Transport'.($item->uraian ? ': '.$item->uraian : '');
                    } else {
                        $perincian = $item->komponen->label().($item->uraian ? ' '.$item->uraian : '');
                    }

                    $ket = [];
                    if ($item->komponen === KomponenBiaya::Penginapan && $item->qty > 0) {
                        $ket[] = $item->qty.' malam × Rp '.number_format($item->harga_satuan, 0, ',', '.');
                    }
                    if ($item->keterangan) {
                        $ket[] = $item->keterangan;
                    }
                    if ($item->pembayar === PembayarPd::Bendahara) {
                        $ket[] = 'Dibayar Bendahara';
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $perincian }}</td>
                    <td class="text-right">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                    <td>{{ implode(' — ', $ket) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center">Belum ada rincian biaya untuk pegawai ini.</td></tr>
            @endforelse
            <tr>
                <th colspan="2" class="text-right">Jumlah</th>
                <th class="text-right">Rp {{ number_format($r['total'], 0, ',', '.') }}</th>
                <th></th>
            </tr>
        </tbody>
    </table>

    <p style="margin:8px 0; font-style:italic;">
        Terbilang : {{ \App\Support\Terbilang::rupiah($r['total']) }}
    </p>

    {{-- Rekap pembayaran per bucket --}}
    <table class="kotak" style="margin-top:10px;">
        @foreach ($r['bucket'] as $key => $jumlah)
            @php
                [$pb, $mt] = explode('|', $key);
                $pbEnum = PembayarPd::from($pb);
                $teks = $pbEnum === PembayarPd::Bendahara
                    ? 'Dibayar langsung oleh Bendahara (tiket/hotel) — '.Sumber::from($mt)->label()
                    : 'Dibayarkan kepada Pelaksana — '.Sumber::from($mt)->label();
            @endphp
            <tr>
                <td>{{ $teks }}</td>
                <td class="text-right" style="width:30%">Rp {{ number_format($jumlah, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>

    <table class="kotak" style="margin-top:8px;">
        <tr>
            <td>Telah dibayar sejumlah</td>
            <td class="text-right" style="width:30%">Rp {{ number_format($r['telah_dibayar'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Sisa <strong>KURANG</strong> dibayar</td>
            <td class="text-right"><strong>Rp {{ number_format($r['sisa_kurang'], 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <table class="ttd">
        <tr>
            <td style="width:50%">
                Telah dibayar sejumlah Rp {{ number_format($r['telah_dibayar'], 0, ',', '.') }}<br>
                {{ config('satker.bendahara.jabatan') }},<br><br><br><br>
                <strong><u>{{ config('satker.bendahara.nama') ?: '...........................' }}</u></strong><br>
                NIP. {{ config('satker.bendahara.nip') }}
            </td>
            <td style="width:50%">
                {{ config('satker.kota') }}, {{ now()->translatedFormat('d F Y') }}<br>
                Telah menerima jumlah uang sebesar Rp {{ number_format($r['total'], 0, ',', '.') }}<br>
                Yang menerima / Pelaksana SPD,<br><br><br><br>
                <strong><u>{{ $data['nama'] }}</u></strong><br>
                NIP. {{ $pegawai['nip'] ?? '' }}
            </td>
        </tr>
    </table>
@endsection
