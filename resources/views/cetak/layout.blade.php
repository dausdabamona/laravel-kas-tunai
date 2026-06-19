<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul ?? 'Dokumen' }}</title>
    <style>
        @page { size: A4; margin: 2cm; }
        * { box-sizing: border-box; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; color: #000; margin: 0; padding: 1.5cm; }
        .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 16px; }
        .kop .kementerian { font-size: 12pt; text-transform: uppercase; }
        .kop .nama { font-size: 14pt; font-weight: bold; text-transform: uppercase; }
        .kop .alamat { font-size: 10pt; }
        h1.judul { text-align: center; font-size: 13pt; text-transform: uppercase; text-decoration: underline; margin: 18px 0 4px; }
        .nomor { text-align: center; margin-bottom: 18px; }
        table { border-collapse: collapse; width: 100%; }
        table.kotak th, table.kotak td { border: 1px solid #000; padding: 4px 6px; font-size: 11pt; }
        table.kotak th { background: #eee; text-align: center; }
        .ttd { margin-top: 32px; width: 100%; }
        .ttd td { vertical-align: top; font-size: 11pt; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .no-print { margin-bottom: 16px; }
        @media print { .no-print { display: none !important; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:10px 18px;font-family:sans-serif;background:#0d9488;color:#fff;border:0;border-radius:8px;cursor:pointer;min-height:44px;">
            Cetak
        </button>
    </div>

    <div class="kop">
        <div class="kementerian">{{ config('satker.kementerian') }}</div>
        <div class="kementerian">{{ config('satker.unit') }}</div>
        <div class="nama">{{ config('satker.nama') }}</div>
        <div class="alamat">{{ config('satker.alamat') }}</div>
    </div>

    @yield('isi')
</body>
</html>
