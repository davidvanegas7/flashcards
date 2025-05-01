<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deck extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'user_id',
        'category_id',
        'is_multiple_selection',
        'document_id',
    ];

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function cards(){
        return $this->hasMany(Card::class);
    }

    public function expandedCards(){
        return $this->hasMany(ExpandedCard::class);
    }
}
