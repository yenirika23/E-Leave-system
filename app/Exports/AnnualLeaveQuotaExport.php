<?php

namespace App\Exports;

use App\Models\LeaveQuota;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AnnualLeaveQuotaExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return LeaveQuota::with(['user.department', 'leaveType'])
            ->whereHas('leaveType', fn($q) => $q->where('code', 'CT'))
            ->when(!empty($this->filters['year']), fn($q) =>
                $q->where('year', $this->filters['year'])
            )
            ->when(!empty($this->filters['department_id']), fn($q) =>
                $q->whereHas('user', fn($q) =>
                    $q->where('department_id', $this->filters['department_id'])
                )
            )
            ->orderBy('year')
            ->orderBy('user_id')
            ->get();
    }

    public function title(): string
    {
        return 'Kuota Cuti Tahunan';
    }

    public function headings(): array
    {
        return [
            'No.',
            'NIK',
            'Nama Karyawan',
            'Departemen',
            'Tanggal Bergabung',
            'Periode Mulai',
            'Periode Akhir',
            'Total Kuota',
            'Kuota Terpakai',
            'Sisa Kuota',
            'Carry Over Dari Tahun',
            'Hari Hangus',
            'Status Kuota',
        ];
    }

    public function map($row): array
    {
        static $index = 0;
        $index++;

        $joinDate = $row->user->join_date
            ? $row->user->join_date->format('d/m/Y')
            : '-';

        $periodStart = $row->user->join_date
            ? $row->user->join_date->copy()->setYear($row->year)->format('d/m/Y')
            : '-';

        $periodEnd = $row->user->join_date
            ? $row->user->join_date->copy()->setYear($row->year)->addYear()->subDay()->format('d/m/Y')
            : '-';

        return [
            $index,
            $row->user->nik,
            $row->user->full_name,
            $row->user->department?->name ?? '-',
            $joinDate,
            $periodStart,
            $periodEnd,
            $row->total_quota,
            $row->used_quota,
            $row->remaining_quota,
            $row->carried_over_from_year ? 'Dari ' . $row->carried_over_from_year : '-',
            $row->expired_days ?? 0,
            $row->getStatusLabel(),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = $sheet->getHighestColumn();

        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 10,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1A56DB'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
        ]);

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
        }

        $sheet->freezePane('A2');
        $sheet->getRowDimension(1)->setRowHeight(30);

        return [];
    }
}
