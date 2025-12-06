<?php
// app/Models/AcademicPeriod.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcademicPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'year_start',
        'year_end',
        'term',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
    ];

    public function sections()
    {
        return $this->hasMany(CourseSection::class);
    }

    // e.g. "AY 2024-2025 • 1st"
    public function getDisplayLabelAttribute(): string
    {
        return $this->label
            ?: "AY {$this->year_start}-{$this->year_end} • {$this->term}";
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }
}
