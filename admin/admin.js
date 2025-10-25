// Admin App State
const admin = {
  currentPage: 'orders',
  orders: [],
  currentOrder: null,
  pagination: {
    limit: 20,
    offset: 0,
    total: 0
  },
  filters: {
    status: '',
    search: ''
  }
};

let csrfToken = sessionStorage.getItem('csrfToken') || '';

function setCsrfToken(token) {
  if (token) {
    csrfToken = token;
    sessionStorage.setItem('csrfToken', token);
  }
}

function getCsrfToken() {
  return csrfToken;
}

function buildHeaders(additional = {}, includeCsrf = false) {
  const headers = {...additional};

  if (includeCsrf) {
    const token = getCsrfToken();
    if (token) {
      headers['X-CSRF-Token'] = token;
    }
  }

  return headers;
}

async function handleJsonResponse(response, defaultError = 'เกิดข้อผิดพลาด') {
  if (response.status === 401) {
    window.location.href = 'login.html';
    return null;
  }

  let data;

  try {
    data = await response.json();
  } catch (error) {
    showError(defaultError);
    return null;
  }

  if (!response.ok) {
    const message = data?.message || data?.error || defaultError;
    showError(message);
    return null;
  }

  return data;
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
  checkAuthentication();
});

// Check Authentication
async function checkAuthentication() {
  try {
    const response = await fetch('check-auth.php?json=1', {
      credentials: 'same-origin'
    });
    const data = await response.json();

    if (!data.authenticated) {
      window.location.href = 'login.html';
      return;
    }

    if (data.csrfToken) {
      setCsrfToken(data.csrfToken);
    }

    // User is authenticated, hide loading and show admin
    document.getElementById('loading-screen').style.display = 'none';
    document.getElementById('admin-container').style.display = 'flex';

    // Continue with initialization
    setupEventListeners();
    loadOrders();
  } catch (error) {
    console.error('Auth check error:', error);
    window.location.href = 'login.html';
  }
}

// Setup Event Listeners
function setupEventListeners() {
  // Navigation
  document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', (e) => {
      e.preventDefault();
      const page = item.getAttribute('data-page');
      navigateToPage(page);
    });
  });

  // Logout
  document.getElementById('logout-btn').addEventListener('click', logout);

  // Search
  document.getElementById('search-input').addEventListener('input', (e) => {
    admin.filters.search = e.target.value;
    admin.pagination.offset = 0;
    loadOrders();
  });

  // Status Filter
  document.getElementById('status-filter').addEventListener('change', (e) => {
    admin.filters.status = e.target.value;
    admin.pagination.offset = 0;
    loadOrders();
  });

  // Modal Close
  document.getElementById('modal-close').addEventListener('click', closeModal);
  document.getElementById('order-modal').addEventListener('click', (e) => {
    if (e.target.id === 'order-modal') {
      closeModal();
    }
  });
}

// Navigation
function navigateToPage(page) {
  // Update active nav
  document.querySelectorAll('.nav-item').forEach(item => {
    item.classList.remove('active');
    if (item.getAttribute('data-page') === page) {
      item.classList.add('active');
    }
  });

  // Show page
  document.querySelectorAll('.page').forEach(p => {
    p.classList.remove('active');
  });
  document.getElementById(`${page}-page`).classList.add('active');

  admin.currentPage = page;

  if (page === 'orders') {
    loadOrders();
  } else if (page === 'products') {
    loadProducts();
  } else if (page === 'reports') {
    loadReports();
  } else if (page === 'settings') {
    loadSettings();
  }
}

// Load Orders
async function loadOrders() {
  try {
    const params = new URLSearchParams({
      limit: admin.pagination.limit,
      offset: admin.pagination.offset
    });

    if (admin.filters.status) {
      params.append('status', admin.filters.status);
    }

    if (admin.filters.search) {
      params.append('search', admin.filters.search);
    }

    const response = await fetch(`../api/admin/orders.php?${params}`, {
      credentials: 'same-origin'
    });
    const data = await handleJsonResponse(response, 'ไม่สามารถโหลดข้อมูลได้');
    if (!data) {
      return;
    }

    if (data.orders) {
      admin.orders = data.orders;
      admin.pagination.total = data.totalCount;
      renderOrders();
      updateStats();
      renderPagination();
    }
  } catch (error) {
    console.error('Error loading orders:', error);
    showError('ไม่สามารถโหลดข้อมูลได้');
  }
}

// Render Orders
function renderOrders() {
  const tbody = document.getElementById('orders-tbody');

  if (admin.orders.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8" class="loading">ไม่พบข้อมูล</td></tr>';
    return;
  }

  tbody.innerHTML = admin.orders.map(order => {
    const items = JSON.parse(order.items || '[]');
    const itemCount = items.reduce((sum, item) => sum + item.quantity, 0);
    const date = new Date(order.order_date).toLocaleDateString('th-TH', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });

    return `
      <tr>
        <td><strong>${order.order_number}</strong></td>
        <td>
          <div>${order.customer_name}</div>
          <div style="font-size: 0.85rem; color: #666;">${order.customer_phone}</div>
        </td>
        <td>${itemCount} รายการ</td>
        <td><strong>฿${parseFloat(order.total).toFixed(2)}</strong></td>
        <td><span class="status-badge ${order.status}">${getStatusText(order.status)}</span></td>
        <td><span class="payment-badge ${order.payment_status}">${getPaymentStatusText(order.payment_status)}</span></td>
        <td>${date}</td>
        <td>
          <button class="btn-view" onclick="viewOrder(${order.id})">ดูรายละเอียด</button>
          <button class="btn-delete" onclick="deleteOrder(${order.id}, '${order.order_number}')">ลบ</button>
        </td>
      </tr>
    `;
  }).join('');
}

// Update Stats
function updateStats() {
  const stats = {
    pending: 0,
    processing: 0,
    completed: 0,
    total: 0
  };

  admin.orders.forEach(order => {
    if (order.status === 'pending') stats.pending++;
    if (order.status === 'processing') stats.processing++;
    if (order.status === 'completed') stats.completed++;
    stats.total += parseFloat(order.total);
  });

  document.getElementById('stat-pending').textContent = stats.pending;
  document.getElementById('stat-processing').textContent = stats.processing;
  document.getElementById('stat-completed').textContent = stats.completed;
  document.getElementById('stat-total').textContent = `฿${stats.total.toFixed(2)}`;
}

// Render Pagination
function renderPagination() {
  const pagination = document.getElementById('pagination');
  const totalPages = Math.ceil(admin.pagination.total / admin.pagination.limit);
  const currentPage = Math.floor(admin.pagination.offset / admin.pagination.limit) + 1;

  if (totalPages <= 1) {
    pagination.innerHTML = '';
    return;
  }

  let html = '';

  // Previous button
  html += `<button ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">« ก่อนหน้า</button>`;

  // Page numbers
  for (let i = 1; i <= totalPages; i++) {
    if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
      html += `<button class="${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
    } else if (i === currentPage - 3 || i === currentPage + 3) {
      html += '<button disabled>...</button>';
    }
  }

  // Next button
  html += `<button ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">ถัดไป »</button>`;

  pagination.innerHTML = html;
}

// Change Page
function changePage(page) {
  admin.pagination.offset = (page - 1) * admin.pagination.limit;
  loadOrders();
}

// View Order
async function viewOrder(orderId) {
  try {
    const response = await fetch(`../api/orders/{id}.php?id=${orderId}`, {
      credentials: 'same-origin'
    });
    const order = await handleJsonResponse(response, 'ไม่สามารถโหลดรายละเอียดได้');
    if (order) {
      admin.currentOrder = order;
      renderOrderDetail(order);
      openModal();
    }
  } catch (error) {
    console.error('Error loading order:', error);
    showError('ไม่สามารถโหลดรายละเอียดได้');
  }
}

// Render Order Detail
function renderOrderDetail(order) {
  const items = JSON.parse(order.items || '[]');
  const date = new Date(order.order_date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });

  const html = `
    <div class="order-detail-section">
      <h4>ข้อมูลคำสั่งซื้อ</h4>
      <div class="detail-row">
        <span class="detail-label">เลขที่คำสั่งซื้อ:</span>
        <span><strong>${order.order_number}</strong></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">วันที่:</span>
        <span>${date}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">สถานะ:</span>
        <div>
          <span class="status-badge ${order.status}">${getStatusText(order.status)}</span>
          <select id="update-status" class="status-select" data-order-id="${order.id}" data-current="${order.status}">
            <option value="">-- เปลี่ยนสถานะ --</option>
            <option value="pending">รอชำระเงิน</option>
            <option value="processing">กำลังดำเนินการ</option>
            <option value="completed">เสร็จสิ้น</option>
            <option value="cancelled">ยกเลิก</option>
          </select>
        </div>
      </div>
      <div class="detail-row">
        <span class="detail-label">การชำระเงิน:</span>
        <span class="payment-badge ${order.payment_status}">${getPaymentStatusText(order.payment_status)}</span>
      </div>
    </div>

    <div class="order-detail-section">
      <h4>ข้อมูลลูกค้า</h4>
      <div class="detail-row">
        <span class="detail-label">ชื่อ:</span>
        <span>${order.customer_name}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">อีเมล:</span>
        <span>${order.customer_email}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">เบอร์โทร:</span>
        <span>${order.customer_phone}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">ที่อยู่:</span>
        <span>${order.customer_address}, ${order.customer_city}, ${order.customer_postal}, ${order.customer_country}</span>
      </div>
    </div>

    <div class="order-detail-section">
      <h4>รายการสินค้า</h4>
      <ul class="items-list">
        ${items.map(item => `
          <li>
            <span>${item.name} x ${item.quantity}</span>
            <span><strong>฿${(item.price * item.quantity).toFixed(2)}</strong></span>
          </li>
        `).join('')}
      </ul>
    </div>

    <div class="order-detail-section">
      <h4>สรุปยอด</h4>
      <div class="detail-row">
        <span class="detail-label">ราคาสินค้า:</span>
        <span>฿${parseFloat(order.subtotal).toFixed(2)}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">ค่าจัดส่ง:</span>
        <span>฿${parseFloat(order.shipping_cost).toFixed(2)}</span>
      </div>
      <div class="detail-row">
        <span class="detail-label"><strong>ยอดรวมทั้งหมด:</strong></span>
        <span><strong style="font-size: 1.2rem; color: var(--color-success);">฿${parseFloat(order.total).toFixed(2)}</strong></span>
      </div>
    </div>
  `;

  document.getElementById('modal-body').innerHTML = html;

  // Add event listener for status update
  const statusSelect = document.getElementById('update-status');
  if (statusSelect) {
    statusSelect.addEventListener('change', async (e) => {
      const newStatus = e.target.value;
      const orderId = e.target.getAttribute('data-order-id');

      if (newStatus && confirm(`ต้องการเปลี่ยนสถานะเป็น "${getStatusText(newStatus)}"?`)) {
        await updateOrderStatus(orderId, newStatus);
      } else {
        e.target.value = '';
      }
    });
  }
}

// Update Order Status
async function updateOrderStatus(orderId, status) {
  try {
    const response = await fetch('../api/admin/orders/update.php', {
      method: 'POST',
      headers: buildHeaders({'Content-Type': 'application/json'}, true),
      credentials: 'same-origin',
      body: JSON.stringify({
        id: orderId,
        status: status
      })
    });

    const result = await handleJsonResponse(response, 'ไม่สามารถอัพเดทสถานะได้');
    if (!result) {
      return;
    }

    if (result.success) {
      alert('อัพเดทสถานะสำเร็จ');
      closeModal();
      loadOrders();
    } else {
      alert('ไม่สามารถอัพเดทสถานะได้: ' + result.message);
    }
  } catch (error) {
    console.error('Error updating status:', error);
    alert('เกิดข้อผิดพลาด');
  }
}

// Delete Order
async function deleteOrder(orderId, orderNumber) {
  if (!confirm(`ต้องการลบคำสั่งซื้อ ${orderNumber}?\n\nการลบจะไม่สามารถกู้คืนได้`)) {
    return;
  }

  try {
    const response = await fetch(`../api/admin/orders/delete.php?id=${orderId}`, {
      method: 'DELETE',
      headers: buildHeaders({}, true),
      credentials: 'same-origin'
    });

    const result = await handleJsonResponse(response, 'ไม่สามารถลบคำสั่งซื้อได้');
    if (!result) {
      return;
    }

    if (result.success) {
      alert('ลบคำสั่งซื้อสำเร็จ');
      loadOrders();
    } else {
      alert('ไม่สามารถลบได้: ' + result.message);
    }
  } catch (error) {
    console.error('Error deleting order:', error);
    alert('เกิดข้อผิดพลาด');
  }
}

// Modal Functions
function openModal() {
  document.getElementById('order-modal').classList.add('active');
}

function closeModal() {
  document.getElementById('order-modal').classList.remove('active');
}

// Helper Functions
function getStatusText(status) {
  const statusMap = {
    'pending': 'รอชำระเงิน',
    'processing': 'กำลังดำเนินการ',
    'completed': 'เสร็จสิ้น',
    'cancelled': 'ยกเลิก'
  };
  return statusMap[status] || status;
}

function getPaymentStatusText(status) {
  const statusMap = {
    'pending': 'รอชำระ',
    'paid': 'ชำระแล้ว'
  };
  return statusMap[status] || status;
}

function showError(message) {
  alert(message);
}

function logout() {
  if (confirm('ต้องการออกจากระบบ?')) {
    sessionStorage.removeItem('csrfToken');
    window.location.href = '../api/admin/logout.php';
  }
}

// Products Management
let products = [];

async function loadProducts() {
  try {
    const response = await fetch('../api/admin/products.php', {
      credentials: 'same-origin'
    });
    const data = await handleJsonResponse(response, 'ไม่สามารถโหลดข้อมูลสินค้าได้');
    if (!data) {
      return;
    }

    if (data.products) {
      products = data.products;
      renderProducts();
    }
  } catch (error) {
    console.error('Error loading products:', error);
    showError('ไม่สามารถโหลดข้อมูลสินค้าได้');
  }
}

function renderProducts() {
  const container = document.getElementById('products-list');

  if (products.length === 0) {
    container.innerHTML = '<p class="empty-state">ยังไม่มีสินค้า</p>';
    return;
  }

  container.innerHTML = `
    <div class="products-grid">
      ${products.map(product => `
        <div class="product-card">
          <img src="../${product.image}" alt="${product.name}" class="product-image">
          <div class="product-info">
            <h3>${product.name}</h3>
            <p class="product-description">${product.description}</p>
            <div class="product-meta">
              <span class="product-price">฿${parseFloat(product.price).toFixed(2)}</span>
              <span class="product-stock">คงเหลือ: ${product.stock}</span>
            </div>
            <div class="product-actions">
              <button class="btn-edit" onclick="editProduct(${product.id})">แก้ไข</button>
              <button class="btn-delete" onclick="deleteProduct(${product.id}, '${product.name}')">ลบ</button>
            </div>
          </div>
        </div>
      `).join('')}
    </div>
  `;
}

function showProductModal(product = null) {
  const modal = document.getElementById('product-modal');
  const form = document.getElementById('product-form');
  const title = document.getElementById('product-modal-title');

  if (product) {
    title.textContent = 'แก้ไขสินค้า';
    document.getElementById('product-id').value = product.id;
    document.getElementById('product-name').value = product.name;
    document.getElementById('product-description').value = product.description;
    document.getElementById('product-price').value = product.price;
    document.getElementById('product-category').value = product.category;
    document.getElementById('product-stock').value = product.stock;
    document.getElementById('product-image').value = product.image;
  } else {
    title.textContent = 'เพิ่มสินค้าใหม่';
    form.reset();
    document.getElementById('product-id').value = '';
  }

  modal.classList.add('active');
}

function closeProductModal() {
  document.getElementById('product-modal').classList.remove('active');
}

async function saveProduct(event) {
  event.preventDefault();

  const id = document.getElementById('product-id').value;
  const data = {
    name: document.getElementById('product-name').value,
    description: document.getElementById('product-description').value,
    price: parseFloat(document.getElementById('product-price').value),
    category: document.getElementById('product-category').value,
    stock: parseInt(document.getElementById('product-stock').value),
    image: document.getElementById('product-image').value
  };

  try {
    let response;
    if (id) {
      // Update
      data.id = id;
      response = await fetch('../api/admin/products/update.php', {
        method: 'PUT',
        headers: buildHeaders({'Content-Type': 'application/json'}, true),
        credentials: 'same-origin',
        body: JSON.stringify(data)
      });
    } else {
      // Create
      response = await fetch('../api/admin/products.php', {
        method: 'POST',
        headers: buildHeaders({'Content-Type': 'application/json'}, true),
        credentials: 'same-origin',
        body: JSON.stringify(data)
      });
    }

    const result = await handleJsonResponse(response, 'เกิดข้อผิดพลาดในการบันทึกสินค้า');
    if (!result) {
      return;
    }

    if (result.success) {
      alert(id ? 'อัพเดทสินค้าสำเร็จ' : 'เพิ่มสินค้าสำเร็จ');
      closeProductModal();
      loadProducts();
    } else {
      alert('เกิดข้อผิดพลาด: ' + result.error);
    }
  } catch (error) {
    console.error('Error saving product:', error);
    alert('เกิดข้อผิดพลาด');
  }
}

function editProduct(productId) {
  const product = products.find(p => p.id == productId);
  if (product) {
    showProductModal(product);
  }
}

async function deleteProduct(productId, productName) {
  if (!confirm(`ต้องการลบสินค้า "${productName}"?\n\nการลบจะไม่สามารถกู้คืนได้`)) {
    return;
  }

  try {
    const response = await fetch(`../api/admin/products/delete.php?id=${productId}`, {
      method: 'DELETE',
      headers: buildHeaders({}, true),
      credentials: 'same-origin'
    });

    const result = await handleJsonResponse(response, 'ไม่สามารถลบสินค้าได้');
    if (!result) {
      return;
    }

    if (result.success) {
      alert('ลบสินค้าสำเร็จ');
      loadProducts();
    } else {
      alert('ไม่สามารถลบได้: ' + result.error);
    }
  } catch (error) {
    console.error('Error deleting product:', error);
    alert('เกิดข้อผิดพลาด');
  }
}

// Reports
async function loadReports() {
  try {
    const response = await fetch('../api/admin/reports.php?type=overview', {
      credentials: 'same-origin'
    });
    const data = await handleJsonResponse(response, 'ไม่สามารถโหลดรายงานได้');
    if (!data) {
      return;
    }

    if (data.stats) {
      document.getElementById('report-total-orders').textContent = data.stats.total_orders || 0;
      document.getElementById('report-total-sales').textContent = `฿${parseFloat(data.stats.total_sales || 0).toFixed(2)}`;
      document.getElementById('report-avg-order').textContent = `฿${parseFloat(data.stats.avg_order_value || 0).toFixed(2)}`;
      document.getElementById('report-completed').textContent = data.stats.completed_orders || 0;
    }

    if (data.dailySales) {
      renderSalesChart(data.dailySales);
    }
  } catch (error) {
    console.error('Error loading reports:', error);
    showError('ไม่สามารถโหลดรายงานได้');
  }
}

function renderSalesChart(dailySales) {
  const canvas = document.getElementById('sales-chart');
  const ctx = canvas.getContext('2d');

  // Simple bar chart implementation
  canvas.width = canvas.offsetWidth;
  canvas.height = 300;

  if (dailySales.length === 0) {
    ctx.fillStyle = '#999';
    ctx.font = '16px Sarabun';
    ctx.textAlign = 'center';
    ctx.fillText('ยังไม่มีข้อมูล', canvas.width / 2, canvas.height / 2);
    return;
  }

  const maxSales = Math.max(...dailySales.map(d => parseFloat(d.sales)));
  const barWidth = canvas.width / dailySales.length - 10;
  const chartHeight = canvas.height - 60;

  dailySales.forEach((day, index) => {
    const sales = parseFloat(day.sales);
    const barHeight = (sales / maxSales) * chartHeight;
    const x = index * (barWidth + 10) + 5;
    const y = canvas.height - barHeight - 40;

    // Draw bar
    ctx.fillStyle = '#333';
    ctx.fillRect(x, y, barWidth, barHeight);

    // Draw label
    ctx.fillStyle = '#666';
    ctx.font = '12px Sarabun';
    ctx.textAlign = 'center';
    const date = new Date(day.date);
    const label = `${date.getDate()}/${date.getMonth() + 1}`;
    ctx.fillText(label, x + barWidth / 2, canvas.height - 20);

    // Draw value
    ctx.fillStyle = '#333';
    ctx.font = 'bold 12px Sarabun';
    ctx.fillText(`฿${sales.toFixed(0)}`, x + barWidth / 2, y - 5);
  });
}

// Settings
let currentSettings = {};

async function loadSettings() {
  try {
    const response = await fetch('../api/admin/settings.php', {
      credentials: 'same-origin'
    });
    const data = await handleJsonResponse(response, 'ไม่สามารถโหลดการตั้งค่าได้');
    if (!data) {
      return;
    }

    if (data.settings) {
      currentSettings = data.settings;
      document.getElementById('shop-name').value = data.settings.shop_name || '';
      document.getElementById('shop-phone').value = data.settings.shop_phone || '';
      document.getElementById('shop-email').value = data.settings.shop_email || '';
      document.getElementById('telegram-token').value = data.settings.telegram_token || '';
      document.getElementById('telegram-chat-id').value = data.settings.telegram_chat_id || '';
    }
  } catch (error) {
    console.error('Error loading settings:', error);
  }
}

async function saveShopSettings(event) {
  event.preventDefault();

  const data = {
    shop_name: document.getElementById('shop-name').value,
    shop_phone: document.getElementById('shop-phone').value,
    shop_email: document.getElementById('shop-email').value,
    telegram_token: document.getElementById('telegram-token').value || '',
    telegram_chat_id: document.getElementById('telegram-chat-id').value || '',
    promptpay_id: currentSettings.promptpay_id || '',
    promptpay_name: currentSettings.promptpay_name || ''
  };

  try {
    const response = await fetch('../api/admin/settings.php', {
      method: 'POST',
      headers: buildHeaders({'Content-Type': 'application/json'}, true),
      credentials: 'same-origin',
      body: JSON.stringify(data)
    });

    const result = await handleJsonResponse(response, 'ไม่สามารถบันทึกข้อมูลร้านได้');
    if (!result) {
      return;
    }

    if (result.success) {
      alert('บันทึกข้อมูลร้านสำเร็จ');
    } else {
      alert('เกิดข้อผิดพลาด: ' + result.error);
    }
  } catch (error) {
    console.error('Error saving settings:', error);
    alert('เกิดข้อผิดพลาด');
  }
}

async function saveTelegramSettings(event) {
  event.preventDefault();

  const data = {
    shop_name: document.getElementById('shop-name').value || '',
    shop_phone: document.getElementById('shop-phone').value || '',
    shop_email: document.getElementById('shop-email').value || '',
    telegram_token: document.getElementById('telegram-token').value,
    telegram_chat_id: document.getElementById('telegram-chat-id').value,
    promptpay_id: currentSettings.promptpay_id || '',
    promptpay_name: currentSettings.promptpay_name || ''
  };

  try {
    const response = await fetch('../api/admin/settings.php', {
      method: 'POST',
      headers: buildHeaders({'Content-Type': 'application/json'}, true),
      credentials: 'same-origin',
      body: JSON.stringify(data)
    });

    const result = await handleJsonResponse(response, 'ไม่สามารถบันทึกการตั้งค่า Telegram ได้');
    if (!result) {
      return;
    }

    if (result.success) {
      alert('บันทึกการตั้งค่า Telegram สำเร็จ');
      currentSettings = data;
    } else {
      alert('เกิดข้อผิดพลาด: ' + result.error);
    }
  } catch (error) {
    console.error('Error saving settings:', error);
    alert('เกิดข้อผิดพลาด');
  }
}

async function testTelegram() {
  const token = document.getElementById('telegram-token').value;
  const chatId = document.getElementById('telegram-chat-id').value;

  if (!token || !chatId) {
    alert('กรุณากรอก Bot Token และ Chat ID');
    return;
  }

  try {
    const response = await fetch('../api/admin/test-telegram.php', {
      method: 'POST',
      headers: buildHeaders({'Content-Type': 'application/json'}, true),
      credentials: 'same-origin',
      body: JSON.stringify({token, chat_id: chatId})
    });

    const result = await handleJsonResponse(response, 'ไม่สามารถทดสอบการส่งข้อความได้');
    if (!result) {
      return;
    }

    if (result.success) {
      alert('✅ ' + result.message);
    } else {
      alert('❌ ' + result.error);
    }
  } catch (error) {
    console.error('Error testing telegram:', error);
    alert('เกิดข้อผิดพลาด');
  }
}

async function changePassword(event) {
  event.preventDefault();

  const oldPassword = document.getElementById('old-password').value;
  const newPassword = document.getElementById('new-password').value;
  const confirmPassword = document.getElementById('confirm-password').value;

  if (newPassword !== confirmPassword) {
    alert('รหัสผ่านใหม่ไม่ตรงกัน');
    return;
  }

  if (newPassword.length < 6) {
    alert('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
    return;
  }

  try {
    const response = await fetch('../api/admin/change-password.php', {
      method: 'POST',
      headers: buildHeaders({'Content-Type': 'application/json'}, true),
      credentials: 'same-origin',
      body: JSON.stringify({
        old_password: oldPassword,
        new_password: newPassword
      })
    });

    const result = await handleJsonResponse(response, 'ไม่สามารถเปลี่ยนรหัสผ่านได้');
    if (!result) {
      return;
    }

    if (result.success) {
      alert('✅ เปลี่ยนรหัสผ่านสำเร็จ');
      document.getElementById('password-form').reset();
    } else {
      alert('❌ ' + result.error);
    }
  } catch (error) {
    console.error('Error changing password:', error);
    alert('เกิดข้อผิดพลาด');
  }
}

// Form handlers
document.addEventListener('DOMContentLoaded', () => {
  // Shop settings form
  const shopForm = document.getElementById('shop-settings-form');
  if (shopForm) {
    shopForm.addEventListener('submit', saveShopSettings);
  }

  // Telegram settings form
  const telegramForm = document.getElementById('telegram-settings-form');
  if (telegramForm) {
    telegramForm.addEventListener('submit', saveTelegramSettings);
  }

  // Password form
  const passwordForm = document.getElementById('password-form');
  if (passwordForm) {
    passwordForm.addEventListener('submit', changePassword);
  }
});
