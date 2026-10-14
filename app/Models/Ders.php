<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DersFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Course with its weekly credit hours.
 *
 * @property int $id
 * @property int $program_id
 * @property string $ders_kodu
 * @property string $ders_adi
 * @property int $sinif
 * @property int $teorik_saat
 * @property int $uygulama_saat
 * @property int $kredi
 * @property int $akts
 * @property bool $aktif
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Program $program
 * @property-read Collection<int, DersAtama> $atamalar
 */
class Ders extends Model
{
    /** @use HasFactory<DersFactory> */
    use HasFactory;

    protected $table = 'dersler';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'program_id',
        'ders_kodu',
        'ders_adi',
        'sinif',
        'teorik_saat',
        'uygulama_saat',
        'kredi',
        'akts',
        'aktif',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sinif' => 'integer',
            'teorik_saat' => 'integer',
            'uygulama_saat' => 'integer',
            'kredi' => 'integer',
            'akts' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    /**
     * @return HasMany<DersAtama, $this>
     */
    public function atamalar(): HasMany
    {
        return $this->hasMany(DersAtama::class, 'ders_id');
    }

    /**
     * Total weekly contact hours (theory + practice).
     */
    public function haftalikSaat(): int
    {
        return $this->teorik_saat + $this->uygulama_saat;
    }
}
