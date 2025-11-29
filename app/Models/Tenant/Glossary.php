<?php

namespace App\Models\Tenant;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;


use App\Models\Media as ModelsMedia;
use App\Models\RaciModule;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Glossary extends BaseModel implements HasMedia
{
    use HasFactory;
    use SoftDeletes;
    use InteractsWithMedia, LogsActivity;

    protected $table = 'glossary';
    protected $fillable = [
        'glossary_term',
    ];


    public function definitions()
    {
        return $this->hasMany(GlossaryDefinition::class, 'glossary_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    //Used for Generating Unique ID for each record
    protected function getTypeForPrefix()
    {
        //return $this->type;
        return null;
    }

    protected static function boot()
    {
        parent::boot();

        //Generate Unique ID for each record
        static::creating(function ($model) {
            $model->unique_id = 'RCD-'. self::count() + 1;
            // $model->unique_id = $model->generateUniqueId() ?? 'RCD-'. $model->id;
        });

        static::deleting(function ($post) {
            $post->definitions()->delete();
        });
    }


    public function racis()
    {
        return $this->morphMany(RaciMatrix::class, 'raciable');
    }


    public function links()
    {
        return $this->morphMany(RaciMatrixLinkable::class, 'linkable');
    }

    public function link()
    {
        return $this->morphMany(LinkMatrix::class, 'linkable');
    }

    public function file()
    {
        return $this->morphMany(ModelsMedia::class, 'model');
    }

   
    public function comments()
    {
        return $this->morphMany(CommentMatrix::class, 'commentable');
    }



    public function getMediaPath(Media $media): string
    {
        return "tenants/{$this->id}/media/{$media->id}/";
    }

    /**
     * Automatically log changes to the model
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'is_active','glossary_term'])  // Only log these attributes
            ->logOnlyDirty()  // Only log the changes
            ->useLogName('glossary')  // Set the log name
            ->setDescriptionForEvent(fn (string $eventName) => "Glossary was {$eventName}")
            ->dontSubmitEmptyLogs();  // Avoid empty logs
    }

    public function addMediaToTenantSpecificDirectory($file)
    {
        return $this->addMedia($file)
            ->setFileName($file->getClientOriginalName())
            ->usingFileName($file->getClientOriginalName())  
            ->toMediaCollection('images','local');  
    
    }
}
