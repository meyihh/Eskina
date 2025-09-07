  <?php
require_once 'auth.php'; 

  $DB_HOST = 'localhost';
  $DB_USER = 'root';
  $DB_PASS = '';
  $DB_NAME = 'eskina';

$loggedInStaff = isset($_SESSION['username']) ? $_SESSION['username'] : 'STAFF NAME';

  $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
  if ($mysqli->connect_errno) {
      $products = [];
  } else {
      $mysqli->set_charset('utf8mb4');
      $stmt = $mysqli->prepare("
          SELECT c.slug AS sectionId, p.name, p.meta, p.price, p.best_seller
          FROM products p
          JOIN categories c ON p.category_id = c.id
          ORDER BY p.id
      ");
      $stmt->execute();
      $res = $stmt->get_result();
      $products = [];
      while ($row = $res->fetch_assoc()) {
          // ensure price formatted same way (with currency symbol later in JS)
          $products[] = [
              'sectionId' => $row['sectionId'],
              'name' => $row['name'],
              'meta' => $row['meta'],
              'price' => number_format($row['price'], 2, '.', ','), // e.g., "120.00"
              'bestSeller' => boolval($row['best_seller'])
          ];
      }
      $stmt->close();
  }
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <title>Eskina Coffee | Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="style.css" />
  </head>
  <body>

    <div class="navbar">
      <div class="brand">
        <div class="logo">
          <img src="eslogo.jpg" alt="Eskina Coffee Logo" onerror="this.onerror=null; this.src='fallback.png'"/>
        </div>
        <span>Eskina Coffee</span>
      </div>

      <div class="center-icons">
        <div class="icon-btn active" title="Menu" id="menuBtn">
          <div class="circle">
            <img src="list.png" alt="Menu" class="icon-img" />
          </div>
        </div>

       <a href="serve.php" class="icon-btn" id="cartBtn" title="Manage Items">
      <div class="circle">
        <img src="cart.png" class="icon-img" alt="Cart" />
      </div>
    </a>

        <div class="icon-btn" title="Bag" id="bagBtn">
          <div class="circle">
            <img src="bag.png" alt="Bag" class="icon-img" />
          </div>
        </div>
      </div>

      <div class="nav-links">
        <form action="logout.php" method="POST" style="display:inline;">
      <a href="logout.php" class="logout-btn">Logout</a>
      </form>
      </div>
    </div>  

    <div class="container">

      <aside class="sidebar">
        <div class="section" data-section="drinks">
          <div class="section-header">
            <div>Drinks</div>
            <div>&#9662;</div>
          </div>
          <ul class="section-list expanded" id="drinks-list">
            <li><a data-target="classics-section">Classics</a></li>
            <li><a data-target="specials-section">Specials</a></li>
            <li><a data-target="iceblendedcoffee">Iced Blended Coffee Based</a></li>
            <li><a data-target="iceblendedcream">Iced Blended Cream Based</a></li>
            <li><a data-target="tea">Tea</a></li>
            <li><a data-target="refreshers">Refreshers</a></li>
            <li><a data-target="anticoffee">Anti-Coffee</a></li>
            <li><a data-target="extras">Extras</a></li>
          </ul>
        </div>

        <div class="section" data-section="foods">
          <div class="section-header">
            <div>Foods</div>
            <div>&#9662;</div>
          </div>
          <ul class="section-list" id="foods-list">
            <li><a data-target="ricebowls">Rice Bowls</a></li>
            <li><a data-target="munchies">Munchies</a></li>
            <li><a data-target="pasta">Pasta</a></li>
            <li><a data-target="wraps">Wraps & Sandwiches</a></li>
          </ul>
        </div>
      </aside>  

      <div class="main">
        <div class="top-row">
          <div class="search-box">
            <input id="globalSearch" type="text" placeholder="Search classics..." aria-label="Search classics">
            <button id="searchBtn" aria-label="Search">
              <img src="search.png" alt="Search" class="search-icon" onerror="this.onerror=null; this.src='🔍';" />
            </button>
          </div>


          <!-- Floating Checkout Button -->
        <div class="floating-checkout-btn" title="Checkout">
          <div class="circle">
            <i class="fa-solid fa-cart-shopping icon-img"></i>
            <span class="cart-badge" id="checkoutCount">0</span>
          </div>
        </div>
        </div>

<!-- Order Modal -->
<div id="orderModal" class="order-modal hidden">
  <div class="order-modal-content">
    <span class="close-btn" onclick="closeOrderModal()">&times;</span>
    <h2 class="modal-title">🧾 Order Summary</h2>

    <!-- Items -->
    <div id="orderItems" class="order-items-list"></div>

    <!-- Dining Option -->
    <div class="dining-option">
      <label for="diningType">Order Type:</label>
      <select id="diningType">
        <option value="DINE_IN">Dine In</option>
        <option value="TAKE_OUT">Take Out</option>
        <option value="ONLINE">Online</option>
      </select>
    </div>

    <!-- Customer Name -->
    <div class="customer-name">
      <label for="customerName">Customer Name:</label>
      <input type="text" id="customerName" placeholder="Enter Customer Name">
    </div>

    <!-- Payment Option -->
    <div class="payment-options">
      <label for="paymentMethod">Payment Method:</label>
      <select id="paymentMethod">
        <option value="CASH">Cash</option>
        <option value="GCASH">GCash</option>
      </select>
    </div>

    <!-- Points -->
    <div class="points-option">
      <label for="points">Points:</label>
      <select id="points" disabled>
        <option value="0">None</option>
        <option value="1">1 for ₱250</option>
        <option value="2">2 for ₱500</option>
        <option value="3">3 for ₱750</option>
        <option value="4">4 for ₱1000</option>
        <option value="5">5 for ₱1250</option>
        <option value="6">6 for ₱1500</option>
        <option value="7">7 for ₱1750</option>
        <option value="8">8 for ₱2000</option>
        <option value="9">9 for ₱2250</option>
        <option value="10">10 for ₱2500</option>
        <option value="20">20 for ₱5000</option>
        <option value="40">40 for ₱10000</option>
      </select>
    </div>

    <!-- Total -->
    <div class="total-price">Total: <span id="totalAmount">₱0.00</span></div>

    <!-- Confirm -->
    <button class="confirm-btn" onclick="confirmOrder()">Confirm Order ✅</button>
  </div>
</div>


        <!-- SECTIONS -->
        <div class="section-title" id="classics-section">Classics</div>
        <div class="product-grid" id="classics-grid"></div>

        <div class="section-title" id="specials-section">Specials</div>
        <div class="product-grid" id="specials-grid"></div>

        <div class="section-title" id="iceblendedcoffee">Iced Blended Coffee Based</div>
        <div class="product-grid" id="iceblendedcoffee-grid"></div>

        <div class="section-title" id="iceblendedcream">Iced Blended Cream Based</div>
        <div class="product-grid" id="iceblendedcream-grid"></div>

        <div class="section-title" id="tea">Tea</div>
        <div class="product-grid" id="tea-grid"></div>

        <div class="section-title" id="refreshers">Refreshers</div>
        <div class="product-grid" id="refreshers-grid"></div>

        <div class="section-title" id="anticoffee">Anti-Coffee</div>
        <div class="product-grid" id="anticoffee-grid"></div>

        <div class="section-title" id="extras">Extras</div>
        <div class="product-grid" id="extras-grid"></div>

                <!-- Foods Sections -->
        <div class="section-title" id="ricebowls">Rice Bowls</div>
        <div class="product-grid" id="ricebowls-grid"></div>

        <div class="section-title" id="munchies">Munchies</div>
        <div class="product-grid" id="munchies-grid"></div>

        <div class="section-title" id="pasta">Pasta</div>
        <div class="product-grid" id="pasta-grid"></div>

        <div class="section-title" id="wraps">Wraps & Sandwiches</div>
        <div class="product-grid" id="wraps-grid"></div>
      </div>
    </div>
    

<form method="POST" id="orderForm">
  <input type="hidden" name="cart_data" id="cartData">
  <button type="submit" onclick="saveCart()">Confirm Order</button>
</form>


    <script src="cart.js"></script>
    <script src="sidebar_navigation.js"></script>

    
<script>
  const products = <?= json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>.map(p => ({
    sectionId: p.sectionId,
    name: p.name,
    meta: p.meta || '',
    price: '₱' + parseFloat(p.price.replace(/,/g, '')).toFixed(2),
    bestSeller: p.bestSeller
  }));

  function createProductCard(p) {
    const card = document.createElement('div');
    card.className = 'product-card';

    let selectedSize = 'Grande';
    const metaHTML = '';

    card.innerHTML = `
      <div class="image"></div>
      <div class="info">
        <div class="name" style="font-size:14px; margin-bottom:4px;">${p.name}</div>
        <div class="controls" style="display:flex; flex-wrap:wrap; gap:6px;">
          <select class="size-select" aria-label="Size">
            <option value="Tall">Tall</option>
            <option value="Grande" selected>Grande</option>
            <option value="Venti">Venti</option>
          </select>
          <div class="temp-toggle" aria-label="Temperature">
            <button type="button" class="temp-btn" data-temp="Hot">Hot</button>
            <button type="button" class="temp-btn" data-temp="Iced">Iced</button>
          </div>
        </div>
        ${metaHTML}
      </div>
      <div class="bottom">
        <div class="price">${p.price}</div>
        <button class="add-btn" aria-label="Add to cart">
          <img src="add.png" alt="Add" onerror="this.onerror=null; this.src='+';" />
        </button>
      </div>
    `;

    const sizeSelect = card.querySelector('.size-select');
    sizeSelect.addEventListener('change', () => {
      selectedSize = sizeSelect.value;
    });

    const tempButtons = card.querySelectorAll('.temp-btn');
    function refreshTempUI() {
      tempButtons.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.temp === selectedTemp);
      });
    }
    tempButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        selectedTemp = btn.dataset.temp;
        refreshTempUI();
      });
    });
    refreshTempUI();

    const addBtn = card.querySelector('.add-btn');
    addBtn.addEventListener('click', () => {
      const existingIndex = cart.findIndex(item =>
        item.name === p.name &&
        item.size === selectedSize &&
        item.temp === selectedTemp
      );

      if (existingIndex >= 0) {
        cart[existingIndex].quantity += 1;
      } else {
        cart.push({
          name: p.name,
          price: p.price,
          size: selectedSize,
          temp: selectedTemp,
          quantity: 1
        });
      }

      updateCheckoutBadge(getTotalCartQuantity());
      saveCartToLocalStorage();
    });

    if (p.bestSeller) {
      const badge = document.createElement('img');
      badge.src = 'reward.png';
      badge.alt = 'Best Seller';
      badge.className = 'badge';
      badge.title = 'Best Seller';
      badge.setAttribute('aria-label', 'Best Seller');
      card.appendChild(badge);
    }

    return card;
  }

    const sectionMap = {
      'classics-section': document.getElementById('classics-grid'),
      'specials-section': document.getElementById('specials-grid'),
      'iceblendedcoffee': document.getElementById('iceblendedcoffee-grid'),
      'iceblendedcream': document.getElementById('iceblendedcream-grid'),
      'tea': document.getElementById('tea-grid'),
      'refreshers': document.getElementById('refreshers-grid'),
      'anticoffee': document.getElementById('anticoffee-grid'),
      'extras': document.getElementById('extras-grid'),
      'ricebowls': document.getElementById('ricebowls-grid'),
      'munchies': document.getElementById('munchies-grid'),
      'pasta': document.getElementById('pasta-grid'),
      'wraps': document.getElementById('wraps-grid'),
    };

  products.forEach(p => {
    const container = sectionMap[p.sectionId];
    if (container) container.appendChild(createProductCard(p));
  });

// Header buttons logic (menu/cart/bag)
const menuBtn = document.getElementById('menuBtn');
const cartBtn = document.getElementById('cartBtn');
const bagBtn = document.getElementById('bagBtn');

function createProductCard(p) {
  const card = document.createElement('div');
  card.className = 'product-card';

  let selectedSize = 'Grande';
  let selectedTemp = p.meta.toLowerCase().includes('iced') ? 'Iced' : 'Hot';
  const metaHTML = '';

  // Check if the product is food
  const isFood = ['ricebowls', 'munchies', 'pasta', 'wraps'].includes(p.sectionId);

  card.innerHTML = `
    <div class="image"></div>
    <div class="info">
      <div class="name" style="font-size:14px; margin-bottom:4px;">${p.name}</div>
      ${
        isFood
          ? '' // no size/temp controls for food
          : `<div class="controls" style="display:flex; flex-wrap:wrap; gap:6px;">
               <select class="size-select" aria-label="Size">
                 <option value="Tall">Tall</option>
                 <option value="Grande" selected>Grande</option>
                 <option value="Venti">Venti</option>
               </select>
               <div class="temp-toggle" aria-label="Temperature">
                 <button type="button" class="temp-btn" data-temp="Hot">Hot</button>
                 <button type="button" class="temp-btn" data-temp="Iced">Iced</button>
               </div>
             </div>`
      }
      ${metaHTML}
    </div>
    <div class="bottom">
      <div class="price">${p.price}</div>
      <button class="add-btn" aria-label="Add to cart">
        <img src="add.png" alt="Add" onerror="this.onerror=null; this.src='+';" />
      </button>
    </div>
  `;

  // If not food, attach size/temp event handlers
  if (!isFood) {
    const sizeSelect = card.querySelector('.size-select');
    sizeSelect.addEventListener('change', () => {
      selectedSize = sizeSelect.value;
    });

    const tempButtons = card.querySelectorAll('.temp-btn');
    function refreshTempUI() {
      tempButtons.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.temp === selectedTemp);
      });
    }
    tempButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        selectedTemp = btn.dataset.temp;
        refreshTempUI();
      });
    });
    refreshTempUI();
  }

  const addBtn = card.querySelector('.add-btn');
  addBtn.addEventListener('click', () => {
    const existingIndex = cart.findIndex(item =>
      item.name === p.name &&
      (isFood || item.size === selectedSize) &&
      (isFood || item.temp === selectedTemp)
    );

    if (existingIndex >= 0) {
      cart[existingIndex].quantity += 1;
    } else {
      cart.push({
        name: p.name,
        price: p.price,
        size: isFood ? null : selectedSize,
        temp: isFood ? null : selectedTemp,
        quantity: 1
      });
    }

    updateCheckoutBadge(getTotalCartQuantity());
    saveCartToLocalStorage();

    // 🔹 NEW: show toast message
    showToast(`Added ${p.name}${isFood ? '' : ` (${selectedSize}, ${selectedTemp})`} to cart`);
  });

  if (p.bestSeller) {
    const badge = document.createElement('img');
    badge.src = 'reward.png';
    badge.alt = 'Best Seller';
    badge.className = 'badge';
    badge.title = 'Best Seller';
    badge.setAttribute('aria-label', 'Best Seller');
    card.appendChild(badge);
  }

  return card;
}


function showToast(message) {
  let container = document.getElementById("toast-container");
  if (!container) {
    container = document.createElement("div");
    container.id = "toast-container";
    document.body.appendChild(container);
  }

  const toast = document.createElement("div");
  toast.className = "toast";
  toast.textContent = message;

  container.appendChild(toast);

  // Auto remove after animation
  setTimeout(() => {
    toast.remove();
  }, 3000);
}


// Include cartBtn in the same array
[menuBtn, cartBtn, bagBtn].forEach(btn => {
  btn.addEventListener('click', () => {
    // Remove active class from all three first
    [menuBtn, cartBtn, bagBtn].forEach(b => b.classList.remove('active'));

    // Add active class to the clicked one
    btn.classList.add('active');

    // Decide what happens on each click
    if (btn === menuBtn) {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (btn === cartBtn) {
      document.getElementById('cartSection').scrollIntoView({ behavior: 'smooth' });
    } else if (btn === bagBtn) {
      document.getElementById('bagSection').scrollIntoView({ behavior: 'smooth' });
    }
  });
});


  // Global search
const searchInput = document.getElementById('globalSearch');

searchInput.addEventListener('input', () => {
  const keyword = searchInput.value.trim().toLowerCase();

  document.querySelectorAll('.product-card').forEach(card => {
    const name = card.querySelector('.name')?.textContent.toLowerCase() || '';
    const meta = card.querySelector('.meta')?.textContent.toLowerCase() || '';
    const category = card.querySelector('.category')?.textContent.toLowerCase() || ''; 
    // or: const category = card.dataset.category?.toLowerCase() || '';

    const matches = 
      name.includes(keyword) || 
      meta.includes(keyword) || 
      category.includes(keyword);

    card.style.display = matches ? 'flex' : 'none';
  });
});

searchInput.addEventListener('keydown', e => {
  if (e.key === 'Enter') console.log('Search for:', searchInput.value);
});

document.getElementById('searchBtn').addEventListener('click', () => {
  console.log('Search for:', searchInput.value);
});

  // Checkout modal logic
  document.querySelector('.floating-checkout-btn')?.addEventListener('click', openOrderModal);

let selectedIndexes = [];

// Open modal & render cart
function openOrderModal() {
  const modal = document.getElementById("orderModal");
  const orderItems = document.getElementById("orderItems");
  const totalAmountEl = document.getElementById("totalAmount");
  const pointsSelect = document.getElementById("points");

  orderItems.innerHTML = "";
  selectedIndexes = [];
  let total = 0;

  // Render items
  cart.forEach((item, index) => {
    const price = parseFloat(item.price.replace(/[₱,]/g, "")) || 0;
    const itemTotal = price * item.quantity;
    total += itemTotal;

    const div = document.createElement("div");
    div.className = "order-item";
    div.dataset.index = index;

    div.innerHTML = `
      <div class="item-left">
        <span class="name">${item.name}</span>
        <div class="qty-controls">
          <button class="qty-btn minus">−</button>
          <span class="qty">${item.quantity}</span>
          <button class="qty-btn plus">+</button>
        </div>
      </div>
      <div class="item-right">
        <span class="price">₱${itemTotal.toFixed(2)}</span>
        <button class="remove-x">&times;</button>
      </div>
    `;

    // ➖ Decrease qty
    div.querySelector(".minus").addEventListener("click", (e) => {
      e.stopPropagation();
      if (cart[index].quantity > 1) {
        cart[index].quantity--;
      } else {
        cart.splice(index, 1); // remove if 0
      }
      saveCartToLocalStorage();
      updateCheckoutBadge(getTotalCartQuantity());
      openOrderModal(); // refresh modal
    });

    // ➕ Increase qty
    div.querySelector(".plus").addEventListener("click", (e) => {
      e.stopPropagation();
      cart[index].quantity++;
      saveCartToLocalStorage();
      updateCheckoutBadge(getTotalCartQuantity());
      openOrderModal();
    });

    // ❌ Remove item
    div.querySelector(".remove-x").addEventListener("click", (e) => {
      e.stopPropagation();
      cart.splice(index, 1);
      saveCartToLocalStorage();
      updateCheckoutBadge(getTotalCartQuantity());
      openOrderModal();
    });

    orderItems.appendChild(div);
  });

  // Update total
  totalAmountEl.textContent = `₱${total.toFixed(2)}`;

  // Update points (only ONLINE orders earn)
  const orderType = document.getElementById("diningType").value;
  const points = calculatePoints(Math.round(total), orderType);
  if (pointsSelect) pointsSelect.value = String(points);

  modal.classList.remove("hidden");
}

// Close modal
function closeOrderModal() {
  document.getElementById("orderModal").classList.add("hidden");
}

// Recalculate points on order type change
document.getElementById("diningType").addEventListener("change", () => {
  const total = parseFloat(
    document.getElementById("totalAmount").textContent.replace(/[₱,]/g, "")
  ) || 0;
  const orderType = document.getElementById("diningType").value;
  const pointsSelect = document.getElementById("points");
  const points = calculatePoints(Math.round(total), orderType);
  if (pointsSelect) pointsSelect.value = String(points);
});

// Calculate points (only ONLINE)
function calculatePoints(total, orderType) {
  let points = 0;
  if (orderType === "ONLINE") {
    if (total >= 250 && total < 500) points = 1;
    else if (total >= 500 && total < 750) points = 2;
    else if (total >= 750 && total < 1000) points = 3;
    else if (total >= 1000 && total < 1250) points = 4;
    else if (total >= 1250 && total < 1500) points = 5;
    else if (total >= 1500 && total < 1750) points = 6;
    else if (total >= 1750 && total < 2000) points = 7;
    else if (total >= 2000 && total < 2250) points = 8;
    else if (total >= 2250 && total < 2500) points = 9;
    else if (total >= 2500 && total < 5000) points = 10;
    else if (total >= 5000 && total < 10000) points = 20;
    else if (total >= 10000) points = 40;
  }
  return points;
}

// Confirm order
async function confirmOrder() {
  if (cart.length === 0) {
    Swal.fire("Cart is empty!", "", "warning");
    return;
  }

  const customerName = document.getElementById("customerName").value.trim();
  const paymentMethod = document.getElementById("paymentMethod").value;
  let orderType = document.getElementById("diningType").value;
  const points = document.getElementById("points").value;

  if (!customerName) {
    Swal.fire("Please enter the customer's name.", "", "warning");
    return;
  }

  orderType = orderType.replace("_", "-").toUpperCase();

  const payload = {
    cart,
    payment: paymentMethod,
    orderType: orderType,
    customerName: customerName,
    points: points,
  };

  try {
    const res = await fetch("save_order.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    const data = await res.json();

    if (data.status === "success") {
      // Print receipt immediately
      generateReceipt(
        data.order_id,
        payload.payment,
        cart,
        data.total,
        "<?php echo $loggedInStaff; ?>",
        data.orderType
      );

      Swal.fire({
        title: "Order Saved!",
        text: `Order #${data.order_id} — Total: ₱${parseFloat(
          data.total
        ).toFixed(2)}`,
        icon: "success",
        confirmButtonColor: "#74512D",
      });

      // Reset
      cart = [];
      saveCartToLocalStorage();
      updateCheckoutBadge(0);
      closeOrderModal();
    } else {
      Swal.fire("Error", data.message || "Something went wrong.", "error");
    }
  } catch (err) {
    console.error("Error:", err);
    Swal.fire("Error", "Could not save order.", "error");
  }
}


</script>
</body>
</html>