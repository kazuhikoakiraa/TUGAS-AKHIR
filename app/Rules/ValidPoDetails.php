<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPoDetails implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Tambahkan pengecekan null
        if ($value === null || !is_array($value) || empty($value)) {
            $fail('Detail item tidak boleh kosong.');
            return;
        }

        foreach ($value as $index => $detail) {
            // Skip jika detail null atau bukan array
            if ($detail === null || !is_array($detail)) {
                $fail("Detail item ke-" . ($index + 1) . " harus berupa data yang valid.");
                continue;
            }

            // Validasi deskripsi - tambahkan null check
            $deskripsi = $detail['deskripsi'] ?? null;
            if ($deskripsi === null || trim($deskripsi) === '') {
                $fail("Detail item ke-" . ($index + 1) . " harus memiliki deskripsi.");
            }

            // Validasi jumlah - lebih robust
            $jumlah = $detail['jumlah'] ?? null;
            if ($jumlah === null || !is_numeric($jumlah) || (float)$jumlah <= 0) {
                $fail("Detail item ke-" . ($index + 1) . " harus memiliki jumlah yang valid (lebih dari 0).");
            }

            // Validasi harga satuan - lebih robust
            $hargaSatuan = $detail['harga_satuan'] ?? null;
            if ($hargaSatuan === null || !is_numeric($hargaSatuan) || (float)$hargaSatuan <= 0) {
                $fail("Detail item ke-" . ($index + 1) . " harus memiliki harga satuan yang valid (lebih dari 0).");
            }

            // Validasi total hanya jika semua field ada dan valid
            if ($jumlah !== null && $hargaSatuan !== null && is_numeric($jumlah) && is_numeric($hargaSatuan)) {
                $total = $detail['total'] ?? null;

                if ($total !== null && is_numeric($total)) {
                    $expectedTotal = (float)$jumlah * (float)$hargaSatuan;
                    $actualTotal = (float)$total;

                    // Gunakan toleransi yang lebih besar untuk floating point
                    if (abs($actualTotal - $expectedTotal) > 0.1) {
                        $fail("Total untuk detail item ke-" . ($index + 1) . " tidak sesuai dengan perhitungan.");
                    }
                }
            }
        }
    }
}
