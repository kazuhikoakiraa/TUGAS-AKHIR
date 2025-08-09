<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
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

            if (empty($model->id_user) && Auth::check()) {
                $model->id_user = Auth::id();
            }
        });
    }

    // Method untuk generate nomor surat jalan
    private static function generateNomorSuratJalan(): string
    {
        $now = Carbon::now();
        $year = $now->format('Y');
        $month = $now->format('m');

        // Ambil nomor terakhir di bulan ini
        $lastRecord = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('nomor_surat_jalan', 'desc')
            ->first();

        $newNumber = 1;
        if ($lastRecord && $lastRecord->nomor_surat_jalan) {
            // Extract nomor urut dari nomor surat jalan terakhir
            $parts = explode('/', $lastRecord->nomor_surat_jalan);
            if (count($parts) === 4) {
                $lastNumber = (int) end($parts);
                $newNumber = $lastNumber + 1;
            }
        }

        return 'SJ/' . $year . '/' . $month . '/' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // Scope untuk filtering
    public function scopeByDateRange($query, $startDate = null, $endDate = null)
    {
        if ($startDate) {
            $query->whereDate('tanggal', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('tanggal', '<=', $endDate);
        }

        return $query;
    }

    // Accessor untuk format tanggal yang mudah dibaca
    public function getFormattedTanggalAttribute(): string
    {
        return $this->tanggal ? $this->tanggal->format('d/m/Y') : '';
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
            'tanggal' => 'required|date|after_or_equal:today',
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
            'tanggal.after_or_equal' => 'Tanggal pengiriman tidak boleh kurang dari hari ini.',
            'alamat_pengiriman.required' => 'Alamat pengiriman harus diisi.',
            'alamat_pengiriman.max' => 'Alamat pengiriman maksimal 500 karakter.',
        ];
    }
}
