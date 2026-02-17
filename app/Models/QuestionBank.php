<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionBank extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'parent_id', 'exam_id'];

    public function parent()
    {
        return $this->belongsTo(QuestionBank::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(QuestionBank::class, 'parent_id');
    }


    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}
