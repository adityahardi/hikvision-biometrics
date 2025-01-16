<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeBiometric extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'employee_biometrics';

    public const TYPE_FACE = 'face';

    public const TYPES = [
        [
            'label' => 'Face',
            'value' => self::TYPE_FACE,
        ],
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
