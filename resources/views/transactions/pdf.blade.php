<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan - DompetKu</title>
    <style>
        @page { 
            margin: 25px 30px 20px 30px;
        }
        body { 
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            font-size: 10px; 
            color: #1e293b; 
            line-height: 1.6;
            background: #ffffff;
        }
        
        /* Corporate Header */
        .header-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px; 
            border-bottom: 3px solid #4f46e5; 
            padding-bottom: 12px;
        }
        .company-logo { 
            font-size: 18px; 
            font-weight: 800; 
            color: #4f46e5; 
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .company-logo small {
            font-size: 10px;
            font-weight: 400;
            color: #64748b;
            letter-spacing: 0;
            text-transform: none;
        }
        .report-title { 
            text-align: right; 
            font-size: 14px; 
            font-weight: 700; 
            color: #0f172a; 
            text-transform: uppercase;
        }
        .report-meta { 
            text-align: right; 
            font-size: 8px; 
            color: #64748b; 
            margin-top: 2px;
        }

        /* Summary Grid */
        .summary-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px; 
        }
        .summary-card { 
            padding: 8px 10px; 
            border: 1px solid #e2e8f0; 
            background-color: #f8fafc; 
            border-radius: 6px; 
            text-align: center; 
        }
        .summary-label { 
            font-size: 7px; 
            font-weight: 700; 
            text-transform: uppercase; 
            color: #64748b; 
            letter-spacing: 0.5px;
        }
        .summary-value { 
            font-size: 13px; 
            font-weight: 800; 
            margin-top: 2px; 
        }
        .income { color: #059669; }
        .expense { color: #dc2626; }
        .balance { color: #4f46e5; }

        /* Main Data Table */
        .data-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px; 
        }
        .data-table th { 
            background-color: #f1f5f9; 
            color: #475569; 
            font-size: 7.5px; 
            font-weight: 700; 
            text-transform: uppercase; 
            padding: 6px 8px; 
            border-bottom: 2px solid #cbd5e1; 
            text-align: left; 
            letter-spacing: 0.5px;
        }
        .data-table td { 
            padding: 6px 8px; 
            border-bottom: 1px solid #f1f5f9; 
            font-size: 9px; 
        }
        .data-table tr:nth-child(even) td {
            background-color: #fafbfc;
        }
        .data-table .total-row td {
            background-color: #f1f5f9 !important;
            font-weight: 700;
            border-top: 2px solid #cbd5e1;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Status Badge */
        .badge-income {
            color: #059669;
            font-weight: 600;
            background: #ecfdf5;
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 7px;
            text-transform: uppercase;
        }
        .badge-expense {
            color: #dc2626;
            font-weight: 600;
            background: #fef2f2;
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 7px;
            text-transform: uppercase;
        }

        /* Footer */
        .footer { 
            position: fixed; 
            bottom: 0; 
            left: 0; 
            right: 0; 
            font-size: 7px; 
            color: #94a3b8; 
            border-top: 1px solid #e2e8f0; 
            padding-top: 6px; 
            background: #ffffff;
        }
        .page-number:after { 
            content: counter(page); 
        }

        /* Print Watermark */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 80px;
            font-weight: 900;
            color: #f1f5f9;
            opacity: 0.4;
            pointer-events: none;
            z-index: 0;
        }

        .note {
            font-size: 8px;
            color: #94a3b8;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #f1f5f9;
            font-style: italic;
        }
    </style>
</head>
<body>

    <!-- Watermark -->
    <div class="watermark">DompetKu</div>

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td width="50%">
                <div class="company-logo">
                    DompetKu <small>• Manajemen Keuangan</small>
                </div>
                <div style="font-size: 8px; color: #64748b; margin-top: 2px;">
                    Laporan Rekapitulasi Keuangan Resmi
                </div>
            </td>
            <td width="50%" class="report-title">
                LAPORAN TRANSAKSI
                <div class="report-meta">
                    Dicetak: {{ $printedAt ?? \Carbon\Carbon::now()->format('d F Y H:i') }}
                </div>
                <div class="report-meta">
                    Oleh: {{ Auth::user()->name ?? 'System' }} ({{ Auth::user()->email ?? '-' }})
                </div>
            </td>
        </tr>
    </table>

    <!-- Summary Cards -->
    <table class="summary-table">
        <tr>
            <td width="32%">
                <div class="summary-card">
                    <div class="summary-label">💰 Total Pemasukan</div>
                    <div class="summary-value income">Rp {{ number_format($totalIncome ?? 0, 0, ',', '.') }}</div>
                </div>
            </td>
            <td width="2%"></td>
            <td width="32%">
                <div class="summary-card">
                    <div class="summary-label">💸 Total Pengeluaran</div>
                    <div class="summary-value expense">Rp {{ number_format($totalExpense ?? 0, 0, ',', '.') }}</div>
                </div>
            </td>
            <td width="2%"></td>
            <td width="32%">
                <div class="summary-card">
                    <div class="summary-label">📊 Sisa Saldo Bersih</div>
                    <div class="summary-value balance">Rp {{ number_format($totalBalance ?? 0, 0, ',', '.') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="13%">Tanggal</th>
                <th width="32%">Keterangan Transaksi</th>
                <th width="14%">Kategori</th>
                <th width="14%" class="text-center">Jenis</th>
                <th width="22%" class="text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions ?? [] as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($item->transaction_date)->format('d/m/Y') }}</td>
                <td><strong>{{ $item->title }}</strong></td>
                <td>{{ $item->category ?? '-' }}</td>
                <td class="text-center">
                    @if($item->type == 'income')
                        <span class="badge-income">Pemasukan</span>
                    @else
                        <span class="badge-expense">Pengeluaran</span>
                    @endif
                </td>
                <td class="text-right {{ $item->type == 'income' ? 'income' : 'expense' }}">
                    Rp {{ number_format($item->amount, 0, ',', '.') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center" style="padding: 30px; color: #94a3b8; font-size: 11px;">
                    Tidak ada data transaksi yang ditemukan.
                </td>
            </tr>
            @endforelse

            <!-- Total Row -->
            @if(isset($transactions) && $transactions->count() > 0)
            <tr class="total-row">
                <td colspan="5" class="text-right">
                    TOTAL SELURUH TRANSAKSI
                </td>
                <td class="text-right" style="font-weight: 800; font-size: 11px;">
                    Rp {{ number_format($transactions->sum('amount'), 0, ',', '.') }}
                </td>
            </tr>
            @endif
        </tbody>
    </table>

    <!-- Notes / Footer Info -->
    <div class="note">
        <table width="100%">
            <tr>
                <td>
                    • Laporan ini dihasilkan secara otomatis oleh sistem DompetKu.
                </td>
                <td style="text-align: right; font-weight: 600; color: #4f46e5;">
                    Periode: {{ $period ?? 'Semua Waktu' }}
                </td>
            </tr>
            <tr>
                <td>
                    • Data transaksi mencakup seluruh pemasukan dan pengeluaran yang tercatat.
                </td>
                <td style="text-align: right; color: #94a3b8;">
                    Total Transaksi: {{ $transactions->count() ?? 0 }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <table width="100%">
            <tr>
                <td>© {{ date('Y') }} DompetKu. Hak Cipta Dilindungi.</td>
                <td style="text-align: right;">
                    Halaman <span class="page-number"></span> | 
                    Dicetak: {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
                </td>
            </tr>
        </table>
    </div>

</body>
</html>