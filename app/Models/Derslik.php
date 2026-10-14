<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DerslikTuru;
use Database\Factories\DerslikFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Classroom.
 *
 * @property int $id
 * @property string $derslik_kodu
 * @property string $derslik_adi
 * @property int $kapasite
 * @property DerslikTuru $tur
 * @property string|null $bina
 * @property bool $aktif
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, HaftalikProgram> $programKayitlari
 */
class Derslik extends Model
{
    /** @use HasFactory<DerslikFactory> */
    use HasFactory;

    protected $table = 'derslikler';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'derslik_kodu',
        'derslik_adi',
        'kapasite',
        'tur',
        'bina',
        'aktif',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tur' => DerslikTuru::class,
            'kapasite' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return HasMany<HaftalikProgram, $this>
     */
    public function programKayitlari(): HasMany
    {
        return $this->hasMany(HaftalikProgram::class, 'derslik_id');
    }
}
