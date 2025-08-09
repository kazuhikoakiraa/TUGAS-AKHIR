<?php

namespace App\Filament\Resources\SuratJalanResource\Pages;

use App\Filament\Resources\SuratJalanResource;
use App\Models\SuratJalan;
use App\Models\PoCustomer;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateSuratJalan extends CreateRecord
{
    protected static string $resource = SuratJalanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Buat Surat Jalan')
            ->icon('heroicon-o-plus');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Batal');
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            // Validasi tambahan sebelum membuat record
            $this->validatePoCustomer($data['id_po_customer'] ?? null);

            // Set user ID jika belum ada
            if (empty($data['id_user'])) {
                $data['id_user'] = \Illuminate\Support\Facades\Auth::id();
            }

            $record = static::getModel()::create($data);

            // Notifikasi sukses
            Notification::make()
                ->title('Surat Jalan Berhasil Dibuat')
                ->body("Surat jalan dengan nomor {$record->nomor_surat_jalan} telah berhasil dibuat.")
                ->success()
                ->send();

            return $record;

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Membuat Surat Jalan')
                ->body('Terjadi kesalahan saat membuat surat jalan: ' . $e->getMessage())
                ->danger()
                ->send();

            throw new ValidationException(validator([], []), ['error' => 'Gagal membuat surat jalan: ' . $e->getMessage()]);
        }
    }

    /**
     * Validasi PO Customer
     */
    private function validatePoCustomer(?int $poCustomerId): void
    {
        if (!$poCustomerId) {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'PO Customer harus dipilih.']);
        }

        // Cek apakah PO Customer exists dan valid
        $po = PoCustomer::with('customer')->find($poCustomerId);

        if (!$po) {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'PO Customer tidak ditemukan.']);
        }

        // Cek apakah PO sudah memiliki surat jalan
        $existingSuratJalan = SuratJalan::where('id_po_customer', $poCustomerId)->first();
        if ($existingSuratJalan) {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'PO Customer ini sudah memiliki surat jalan dengan nomor: ' . $existingSuratJalan->nomor_surat_jalan]);
        }

        // Cek status PO
        if ($po->status_po !== \App\Enums\PoStatus::APPROVED) {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'PO Customer harus berstatus APPROVED untuk dapat dibuatkan surat jalan.']);
        }

        // Cek jenis PO
        if ($po->jenis_po !== 'Produk') {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'Hanya PO dengan jenis "Produk" yang dapat dibuatkan surat jalan.']);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pastikan user_id terisi
        $data['id_user'] = \Illuminate\Support\Facades\Auth::id();

        // Validasi tanggal
        if (isset($data['tanggal'])) {
            $tanggal = \Carbon\Carbon::parse($data['tanggal']);
            if ($tanggal->isPast() && $tanggal->diffInDays(now()) > 7) {
                throw new ValidationException(validator([], []), ['tanggal' => 'Tanggal pengiriman tidak boleh lebih dari 7 hari yang lalu.']);
            }
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Kembali ke Daftar')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }
}
