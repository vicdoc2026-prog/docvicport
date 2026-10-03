<?php
// includes/sidebar.php
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="hidden lg:flex lg:flex-col lg:w-64 bg-white border-r border-gray-200 h-screen sticky top-0 overflow-y-auto">
  <div class="p-5 border-b">
    <div class="text-sm font-bold text-gray-900">Career Guidance</div>
    <div class="text-xs text-gray-500">Forecasting Report</div>
  </div>

  <nav class="p-3 space-y-1 text-sm">
    <a href="career_guidance.php"
       class="flex items-center gap-2 px-3 py-2 rounded-lg <?php echo ($current === 'career_guidance.php') ? 'bg-purple-50 text-purple-800 font-semibold' : 'text-gray-700 hover:bg-gray-50'; ?>">
      <i class="fas fa-table"></i>
      <span>Enrollees</span>
    </a>

    <a href="career_guidance_reports.php"
       class="flex items-center gap-2 px-3 py-2 rounded-lg <?php echo ($current === 'career_guidance_reports.php') ? 'bg-purple-50 text-purple-800 font-semibold' : 'text-gray-700 hover:bg-gray-50'; ?>">
      <i class="fas fa-chart-line"></i>
      <span>Reports</span>
    </a>

    <div class="pt-3 mt-3 border-t">
      <a href="../portal.php"
         class="flex items-center gap-2 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-50">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Portal</span>
      </a>
    </div>
  </nav>
</aside>
