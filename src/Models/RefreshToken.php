<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    protected $table = 'refresh_tokens';
    
    protected $fillable = [
        'usuario_id', 
        'token', 
        'expires_at'
    ];

    public $timestamps = true; 

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}