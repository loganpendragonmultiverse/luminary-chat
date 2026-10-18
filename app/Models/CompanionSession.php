<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CompanionSession extends Model
{
    protected $fillable = ['user_id', 'character_id', 'chat_id', 'state'];
    protected function casts(): array { return ['state' => 'array']; }
}
