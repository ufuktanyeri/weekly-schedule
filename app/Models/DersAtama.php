<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DersAtamaFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Allocation of a course to a faculty member for an academic term.
 *
 * @property int $id
 * @property int $akademik_donem_id
 * @property int $ders_id
 * @property int $ogretim_elemani_id
 * @property int $haftalik_saat
 * @property string $sube
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AkademikDonem $akademikDonem
 * @property-read Ders $ders
 * @property-read OgretimElemani $ogretimElemani
 * @property-read Collection<int, HaftalikProgram> $programKayitlari
 */
class DersAtama extends Model
{
    /** @use HasFactory<DersAtamaFactory> */
    use HasFactory;

    protected $table = 'ders_atamalari';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'akademik_donem_id',
        'ders_id',
        'ogretim_elemani_id',
        'haftalik_saat',
        'sube',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'haftalik_saat' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AkademikDonem, $this>
     */
    public function akademikDonem(): BelongsTo
    {
        return $this->belongsTo(AkademikDonem::class, 'akademik_donem_id');
    }

    /**
     * @return BelongsTo<Ders, $this>
     */
    public function ders(): BelongsTo
    {
        return $this->belongsTo(Ders::class, 'ders_id');
    }

    /**
     * @return BelongsTo<OgretimElemani, $this>
     */
    public function ogretimElemani(): BelongsTo
    {
        return $this->belongsTo(OgretimElemani::class, 'ogretim_elemani_id');
    }

    /**
     * @return HasMany<HaftalikProgram, $this>
     */
    public function programKayitlari(): HasMany
    {
        return $this->hasMany(HaftalikProgram::class, 'ders_atama_id');
    }
}
