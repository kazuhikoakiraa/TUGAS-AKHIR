<?php

namespace App\Filament\Widgets;

use App\Helpers\KeuanganHelper;
use App\Models\TransaksiKeuangan;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class FinancialTrendWidget extends ChartWidget
{
    protected static ?string $heading = 'Financial Trends (Last 30 Days)';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $trendData = KeuanganHelper::dailyTrends(30);

        return [
            'datasets' => [
                [
                    'label' => 'Income',
                    'data' => array_column($trendData, 'income'),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'Expenses',
                    'data' => array_column($trendData, 'expenses'),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'Profit',
                    'data' => array_column($trendData, 'profit'),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => false,
                    'type' => 'line',
                ],
            ],
            'labels' => array_column($trendData, 'date_formatted'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "Rp " + value.toLocaleString("id-ID"); }'
                    ]
                ]
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top'
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(context) { return context.dataset.label + ": Rp " + context.parsed.y.toLocaleString("id-ID"); }'
                    ]
                ]
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false
            ]
        ];
    }
}

class MonthlyFinancialWidget extends ChartWidget
{
    protected static ?string $heading = 'Monthly Trends (This Year)';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $trendData = KeuanganHelper::monthlyTrends();

        return [
            'datasets' => [
                [
                    'label' => 'Income',
                    'data' => array_column($trendData, 'income'),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                ],
                [
                    'label' => 'Expenses',
                    'data' => array_column($trendData, 'expenses'),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                ],
            ],
            'labels' => array_column($trendData, 'month_name'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "Rp " + value.toLocaleString("id-ID"); }'
                    ]
                ]
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top'
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.dataset.label + ": Rp " + context.parsed.y.toLocaleString("id-ID"); }'
                    ]
                ]
            ]
        ];
    }
}

// Legacy class aliases for backward compatibility
class KeuanganDashboardWidget extends FinancialTrendWidget
{
    protected static ?string $heading = 'Financial Trends (Last 30 Days)';
}

class KeuanganBulananWidget extends MonthlyFinancialWidget
{
    protected static ?string $heading = 'Monthly Trends (This Year)';
}
