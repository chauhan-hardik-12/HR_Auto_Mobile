<?php
require_once "../config/db.php";

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Only GET requests are allowed", "data" => []]);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'types':
            $stmt = $pdo->prepare("SELECT id, name, icon FROM vehicle_types WHERE status = 1 ORDER BY name ASC");
            $stmt->execute();
            echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);
            break;

        case 'brands':
            $typeId = filter_input(INPUT_GET, 'vehicle_type_id', FILTER_VALIDATE_INT);
            if (!$typeId) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Invalid vehicle_type_id", "data" => []]);
                exit;
            }
            $stmt = $pdo->prepare("SELECT id, vehicle_type_id, name FROM brands WHERE vehicle_type_id = :type_id AND status = 1 ORDER BY name ASC");
            $stmt->execute(['type_id' => $typeId]);
            echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);
            break;

        case 'models':
            $brandId = filter_input(INPUT_GET, 'brand_id', FILTER_VALIDATE_INT);
            if (!$brandId) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Invalid brand_id", "data" => []]);
                exit;
            }
            $stmt = $pdo->prepare("SELECT id, brand_id, name FROM models WHERE brand_id = :brand_id AND status = 1 ORDER BY name ASC");
            $stmt->execute(['brand_id' => $brandId]);
            echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);
            break;

        case 'services':
            $modelId = filter_input(INPUT_GET, 'model_id', FILTER_VALIDATE_INT);
            if (!$modelId) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Invalid model_id", "data" => []]);
                exit;
            }
            $stmt = $pdo->prepare("
                SELECT 
                    ms.id AS model_service_id,
                    ms.model_id,
                    s.id AS service_id,
                    s.name AS service_name,
                    s.description,
                    COALESCE(ms.duration, s.duration) AS duration,
                    ms.price,
                    ms.is_recommended,
                    ms.status
                FROM model_services ms
                INNER JOIN services s ON ms.service_id = s.id
                WHERE ms.model_id = :model_id 
                  AND ms.status = 1 
                  AND s.status = 1
                ORDER BY ms.is_recommended DESC, ms.price ASC
            ");
            $stmt->execute(['model_id' => $modelId]);
            echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);
            break;

        default:
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Invalid action", "data" => []]);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error occurred", "data" => []]);
    error_log("catalog-api.php error: " . $e->getMessage());
}