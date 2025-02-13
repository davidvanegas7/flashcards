<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    public function decks()
    {
        return $this->hasMany(Deck::class);
    }

    public function cards()
    {
        return $this->hasManyThrough(Card::class, Deck::class);
    }
}
