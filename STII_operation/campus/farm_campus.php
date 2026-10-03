<?php
$campus = 'farm';
$campusName = 'Farm Campus';
$totalAssets = 3;
$totalBuildings = 1;
$assetCategories = [
    [
        'name' => 'Air Conditioning Units',
        'icon' => 'snowflake',
        'description' => 'Climate control systems in Agri-Farm School facilities',
        'count' => 3,
        'onclick' => "openAssetDetails('farm', 'aircon')"
    ],
    [
        'name' => 'Farm Equipment',
        'icon' => 'tractor',
        'description' => 'Agricultural machinery and farming tools',
        'count' => 0,
        'onclick' => "alert('Farm Equipment category - Coming soon!')"
    ]
];
include 'campus_template.php';
?>