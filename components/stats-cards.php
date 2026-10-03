<div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 card-hover transition duration-300">
  <div class="flex justify-between">
    <div>
      <p class="text-sm font-medium text-gray-500">Total Assets</p>
      <h3 class="text-2xl font-bold mt-1">
        <?php 
        $stmt = $conn->query("SELECT COUNT(*) FROM assets");
        echo $stmt->fetchColumn();
        ?>
      </h3>
    </div>
    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
      <i class="fas fa-boxes text-blue-600"></i>
    </div>
  </div>
</div>

<div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 card-hover transition duration-300">
  <div class="flex justify-between">
    <div>
      <p class="text-sm font-medium text-gray-500">Vehicles</p>
      <h3 class="text-2xl font-bold mt-1">
        <?php 
        $stmt = $conn->query("SELECT COUNT(*) FROM assets WHERE category = 'vehicle'");
        echo $stmt->fetchColumn();
        ?>
      </h3>
    </div>
    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
      <i class="fas fa-truck text-green-600"></i>
    </div>
  </div>
</div>

<!-- Other cards similarly -->