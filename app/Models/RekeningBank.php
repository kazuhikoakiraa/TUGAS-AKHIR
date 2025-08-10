<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Helpers\BankHelper;

class RekeningBank extends Model
{
    use HasFactory;

    protected $table = 'rekening_bank';

   protected $fillable = [
        'nama_bank',
        'nomor_rekening',
        'nama_pemilik',
        'keterangan',
        'kode_bank',
   ];

    public function transaksiKeuangan(): HasMany
    {
        return $this->hasMany(TransaksiKeuangan::class, 'id_rekening');
    }
    // Auto set kode bank ketika nama bank diubah
    public function setNamaBankAttribute($value)
    {
        $this->attributes['nama_bank'] = $value;
        $this->attributes['kode_bank'] = BankHelper::getBankCode($value);
    }

    // Accessor untuk mendapatkan nama bank pendek
    public function getShortBankNameAttribute()
    {
        return BankHelper::getShortBankName($this->nama_bank);
    }

    /**
     * Relationship with Invoice
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'id_rekening_bank');
    }
}
