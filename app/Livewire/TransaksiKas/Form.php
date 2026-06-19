<?php

namespace App\Livewire\TransaksiKas;

use App\Enums\JenisTransaksi;
use App\Enums\StatusSpj;
use App\Enums\Sumber;
use App\Models\TransaksiKas;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $transaksiId = null;

    #[Validate]
    public string $tanggal = '';

    #[Validate]
    public string $kegiatan = '';

    public string $keterangan = '';

    public string $penjab = '';

    #[Validate]
    public string $sumber = '';

    #[Validate]
    public string $jenis = '';

    #[Validate]
    public int $debet = 0;

    #[Validate]
    public int $kredit = 0;

    #[Validate]
    public string $status_spj = '';

    public string $no_spby = '';

    public string $tgl_spby = '';

    public int $nilai_spby = 0;

    public int $uang_diserahkan = 0;

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'kegiatan' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'sumber' => ['required', 'in:'.implode(',', array_column(Sumber::cases(), 'value'))],
            'jenis' => ['required', 'in:'.implode(',', array_column(JenisTransaksi::cases(), 'value'))],
            'debet' => ['required', 'integer', 'min:0'],
            'kredit' => ['required', 'integer', 'min:0', $this->validasiMinimalNominal()],
            'status_spj' => ['required', 'in:'.implode(',', array_column(StatusSpj::cases(), 'value'))],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal.required' => 'Tanggal wajib diisi.',
            'kegiatan.required' => 'Uraian kegiatan wajib diisi.',
            'sumber.required' => 'Pilih sumber dana.',
            'jenis.required' => 'Pilih jenis transaksi.',
            'kredit.min' => 'Nominal harus nol atau lebih.',
            'kredit.salah_satu' => 'Debet atau kredit harus diisi (tidak boleh keduanya nol).',
        ];
    }

    public function mount(?int $transaksiId = null): void
    {
        $this->transaksiId = $transaksiId;
        $this->status_spj = StatusSpj::Belum->value;

        if ($this->transaksiId) {
            $trx = TransaksiKas::findOrFail($this->transaksiId);
            $this->tanggal = $trx->tanggal->format('Y-m-d');
            $this->kegiatan = $trx->kegiatan;
            $this->keterangan = $trx->keterangan ?? '';
            $this->penjab = $trx->penjab ?? '';
            $this->sumber = $trx->sumber->value;
            $this->jenis = $trx->jenis->value;
            $this->debet = $trx->debet;
            $this->kredit = $trx->kredit;
            $this->status_spj = $trx->status_spj->value;
            $this->no_spby = $trx->no_spby ?? '';
            $this->tgl_spby = $trx->tgl_spby?->format('Y-m-d') ?? '';
            $this->nilai_spby = $trx->nilai_spby ?? 0;
            $this->uang_diserahkan = $trx->uang_diserahkan ?? 0;
        }
    }

    public function simpan(): void
    {
        $trx = $this->transaksiId ? TransaksiKas::findOrFail($this->transaksiId) : null;

        $this->authorize($trx ? 'update' : 'create', $trx ?? TransaksiKas::class);

        $data = $this->validate();

        $data['keterangan'] = $this->keterangan ?: null;
        $data['penjab'] = $this->penjab ?: null;
        $data['no_spby'] = $this->no_spby ?: null;
        $data['tgl_spby'] = $this->tgl_spby ?: null;
        $data['nilai_spby'] = $this->nilai_spby ?: null;
        $data['uang_diserahkan'] = $this->uang_diserahkan ?: null;

        if ($trx) {
            $trx->update($data);
        } else {
            TransaksiKas::create($data);
        }

        $this->dispatch('transaksi-tersimpan');
        $this->reset(['kegiatan', 'keterangan', 'penjab', 'debet', 'kredit', 'no_spby', 'tgl_spby', 'nilai_spby', 'uang_diserahkan']);
    }

    public function render()
    {
        return view('livewire.transaksi-kas.form', [
            'sumberPilihan' => Sumber::cases(),
            'jenisPilihan' => JenisTransaksi::cases(),
            'statusSpjPilihan' => StatusSpj::cases(),
        ])->layout('layouts.app');
    }

    private function validasiMinimalNominal(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if ($this->debet === 0 && (int) $value === 0) {
                $fail('Debet atau kredit harus diisi (tidak boleh keduanya nol).');
            }
        };
    }
}
