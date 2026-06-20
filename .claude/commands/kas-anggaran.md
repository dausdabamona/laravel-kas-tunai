---
file: .claude/commands/kas-anggaran.md
command: /kas-anggaran
module: Anggaran & Persediaan (Fase 7) — tracker internal + dokumentasi
branch: feat/anggaran-persediaan (cabang dari main siap-produksi)
---

# /kas-anggaran — Modul Anggaran & Persediaan (Fase 7)

Modul ADITIF di atas buku kas. **Tracker internal + dokumentasi — BUKAN pengganti SAKTI/
Aplikasi Persediaan resmi.** Pagu/realisasi/sisa OTORITATIF tetap di SAKTI; modul ini
menampilkan snapshot-nya + mencatat detail operasional yang SAKTI tak tangkap.

## Prinsip isolasi (WAJIB — "tidak mengganggu aplikasi yang sudah jadi")
1. Tabel BARU saja. JANGAN ubah tabel lama (transaksi_kas, multi_nota, lampiran, dst).
2. FK satu arah: **BARU → LAMA**. Yang lama tak pernah tahu modul ini ada.
3. Tiap slice diakhiri **SELURUH suite hijau** (216 test lama = bukti non-gangguan), bukan
   cuma test baru. Satu test buku-kas merah = Anda menyentuh yang seharusnya tidak.
4. **Reuse infrastruktur**: Lampiran (foto taging), Cascade + HasMicrosecondTimestamps,
   RBAC-dari-enum, Periode (period-lock), signed-route, cetak Blade+print, phpspreadsheet (impor).
   Jangan bikin baru.
5. Sentuhan ke kode lama HANYA registry **append-only**: `KategoriLampiran` (+FotoTaging) &
   morph map (+serahTerima) di 7.5 — dijaga test penjaga. Plus ability baru di `Role` enum (append).
6. Branch `feat/anggaran-persediaan`; merge ke main hanya saat stabil.

## Sumber data anggaran: IMPOR dari SAKTI (bukan dihitung)
Pagu/realisasi/sisa = snapshot unduhan SAKTI (GLP039 "Realisasi Super"). App MENAMPILKAN,
tak menghitung. `mata_anggaran` simpan snapshot + tanggal; re-impor = upsert (terbaru menang).

## Struktur file SAKTI GLP039 (BERJENJANG — indentasi=level via kolom)
Baris MAK = **kolom 8 (H) berisi akun 6 digit**. Kolom (1-indexed | huruf):
  RO code = 6 (F)   | RO uraian = 12 (L)
  MAK code = 8 (H)  | MAK uraian = 13 (M)
  Pagu = 17 (Q)     | Realisasi = 24 (X)  | Sisa = 31 (AE)
- Bawa konteks RO dari baris di atas (akun sama bisa berulang antar output → kunci = RO+akun).
- Validasi: `pagu − realisasi == sisa` per baris; mismatch → TANDAI tinjau, JANGAN impor diam.
- Lewati baris total/subtotal (kolom 1 mengandung "JUMLAH").
- ⚠ Layout dikunci ke GLP039 ini; bila SAKTI ganti format, mapping kolom perlu disesuaikan.
- Pratinjau + konfirmasi sebelum simpan (pola 3.3).

## Slice (TDD, ramping)
- **7.1** Impor `mata_anggaran` dari xlsx SAKTI (parser hirarki + validasi + pratinjau + upsert snapshot).
- **7.2** `permintaan` (blangko) + item + cetak blangko.
- **7.3** `belanja_item` (detail persediaan, tautkan MAK) — **DOKUMENTASI**, bukan penggerak sisa.
- **7.4** `barang_persediaan` + stok ledger (`StokService`: masuk−keluar, DIHITUNG).
- **7.5** `serah_terima` + item + foto taging (`Lampiran::FotoTaging`) + cetak BAST.

## Skema inti
`mata_anggaran`: id, tahun, kode_ro, uraian_ro, kode_mak, uraian, pagu, realisasi, sisa,
  tanggal_snapshot, timestamps. **UNIQUE(tahun, kode_ro, kode_mak)**. TANPA SoftDeletes/Auditable
  (data referensi snapshot — jaga log sunyi). **Upsert by unique key** (JANGAN delete+insert →
  jaga id stabil untuk FK `belanja_item` nanti).

Detail konvensi penuh → Skill firdaus-dev / references/laravel.md.
