<?php

namespace App\Enums;

/**
 * Peran akun. Pengelola Hub bukan peran, melainkan penugasan di tabel hub_managers.
 */
enum UserRole: string
{
    case Warga = 'WARGA';
    case Admin = 'ADMIN';
}
