<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DonemTuru;
use Database\Factories\AkademikDonemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Academic term (semester).
 *
 * @property int $id
 * @property string $egitim_yili e.g. 2025-2026
 * @property DonemTuru $donem
 * @property Carbon $baslangic_tarihi
 * @property Carbon $bitis_tarihi
 * @property bool $aktif
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, DersAtama> $dersAtamalari
 */
class AkademikDonem extends Model
{
    /** @use HasFactory<AkademikDonemFactory> */
    use HasFactory;

    protected $table = 'akademik_donemler';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'egitim_yili',
        'donem',
        'baslangic_tarihi',
        'bitis_tarihi',
        'aktif',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'donem' => DonemTuru::class,
            'baslangic_tarihi' => 'date',
            'bitis_tarihi' => 'date',
            'aktif' => 'boolean',
        ];
    }

    /**
     * @param  Builder<AkademikDonem>  $query
     */
    public function scopeAktif(Builder $query): void
    {
        $query->where('aktif', true);
    }

    /**
     * @return HasMany<DersAtama, $this>
     */
    public function dersAtamalari(): HasMany
    {
        return $this->hasMany(DersAtama::class, 'akademik_donem_id');
    }

    public function ad(): string
    {
        return $this->egitim_yili.' '.$this->donem->label();
    }
}
