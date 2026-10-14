<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Gun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single 30-minute slot of the weekly schedule grid.
 *
 * @property int $id
 * @property int $ders_atama_id
 * @property int $akademik_donem_id
 * @property int $ogretim_elemani_id
 * @property int $derslik_id
 * @property Gun $gun
 * @property string $baslangic_saati HH:MM
 * @property string $bitis_saati HH:MM
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DersAtama $dersAtama
 * @property-read AkademikDonem $akademikDonem
 * @property-read OgretimElemani $ogretimElemani
 * @property-read Derslik $derslik
 */
class HaftalikProgram extends Model
{
    protected $table = 'haftalik_program';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ders_atama_id',
        'akademik_donem_id',
        'ogretim_elemani_id',
        'derslik_id',
        'gun',
        'baslangic_saati',
        'bitis_saati',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gun' => Gun::class,
        ];
    }

    /**
     * @return BelongsTo<DersAtama, $this>
     */
    public function dersAtama(): BelongsTo
    {
        return $this->belongsTo(DersAtama::class, 'ders_atama_id');
    }

    /**
     * @return BelongsTo<AkademikDonem, $this>
     */
    public function akademikDonem(): BelongsTo
    {
        return $this->belongsTo(AkademikDonem::class, 'akademik_donem_id');
    }

    /**
     * @return BelongsTo<OgretimElemani, $this>
     */
    public function ogretimElemani(): BelongsTo
    {
        return $this->belongsTo(OgretimElemani::class, 'ogretim_elemani_id');
    }

    /**
     * @return BelongsTo<Derslik, $this>
     */
    public function derslik(): BelongsTo
    {
        return $this->belongsTo(Derslik::class, 'derslik_id');
    }
}
