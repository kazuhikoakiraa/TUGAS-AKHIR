<?php

namespace App\Helpers;

class BankHelper
{
    /**
     * Daftar lengkap bank di Indonesia
     * Diurutkan berdasarkan kategori BUKU dan alfabetis
     */
    public static function getBankOptions(): array
    {
        return [
            // Bank BUKU 4 (Modal Inti ≥ 30 Triliun)
            'Bank Central Asia' => 'Bank Central Asia',
            'Bank Mandiri' => 'Bank Mandiri',
            'Bank Negara Indonesia' => 'Bank Negara Indonesia',
            'Bank Rakyat Indonesia' => 'Bank Rakyat Indonesia',

            // Bank BUKU 3 (Modal Inti 5-30 Triliun)
            'Bank BTPN' => 'Bank BTPN',
            'Bank CIMB Niaga' => 'Bank CIMB Niaga',
            'Bank Danamon Indonesia' => 'Bank Danamon Indonesia',
            'Bank Maybank Indonesia' => 'Bank Maybank Indonesia',
            'Bank OCBC NISP' => 'Bank OCBC NISP',
            'Bank Panin' => 'Bank Panin',
            'Bank Permata' => 'Bank Permata',
            'Bank Syariah Indonesia' => 'Bank Syariah Indonesia',
            'Bank Tabungan Negara' => 'Bank Tabungan Negara',
            'Bank UOB Indonesia' => 'Bank UOB Indonesia',

            // Bank BUKU 2 (Modal Inti 1-5 Triliun)
            'Bank Bukopin' => 'Bank Bukopin',
            'Bank Commonwealth' => 'Bank Commonwealth',
            'Bank HSBC Indonesia' => 'Bank HSBC Indonesia',
            'Bank Index Selindo' => 'Bank Index Selindo',
            'Bank Mayapada International' => 'Bank Mayapada International',
            'Bank Mega' => 'Bank Mega',
            'Bank Mestika Dharma' => 'Bank Mestika Dharma',
            'Bank MNC Internasional' => 'Bank MNC Internasional',
            'Bank Muamalat Indonesia' => 'Bank Muamalat Indonesia',
            'Bank Neo Commerce' => 'Bank Neo Commerce',
            'Bank QNB Indonesia' => 'Bank QNB Indonesia',
            'Bank Sinarmas' => 'Bank Sinarmas',

            // Bank BUKU 1 (Modal Inti < 1 Triliun)
            'Bank Agroniaga' => 'Bank Agroniaga',
            'Bank Aladin Syariah' => 'Bank Aladin Syariah',
            'Bank Allo Indonesia' => 'Bank Allo Indonesia',
            'Bank Amar Indonesia' => 'Bank Amar Indonesia',
            'Bank Andara' => 'Bank Andara',
            'Bank Artha Graha Internasional' => 'Bank Artha Graha Internasional',
            'Bank Bumi Arta' => 'Bank Bumi Arta',
            'Bank Capital Indonesia' => 'Bank Capital Indonesia',
            'Bank China Construction Bank Indonesia' => 'Bank China Construction Bank Indonesia',
            'Bank CTBC Indonesia' => 'Bank CTBC Indonesia',
            'Bank DBS Indonesia' => 'Bank DBS Indonesia',
            'Bank Ganesha' => 'Bank Ganesha',
            'Bank Harda Internasional' => 'Bank Harda Internasional',
            'Bank Ina Perdana' => 'Bank Ina Perdana',
            'Bank Jago' => 'Bank Jago',
            'Bank J Trust Indonesia' => 'Bank J Trust Indonesia',
            'Bank Mandiri Taspen' => 'Bank Mandiri Taspen',
            'Bank Maspion Indonesia' => 'Bank Maspion Indonesia',
            'Bank Mitraniaga' => 'Bank Mitraniaga',
            'Bank Multiarta Sentosa' => 'Bank Multiarta Sentosa',
            'Bank Nationalnobu' => 'Bank Nationalnobu',
            'Bank Net Indonesia Syariah' => 'Bank Net Indonesia Syariah',
            'Bank Prima Master' => 'Bank Prima Master',
            'Bank Resona Perdania' => 'Bank Resona Perdania',
            'Bank Royal Indonesia' => 'Bank Royal Indonesia',
            'Bank SBI Indonesia' => 'Bank SBI Indonesia',
            'Bank Seabank Indonesia' => 'Bank Seabank Indonesia',
            'Bank Shinhan Indonesia' => 'Bank Shinhan Indonesia',
            'Bank Victoria International' => 'Bank Victoria International',
            'Bank Woori Saudara Indonesia' => 'Bank Woori Saudara Indonesia',
            'Bank Yudha Bhakti' => 'Bank Yudha Bhakti',

            // Bank Pembangunan Daerah (BPD)
            'Bank Aceh' => 'Bank Aceh',
            'Bank Banten' => 'Bank Banten',
            'Bank DKI' => 'Bank DKI',
            'Bank Jambi' => 'Bank Jambi',
            'Bank Jawa Barat dan Banten' => 'Bank Jawa Barat dan Banten',
            'Bank Jawa Tengah' => 'Bank Jawa Tengah',
            'Bank Jawa Timur' => 'Bank Jawa Timur',
            'Bank Kalimantan Barat' => 'Bank Kalimantan Barat',
            'Bank Kalimantan Selatan' => 'Bank Kalimantan Selatan',
            'Bank Kalimantan Tengah' => 'Bank Kalimantan Tengah',
            'Bank Kalimantan Timur dan Utara' => 'Bank Kalimantan Timur dan Utara',
            'Bank Lampung' => 'Bank Lampung',
            'Bank Maluku dan Maluku Utara' => 'Bank Maluku dan Maluku Utara',
            'Bank Nusa Tenggara Barat' => 'Bank Nusa Tenggara Barat',
            'Bank Nusa Tenggara Timur' => 'Bank Nusa Tenggara Timur',
            'Bank Papua' => 'Bank Papua',
            'Bank Riau Kepri' => 'Bank Riau Kepri',
            'Bank Sulawesi Selatan dan Barat' => 'Bank Sulawesi Selatan dan Barat',
            'Bank Sulawesi Tengah' => 'Bank Sulawesi Tengah',
            'Bank Sulawesi Tenggara' => 'Bank Sulawesi Tenggara',
            'Bank Sulawesi Utara dan Gorontalo' => 'Bank Sulawesi Utara dan Gorontalo',
            'Bank Sumatera Barat' => 'Bank Sumatera Barat',
            'Bank Sumatera Selatan dan Bangka Belitung' => 'Bank Sumatera Selatan dan Bangka Belitung',
            'Bank Sumatera Utara' => 'Bank Sumatera Utara',
            'Bank Yogyakarta' => 'Bank Yogyakarta',

            // Bank Campuran (Joint Venture)
            'Bank ANZ Indonesia' => 'Bank ANZ Indonesia',
            'Bank China Construction Bank Indonesia' => 'Bank China Construction Bank Indonesia',
            'Bank Mizuho Indonesia' => 'Bank Mizuho Indonesia',
            'Bank Rabobank International Indonesia' => 'Bank Rabobank International Indonesia',
            'Bank Sumitomo Mitsui Indonesia' => 'Bank Sumitomo Mitsui Indonesia',
            'Bank Tokyo Mitsubishi UFJ Indonesia' => 'Bank Tokyo Mitsubishi UFJ Indonesia',

            // Bank Khusus
            'Bank Ekspor Indonesia' => 'Bank Ekspor Indonesia',
            'Bank Multi Arta Sentosa' => 'Bank Multi Arta Sentosa',
            'Bank Sahabat Sampoerna' => 'Bank Sahabat Sampoerna',
        ];
    }

    /**
     * Mendapatkan daftar bank populer (10 bank terbesar berdasarkan aset)
     */
    public static function getPopularBankOptions(): array
    {
        return [
            'Bank Central Asia' => 'Bank Central Asia',
            'Bank Mandiri' => 'Bank Mandiri',
            'Bank Rakyat Indonesia' => 'Bank Rakyat Indonesia',
            'Bank Negara Indonesia' => 'Bank Negara Indonesia',
            'Bank CIMB Niaga' => 'Bank CIMB Niaga',
            'Bank Danamon Indonesia' => 'Bank Danamon Indonesia',
            'Bank Permata' => 'Bank Permata',
            'Bank OCBC NISP' => 'Bank OCBC NISP',
            'Bank Maybank Indonesia' => 'Bank Maybank Indonesia',
            'Bank Panin' => 'Bank Panin',
        ];
    }

    /**
     * Mendapatkan kode bank berdasarkan nama bank
     */
    public static function getBankCode(string $bankName): ?string
    {
        $bankCodes = [
            // Bank BUKU 4
            'Bank Central Asia' => '014',
            'Bank Mandiri' => '008',
            'Bank Negara Indonesia' => '009',
            'Bank Rakyat Indonesia' => '002',

            // Bank BUKU 3
            'Bank BTPN' => '213',
            'Bank CIMB Niaga' => '022',
            'Bank Danamon Indonesia' => '011',
            'Bank Maybank Indonesia' => '016',
            'Bank OCBC NISP' => '028',
            'Bank Panin' => '019',
            'Bank Permata' => '013',
            'Bank Syariah Indonesia' => '451',
            'Bank Tabungan Negara' => '200',
            'Bank UOB Indonesia' => '023',

            // Bank BUKU 2
            'Bank Bukopin' => '441',
            'Bank Commonwealth' => '950',
            'Bank HSBC Indonesia' => '041',
            'Bank Mayapada International' => '097',
            'Bank Mega' => '426',
            'Bank Mestika Dharma' => '151',
            'Bank MNC Internasional' => '485',
            'Bank Muamalat Indonesia' => '147',
            'Bank QNB Indonesia' => '167',
            'Bank Sinarmas' => '153',

            // Bank BUKU 1
            'Bank Agroniaga' => '494',
            'Bank Allo Indonesia' => '567',
            'Bank Amar Indonesia' => '531',
            'Bank Andara' => '088',
            'Bank Artha Graha Internasional' => '037',
            'Bank Bumi Arta' => '076',
            'Bank Capital Indonesia' => '054',
            'Bank China Construction Bank Indonesia' => '036',
            'Bank CTBC Indonesia' => '949',
            'Bank DBS Indonesia' => '046',
            'Bank Ganesha' => '161',
            'Bank Ina Perdana' => '513',
            'Bank Jago' => '094',
            'Bank J Trust Indonesia' => '095',
            'Bank Mandiri Taspen' => '564',
            'Bank Maspion Indonesia' => '157',
            'Bank Mitraniaga' => '491',
            'Bank Nationalnobu' => '503',
            'Bank Prima Master' => '520',
            'Bank SBI Indonesia' => '498',
            'Bank Seabank Indonesia' => '535',
            'Bank Shinhan Indonesia' => '152',
            'Bank Victoria International' => '566',
            'Bank Woori Saudara Indonesia' => '212',
            'Bank Yudha Bhakti' => '490',

            // Bank Pembangunan Daerah
            'Bank Aceh' => '116',
            'Bank DKI' => '111',
            'Bank Jawa Barat dan Banten' => '110',
            'Bank Jawa Tengah' => '113',
            'Bank Jawa Timur' => '114',
            'Bank Kalimantan Barat' => '123',
            'Bank Kalimantan Selatan' => '122',
            'Bank Kalimantan Tengah' => '125',
            'Bank Kalimantan Timur dan Utara' => '124',
            'Bank Lampung' => '121',
            'Bank Maluku dan Maluku Utara' => '131',
            'Bank Nusa Tenggara Barat' => '128',
            'Bank Nusa Tenggara Timur' => '129',
            'Bank Papua' => '132',
            'Bank Riau Kepri' => '119',
            'Bank Sulawesi Selatan dan Barat' => '126',
            'Bank Sulawesi Tengah' => '130',
            'Bank Sulawesi Utara dan Gorontalo' => '127',
            'Bank Sumatera Barat' => '118',
            'Bank Sumatera Selatan dan Bangka Belitung' => '120',
            'Bank Sumatera Utara' => '117',
            'Bank Yogyakarta' => '112',
        ];

        return $bankCodes[$bankName] ?? null;
    }

    /**
     * Format nama bank untuk tampilan singkat
     */
    public static function getShortBankName(string $bankName): string
    {
        $shortNames = [
            'Bank Central Asia' => 'BCA',
            'Bank Mandiri' => 'Mandiri',
            'Bank Negara Indonesia' => 'BNI',
            'Bank Rakyat Indonesia' => 'BRI',
            'Bank Tabungan Negara' => 'BTN',
            'Bank Syariah Indonesia' => 'BSI',
            'Bank Jawa Barat dan Banten' => 'BJB',
            'Bank Danamon Indonesia' => 'Danamon',
            'Bank CIMB Niaga' => 'CIMB',
            'Bank OCBC NISP' => 'OCBC NISP',
            'Bank Maybank Indonesia' => 'Maybank',
            'Bank UOB Indonesia' => 'UOB',
            'Bank Commonwealth' => 'Commonwealth',
            'Bank Mayapada International' => 'Mayapada',
            'Bank Muamalat Indonesia' => 'Muamalat',
            'Bank QNB Indonesia' => 'QNB',
            'Bank MNC Internasional' => 'MNC',
            'Bank Mestika Dharma' => 'Mestika',
            'Bank Artha Graha Internasional' => 'Artha Graha',
            'Bank China Construction Bank Indonesia' => 'CCB Indonesia',
            'Bank CTBC Indonesia' => 'CTBC',
            'Bank DBS Indonesia' => 'DBS',
            'Bank HSBC Indonesia' => 'HSBC',
            'Bank J Trust Indonesia' => 'J Trust',
            'Bank Mandiri Taspen' => 'Mantap',
            'Bank Maspion Indonesia' => 'Maspion',
            'Bank Victoria International' => 'Victoria',
            'Bank Woori Saudara Indonesia' => 'Woori Saudara',
            'Bank Seabank Indonesia' => 'SeaBank',
            'Bank Shinhan Indonesia' => 'Shinhan',
            'Bank Neo Commerce' => 'Neo Commerce',
            'Bank Allo Indonesia' => 'Allo Bank',
            'Bank Amar Indonesia' => 'Amar Bank',
        ];

        return $shortNames[$bankName] ?? $bankName;
    }

    /**
     * Mendapatkan kategori bank berdasarkan nama
     */
    public static function getBankCategory(string $bankName): string
    {
        $buku4 = ['Bank Central Asia', 'Bank Mandiri', 'Bank Negara Indonesia', 'Bank Rakyat Indonesia'];
        $buku3 = ['Bank BTPN', 'Bank CIMB Niaga', 'Bank Danamon Indonesia', 'Bank Maybank Indonesia',
                  'Bank OCBC NISP', 'Bank Panin', 'Bank Permata', 'Bank Syariah Indonesia',
                  'Bank Tabungan Negara', 'Bank UOB Indonesia'];

        if (in_array($bankName, $buku4)) {
            return 'BUKU 4';
        } elseif (in_array($bankName, $buku3)) {
            return 'BUKU 3';
        } elseif (strpos($bankName, 'Bank Aceh') !== false ||
                  strpos($bankName, 'Bank DKI') !== false ||
                  strpos($bankName, 'Bank Jawa') !== false ||
                  strpos($bankName, 'Bank Kalimantan') !== false ||
                  strpos($bankName, 'Bank Lampung') !== false ||
                  strpos($bankName, 'Bank Maluku') !== false ||
                  strpos($bankName, 'Bank Nusa') !== false ||
                  strpos($bankName, 'Bank Papua') !== false ||
                  strpos($bankName, 'Bank Riau') !== false ||
                  strpos($bankName, 'Bank Sulawesi') !== false ||
                  strpos($bankName, 'Bank Sumatera') !== false ||
                  strpos($bankName, 'Bank Yogyakarta') !== false) {
            return 'BPD';
        } else {
            return 'BUKU 2 & 1';
        }
    }

    /**
     * Cek apakah bank masih aktif beroperasi
     */
    public static function isBankActive(string $bankName): bool
    {
        // Daftar bank yang sudah tidak aktif atau berganti nama
        $inactiveBanks = [
            'Bank Bumiputera',
            'Bank Century',
            'Bank IFI',
            'Bank Lippo',
            'Bank Niaga', // Sudah menjadi CIMB Niaga
            'Bank Mandiri Syariah', // Sudah merger jadi BSI
            'Bank BRI Syariah', // Sudah merger jadi BSI
            'Bank BNI Syariah', // Sudah merger jadi BSI
        ];

        return !in_array($bankName, $inactiveBanks);
    }
}
