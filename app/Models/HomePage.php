<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomePage extends Model
{
    use HasFactory;
    protected $fillable = ['meta_title','meta_description','section_request_title','section_request_subtitle','section_request_text','section_delivery_title','section_delivery_top_text','section_delivery_bottom_text',];
}
