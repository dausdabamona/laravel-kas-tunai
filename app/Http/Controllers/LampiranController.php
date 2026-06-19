<?php

namespace App\Http\Controllers;

use App\Models\Lampiran;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LampiranController extends Controller
{
    /**
     * Sajikan file lampiran dari disk privat.
     *
     * Gerbang: middleware auth + signed (route). Policy view memastikan
     * pengguna berperan boleh baca (read terbuka semua peran).
     */
    public function stream(Lampiran $lampiran): StreamedResponse
    {
        $this->authorize('view', $lampiran);

        return Storage::disk($lampiran->disk)->response($lampiran->path, $lampiran->nama_file);
    }
}
