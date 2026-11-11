<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/programs.php';
$featuredPrograms = allPrograms();

$instructors = [];

$articles = [
    ['category' => 'Reliability', 'date' => 'Topik usulan', 'title' => 'Apa itu Reliability Centered Maintenance?', 'image' => 'https://images.unsplash.com/photo-1504328345606-18bbc8c9d7d1?auto=format&fit=crop&w=900&q=85'],
    ['category' => 'Diagnostics', 'date' => 'Topik usulan', 'title' => 'Kapan Vibration Analysis diperlukan?', 'image' => 'https://images.unsplash.com/photo-1581092795360-fd1ca04f0952?auto=format&fit=crop&w=900&q=85'],
    ['category' => 'Diagnostics', 'date' => 'Topik usulan', 'title' => 'Memahami analisis pelumas dan kondisi mesin', 'image' => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=900&q=85'],
];

require __DIR__ . '/../resources/views/pages/home.php';
