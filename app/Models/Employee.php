<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function faceBiometric(): HasOne
    {
        return $this->hasOne(EmployeeBiometric::class)->where('type', EmployeeBiometric::TYPE_FACE);
    }

    public function biometrics(): HasMany
    {
        return $this->hasMany(EmployeeBiometric::class);
    }
}
