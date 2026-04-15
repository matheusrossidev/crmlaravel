<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Gera URLs pra QuickChart.io — serviço free que transforma config Chart.js
 * em imagens PNG via URL. Usado no PDF de relatórios (DomPDF não executa JS).
 *
 * Privacy: passamos apenas dados AGREGADOS (counts, %, nomes de etapas).
 * Nunca vai PII (nome de lead, email, telefone).
 *
 * Docs: https://quickchart.io/documentation/
 */
class ChartUrlBuilder
{
    private const BASE = 'https://quickchart.io/chart';

    /** Paleta oficial Syncro — mesma do app */
    public const BLUE   = '#0085f3';
    public const GREEN  = '#10b981';
    public const RED    = '#ef4444';
    public const YELLOW = '#f59e0b';
    public const GRAY   = '#6b7280';
    public const PURPLE = '#8b5cf6';
    public const PINK   = '#ec4899';
    public const TEAL   = '#14b8a6';

    public const PALETTE = [
        self::BLUE, self::GREEN, self::YELLOW, self::PURPLE,
        self::PINK, self::TEAL, self::RED, self::GRAY,
    ];

    /**
     * Line chart (serve pra "Leads por dia" full-width no PDF).
     *
     * @param array<int|float> $data
     * @param array<string> $labels
     */
    public static function line(array $data, array $labels, string $title = '', int $w = 600, int $h = 240): string
    {
        $config = [
            'type' => 'line',
            'data' => [
                'labels'   => $labels,
                'datasets' => [[
                    'label'           => $title ?: 'Leads',
                    'data'            => $data,
                    'fill'            => true,
                    'backgroundColor' => 'rgba(0, 133, 243, 0.1)',
                    'borderColor'     => self::BLUE,
                    'borderWidth'     => 2,
                    'tension'         => 0.35,
                    'pointRadius'     => 0,
                    'pointHoverRadius'=> 0,
                ]],
            ],
            'options' => [
                'plugins' => [
                    'legend' => ['display' => false],
                ],
                'scales' => [
                    'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
                    'x' => ['ticks' => ['maxRotation' => 0, 'autoSkip' => true]],
                ],
            ],
        ];

        return self::build($config, $w, $h);
    }

    /**
     * Bar chart (vertical).
     *
     * @param array<int|float> $data
     * @param array<string> $labels
     * @param array<string>|null $colors uma cor por barra; default usa BLUE
     */
    public static function bar(array $data, array $labels, string $title = '', ?array $colors = null, int $w = 600, int $h = 240): string
    {
        $config = [
            'type' => 'bar',
            'data' => [
                'labels'   => $labels,
                'datasets' => [[
                    'label'           => $title ?: 'Total',
                    'data'            => $data,
                    'backgroundColor' => $colors ?? self::BLUE,
                    'borderRadius'    => 6,
                ]],
            ],
            'options' => [
                'plugins' => [
                    'legend' => ['display' => false],
                ],
                'scales' => [
                    'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
                ],
            ],
        ];

        return self::build($config, $w, $h);
    }

    /**
     * Horizontal bar (pra "Funil de conversão" com etapas + %).
     */
    public static function barHorizontal(array $data, array $labels, int $w = 600, int $h = 260): string
    {
        $config = [
            'type' => 'bar',
            'data' => [
                'labels'   => $labels,
                'datasets' => [[
                    'data'            => $data,
                    'backgroundColor' => array_slice(self::PALETTE, 0, count($data)),
                    'borderRadius'    => 6,
                ]],
            ],
            'options' => [
                'indexAxis' => 'y',
                'plugins' => [
                    'legend'     => ['display' => false],
                    'datalabels' => [
                        'color'  => '#fff',
                        'anchor' => 'end',
                        'align'  => 'start',
                        'font'   => ['weight' => 'bold', 'size' => 12],
                    ],
                ],
                'scales' => [
                    'x' => ['beginAtZero' => true],
                ],
            ],
        ];

        return self::build($config, $w, $h);
    }

    /**
     * Doughnut chart (pra "Leads por origem").
     *
     * @param array<int|float> $data
     * @param array<string> $labels
     * @param array<string>|null $colors
     */
    public static function doughnut(array $data, array $labels, ?array $colors = null, int $w = 320, int $h = 320): string
    {
        $config = [
            'type' => 'doughnut',
            'data' => [
                'labels'   => $labels,
                'datasets' => [[
                    'data'            => $data,
                    'backgroundColor' => $colors ?? array_slice(self::PALETTE, 0, count($data)),
                    'borderWidth'     => 2,
                    'borderColor'     => '#fff',
                ]],
            ],
            'options' => [
                'cutout'  => '65%',
                'plugins' => [
                    'legend' => [
                        'position' => 'bottom',
                        'labels'   => ['font' => ['size' => 11], 'boxWidth' => 12, 'padding' => 10],
                    ],
                ],
            ],
        ];

        return self::build($config, $w, $h);
    }

    /**
     * Sparkline mini (pra KPI cards). Line sem axes.
     *
     * @param array<int|float> $data
     */
    public static function sparkline(array $data, string $color = self::BLUE, int $w = 160, int $h = 40): string
    {
        $config = [
            'type' => 'line',
            'data' => [
                'labels'   => array_fill(0, count($data), ''),
                'datasets' => [[
                    'data'            => $data,
                    'fill'            => true,
                    'backgroundColor' => 'rgba(0, 133, 243, 0.15)',
                    'borderColor'     => $color,
                    'borderWidth'     => 2,
                    'tension'         => 0.4,
                    'pointRadius'     => 0,
                ]],
            ],
            'options' => [
                'plugins' => ['legend' => ['display' => false]],
                'scales'  => [
                    'x' => ['display' => false],
                    'y' => ['display' => false],
                ],
            ],
        ];

        return self::build($config, $w, $h);
    }

    /**
     * Monta a URL final.
     */
    private static function build(array $config, int $w, int $h): string
    {
        $params = [
            'c'  => json_encode($config, JSON_UNESCAPED_UNICODE),
            'w'  => $w,
            'h'  => $h,
            'bkg' => 'white',
            'v'  => '4',  // Chart.js v4
        ];

        return self::BASE . '?' . http_build_query($params);
    }
}
