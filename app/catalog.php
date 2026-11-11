<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/../config/programs.php';
function catalogPrograms(): array {
    return rows("SELECT p.*,d.level,d.duration,d.method,LPAD(p.sort_order,2,'0') number,
      (SELECT MIN(b.start_date) FROM training_batches b WHERE b.program_slug=p.slug AND b.status='confirmed' AND b.start_date>=CURRENT_DATE()) nearest_date,
      (SELECT MIN(s.price) FROM training_batches b JOIN batch_settings s ON s.batch_id=b.id WHERE b.program_slug=p.slug AND b.status='confirmed' AND b.start_date>=CURRENT_DATE()) price,
      (SELECT COUNT(*) FROM registrations r JOIN training_batches b ON b.id=r.batch_id WHERE b.program_slug=p.slug AND r.status='approved') popularity
      FROM training_programs p LEFT JOIN program_details d ON d.program_slug=p.slug WHERE p.is_active=1 ORDER BY p.sort_order");
}
function availableBatches(?string $slug=null): array {
    return rows("SELECT b.*,p.title,p.category,p.subtitle,s.capacity,s.price,s.registration_deadline,s.method,
      (SELECT COUNT(*) FROM registrations r WHERE r.batch_id=b.id AND r.status IN ('pending','under_review','approved')) reserved
      FROM training_batches b JOIN training_programs p ON p.slug=b.program_slug AND p.is_active=1 LEFT JOIN batch_settings s ON s.batch_id=b.id
      WHERE b.end_date>=CURRENT_DATE() AND b.status IN ('proposed','confirmed')".($slug?' AND b.program_slug=?':'').' ORDER BY b.start_date',$slug?[$slug]:[]);
}
function batchOpen(array $b): bool { return $b['status']==='confirmed' && !empty($b['capacity']) && $b['price']!==null && !empty($b['registration_deadline']) && strtotime($b['registration_deadline'])>=time() && (int)$b['reserved']<(int)$b['capacity'] && $b['start_date']>=date('Y-m-d'); }
