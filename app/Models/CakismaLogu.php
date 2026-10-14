<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CakismaTuru;
use App\Enums\Gun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Audit record of a rejected scheduling / allocation attempt.
 *
 * @property int $id
 * @property CakismaTuru $cakisma_turu
 * @property int|null $akademik_donem_id
 * @property int|null $ders_atama_id
 * @property int|null $ders_id
 * @property int|null $ogretim_elemani_id
 * @property int|null $derslik_id
 * @property int|null $cakisan_kayit_id
 * @property int|null $admin_user_id
 * @property Gun|null $gun
 * @property string|null $baslangic_saati
 * @property string|null $bitis_saati
 * @property string $mesaj
 * @property Carbon|null $created_at
 * @property-read AkademikDonem|null $akademikDonem
 * @property-read OgretimElemani|null $ogretimElemani
 * @property-read Derslik|null $derslik
 * @property-read Ders|null $ders
 * @property-read AdminUser|null $user
 */
class CakismaLogu extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'cakisma_loglari';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cakisma_turu',
        'akademik_donem_id',
        'ders_atama_id',
        'ders_id',
        'ogretim_elemani_id',
        'derslik_id',
        'cakisan_kayit_id',
        'admin_user_id',
        'gun',
        'baslangic_saati',
        'bitis_saati',
        'mesaj',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cakisma_turu' => CakismaTuru::class,
            'gun' => Gun::class,
            'created_at' => 'datetime',
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

    /**
     * @return BelongsTo<Ders, $this>
     */
    public function ders(): BelongsTo
    {
        return $this->belongsTo(Ders::class, 'ders_id');
    }

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }
}
