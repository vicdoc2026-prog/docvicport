<?php
//insert_animal_type.php
include '../config/conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['animal_name'])) {
  $animal_name = trim($_POST['animal_name']);

  if ($animal_name === '') {
    echo json_encode(['status' => 'empty']);
    exit;
  }

  // Check if animal already exists
  $check = $conn->prepare("SELECT animal_type_id FROM farm_animal_type WHERE LOWER(animal_name) = LOWER(?)");
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
?>
