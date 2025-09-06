let cart = [];

// 🛒 Get total cart items
function getTotalCartQuantity() {
  return cart.reduce((total, item) => total + item.quantity, 0);
}

// 💾 Save cart to localStorage
function saveCartToLocalStorage() {
  localStorage.setItem("eskinaCart", JSON.stringify(cart));
}

// 📂 Load cart from localStorage
function loadCartFromLocalStorage() {
  const stored = localStorage.getItem("eskinaCart");
  if (stored) {
    try {
      cart = JSON.parse(stored);
    } catch (e) {
      cart = [];
      localStorage.removeItem("eskinaCart");
    }
  }
  updateCheckoutBadge(getTotalCartQuantity());
}

// 🔔 Update checkout badge
function updateCheckoutBadge(count) {
  const badge = document.getElementById("checkoutCount");
  if (badge) {
    badge.textContent = count;
    badge.style.display = count > 0 ? "inline-block" : "none";
  }
}

document.addEventListener("DOMContentLoaded", () => {
  loadCartFromLocalStorage();
});

// ✅ Confirm order and send to backend
function confirmOrder() {
  const payment = document.getElementById("paymentMethod").value;
  const orderTypeSelect = document.getElementById("diningType");

  // Normalize orderType values
  let orderType = "TAKE OUT";
  if (orderTypeSelect) {
    const rawValue = orderTypeSelect.value.trim().toUpperCase();
    if (rawValue === "DINE IN") orderType = "DINE IN";
    else if (rawValue === "TAKE OUT") orderType = "TAKE OUT";
    else if (rawValue === "ONLINE") orderType = "ONLINE";
  }

  if (!cart || cart.length === 0) {
    Swal.fire({
      icon: "warning",
      title: "Oops!",
      text: "Your cart is empty!",
      confirmButtonColor: "#74512D",
      background: "#FFF8F0",
      color: "#333",
      customClass: {
        popup: 'swal-custom-popup',
        title: 'swal-custom-title',
        confirmButton: 'swal-custom-button'
      }
    });
    return;
  }

  fetch("save_order.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ cart: cart, payment: payment, orderType: orderType }),
  })
    .then(async (res) => {
      const text = await res.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        console.error("Non-JSON response from server:", text);
        throw new Error("Invalid server response");
      }
      if (!res.ok || !data || data.status !== "success") {
        const msg = data && data.message ? data.message : "Unknown error";
        throw new Error(msg + (data.details ? ` (${data.details})` : ""));
      }
      return data;
    })
    .then((data) => {
      Swal.fire({
        icon: "success",
        title: "Order Saved!",
        html: "<b>Order #" + data.order_id + "</b><br>Total: ₱" + data.total.toFixed(2),
        confirmButtonColor: "#74512D",
        background: "#FFF8F0",
        color: "#333",
        customClass: {
          popup: 'swal-custom-popup',
          title: 'swal-custom-title',
          confirmButton: 'swal-custom-button'
        }
      });

      // 🧾 Generate and print receipt
      generateReceipt(data.order_id, payment, cart, data.total, "STAFF NAME", orderType);

      // 🧹 Clear cart after order is saved
      cart = [];
      saveCartToLocalStorage();
      updateCheckoutBadge(0);

      const itemsEl = document.getElementById("orderItems");
      if (itemsEl) itemsEl.innerHTML = "";
      const totalEl = document.getElementById("totalAmount");
      if (totalEl) totalEl.textContent = "₱0.00";

      closeOrderModal && closeOrderModal();
    })
    .catch((err) => {
      console.error("Save order failed:", err);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: String(err.message || err),
        confirmButtonColor: "#74512D",
        background: "#FFF8F0",
        color: "#333",
        customClass: {
          popup: 'swal-custom-popup',
          title: 'swal-custom-title',
          confirmButton: 'swal-custom-button'
        }
      });
    });
}


// 🧾 Receipt Generator
const loggedInStaff = "<?php echo $loggedInStaff; ?>";

function generateReceipt(orderId, payment, cart, total, staff = loggedInStaff, orderType = "TAKE OUT") {
  let receiptWindow = window.open("", "PRINT", "height=600,width=400");

  let date = new Date();
  let formattedDate = date.toLocaleString();

  // Generate receipt number: DDMMYYYY + 8 random digits
  let day = String(date.getDate()).padStart(2, '0');
  let month = String(date.getMonth() + 1).padStart(2, '0');
  let year = date.getFullYear();
  let randomDigits = String(Math.floor(10000000 + Math.random() * 90000000));
  let receiptNo = `${day}${month}${year}${randomDigits}`;

  total = parseFloat(total) || 0;

  let receiptHTML = `
    <html>
      <head>
        <title>Receipt #${orderId}</title>
        <style>
          @page { size: 80mm auto; margin: 5mm; }
          body { font-family: 'Courier New', monospace; font-size: 12px; margin:0; padding:0; width:80mm; }
          .receipt { padding:5px; }
          .center { text-align:center; margin:2px 0; }
          .line { border-top:1px dashed #000; margin:2px 0; }
          .info { display:flex; justify-content:space-between; margin:2px 0; }
          table { width:100%; border-collapse:collapse; margin-top:3px; }
          th, td { padding:1px 0; }
          th { border-bottom:1px dashed #000; }
          tfoot td { border-top:1px dashed #000; font-weight:bold; padding-top:3px; }
          .right { text-align:right; }
        </style>
      </head>
      <body>
        <div class="receipt">
          <div class="center"><b>☕ Eskina Coffee</b></div>
          <div class="center">EST. 2025</div>
          <div class="center">203 11th Avenue 4th St, East Grace Park, Caloocan City</div>
          <div class="center">Open Daily | Sun-Thu 1PM-11PM | Fri-Sat 1PM-12MN</div>
          <div class="center">THIS IS NOT AN OFFICIAL RECEIPT</div>
          <div class="line"></div>

          <div class="info"><span>Receipt No:</span><span>${receiptNo}</span></div>
          <div class="info"><span>Payment Method:</span><span>${payment}</span></div>
          <div class="info"><span>Order Type:</span><span>${orderType}</span></div>
          <div class="info"><span>Staff:</span><span>${staff}</span></div>
          <div class="line"></div>

          <table>
            <thead>
              <tr><th>ORDER ITEM</th><th class="right">PRICE</th></tr>
            </thead>
            <tbody>
              ${cart.map(item => {
                const price = parseFloat(String(item.price || "").replace(/[^\d.]/g, "")) || 0;
                const qty = parseInt(item.quantity) || 0;
                return `<tr><td>${item.name} x${qty}</td><td class="right">₱${(price*qty).toFixed(2)}</td></tr>`;
              }).join("")}
            </tbody>
            <tfoot>
              <tr><td class="right">TOTAL:</td><td class="right">₱${total.toFixed(2)}</td></tr>
            </tfoot>
          </table>

          <div class="line"></div>
          <div class="center">THANK YOU FOR YOUR PURCHASE!</div>
          <div class="center">ENJOY YOUR MEAL ☕</div>
          <div class="center">${formattedDate}</div>
        </div>
      </body>
    </html>
  `;

  receiptWindow.document.write(receiptHTML);
  receiptWindow.document.close();
  receiptWindow.focus();
  receiptWindow.print();
  receiptWindow.close();
}
