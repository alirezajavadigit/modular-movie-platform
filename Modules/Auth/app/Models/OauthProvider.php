<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthProvider extends Model
{
    protected $table = 'oauth_providers';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
