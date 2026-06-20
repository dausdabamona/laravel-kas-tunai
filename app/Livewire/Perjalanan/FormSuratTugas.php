<?php

namespace App\Livewire\Perjalanan;

use App\Models\SuratTugas;
use App\Services\SuratTugasService;
use Livewire\Component;

/**
 * Form buat/ubah Surat Tugas (header perjalanan dinas).
 *
 * lama_hari dihitung OTOMATIS dari tanggal berangkat–kembali (inklusif) dan
 * dipakai sebagai default qty uang harian/fullboard di panel rincian. Pergerakan
 * kas mengikuti Uang Muka (porsi diset 0), bukan form ini.
 */
class FormSuratTugas extends Component
{
    public ?int $suratTugasId = null;

    public string $nomor_surat = '';

    public string $tanggal_surat = '';

    public string $tempat_berangkat = '';

    public string $tempat_tujuan = '';

    public string $maksud = '';

    public string $kegiatan = '';

    public string $jenis = 'luar_kota';

    public string $tgl_berangkat = '';

    public string $tgl_kembali = '';

    public string $akun = '';

    public string $dasar = '';

    public string $ttd_ppk = '';

    public string $nip_ppk = '';

    /** @var array<int, array<string, string>> */
    public array $pegawai = [];

    public function rules(): array
    {
        return [
            'nomor_surat' => ['required', 'string', 'max:255'],
            'tanggal_surat' => ['nullable', 'date'],
            'tempat_berangkat' => ['required', 'string', 'max:255'],
            'tempat_tujuan' => ['required', 'string', 'max:255'],
            'maksud' => ['required', 'string', 'max:255'],
            'kegiatan' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'in:dalam_kota,luar_kota'],
            'tgl_berangkat' => ['required', 'date'],
            'tgl_kembali' => ['required', 'date', 'after_or_equal:tgl_berangkat'],
            'akun' => ['nullable', 'string', 'max:255'],
            'dasar' => ['nullable', 'string', 'max:255'],
            'ttd_ppk' => ['nullable', 'string', 'max:255'],
            'nip_ppk' => ['nullable', 'string', 'max:255'],
            'pegawai' => ['required', 'array', 'min:1'],
            'pegawai.*.nama' => ['required', 'string', 'max:255'],
            'pegawai.*.nip' => ['nullable', 'string', 'max:255'],
            'pegawai.*.pangkat' => ['nullable', 'string', 'max:255'],
            'pegawai.*.jabatan' => ['nullable', 'string', 'max:255'],
            'pegawai.*.golongan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'tgl_kembali.after_or_equal' => 'Tanggal kembali tidak boleh sebelum tanggal berangkat.',
            'pegawai.required' => 'Tambahkan minimal satu pegawai.',
            'pegawai.*.nama.required' => 'Nama pegawai wajib diisi.',
        ];
    }

    public function mount(?int $suratTugasId = null): void
    {
        $this->suratTugasId = $suratTugasId;
        $this->tempat_berangkat = config('satker.kota', '');
        $this->ttd_ppk = config('satker.ppk.nama', '');
        $this->nip_ppk = config('satker.ppk.nip', '');

        if ($suratTugasId) {
            $this->isiDari(SuratTugas::findOrFail($suratTugasId));
        } else {
            $this->pegawai = [$this->barisPegawaiKosong()];
        }
    }

    private function isiDari(SuratTugas $st): void
    {
        $this->nomor_surat = $st->nomor_surat;
        $this->tanggal_surat = $st->tanggal_surat?->format('Y-m-d') ?? '';
        $this->tempat_berangkat = $st->tempat_berangkat;
        $this->tempat_tujuan = $st->tempat_tujuan;
        $this->maksud = $st->maksud;
        $this->kegiatan = $st->transaksi?->kegiatan ?? $st->maksud;
        $this->jenis = $st->jenis;
        $this->tgl_berangkat = $st->tgl_berangkat->format('Y-m-d');
        $this->tgl_kembali = $st->tgl_kembali->format('Y-m-d');
        $this->akun = $st->akun ?? '';
        $this->dasar = $st->dasar ?? '';
        $this->ttd_ppk = $st->ttd_ppk ?? '';
        $this->nip_ppk = $st->nip_ppk ?? '';
        $this->pegawai = array_map(fn ($p) => [
            'nama' => $p['nama'] ?? '',
            'nip' => $p['nip'] ?? '',
            'pangkat' => $p['pangkat'] ?? '',
            'jabatan' => $p['jabatan'] ?? '',
            'golongan' => $p['golongan'] ?? '',
        ], $st->pegawai ?: [$this->barisPegawaiKosong()]);
    }

    public function tambahPegawai(): void
    {
        $this->pegawai[] = $this->barisPegawaiKosong();
    }

    public function hapusPegawai(int $i): void
    {
        unset($this->pegawai[$i]);
        $this->pegawai = array_values($this->pegawai);

        if (empty($this->pegawai)) {
            $this->pegawai = [$this->barisPegawaiKosong()];
        }
    }

    public function getLamaHariProperty(): int
    {
        if (! $this->tgl_berangkat || ! $this->tgl_kembali) {
            return 0;
        }

        try {
            return app(SuratTugasService::class)->hitungLamaHari($this->tgl_berangkat, $this->tgl_kembali);
        } catch (\Throwable) {
            return 0;
        }
    }

    public function simpan(SuratTugasService $service): void
    {
        $this->authorize('perjalanan-dinas');
        $data = $this->validate();

        $payload = [
            ...$data,
            'pegawai' => array_map(fn ($p) => [...$p, 'biaya' => 0], $data['pegawai']),
        ];

        if ($this->suratTugasId) {
            $service->update(SuratTugas::findOrFail($this->suratTugasId), $payload);
        } else {
            $service->simpan([
                ...$payload,
                'porsi_pelaksana' => 0,
                'sumber_pelaksana' => 'tunai',
                'porsi_bendahara' => 0,
                'sumber_bendahara' => null,
            ]);
        }

        session()->flash('status', 'Surat tugas tersimpan.');
        $this->redirect(route('perjalanan-dinas.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.perjalanan.form-surat-tugas')->layout('layouts.app');
    }

    /** @return array<string, string> */
    private function barisPegawaiKosong(): array
    {
        return ['nama' => '', 'nip' => '', 'pangkat' => '', 'jabatan' => '', 'golongan' => ''];
    }
}
