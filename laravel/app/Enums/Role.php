<?php

namespace App\Enums;

enum Role: string
{
    case CustomerService = 'customer_service';
    case Manajemen = 'manajemen';
    case Supervisor = 'supervisor';

    /** Label yang tampil di UI (sama dengan dropdown "Pilih Peran" di prototype). */
    public function label(): string
    {
        return match ($this) {
            self::CustomerService => 'Customer Service',
            self::Manajemen => 'Manajemen',
            self::Supervisor => 'Supervisor',
        };
    }
}
