<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

function fallbackPrograms(): array
{
    return [
        ['slug' => 'rcm', 'number' => '01', 'title' => 'RCM', 'subtitle' => 'Reliability Centered Maintenance', 'category' => 'Reliability', 'description' => 'Topik pemeliharaan berpusat pada keandalan.', 'image' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=1200&q=85'],
        ['slug' => 'gas-engine-maintenance', 'number' => '02', 'title' => 'Gas Engine Maintenance', 'subtitle' => 'Compressor & Generator', 'category' => 'Equipment', 'description' => 'Pemeliharaan mesin gas, kompresor, dan generator.', 'image' => 'https://images.unsplash.com/photo-1487875961445-47a00398c267?auto=format&fit=crop&w=1200&q=85'],
        ['slug' => 'plant-assessment', 'number' => '03', 'title' => 'Plant Assessment', 'subtitle' => 'Remaining Life / Condition Review', 'category' => 'Reliability', 'description' => 'Tinjauan remaining life dan kondisi aset.', 'image' => 'https://images.unsplash.com/photo-1504328345606-18bbc8c9d7d1?auto=format&fit=crop&w=1200&q=85'],
        ['slug' => 'troubleshooting', 'number' => '04', 'title' => 'Troubleshooting', 'subtitle' => 'Metodologi diagnosa kegagalan', 'category' => 'Diagnostics', 'description' => 'Metodologi untuk mendiagnosa kegagalan.', 'image' => 'assets/images/troubleshooting-training.png'],
        ['slug' => 'turbomachinery', 'number' => '05', 'title' => 'Turbomachinery', 'subtitle' => 'Turbin, kompresor & rotating equipment', 'category' => 'Equipment', 'description' => 'Topik turbin, kompresor, dan peralatan berputar.', 'image' => 'https://images.unsplash.com/photo-1531988042231-d39a9cc12a9a?auto=format&fit=crop&w=1200&q=85'],
        ['slug' => 'vibration-analysis', 'number' => '06', 'title' => 'Vibration Analysis', 'subtitle' => 'Pengukuran & interpretasi getaran', 'category' => 'Diagnostics', 'description' => 'Topik pengukuran dan interpretasi getaran.', 'image' => 'https://images.unsplash.com/photo-1504917595217-d4dc5ebe6122?auto=format&fit=crop&w=1200&q=85'],
        ['slug' => 'lube-oil-analysis', 'number' => '07', 'title' => 'Lube Oil Analysis', 'subtitle' => 'Analisis pelumas dan kondisi mesin', 'category' => 'Diagnostics', 'description' => 'Topik analisis pelumas dan kondisi mesin.', 'image' => 'assets/images/lube-oil-analysis.png'],
        ['slug' => 'guest-lecture', 'number' => '08', 'title' => 'Guest Lecture', 'subtitle' => 'Sharing teknis untuk kampus', 'category' => 'Academic', 'description' => 'Sesi berbagi teknis untuk kampus atau asosiasi.', 'image' => 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=1200&q=85'],
    ];
}

function allPrograms(): array
{
    try {
        $query = database()->query("SELECT slug, LPAD(sort_order, 2, '0') AS number, title, subtitle, category, description, image FROM training_programs WHERE is_active = 1 ORDER BY sort_order, title");
        $programs = $query->fetchAll();
        return $programs;
    } catch (PDOException $error) {
        error_log('MIGAS program catalog database fallback: ' . $error->getMessage());
    }

    return fallbackPrograms();
}

function findProgram(string $slug): ?array
{
    try {
        $query = database()->prepare("SELECT slug, LPAD(sort_order, 2, '0') AS number, title, subtitle, category, description, image FROM training_programs WHERE slug = :slug AND is_active = 1 LIMIT 1");
        $query->execute(['slug' => $slug]);
        $program = $query->fetch();
        if (is_array($program)) {
            return $program;
        }
        return null;
    } catch (PDOException $error) {
        error_log('MIGAS program detail database fallback: ' . $error->getMessage());
    }

    foreach (fallbackPrograms() as $program) {
        if ($program['slug'] === $slug) {
            return $program;
        }
    }
    return null;
}
