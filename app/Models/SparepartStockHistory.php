<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SparepartStockHistory extends Model
{
    use HasFactory;

    protected $table = 'sparepart_stock_histories';

    protected $fillable = [
        'sparepart_id',
        'performed_at',
        'direction',
        'qty',
        'balance_after',
        'note',
        'actor_name',
    ];

    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class);
    }

    public function getDirectionLabelAttribute(): string
    {
        return match ($this->direction) {
            'IN'     => 'Masuk',
            'OUT'    => 'Keluar',
            'ADJUST' => 'Penyesuaian',
            default  => $this->direction,
        };
    }
}
