<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Sparepart extends Model
{
    use HasFactory;

    public function stockHistories()
    {
        return $this->hasMany(SparepartStockHistory::class);
    }

    // public function increaseStock(int $qty, ?string $note = null, ?string $actor = null): void
    // {
    //     DB::transaction(function () use ($qty, $note, $actor) {
    //         $newBalance = $this->Stok + $qty;

    //         $this->stockHistories()->create([
    //             'performed_at'  => Carbon::now(),
    //             'direction'     => 'IN',
    //             'qty'           => $qty,
    //             'balance_after' => $newBalance,
    //             'note'          => $note,
    //             'actor_name'    => $actor,
    //         ]);

    //         $this->update(['Stok' => $newBalance]);
    //     });
    // }

    public function increaseStock(int $qty, ?string $note = null, ?string $actor = null): void
    {
        DB::transaction(function () use ($qty, $note, $actor) {
            $newBalance = $this->Stok + $qty;

            $this->stockHistories()->create([
                'performed_at'  => Carbon::now(),
                'direction'     => 'IN',
                'qty'           => $qty,
                'balance_after' => $newBalance,
                'note'          => $note,
                'actor_name'    => $actor,
            ]);

            // update stok + akumulasi Masuk
            $this->update([
                'Stok'  => $newBalance,
                'Masuk' => ($this->Masuk ?? 0) + $qty,
            ]);
        });
    }

    // public function decreaseStock(int $qty, ?string $note = null, ?string $actor = null): void
    // {
    //     DB::transaction(function () use ($qty, $note, $actor) {
    //         $newBalance = $this->Stok - $qty;

    //         if ($newBalance < 0) {
    //             throw new \InvalidArgumentException("Stok tidak boleh negatif");
    //         }

    //         $this->stockHistories()->create([
    //             'performed_at'  => Carbon::now(),
    //             'direction'     => 'OUT',
    //             'qty'           => $qty,
    //             'balance_after' => $newBalance,
    //             'note'          => $note,
    //             'actor_name'    => $actor,
    //         ]);

    //         $this->update(['Stok' => $newBalance]);
    //     });
    // }

    public function decreaseStock(int $qty, ?string $note = null, ?string $actor = null): void
    {
        DB::transaction(function () use ($qty, $note, $actor) {
            $newBalance = $this->Stok - $qty;

            if ($newBalance < 0) {
                throw new \InvalidArgumentException("Stok tidak boleh negatif");
            }

            $this->stockHistories()->create([
                'performed_at'  => Carbon::now(),
                'direction'     => 'OUT',
                'qty'           => $qty,
                'balance_after' => $newBalance,
                'note'          => $note,
                'actor_name'    => $actor,
            ]);

            // update stok + akumulasi Keluar
            $this->update([
                'Stok'   => $newBalance,
                'Keluar' => ($this->Keluar ?? 0) + $qty,
            ]);
        });
    }


    // public function adjustStock(int $targetQty, ?string $note = null, ?string $actor = null): void
    // {
    //     DB::transaction(function () use ($targetQty, $note, $actor) {
    //         $difference = $targetQty - $this->Stok;
    //         $direction  = $difference >= 0 ? 'IN' : 'ADJUST';

    //         $this->stockHistories()->create([
    //             'performed_at'  => Carbon::now(),
    //             'direction'     => 'ADJUST',
    //             'qty'           => abs($difference),
    //             'balance_after' => $targetQty,
    //             'note'          => $note,
    //             'actor_name'    => $actor,
    //         ]);

    //         $this->update(['Stok' => $targetQty]);
    //     });
    // }

    public function adjustStock(int $targetQty, ?string $note = null, ?string $actor = null): void
    {
        DB::transaction(function () use ($targetQty, $note, $actor) {
            $current = $this->Stok;
            $diff    = $targetQty - $current; // >0 tambah, <0 kurangi

            $this->stockHistories()->create([
                'performed_at'  => Carbon::now(),
                'direction'     => 'ADJUST',
                'qty'           => abs($diff),
                'balance_after' => $targetQty,
                'note'          => $note,
                'actor_name'    => $actor,
            ]);

            $payload = ['Stok' => $targetQty];

            if ($diff > 0) {
                $payload['Masuk'] = ($this->Masuk ?? 0) + $diff;
            } elseif ($diff < 0) {
                $payload['Keluar'] = ($this->Keluar ?? 0) + abs($diff);
            }

            $this->update($payload);
        });
    }
}
