<?php

namespace App\Filament\Resources\SparepartResource\Pages;

use App\Filament\Resources\SparepartResource;
use App\Models\SparepartStockHistory;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateSparepart extends CreateRecord
{
    protected static string $resource = SparepartResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $masuk  = (int) ($data['Masuk']  ?? 0);
        $keluar = (int) ($data['Keluar'] ?? 0);

        // Validasi sederhana: jika tidak mengizinkan minus, blok di sini.
        $stok = $masuk - $keluar;
        if ($stok < 0) {
            // Kamu bisa lempar ValidationException kalau mau lebih strict.
            $stok = 0; // atau throw new \InvalidArgumentException('Stok tidak boleh negatif');
        }

        $data['Masuk'] = $masuk;
        $data['Keluar'] = $keluar;
        $data['Stok'] = $stok;

        return $data;
    }

    /**
     * Setelah record spareparts tersimpan, buat histori stok awal:
     * - IN (qty = Masuk) -> balance_after = Masuk
     * - OUT (qty = Keluar) -> balance_after = Masuk - Keluar
     * Lalu sinkronkan snapshot Stok.
     */
    protected function afterCreate(): void
    {
        $sp = $this->record; // instance Sparepart yang baru dibuat

        $masuk  = (int) $sp->Masuk;
        $keluar = (int) $sp->Keluar;

        // Tanggal transaksi histori: pakai kolom Tanggal jika ada, else now()
        $performedAt = $sp->Tanggal
            ? Carbon::parse($sp->Tanggal)->startOfDay()
            : Carbon::now();

        DB::transaction(function () use ($sp, $masuk, $keluar, $performedAt) {
            $balance = 0;

            // Histori IN (jika qty > 0)
            if ($masuk > 0) {
                $balance = $masuk;

                SparepartStockHistory::create([
                    'sparepart_id'  => $sp->id,
                    'performed_at'  => $performedAt,
                    'direction'     => 'IN',
                    'qty'           => $masuk,
                    'balance_after' => $balance,
                    'note'          => 'init masuk',
                    'actor_name'    => auth()->user()->name ?? null,
                ]);
            }

            // Histori OUT (jika qty > 0)
            if ($keluar > 0) {
                $balance = max(0, $balance - $keluar); // cegah negatif jika kebijakanmu begitu

                SparepartStockHistory::create([
                    'sparepart_id'  => $sp->id,
                    'performed_at'  => $performedAt, // tetap di tanggal yang sama
                    'direction'     => 'OUT',
                    'qty'           => $keluar,
                    'balance_after' => $balance,
                    'note'          => 'init keluar',
                    'actor_name'    => auth()->user()->name ?? null,
                ]);
            }

            // Sinkronkan snapshot agar selalu sesuai histori terakhir
            $sp->update(['Stok' => $balance]);
        });
    }
}
