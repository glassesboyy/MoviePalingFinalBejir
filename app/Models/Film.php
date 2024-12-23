<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Film extends Model
{
    use HasFactory;
    protected $table = 'films';

    protected $fillable = [
        'poster',
        'judul',
        'deskripsi',
        'genre',
        'tanggalRilis',
        'duration',
        'status',
    ];

    protected $hidden = ['remember_token']; // Hapus 'poster' dari hidden

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
    
}
