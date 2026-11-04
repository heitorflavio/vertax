<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property string $full_name
 * @property string|null $cpf
 * @property-read string|null $cpf_masked
 * @property string|null $cnh
 * @property string|null $cnpj
 * @property Carbon $birth_date
 * @property string|null $mother_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property array<string, mixed>|null $hidden_data
 * @property int $trust_score
 * @property string $trust_label
 * @property array<string, mixed>|null $trust_breakdown
 * @property int $sources_count
 * @property int $consultations_count
 * @property Carbon|null $last_consulted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DemoRecord extends Model
{
    protected $fillable = [
        'public_id',
        'full_name',
        'cpf',
        'cnh',
        'cnpj',
        'birth_date',
        'mother_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'hidden_data',
        'trust_score',
        'trust_label',
        'trust_breakdown',
        'sources_count',
        'consultations_count',
        'last_consulted_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'hidden_data' => 'array',
            'trust_breakdown' => 'array',
            'trust_score' => 'integer',
            'sources_count' => 'integer',
            'consultations_count' => 'integer',
            'last_consulted_at' => 'datetime',
        ];
    }

    /**
     * CPF mascarado para listagens: mantém o bloco do meio e o final.
     *
     * @return Attribute<string|null, never>
     */
    protected function cpfMasked(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->maskCpf($this->cpf));
    }

    private function maskCpf(?string $cpf): ?string
    {
        if ($cpf === null) {
            return null;
        }

        $digits = (string) preg_replace('/\D+/', '', $cpf);

        if (strlen($digits) !== 11) {
            return '***.***.***-**';
        }

        return sprintf('***.%s.***-%s', substr($digits, 3, 3), substr($digits, 9, 2));
    }

    /**
     * @param  Builder<DemoRecord>  $query
     * @return Builder<DemoRecord>
     */
    public function scopePublicId(Builder $query, string $publicId): Builder
    {
        return $query->where('public_id', mb_strtoupper(trim($publicId)));
    }

    /** @return HasMany<DemoRecordSource, $this> */
    public function recordSources(): HasMany
    {
        return $this->hasMany(DemoRecordSource::class);
    }

    /** @return HasMany<DemoSearchTerm, $this> */
    public function searchTerms(): HasMany
    {
        return $this->hasMany(DemoSearchTerm::class);
    }

    /** @return HasMany<DemoConsultation, $this> */
    public function consultations(): HasMany
    {
        return $this->hasMany(DemoConsultation::class);
    }
}
