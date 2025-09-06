<?php
// save_order.php
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'eskina';

try {
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $mysqli->set_charset('utf8mb4');

    // Read JSON body
    $raw = file_get_contents("php://input");
    if ($raw === false) throw new Exception("No request body");

    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['cart'], $data['payment'], $data['orderType'], $data['customerName'])) {
        throw new Exception("Invalid request data");
    }

    $cart = $data['cart'];
    $payment = trim((string)$data['payment']);
    $customerName = trim((string)$data['customerName']);

    // Debug log to verify frontend is sending orderType
    file_put_contents(
        "debug.log",
        "[" . date("Y-m-d H:i:s") . "] RAW orderType: " . json_encode($data['orderType']) . PHP_EOL,
        FILE_APPEND
    );

   // Normalize order type
$rawOrderType = strtolower(trim((string)$data['orderType']));
$rawOrderType = str_replace([' ', '-', '_'], '', $rawOrderType); // remove spaces, dashes, underscores

switch ($rawOrderType) {
    case 'dinein':
        $orderType = 'DINE-IN';   // matches DB ENUM
        break;
    case 'takeout':
        $orderType = 'TAKE-OUT';  // matches DB ENUM
        break;
    case 'online':
        $orderType = 'ONLINE';    // matches DB ENUM
        break;
    default:
        $orderType = 'DINE-IN';   // fallback
}


    if (!is_array($cart) || count($cart) === 0) throw new Exception("Cart is empty");

    // Sanitize and calculate total
    $total = 0.0;
    foreach ($cart as $i => $item) {
        $name = isset($item['name']) ? trim((string)$item['name']) : '';
        $qty = isset($item['quantity']) ? (int)$item['quantity'] : 0;
        $priceStr = isset($item['price']) ? (string)$item['price'] : '0';
        $price = (float)preg_replace('/[^\d.]/', '', $priceStr);

        if ($name === '' || $qty <= 0 || $price < 0) throw new Exception("Invalid cart item at index $i");

        $total += ($price * $qty);

        $cart[$i]['_name'] = $name;
        $cart[$i]['_qty'] = $qty;
        $cart[$i]['_price'] = $price;
    }

    // Compute points if provided, else derive
    $points = isset($data['points']) ? (int)$data['points'] : floor($total / 100);

    $mysqli->begin_transaction();

    // Insert order
    $stmt = $mysqli->prepare("
        INSERT INTO orders (payment_method, order_type, customer_name, total_price, points, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("sssdi", $payment, $orderType, $customerName, $total, $points);
    $stmt->execute();
    $orderId = $stmt->insert_id;
    $stmt->close();

    // Insert order items
    $stmt = $mysqli->prepare("
        INSERT INTO order_items (order_id, product_name, price, quantity)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($cart as $item) {
        $stmt->bind_param("isdi", $orderId, $item['_name'], $item['_price'], $item['_qty']);
        $stmt->execute();
    }
    $stmt->close();

    $mysqli->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Order saved successfully!",
        "order_id" => $orderId,
        "total" => $total,
        "orderType" => $orderType,
        "customerName" => $customerName,
        "points" => $points
    ]);

} catch (Throwable $e) {
    if (isset($mysqli) && $mysqli instanceof mysqli) @$mysqli->rollback();
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Server error while saving order.",
        "details" => $e->getMessage()
    ]);
    exit;
}
