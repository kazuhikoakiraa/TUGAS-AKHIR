<?php

namespace App\Filament\Resources\SuratJalanResource\Pages;

use App\Filament\Resources\SuratJalanResource;
use App\Models\SuratJalan;
use App\Models\PoCustomer;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditSuratJalan extends EditRecord
{
    protected static string $resource = SuratJalanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->color('info'),

            Actions\Action::make('print_pdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn (): string => route('surat-jalan.pdf', $this->record))
                ->openUrlInNewTab()
                ->tooltip('Cetak Surat Jalan dalam format PDF'),

            Actions\DeleteAction::make()
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus Surat Jalan')
                ->modalDescription('Apakah Anda yakin ingin menghapus surat jalan ini? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, Hapus')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Surat Jalan Dihapus')
                        ->body('Surat jalan berhasil dihapus.')
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Simpan Perubahan')
            ->icon('heroicon-o-check');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Batal');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            // Validasi tambahan untuk edit
            $this->validatePoCustomerForEdit($data['id_po_customer'] ?? null, $record->id);

            $record->update($data);

            // Notifikasi sukses
            Notification::make()
                ->title('Surat Jalan Berhasil Diperbarui')
                ->body("Surat jalan {$record->nomor_surat_jalan} telah berhasil diperbarui.")
                ->success()
                ->send();

            return $record;

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Memperbarui Surat Jalan')
                ->body('Terjadi kesalahan saat memperbarui surat jalan: ' . $e->getMessage())
                ->danger()
                ->send();

            throw new ValidationException(validator([], []), ['error' => 'Gagal memperbarui surat jalan: ' . $e->getMessage()]);
        }
    }

    /**
     * Validasi PO Customer untuk edit
     */
    private function validatePoCustomerForEdit(?int $poCustomerId, int $currentRecordId): void
    {
        if (!$poCustomerId) {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'PO Customer harus dipilih.']);
        }

        // Cek apakah PO Customer exists dan valid
        $po = PoCustomer::with('customer')->find($poCustomerId);

        if (!$po) {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'PO Customer tidak ditemukan.']);
        }

        // Cek apakah PO sudah memiliki surat jalan lain (selain yang sedang diedit)
        $existingSuratJalan = SuratJalan::where('id_po_customer', $poCustomerId)
                                       ->where('id', '!=', $currentRecordId)
                                       ->first();

        if ($existingSuratJalan) {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'PO Customer ini sudah memiliki surat jalan lain dengan nomor: ' . $existingSuratJalan->nomor_surat_jalan]);
        }

        // Cek status PO
        if ($po->status_po !== \App\Enums\PoStatus::APPROVED) {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'PO Customer harus berstatus APPROVED.']);
        }

        if ($po->jenis_po !== 'Produk') {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'Hanya PO dengan jenis "Produk" yang dapat dibuatkan surat jalan.']);
        }
    }

    // If you need tanggal validation, move it to handleRecordUpdate or another appropriate method.

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }
}