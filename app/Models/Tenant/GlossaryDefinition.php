<?php

namespace App\Models\Tenant;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
class GlossaryDefinition extends Model
{
    use HasFactory;
    use SoftDeletes, LogsActivity;

    protected $table = 'glossary_definition';
    
    protected $fillable = [
        'description',
        'source_name',
        'source_description',
        'source_url',
        'definition',
        'user_id',
        'glossary_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function glossary()
    {
        return $this->belongsTo(Glossary::class, 'glossary_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['definition', 'source_name', 'source_description', 'source_url','description']) // adjust based on your columns
            ->logOnlyDirty()
            ->useLogName('glossary_definition')
            ->setDescriptionForEvent(fn (string $eventName) => "Glossary definition was {$eventName}")
            ->dontSubmitEmptyLogs();
    }

    protected static function booted()
    {
        static::created(function ($model) {
            $activity = Activity::where('subject_id', $model->id)->first();

            if ($activity) {
                Activity::where('subject_id', $model->id)->update([
                    'parent_id' => $model->glossary_id,
                    'parent_type' => Glossary::class,
                ]);
            }
        });

        static::updated(function ($model) {
            $activity = Activity::where('subject_id', $model->id)->first();
            if ($activity) {
                Activity::where('subject_id', $model->id)->update([
                    'parent_id' => $model->glossary_id, 
                    'parent_type' => Glossary::class,
                ]);
            }
        });

        static::deleted(function ($model) {
            if($model->isForceDeleting()){
                return;
            }

            $activity = Activity::where('subject_id', $model->id)->latest()->first();
            if ($activity) {
                Activity::where('subject_id', $model->id)->update([
                    'parent_id' => $model->glossary_id,
                    'parent_type' => Glossary::class,
                ]);
            }
        });
    }
    
}
