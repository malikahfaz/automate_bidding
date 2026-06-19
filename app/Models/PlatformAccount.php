<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PlatformAccount extends Model
{
    protected $fillable = [
        'platform',
        'email',
        'encrypted_password',
        'status',
        'last_login_at',
        'last_error',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
    ];

    /**
     * Get the decrypted password.
     */
    public function getDecryptedPassword(): string
    {
        return Crypt::decryptString($this->encrypted_password);
    }

    /**
     * Set the encrypted password.
     */
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['encrypted_password'] = Crypt::encryptString($value);
    }
}
