<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    public function mesin(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Mesin::class, 'mesin_id');
    }

    protected $fillable = [
        'nama_plant',
        'mesin_id',
        'rencana_cek',
        'keterangan',
    ];

    protected $casts = [
        'rencana_cek' => 'date',
        // JANGAN cast 'keterangan' ke array langsung, karena form kamu masih teks.
        // Kita bikin accessor/mutator custom supaya tetap bisa teks ATAU JSON.
    ];

    /**
     * Ambil metadata dari kolom keterangan.
     * Format yang kita pakai:
     *  - Jika user isi teks biasa: {"note": "teks aslinya"}
     *  - Jika sudah ada link: {"note": "...", "pred": [{"id": 12, "type": "0"}, ...]}
     */
    public function getKeteranganMetaAttribute(): array
    {
        $raw = $this->attributes['keterangan'] ?? '';

        // Kalau valid JSON -> pakai
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded + ['note' => '', 'pred' => []];
            }
            // Bukan JSON -> anggap teks biasa
            return ['note' => $raw, 'pred' => []];
        }

        // Kalau bukan string (aman-aman saja)
        return is_array($raw) ? ($raw + ['note' => '', 'pred' => []]) : ['note' => '', 'pred' => []];
    }

    public function setKeteranganMetaAttribute(array $value): void
    {
        // Normalisasi struktur
        $value = [
            'note' => (string)($value['note'] ?? ''),
            'pred' => array_values(array_map(function ($it) {
                return [
                    'id'   => (int)($it['id'] ?? 0),
                    'type' => (string)($it['type'] ?? '0'), // 0=FS, 1=SS, 2=FF, 3=SF (sesuai dhtmlx)
                ];
            }, $value['pred'] ?? [])),
        ];

        $this->attributes['keterangan'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /** Helper: daftar predecessor (array of ['id'=>int,'type'=>string]) */
    public function getPredIdsAttribute(): array
    {
        return $this->keterangan_meta['pred'] ?? [];
    }

    /** Set seluruh predecessor (overwrite) */
    public function setPredIdsAttribute(array $preds): void
    {
        $meta = $this->keterangan_meta; // ambil note lama
        $meta['pred'] = array_values(array_filter(array_map(function ($it) {
            $id = (int)($it['id'] ?? 0);
            if ($id <= 0) return null;
            return [
                'id'   => $id,
                'type' => (string)($it['type'] ?? '0'),
            ];
        }, $preds)));

        $this->keterangan_meta = $meta;
    }

    /** Tambah 1 predecessor jika belum ada (hindari duplikasi) */
    public function addPred(int $sourceId, string $type = '0'): void
    {
        $meta = $this->keterangan_meta;
        $exists = collect($meta['pred'] ?? [])->firstWhere('id', $sourceId);
        if (!$exists) {
            $meta['pred'][] = ['id' => $sourceId, 'type' => (string)$type];
            $this->keterangan_meta = $meta;
        }
    }

    /** Hapus predecessor tertentu */
    public function removePred(int $sourceId): void
    {
        $meta = $this->keterangan_meta;
        $meta['pred'] = array_values(array_filter($meta['pred'] ?? [], fn($p) => (int)$p['id'] !== $sourceId));
        $this->keterangan_meta = $meta;
    }

    /** Ambil catatan teks (untuk kompatibilitas form) */
    public function getKeteranganNoteAttribute(): string
    {
        return (string)($this->keterangan_meta['note'] ?? '');
    }

    /** Set catatan teks (untuk kompatibilitas form) */
    public function setKeteranganNoteAttribute(string $note): void
    {
        $meta = $this->keterangan_meta;
        $meta['note'] = $note;
        $this->keterangan_meta = $meta;
    }
}
