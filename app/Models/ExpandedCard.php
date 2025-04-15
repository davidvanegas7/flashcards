<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpandedCard extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'question',
        'option1',
        'option2',
        'option3',
        'option4',
        'answer',
        'explanation',
        'deck_id',
        'user_id',
    ];

    public function deck()
    {
        return $this->belongsTo(Deck::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
