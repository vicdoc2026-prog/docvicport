<?php include 'bar/header.php';?>
<?php include 'bar/sidebar.php'; ?>




<div class="mt-8 bg-white rounded-xl shadow">
  <div class="border-b border-gray-200">
    <nav class="flex -mb-px">
      <button class="px-6 py-4 border-b-2 border-primary font-medium text-primary">Ordinances</button>
      <button class="px-6 py-4 text-gray-500 hover:text-primary">Resolutions</button>
    </nav>
  </div>
  
  <div class="p-6">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ordinance No.</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proponent</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date Approved</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">2023-045</td>
            <td class="px-6 py-4 text-sm text-gray-500">An Ordinance Establishing...</td>
            <td class="px-6 py-4 text-sm text-gray-500">Hon. Dela Cruz</td>
            <td class="px-6 py-4 text-sm text-gray-500">June 15, 2023</td>
            <td class="px-6 py-4 text-sm text-gray-500">
              <a href="#" class="text-primary hover:text-primary-dark">View PDF</a>
            </td>
          </tr>
          <!-- More rows -->
        </tbody>
      </table>
    </div>
  </div>
</div>


<?php include 'bar/footer.php'; ?>