<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception('Lot ID is required.');
    }

    $lot_id = $_POST['id'];

    // First, get the document path to delete the file
    $stmt = $pdo->prepare("SELECT document_path FROM land_lots WHERE id = ?");
    $stmt->execute([$lot_id]);
    $document_path = $stmt->fetchColumn();

    // Delete the lot from database
    $stmt = $pdo->prepare("DELETE FROM land_lots WHERE id = ?");
    $success = $stmt->execute([$lot_id]);

    if ($success) {
        // Delete the associated document file if it exists
        if ($document_path && file_exists($document_path)) {
            unlink($document_path);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Lot deleted successfully!'
        ]);
    } else {
        throw new Exception('Failed to delete lot from database.');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>