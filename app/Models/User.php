<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

final class User extends Model
{
    protected static string $table = 'users';
    protected static array $fillable = [
        'role', 'name', 'email', 'password', 'phone', 'store_name', 'store_description',
        'address_line', 'city', 'province', 'region', 'postal_code',
    ];

    public static function register(array $data): int
    {
        $data['email'] = strtolower($data['email']);
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        return self::create($data);
    }

    public static function hasAddress(array $user): bool
    {
        return !empty($user['address_line']) && !empty($user['city']) && !empty($user['region']) && !empty($user['phone']);
    }

    public static function displayStore(array $user): string
    {
        return $user['store_name'] ?: $user['name'];
    }
}
