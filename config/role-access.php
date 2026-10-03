<?php
function docVicCanAccessPortal($portal)
{
    $role = $_SESSION['role'] ?? '';

    if (in_array($role, ['president', 'admin'], true)) {
        return true;
    }

    $portalRoles = [
        'stii' => 'stii_admin',
        'belvic' => 'belvic_admin',
    ];

    return isset($portalRoles[$portal]) && $role === $portalRoles[$portal];
}

function docVicRequirePortalAccess($portal)
{
    if (docVicCanAccessPortal($portal)) {
        return;
    }

    http_response_code(403);

    if (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    exit('You do not have permission to access this portal.');
}
?>