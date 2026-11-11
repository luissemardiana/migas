document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('#programs > div:first-child > a').forEach((link) => { link.href = 'programs.php'; });
  document.querySelectorAll('#insights > div > div:first-child + a').forEach((link) => { link.href = 'insights.php'; });
  document.querySelectorAll('#inhouse a').forEach((link) => { link.href = 'in-house.php'; });

  // Hero CTA destinations are defined by the homepage template.
});
