<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Membuat satu akun contoh per peran untuk pengembangan/uji coba lokal.
     * Kata sandi seragam: "password".
     */
    public function run(): void
    {
        $akun = [
            ['Operator Kas', 'operator@kas.test', Role::Operator],
            ['Bendahara Pengeluaran', 'bendahara@kas.test', Role::Bendahara],
            ['Pejabat Pembuat Komitmen', 'ppk@kas.test', Role::Ppk],
            ['Pimpinan Satker', 'pimpinan@kas.test', Role::Pimpinan],
        ];

        foreach ($akun as [$nama, $email, $role]) {
            User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $nama,
                    'password' => Hash::make('password'),
                    'role' => $role,
                ],
            );
        }
    }
}
