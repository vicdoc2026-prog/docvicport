<?php
//fetch_animal_types.php
require_once '../config/conn.php';

header('Content-Type: application/json');

// Fetch all animal types from farm_animal_type table
$sql = "SELECT animal_name FROM farm_animal_type ORDER BY animal_name ASC";
$result = $conn->query($sql);

$types = [];
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $types[] = $row['animal_name'];
  }
}

// Return as JSON
echo json_encode($types);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['animal_name'])) {
  $animal_name = trim($_POST['animal_name']);

  if ($animal_name === '') {
    echo json_encode(['status' => 'empty']);
    exit;
  }

  // Check if animal already exists
  $check = $conn->prepare("SELECT id FROM farm_animal_type WHERE LOWER(animal_name) = LOWER(?)");
  $check->bind_param("s", $animal_name);
  $check->execute();
  $check->store_result();

  if ($check->num_rows > 0) {
    echo json_encode(['status' => 'exists']);
    exit;
  }

  // Insert new animal
  $insert = $conn->prepare("INSERT INTO farm_animal_type (animal_name) VALUES (?)");
  $insert->bind_param("s", $animal_name);

  if ($insert->execute()) {
    echo json_encode(['status' => 'success']);
  } else {
    echo json_encode(['status' => 'error']);
  }
}
$conn->close();
?>
