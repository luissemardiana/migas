<?php
declare(strict_types=1);

function reliabilityCategories(): array
{
    return [
        'services' => ['title' => 'Reliability Services', 'eyebrow' => 'ANALISIS & STRATEGI ASET', 'headline' => 'Kenali kondisi aset. Tentukan langkah berikutnya.', 'description' => 'Mulai dari gejala di lapangan hingga prioritas pemeliharaan. Telusuri ruang lingkup analisis yang sesuai dengan tantangan operasi Anda.', 'image' => 'engineering', 'groups' => ['asset-wellness' => 'Asset Wellness Analysis', 'life-cycle' => 'Life Cycle Strategy', 'field-engineering' => 'Field Engineering and Analysis']],
        'technology' => ['title' => 'Reliability Technology', 'eyebrow' => 'PEMANTAUAN & TEKNOLOGI', 'headline' => 'Data yang tepat untuk peralatan yang berbeda.', 'description' => 'Setiap aset memiliki kondisi operasi dan kebutuhan informasi sendiri. Temukan pendekatan pemantauan untuk motor, pompa, turbin, dan peralatan pendukung fasilitas.', 'image' => 'plant', 'groups' => ['rotating' => 'Rotating Equipment', 'electrical' => 'Electrical Assets', 'installation' => 'Machinery Installation']],
        'competency' => ['title' => 'Reliability Competency', 'eyebrow' => 'PENGEMBANGAN KOMPETENSI', 'headline' => 'Pengetahuan teknis yang terhubung dengan pekerjaan.', 'description' => 'Rancang pembelajaran berdasarkan pengalaman peserta, alat yang digunakan, serta persoalan yang mereka hadapi dalam kegiatan pemeliharaan.', 'image' => 'learning', 'groups' => ['management' => 'Reliability Management', 'condition' => 'Condition Management']],
    ];
}

function reliabilityItems(): array
{
    static $items;
    if ($items === null) {
        $rows = json_decode(file_get_contents(__DIR__.'/../config/reliability-services.json'), true, 512, JSON_THROW_ON_ERROR);
        $items = [];
        foreach ($rows as $row) $items[$row['slug']] = $row;
    }
    return $items;
}

function reliabilityImage(string $key): string
{
    $known = in_array($key, ['engineering','plant','oil','learning'], true) || isset(reliabilityItems()[$key]);
    return 'assets/images/services/'.($known ? $key : 'engineering').'.jpg';
}

function reliabilityPhotoCredit(string $key): ?array
{
    static $credits;
    if ($credits === null) $credits = json_decode(file_get_contents(__DIR__.'/../config/reliability-photos.json'), true, 512, JSON_THROW_ON_ERROR);
    return $credits[$key] ?? null;
}

function reliabilityUrl(array $item): string { return 'service.php?slug='.rawurlencode($item['slug']); }
function reliabilityInquiry(array $item): string
{
    return 'https://wa.me/6285600604388?text='.rawurlencode('Halo tim MIGAS Training, saya ingin berdiskusi mengenai '.$item['title'].'. Mohon informasi ruang lingkup, kebutuhan data, dan ketersediaannya.');
}
