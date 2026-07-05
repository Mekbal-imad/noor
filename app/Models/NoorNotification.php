<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoorNotification extends Model
{
    protected $table = 'noor_notifications';

    protected $fillable = [
        'notifiable_id', 'notifiable_type',
        'title', 'body', 'type', 'read_at'
    ];

    public function notifiable()
    {
        return $this->morphTo();
    }
}