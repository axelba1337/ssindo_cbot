<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeHour extends Model
{
    protected $fillable = ['open_time','close_time','timezone','note'];
}