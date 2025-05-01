<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'file_path',
        'content',
        'file_type',
        'is_cleaned',
        'is_processed',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function podcast()
    {
        return $this->hasOne(Podcast::class);
    }

    public function decks()
    {
        return $this->hasMany(Deck::class, 'document_id', 'id');
    }
} 