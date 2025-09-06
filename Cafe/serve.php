<?php
require_once "config.php";
require_once "auth.php";

// Handle AJAX update requests
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_status"])) {
    $ids = json_decode($_POST["ids"], true);
    $status = strtoupper(trim($_POST["status"]));

    if (is_array($ids) && count($ids) > 0) {
        $placeholders = implode(",", array_fill(0, count($ids), "?"));
        $types = str_repeat("i", count($ids));
        $sql = "UPDATE order_items SET status = ? WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $params = array_merge([$status], $ids);
        $stmt->bind_param("s" . $types, ...$params);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "No record updated"]);
        }
    }
    exit;
}

// Get today's orders
$today = date("Y-m-d");
$sql = "SELECT oi.id, oi.order_id, oi.product_name, oi.quantity, oi.price, oi.status, oi.created_at, 
              o.order_type, o.customer_name, o.points
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) = ?
        ORDER BY oi.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $row["order_type"] = strtoupper(trim($row["order_type"]));
    $items[] = $row;
}

// Group items by customer name + order type
$grouped = [];
foreach ($items as $row) {
    $key = $row['order_type'] . '_' . $row['customer_name'];
    if (!isset($grouped[$key])) {
        $grouped[$key] = [
            "order_type" => $row['order_type'],
            "customer_name" => $row['customer_name'],
            "points" => $row['points'],
            "orders" => [],
            "created_at" => $row['created_at'],
        ];
    }
    $grouped[$key]["orders"][] = $row;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Serve</title>
<link rel="stylesheet" href="serve.css" />
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<!-- NAVBAR -->
<div class="navbar">
  <div class="brand">
    <div class="logo">
      <img src="eslogo.jpg" alt="Eskina Coffee Logo" onerror="this.onerror=null; this.src='fallback.png'"/>
    </div>
    <span>Eskina Coffee</span>
  </div>
  <div class="center-icons">
    <a href="main.php" class="icon-btn" title="Menu" id="menuBtn">
      <div class="circle"><img src="list.png" alt="Menu" class="icon-img" /></div>
    </a>
    <a href="serve.php" class="icon-btn active" id="cartBtn">
      <div class="circle"><img src="cart.png" alt="Cart" class="icon-img" /></div>
    </a>
    <div class="icon-btn" title="Bag" id="bagBtn">
      <div class="circle"><img src="bag.png" alt="Bag" class="icon-img" /></div>
    </div>
  </div>
  <div class="nav-links">
    <form action="logout.php" method="POST" style="display:inline;">
      <a href="logout.php" class="logout-btn">Logout</a>
    </form>
  </div>
</div>  

<div class="container">
  <div class="board">

    <!-- DINE-IN -->
    <div class="column dinein" id="dineinList">
      <h2>🍽️ Dine-in</h2>
      <div class="list">
        <?php foreach ($grouped as $group): if ($group['order_type'] === 'DINE-IN'): 
              $overallTotal = 0; ?>
          <div class="card" data-order-id="<?= $group['customer_name'] ?>">
            <div class="card-header">
              <span class="customer-name"><?= htmlspecialchars($group['customer_name']) ?></span>
              <span class="status-label"><?= htmlspecialchars($group['orders'][0]['status']) ?></span>
            </div>

            <div class="orders">
              <?php foreach ($group['orders'] as $order): 
                    $overallTotal += $order['price'] * $order['quantity']; ?>
                <div class="order-row" data-id="<?= $order['id'] ?>">
                  <span class="qty"><?= $order['quantity'] ?>x</span>
                  <span class="item-name"><?= htmlspecialchars($order['product_name']) ?></span>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="card-footer">
              <span class="overall-total">Total: ₱<?= number_format($overallTotal, 2) ?></span>
              <span class="timestamp"><?= date("h:i A", strtotime($group['created_at'])) ?></span>
            </div>
          </div>
        <?php endif; endforeach; ?>
      </div>
      <button class="mark-btn" onclick="setDone()">Set as Done</button>
    </div>

    <!-- TAKE-OUT -->
    <div class="column takeout" id="takeoutList">
      <h2>🥡 Take-out</h2>
      <div class="list">
        <?php foreach ($grouped as $group): if ($group['order_type'] === 'TAKE-OUT'): 
              $overallTotal = 0; ?>
          <div class="card" data-order-id="<?= $group['customer_name'] ?>">
            <div class="card-header">
              <span class="customer-name"><?= htmlspecialchars($group['customer_name']) ?></span>
              <span class="status-label"><?= htmlspecialchars($group['orders'][0]['status']) ?></span>
            </div>

            <div class="orders">
              <?php foreach ($group['orders'] as $order): 
                    $overallTotal += $order['price'] * $order['quantity']; ?>
                <div class="order-row" data-id="<?= $order['id'] ?>">
                  <span class="qty"><?= $order['quantity'] ?>x</span>
                  <span class="item-name"><?= htmlspecialchars($order['product_name']) ?></span>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="card-footer">
              <span class="overall-total">Total: ₱<?= number_format($overallTotal, 2) ?></span>
              <span class="timestamp"><?= date("h:i A", strtotime($group['created_at'])) ?></span>
            </div>
          </div>
        <?php endif; endforeach; ?>
      </div>
      <button class="mark-btn" onclick="setDone()">Set as Done</button>
    </div>

    <!-- ONLINE -->
    <div class="column online" id="onlineList">
      <h2>🟤 Online Orders</h2>
      <div class="list">
        <?php foreach ($grouped as $group): if ($group['order_type'] === 'ONLINE'): 
              $overallTotal = 0; ?>
          <div class="card" data-order-id="<?= $group['customer_name'] ?>">
            <div class="card-header">
              <span class="customer-name"><?= htmlspecialchars($group['customer_name']) ?></span>
              <span class="status-label"><?= htmlspecialchars($group['orders'][0]['status']) ?></span>
            </div>

            <div class="points">Points: <?= htmlspecialchars($group['points']) ?> pts</div>

            <div class="orders">
              <?php foreach ($group['orders'] as $order): 
                    $overallTotal += $order['price'] * $order['quantity']; ?>
                <div class="order-row" data-id="<?= $order['id'] ?>">
                  <span class="qty"><?= $order['quantity'] ?>x</span>
                  <span class="item-name"><?= htmlspecialchars($order['product_name']) ?></span>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="card-footer">
              <span class="overall-total">Total: ₱<?= number_format($overallTotal, 2) ?></span>
              <span class="timestamp"><?= date("h:i A", strtotime($group['created_at'])) ?></span>
            </div>
          </div>
        <?php endif; endforeach; ?>
      </div>
      <button class="mark-btn" onclick="setDone()">Set as Done</button>
    </div>

  </div>
</div>

<script>
let selectedCard = null;

document.querySelectorAll(".card").forEach(card => {
  card.addEventListener("click", () => {
    if (selectedCard) selectedCard.classList.remove("selected");
    selectedCard = card;
    card.classList.add("selected");
  });
});

function setDone() {
  if (!selectedCard) {
    Swal.fire("No order selected", "Please tap a card first.", "warning");
    return;
  }

  const ids = Array.from(selectedCard.querySelectorAll(".order-row")).map(row => row.dataset.id);

  const formData = new FormData();
  formData.append("update_status", "1");
  formData.append("ids", JSON.stringify(ids));
  formData.append("status", "DONE");

  fetch("serve.php", { method: "POST", body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.status === "success") {
        selectedCard.querySelector(".status-label").textContent = "DONE";
        Swal.fire("Updated!", "All orders marked as DONE.", "success");
      } else {
        Swal.fire("Error", data.message, "error");
      }
    })
    .catch(() => Swal.fire("Error", "Failed to update order.", "error"));
}
</script>
</body>
</html>
