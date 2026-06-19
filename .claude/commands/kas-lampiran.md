---
description: "Prime konteks Phase 2 — Nota, lampiran polymorphic, storage privat, kompresi queue"
---

# /kas-lampiran — Phase 2: Nota & Lampiran

Load HANYA saat kerja nota + lampiran + storage. ~2K token. Konvensi penuh →
`CLAUDE.md` + skill `firdaus-dev`. Bangun di atas spine Phase 1 (transaksi_kas,
SaldoService, TransaksiKasPolicy, dibuat_oleh).

## Tujuan
Lampiran bukti ke transaksi: rincian **multi_nota** (banyak penyedia per
transaksi), **lampiran polymorphic** (nempel ke transaksi & nota), **storage
privat + signed URL**, dan **kompresi gambar via queue**. `status_spj`
dihitung ulang oleh `NotaService::recalc` saat nota berubah.

## Keputusan terkunci (jangan dilanggar tanpa konfirmasi)
1. **Cache minimal.** `nota_jml`/`nota_total` TIDAK jadi kolom — dihitung via
   `withCount('nota')` / `withSum('nota','nominal')` di query Index. Satu-satunya
   field denormalisasi = `status_spj` (demi filter cepat). Konsisten dgn SaldoService.
2. **recalc extensible.** `status_spj` di Phase 2 hanya mempertimbangkan nota
   (`kembalian_total = 0`). Rancang `recalc` agar SUDAH membaca komponen
   pengembalian (kini 0, diperluas di Phase 3) — Phase 3 cuma menambah sumber
   angka, BUKAN ubah signature.
3. **Kompresi server-side via queue** (Intervention Image), **PDF pass-through**.
   Satu jalur file. (Flip ke klien hanya bila bandwidth wifi jadi masalah nyata.)

## Skema tabel (sumber kebenaran)

**master_penyedia**: id, nama (unique), npwp nullable, alamat nullable,
terakhir_digunakan (timestamp nullable), frekuensi (unsignedInteger default 0).
*Tanpa softDeletes* (master ref).

**multi_nota**: id, transaksi_id FK→transaksi_kas (cascade soft), urutan
(unsignedInteger), nama_penyedia, nominal (bigInteger, cast int), npwp_penyedia
nullable, alamat_penyedia nullable, tgl_nota date nullable, penyedia_id FK→
master_penyedia nullable nullOnDelete, +timestamps +softDeletes.

**lampiran** (polymorphic): id, attachable_type, attachable_id (index gabungan),
kategori enum[foto_nota|foto_barang|bukti_pd|kuitansi|umum], urutan, path
(string, disk privat), nama_file, url nullable (signed, sementara), mime, meta
JSON (lat,lng,maps_url,jenis_dok,keterangan,waktu), +timestamps +softDeletes.

Uang = **bigInteger** rupiah, cast `integer`. Bahasa Indonesia, tema teal/biru,
mobile-first (tap ≥44px).

## Slice (1 per giliran: test MERAH → implementasi → HIJAU → commit → STOP)

### 2.0 — master_penyedia + PenyediaService
- Migration + Model (fillable eksplisit, tanpa softDeletes) + Factory.
- `PenyediaService::simpanAtauUpdate(array $data)`: cari by `nama` (atau npwp);
  ada → `frekuensi++` + `terakhir_digunakan = now()`; tidak ada → create freq=1.
- `PenyediaService::cari(string $q)`: LIKE nama/npwp, urut frekuensi desc.
- **DoD**: reuse menaikkan frekuensi (teruji); cari nama & NPWP teruji.

### 2.1 — multi_nota + model + relasi
- Migration + Model (`Auditable`? TIDAK — nota tak butuh log sendiri; cukup
  softDeletes + casts) + Factory.
- `TransaksiKas::nota(): HasMany`. Cascade soft-delete: hapus transaksi →
  nota ikut ter-soft-delete (gunakan model event `deleting`).
- **DoD**: nota persist; soft-delete cascade; nominal bigInteger cast int.

### 2.2 — NotaService::recalc → status_spj (LOGIKA UANG, wajib test)
- `recalc(TransaksiKas $t): void`. Hitung:
  `nota_total = Σ nota.nominal`; `kembalian_total = 0` (placeholder Phase 3,
  baca dari relasi anak pengembalian yg kini kosong).
  `target = $t->kredit` (nilai belanja). `status = (nota_total + kembalian_total)
  >= target ? Lunas : Belum`. Simpan hanya `status_spj`.
- Panggil recalc setelah nota ditambah/diubah/dihapus.
- **DoD**: status Lunas bila nota_total ≥ target; berubah saat nota tambah/hapus;
  kembalian_total terbaca (0) tanpa pecah saat Phase 3 menambah angka.

### 2.3 — lampiran polymorphic + morphMany
- Migration + Model (casts meta→array, softDeletes) + Factory.
- `morphMany` di TransaksiKas DAN MultiNota (`lampiran()`); `attachable()` morphTo.
- Daftarkan `Relation::enforceMorphMap(['transaksi'=>TransaksiKas, 'nota'=>MultiNota])`.
- **DoD**: lampiran nempel ke 2 tipe induk; meta JSON bolak-balik; soft-delete.

### 2.4 — LampiranService + storage privat + kompresi queue
- `composer require intervention/image` (BELUM terpasang — install dulu).
- Disk privat (`config/filesystems.php` disk `privat`, visibility private).
- `LampiranService::simpan(Model $induk, UploadedFile $file, string $kategori,
  array $meta=[])`: simpan ke disk privat → row lampiran → dispatch
  `KompresiGambar` (queue). PDF/non-gambar = pass-through (skip kompresi).
- `urlSementara(Lampiran): string` = `Storage::disk('privat')->temporaryUrl(...)`.
- Job `KompresiGambar`: resize/encode kualitas, timpa file, update meta.
- **DoD** (pakai `Storage::fake('privat')`, `Queue::fake`, `Bus::fake`): file di
  disk privat; signed/temporary URL; PDF pass-through; job kompresi ter-dispatch.

### 2.5 — Livewire kelola nota & foto
- Komponen `Transaksi/KelolaNota` (tipis): tambah/hapus nota → `NotaService` →
  recalc; reuse penyedia via `PenyediaService` (autocomplete).
- Upload foto nota & barang (`WithFileUploads`), kirim ke `LampiranService`;
  tangkap meta GPS (lat/lng dari klien, maps_url).
- **Tegakkan policy**: create/update via `TransaksiKasPolicy` (bendahara penuh,
  operator hanya draft); hormati period-lock.
- **DoD**: tambah/hapus nota memicu recalc status_spj; upload → row lampiran;
  policy & period-lock ditegakkan (teruji).

## Aturan ketat (warisan Phase 0–1)
- Logic → `app/Services/`; komponen Livewire & controller tipis.
- `$fillable` eksplisit; uang bigInteger; SoftDeletes semua model transaksional.
- `DB::transaction()` untuk operasi multi-tabel.
- Audit via trait `Auditable` HANYA di model yang perlu jejak ubah (transaksi).
- Test Pest dulu (MERAH) untuk tiap logika uang; HIJAU baru selesai; lalu commit + STOP.
