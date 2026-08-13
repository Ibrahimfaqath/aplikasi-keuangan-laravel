<?php

namespace App\Exports;

use App\Services\ReportingService;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class TransactionsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $reportingService = new ReportingService();
        return $reportingService->getFilteredQuery($this->filters)->orderBy('transaction_date', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID Transaksi',
            'Tanggal Transaksi',
            'Keterangan / Judul',
            'Jenis Transaksi',
            'Nominal (Rp)',
            'Status Bukti Upload'
        ];
    }

    public function map($transaction): array
    {
        return [
            'TRX-' . str_pad($transaction->id, 5, '0', STR_PAD_LEFT),
            Carbon::parse($transaction->transaction_date)->format('d/m/Y'),
            $transaction->title,
            $transaction->type == 'income' ? 'Pemasukan' : 'Pengeluaran',
            $transaction->amount,
            $transaction->image ? 'Ada (Ter-upload)' : 'Tidak Ada'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'] // Indigo 600 Header
                ]
            ],
        ];
    }
}