<?php

namespace App\Enums;

enum PenawaranStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';

    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Terkirim',
            self::ACCEPTED => 'Diterima',
            self::REJECTED => 'Ditolak',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::SENT => 'info',
            self::ACCEPTED => 'success',
            self::REJECTED => 'danger',
        };
    }

    public static function getOptions(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->getLabel(), self::cases())
        );
    }
}

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case PAID = 'paid';
    case OVERDUE = 'overdue';

    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Terkirim',
            self::PAID => 'Dibayar',
            self::OVERDUE => 'Jatuh Tempo',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::SENT => 'info',
            self::PAID => 'success',
            self::OVERDUE => 'danger',
        };
    }

    public static function getOptions(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->getLabel(), self::cases())
        );
    }
}

enum TransaksiJenis: string
{
    case PEMASUKAN = 'pemasukan';
    case PENGELUARAN = 'pengeluaran';

    public function getLabel(): string
    {
        return match($this) {
            self::PEMASUKAN => 'Pemasukan',
            self::PENGELUARAN => 'Pengeluaran',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::PEMASUKAN => 'success',
            self::PENGELUARAN => 'danger',
        };
    }

    public static function getOptions(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->getLabel(), self::cases())
        );
    }
}

enum UserRole: string
{
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case STAFF = 'staff';

    public function getLabel(): string
    {
        return match($this) {
            self::ADMIN => 'Administrator',
            self::MANAGER => 'Manager',
            self::STAFF => 'Staff',
        };
    }

    public function getPermissions(): array
    {
        return match($this) {
            self::ADMIN => ['*'],
            self::MANAGER => ['*'],
            self::STAFF => [
                'view_dashboard',
                'create_po',
                'view_po',
                'create_penawaran',
                'view_penawaran',
                'create_surat_jalan',
                'view_surat_jalan',
                'create_invoice',
                'view_invoice',
            ],
        };
    }

    public static function getOptions(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->getLabel(), self::cases())
        );
    }
}
