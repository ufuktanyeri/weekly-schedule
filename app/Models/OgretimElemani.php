<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Desen;
use Database\Factories\OgretimElemaniFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Faculty member (instructor).
 *
 * @property int $id
 * @property int|null $admin_user_id
 * @property string|null $unvan
 * @property string $ad
 * @property string $soyad
 * @property string|null $eposta
 * @property string $renk_kodu HEX colour, e.g. #3B82F6
 * @property Desen $desen
 * @property int $haftalik_ders_limiti
 * @property bool $aktif
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AdminUser|null $user
 * @property-read Collection<int, DersAtama> $dersAtamalari
 */
class OgretimElemani extends Model
{
    /** @use HasFactory<OgretimElemaniFactory> */
    use HasFactory;

    protected $table = 'ogretim_elemanlari';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'admin_user_id',
        'unvan',
        'ad',
        'soyad',
        'eposta',
        'renk_kodu',
        'desen',
        'haftalik_ders_limiti',
        'aktif',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'desen' => Desen::class,
            'haftalik_ders_limiti' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }

    /**
     * @return HasMany<DersAtama, $this>
     */
    public function dersAtamalari(): HasMany
    {
        return $this->hasMany(DersAtama::class, 'ogretim_elemani_id');
    }

    /**
     * HEX colour safe for inline CSS; falls back to the default when malformed.
     */
    public function guvenliRenk(): string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $this->renk_kodu) === 1 ? $this->renk_kodu : '#3B82F6';
    }

    public function tamAd(): string
    {
        return trim(($this->unvan ?? '').' '.$this->ad.' '.$this->soyad);
    }
}
