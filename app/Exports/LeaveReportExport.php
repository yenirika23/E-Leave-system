<?php
// app/Exports/LeaveReportExport.php

namespace App\Exports;

use App\Models\LeaveRequest;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class LeaveReportExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    ShouldAutoSize
{
    protected array $filters;

    // Terima filter dari request
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    // Query data sesuai filter
    public function query()
    {
        $q = LeaveRequest::with(['user.department', 'leaveType', 'approver'])->latest();

        if (!empty($this->filters['status']))        $q->where('status', $this->filters['status']);
        if (!empty($this->filters['department_id'])) $q->whereHas('user', fn($u) => $u->where('department_id', $this->filters['department_id']));
        if (!empty($this->filters['year']))          $q->whereYear('request_date', $this->filters['year']);
        if (!empty($this->filters['month']))         $q->whereMonth('request_date', $this->filters['month']);
        if (!empty($this->filters['leave_type_id'])) $q->where('leave_type_id', $this->filters['leave_type_id']);

        return $q;
    }

    // Nama sheet Excel
    public function title(): string
    {
        return 'Laporan Cuti';
    }

    // Header kolom
    public function headings(): array
    {
        return [
            'No.',
            'NIK',
            'Nama Karyawan',
            'Departemen',
            'Jabatan',
            'Jenis Cuti',
            'Tgl. Pengajuan',
            'Tgl. Mulai',
            'Tgl. Selesai',
            'Total Hari',
            'Tipe Hari',
            'Alasan',
            'Status',
            'Diproses Oleh',
            'Tgl. Diproses',
            'Alasan Penolakan',
        ];
    }

    // Mapping data ke baris Excel
    public function map($row): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $row->user->nik,
            $row->user->full_name,
            $row->user->department?->name ?? '-',
            $row->user->position ?? '-',
            $row->leaveType->name,
            $row->request_date->format('d/m/Y'),
            $row->start_date->format('d/m/Y'),
            $row->end_date->format('d/m/Y'),
            $row->getTotalLabel(),
            $row->getDayTypeLabel(),
            $row->reason,
            ucfirst($row->status),
            $row->approver?->full_name ?? '-',
            $row->approved_at?->format('d/m/Y H:i') ?? '-',
            $row->rejection_reason ?? '-',
        ];
    }

    // Style Excel
    public function styles(Worksheet $sheet): void
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = $sheet->getHighestColumn();

        // ─── Header Row (baris 1) ───
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 10,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1A56DB'], // Biru
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
        ]);

        // ─── Data rows ───
        if ($lastRow > 1) {
            $sheet->getStyle('A2:' . $lastCol . $lastRow)->applyFromArray([
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'E2E8F0'],
                    ],
                ],
            ]);

            // Zebra stripe (baris genap sedikit berwarna)
            for ($i = 2; $i <= $lastRow; $i++) {
                if ($i % 2 === 0) {
                    $sheet->getStyle('A' . $i . ':' . $lastCol . $i)->applyFromArray([
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8FAFF'],
                        ],
                    ]);
                }
            }

            // Warna kolom Status (kolom M = ke-13)
            for ($i = 2; $i <= $lastRow; $i++) {
                $status = $sheet->getCell('M' . $i)->getValue();
                $color  = match(strtolower($status)) {
                    'disetujui'  => ['bg' => 'DCFCE7', 'fg' => '15803D'],
                    'ditolak'    => ['bg' => 'FEE2E2', 'fg' => 'B91C1C'],
                    'menunggu'   => ['bg' => 'FEF9C3', 'fg' => '854D0E'],
                    'dibatalkan' => ['bg' => 'F1F5F9', 'fg' => '475569'],
                    default      => ['bg' => 'FFFFFF', 'fg' => '000000'],
                };
                $sheet->getStyle('M' . $i)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color['bg']]],
                    'font' => ['bold' => true, 'color' => ['rgb' => $color['fg']]],
                ]);
            }
        }

        // Freeze baris header agar tidak ikut scroll
        $sheet->freezePane('A2');

        // Tinggi header row
        $sheet->getRowDimension(1)->setRowHeight(30);
    }
}