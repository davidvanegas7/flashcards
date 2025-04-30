<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Podcast extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'title',
        'transcript',
        'audio_path',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
} 