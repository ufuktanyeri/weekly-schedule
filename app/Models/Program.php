<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProgramDerecesi;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Vocational / associate degree academic programme.
 *
 * @property int $id
 * @property string $program_kodu
 * @property string $program_adi
 * @property ProgramDerecesi $derece
 * @property string|null $bolum_adi
 * @property bool $aktif
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Ders> $dersler
 */
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory;

    protected $table = 'programlar';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'program_kodu',
        'program_adi',
        'derece',
        'bolum_adi',
        'aktif',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'derece' => ProgramDerecesi::class,
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Ders, $this>
     */
    public function dersler(): HasMany
    {
        return $this->hasMany(Ders::class, 'program_id');
    }
}
