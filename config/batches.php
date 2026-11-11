<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

function fallbackTrainingBatches(): array
{
    return [
        ['program_slug' => 'rcm', 'title' => 'RCM', 'subtitle' => 'Reliability Centered Maintenance', 'category' => 'Reliability', 'start_date' => '2026-12-15', 'end_date' => '2026-12-16', 'delivery_format' => 'Daring (kelas langsung)', 'location' => null, 'status' => 'proposed'],
        ['program_slug' => 'vibration-analysis', 'title' => 'Vibration Analysis', 'subtitle' => 'Pengukuran & interpretasi getaran', 'category' => 'Diagnostics', 'start_date' => '2027-01-19', 'end_date' => '2027-01-21', 'delivery_format' => 'Daring (kelas langsung)', 'location' => null, 'status' => 'proposed'],
        ['program_slug' => 'gas-engine-maintenance', 'title' => 'Gas Engine Maintenance', 'subtitle' => 'Compressor & Generator', 'category' => 'Equipment', 'start_date' => '2027-02-16', 'end_date' => '2027-02-17', 'delivery_format' => 'Daring (kelas langsung)', 'location' => null, 'status' => 'proposed'],
    ];
}

function publicTrainingBatches(): array
{
    try {
        $query = database()->query(
            "SELECT b.program_slug, p.title, p.subtitle, p.category, b.start_date, b.end_date,
                    b.delivery_format, b.location, b.status
             FROM training_batches b
             INNER JOIN training_programs p ON p.slug = b.program_slug
             WHERE p.is_active = 1
               AND b.end_date >= CURRENT_DATE()
               AND b.status IN ('proposed', 'confirmed')
             ORDER BY b.start_date, p.sort_order"
        );
        return $query->fetchAll();
    } catch (PDOException $error) {
        error_log('MIGAS schedule database fallback: ' . $error->getMessage());
        return fallbackTrainingBatches();
    }
}

function indonesianDateRange(string $startDate, string $endDate): string
{
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];
    $start = new DateTimeImmutable($startDate);
    $end = new DateTimeImmutable($endDate);
    $startMonth = $months[(int) $start->format('n')];
    $endMonth = $months[(int) $end->format('n')];

    if ($start->format('Y-m') === $end->format('Y-m')) {
        return $start->format('j') . '–' . $end->format('j') . ' ' . $startMonth . ' ' . $start->format('Y');
    }
    return $start->format('j') . ' ' . $startMonth . ' – ' . $end->format('j') . ' ' . $endMonth . ' ' . $end->format('Y');
}
