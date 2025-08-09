<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratJalan extends Model
{
    use HasFactory;

    protected $table = 'surat_jalan';

    protected $fillable = [
        'nomor_surat_jalan',
        'id_po_customer',
        'id_user',
        'tanggal',
        'alamat_pengiriman',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    // Relationship
    public function poCustomer(): BelongsTo
    {
        return $this->belongsTo(PoCustomer::class, 'id_po_customer');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Boot method untuk auto-generate nomor surat jalan
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->nomor_surat_jalan)) {
                $model->nomor_surat_jalan = static::generateNomorSuratJalan();
            }
        });
    }

    // Method untuk generate nomor surat jalan
    private static function generateNomorSuratJalan(): string
    {
        $year = date('Y');
        $month = date('m');

        // Ambil nomor terakhir di bulan ini
        $lastRecord = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastRecord) {
            // Extract nomor urut dari nomor surat jalan terakhir
            $lastNumber = (int) substr($lastRecord->nomor_surat_jalan, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return 'SJ/' . $year . '/' . $month . '/' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // Validation rules
    public static function rules($id = null): array
    {
        return [
            'id_po_customer' => [
                'required',
                'exists:po_customer,id',
                'unique:surat_jalan,id_po_customer' . ($id ? ',' . $id : ''),
            ],
            'tanggal' => 'required|date',
            'alamat_pengiriman' => 'required|string|max:500',
        ];
    }

    // Custom validation messages
    public static function messages(): array
    {
        return [
            'id_po_customer.unique' => 'PO Customer ini sudah memiliki surat jalan. Satu PO hanya bisa memiliki satu surat jalan.',
            'id_po_customer.required' => 'PO Customer harus dipilih.',
            'id_po_customer.exists' => 'PO Customer yang dipilih tidak valid.',
            'tanggal.required' => 'Tanggal pengiriman harus diisi.',
            'tanggal.date' => 'Format tanggal tidak valid.',
            'alamat_pengiriman.required' => 'Alamat pengiriman harus diisi.',
            'alamat_pengiriman.max' => 'Alamat pengiriman maksimal 500 karakter.',
        ];
    }
}
