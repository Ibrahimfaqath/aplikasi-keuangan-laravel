<?php

namespace App\Exports;

use App\Services\ReportingService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    protected ?int $userId;

    public function __construct(array $filters, ?int $userId = null)
    {
        $this->filters = $filters;
        $this->userId = $userId;
    }

    public function query()
    {
        $reportingService = new ReportingService;

        // userId eksplisit — jangan ngandelin request() di dalam job/queue.
        return $reportingService->getFilteredQuery($this->filters, $this->userId ?? request()->user()?->id)->orderBy('transaction_date', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID Transaksi',
            'Tanggal Transaksi',
            'Keterangan / Judul',
            'Kategori',
            'Jenis Transaksi',
            'Nominal (Rp)',
            'Status Bukti Upload',
        ];
    }

    public function map($transaction): array
    {
        return [
            'TRX-'.str_pad($transaction->id, 5, '0', STR_PAD_LEFT),
            Carbon::parse($transaction->transaction_date)->format('d/m/Y'),
            $this->escapeFormula($transaction->title),
            $this->escapeFormula($transaction->category ?? '-'),
            $transaction->type == 'income' ? 'Pemasukan' : 'Pengeluaran',
            $transaction->amount,
            $transaction->image ? 'Ada (Ter-upload)' : 'Tidak Ada',
        ];
    }

    /**
     * Cegah formula injection Excel: "=CMD|..." -> "'=CMD|...".
     */
    private function escapeFormula(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'], // Indigo 600 Header
                ],
            ],
        ];
    }
}
