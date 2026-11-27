<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Something a kid learns, from the Quebec "Progression des apprentissages"
 * (database/data/skills.json): its code, subject, domain and French name.
 *
 * @property int $id
 * @property string $code
 * @property string $subject
 * @property string $domain
 * @property string $name_fr
 */
class Skill extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    /** @return HasMany<SkillGrade, $this> */
    public function grades(): HasMany
    {
        return $this->hasMany(SkillGrade::class);
    }

    /** @return BelongsToMany<Skill, $this> */
    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'skill_prerequisites', 'skill_id', 'prerequisite_id')->withPivot('strength');
    }

    /** @return HasMany<ExerciseVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ExerciseVariant::class);
    }
}
