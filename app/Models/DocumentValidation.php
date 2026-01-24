<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentValidation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document_type',
        'image_path',
        'ocr_response',
        'validation_status',
        'extracted_data',
        'error_message'
    ];

    protected $casts = [
        'ocr_response' => 'json',
        'extracted_data' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
