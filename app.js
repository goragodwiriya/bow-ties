// App State
const app = {
  currentPage: 'home',
  products: [],
  cart: [],
  currentProduct: null,
  orderData: null
};

// DOM Elements
let elements = {};

// Initialize App
document.addEventListener('DOMContentLoaded', () => {
  // Initialize DOM elements after DOM is loaded
  elements = {
    pages: document.querySelectorAll('.page'),
    navLinks: document.querySelectorAll('.nav-link'),
    cartToggle: document.querySelector('.cart-toggle'),
    cartSidebar: document.getElementById('cart-sidebar'),
    closeCart: document.getElementById('close-cart'),
    overlay: document.getElementById('overlay'),
    cartCount: document.querySelector('.cart-count'),
    toast: document.getElementById('toast'),
    toastMessage: document.getElementById('toast-message'),
    toastClose: document.getElementById('toast-close')
  };

  loadProducts();
  setupEventListeners();
  loadCartFromStorage();
  updateCartUI();
});

// Load Products
async function loadProducts() {
  try {
    const response = await fetch('api/products.php');
    const data = await response.json();

    // Transform API data to match frontend format
    app.products = data.map(product => ({
      id: parseInt(product.id),
      name: product.name,
      price: parseFloat(product.price),
      category: product.category,
      description: product.description,
      image: product.image,
      images: JSON.parse(product.images),
      details: JSON.parse(product.details)
    }));

    renderFeaturedProducts();
    renderAllProducts();
  } catch (error) {
    console.error('Error loading products:', error);
    showToast('ไม่สามารถโหลดสินค้าได้ กรุณาลองใหม่อีกครั้ง');
  }
}

// Setup Event Listeners
function setupEventListeners() {
  // Navigation
  elements.navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const page = link.getAttribute('data-page');
      navigateToPage(page);
    });
  });

  // All links with data-page attribute
  document.querySelectorAll('a[data-page], button[data-page]').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const page = link.getAttribute('data-page');
      navigateToPage(page);
    });
  });

  // Cart Toggle
  elements.cartToggle.addEventListener('click', openCart);
  elements.closeCart.addEventListener('click', closeCart);
  elements.overlay.addEventListener('click', closeCart);

  // Toast Close
  elements.toastClose.addEventListener('click', hideToast);

  // Product Filters
  const categoryFilter = document.getElementById('category-filter');
  const sortFilter = document.getElementById('sort-filter');

  if (categoryFilter) {
    categoryFilter.addEventListener('change', filterAndSortProducts);
  }

  if (sortFilter) {
    sortFilter.addEventListener('change', filterAndSortProducts);
  }

  // Product Detail
  const quantityDecrease = document.getElementById('quantity-decrease');
  const quantityIncrease = document.getElementById('quantity-increase');
  const addToCartBtn = document.getElementById('add-to-cart');
  const buyNowBtn = document.getElementById('buy-now');

  if (quantityDecrease) {
    quantityDecrease.addEventListener('click', () => updateQuantity(-1));
  }

  if (quantityIncrease) {
    quantityIncrease.addEventListener('click', () => updateQuantity(1));
  }

  if (addToCartBtn) {
    addToCartBtn.addEventListener('click', addToCart);
  }

  if (buyNowBtn) {
    buyNowBtn.addEventListener('click', buyNow);
  }

  // Checkout
  const checkoutForm = document.getElementById('checkout-form');
  if (checkoutForm) {
    checkoutForm.addEventListener('submit', processCheckout);
  }

  // Shipping Options
  const shippingOptions = document.querySelectorAll('input[name="shipping"]');
  shippingOptions.forEach(option => {
    option.addEventListener('change', updateCheckoutSummary);
  });

  // Order Confirmation
  const confirmPaymentBtn = document.getElementById('confirm-payment');
  if (confirmPaymentBtn) {
    confirmPaymentBtn.addEventListener('click', confirmPayment);
  }

  // Contact Form
  const contactForm = document.getElementById('contact-form');
  if (contactForm) {
    contactForm.addEventListener('submit', submitContactForm);
  }

  // Cart page checkout button
  const checkoutBtn = document.getElementById('checkout-btn');
  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', (e) => {
      e.preventDefault();
      if (app.cart.length === 0) {
        showToast('ตะกร้าสินค้าว่างเปล่า');
        return;
      }
      navigateToPage('checkout');
    });
  }

  // Footer links
  document.querySelectorAll('.footer-link').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const page = link.getAttribute('data-page');
      navigateToPage(page);
    });
  });
}

// Navigation
function navigateToPage(page) {
  // Update active nav link
  elements.navLinks.forEach(link => {
    link.classList.remove('active');
    if (link.getAttribute('data-page') === page) {
      link.classList.add('active');
    }
  });

  // Hide all pages
  elements.pages.forEach(p => {
    p.classList.remove('active');
  });

  // Show selected page
  const targetPage = document.getElementById(page);
  if (targetPage) {
    targetPage.classList.add('active');
    app.currentPage = page;

    // Scroll to top
    window.scrollTo(0, 0);

    // Page-specific initialization
    if (page === 'products') {
      renderAllProducts();
    } else if (page === 'cart') {
      renderCartItems();
      updateCartSummary();
    } else if (page === 'checkout') {
      renderCheckoutItems();
      updateCheckoutSummary();
    }
  }

  // Close cart if open
  closeCart();
}

// Render Products
function renderFeaturedProducts() {
  const container = document.getElementById('featured-products');
  if (!container) return;

  // Get first 3 products as featured
  const featuredProducts = app.products.slice(0, 3);

  container.innerHTML = featuredProducts.map(product => createProductCard(product)).join('');

  // Add event listeners to product cards
  container.querySelectorAll('.product-card').forEach(card => {
    // Click on card (but not on button) to view details
    card.addEventListener('click', (e) => {
      if (!e.target.closest('.quick-add')) {
        const productId = parseInt(card.getAttribute('data-product-id'));
        showProductDetail(productId);
      }
    });
  });

  // Add event listeners to quick-add buttons
  container.querySelectorAll('.quick-add').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const productId = parseInt(btn.getAttribute('data-product-id'));
      quickAddToCart(productId);
    });
  });
}

function renderAllProducts() {
  const container = document.getElementById('all-products');
  if (!container) return;

  let products = [...app.products];

  // Apply filters
  const categoryFilter = document.getElementById('category-filter');
  if (categoryFilter && categoryFilter.value !== 'all') {
    products = products.filter(p => p.category === categoryFilter.value);
  }

  // Apply sorting
  const sortFilter = document.getElementById('sort-filter');
  if (sortFilter) {
    switch (sortFilter.value) {
      case 'name-asc':
        products.sort((a, b) => a.name.localeCompare(b.name));
        break;
      case 'name-desc':
        products.sort((a, b) => b.name.localeCompare(a.name));
        break;
      case 'price-asc':
        products.sort((a, b) => a.price - b.price);
        break;
      case 'price-desc':
        products.sort((a, b) => b.price - a.price);
        break;
    }
  }

  container.innerHTML = products.map(product => createProductCard(product)).join('');

  // Add event listeners to product cards
  container.querySelectorAll('.product-card').forEach(card => {
    // Click on card (but not on button) to view details
    card.addEventListener('click', (e) => {
      if (!e.target.closest('.quick-add')) {
        const productId = parseInt(card.getAttribute('data-product-id'));
        showProductDetail(productId);
      }
    });
  });

  // Add event listeners to quick-add buttons
  container.querySelectorAll('.quick-add').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const productId = parseInt(btn.getAttribute('data-product-id'));
      quickAddToCart(productId);
    });
  });
}

function filterAndSortProducts() {
  renderAllProducts();
}

function createProductCard(product) {
  return `
        <div class="product-card" data-product-id="${product.id}">
            <div class="product-image">
                <img src="${product.image}" alt="${product.name}">
            </div>
            <div class="product-info">
                <h3 class="product-name">${product.name}</h3>
                <div class="product-price">฿${product.price.toFixed(2)}</div>
                <div class="product-actions">
                    <button class="btn btn-secondary quick-add" data-product-id="${product.id}">เพิ่มลงตะกร้า</button>
                </div>
            </div>
        </div>
    `;
}

// Product Detail
function showProductDetail(productId) {
  const product = app.products.find(p => p.id === productId);
  if (!product) return;

  app.currentProduct = product;

  // Update product detail page
  document.getElementById('main-product-image').src = product.image;
  document.getElementById('product-name').textContent = product.name;
  document.getElementById('product-price').textContent = `฿${product.price.toFixed(2)}`;
  document.getElementById('product-description').textContent = product.description;

  // Reset quantity
  document.getElementById('product-quantity').value = 1;

  // Render thumbnails
  const thumbnailsContainer = document.querySelector('.thumbnail-images');
  thumbnailsContainer.innerHTML = product.images.map((img, index) => `
        <div class="thumbnail ${index === 0 ? 'active' : ''}" data-image="${img}">
            <img src="${img}" alt="${product.name}">
        </div>
    `).join('');

  // Add event listeners to thumbnails
  thumbnailsContainer.querySelectorAll('.thumbnail').forEach(thumb => {
    thumb.addEventListener('click', () => {
      const imageSrc = thumb.getAttribute('data-image');
      document.getElementById('main-product-image').src = imageSrc;

      // Update active thumbnail
      thumbnailsContainer.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
    });
  });

  // Render product details
  const detailsList = document.getElementById('product-details-list');
  detailsList.innerHTML = product.details.map(detail => `<li>${detail}</li>`).join('');

  // Navigate to product detail page
  navigateToPage('product-detail');
}

function updateQuantity(change) {
  const quantityInput = document.getElementById('product-quantity');
  let quantity = parseInt(quantityInput.value);
  quantity += change;

  if (quantity < 1) quantity = 1;

  quantityInput.value = quantity;
}

function addToCart() {
  if (!app.currentProduct) return;

  const quantity = parseInt(document.getElementById('product-quantity').value);
  const existingItem = app.cart.find(item => item.id === app.currentProduct.id);

  if (existingItem) {
    existingItem.quantity += quantity;
  } else {
    app.cart.push({
      id: app.currentProduct.id,
      name: app.currentProduct.name,
      price: app.currentProduct.price,
      image: app.currentProduct.image,
      quantity: quantity
    });
  }

  saveCartToStorage();
  updateCartUI();
  showToast(`เพิ่ม ${app.currentProduct.name} ลงตะกร้าแล้ว`);
}

function quickAddToCart(productId) {
  const product = app.products.find(p => p.id === productId);
  if (!product) {
    console.error('Product not found:', productId);
    return;
  }

  const existingItem = app.cart.find(item => item.id === productId);

  if (existingItem) {
    existingItem.quantity += 1;
  } else {
    app.cart.push({
      id: product.id,
      name: product.name,
      price: product.price,
      image: product.image,
      quantity: 1
    });
  }

  saveCartToStorage();
  updateCartUI();
  showToast(`เพิ่ม ${product.name} ลงตะกร้าแล้ว`);
}

function buyNow() {
  addToCart();
  navigateToPage('cart');
}

// Cart Management
function openCart() {
  elements.cartSidebar.classList.add('open');
  elements.overlay.classList.add('active');
  renderSidebarCartItems();
  updateSidebarCartTotal();
}

function closeCart() {
  elements.cartSidebar.classList.remove('open');
  elements.overlay.classList.remove('active');
}

function renderSidebarCartItems() {
  const container = document.getElementById('sidebar-cart-items');
  if (!container) return;

  if (app.cart.length === 0) {
    container.innerHTML = '<p>ตะกร้าสินค้าว่างเปล่า</p>';
    return;
  }

  container.innerHTML = app.cart.map(item => createCartItemHTML(item)).join('');

  // Add event listeners to cart items
  container.querySelectorAll('.cart-item-remove').forEach(btn => {
    btn.addEventListener('click', () => {
      const productId = parseInt(btn.getAttribute('data-product-id'));
      removeFromCart(productId);
    });
  });

  container.querySelectorAll('.quantity-change').forEach(btn => {
    btn.addEventListener('click', () => {
      const productId = parseInt(btn.getAttribute('data-product-id'));
      const change = parseInt(btn.getAttribute('data-change'));
      updateCartItemQuantity(productId, change);
    });
  });
}

function renderCartItems() {
  const container = document.getElementById('cart-items');
  if (!container) return;

  if (app.cart.length === 0) {
    container.innerHTML = '<p>ตะกร้าสินค้าว่างเปล่า</p>';
    return;
  }

  container.innerHTML = app.cart.map(item => createCartItemHTML(item)).join('');

  // Add event listeners to cart items
  container.querySelectorAll('.cart-item-remove').forEach(btn => {
    btn.addEventListener('click', () => {
      const productId = parseInt(btn.getAttribute('data-product-id'));
      removeFromCart(productId);
    });
  });

  container.querySelectorAll('.quantity-change').forEach(btn => {
    btn.addEventListener('click', () => {
      const productId = parseInt(btn.getAttribute('data-product-id'));
      const change = parseInt(btn.getAttribute('data-change'));
      updateCartItemQuantity(productId, change);
    });
  });
}

function createCartItemHTML(item) {
  return `
        <div class="cart-item">
            <div class="cart-item-image">
                <img src="${item.image}" alt="${item.name}">
            </div>
            <div class="cart-item-info">
                <h4 class="cart-item-name">${item.name}</h4>
                <div class="cart-item-price">฿${item.price.toFixed(2)}</div>
                <div class="cart-item-quantity">
                    <button class="quantity-change" data-product-id="${item.id}" data-change="-1">-</button>
                    <span>${item.quantity}</span>
                    <button class="quantity-change" data-product-id="${item.id}" data-change="1">+</button>
                </div>
            </div>
            <button class="cart-item-remove" data-product-id="${item.id}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    `;
}

function updateCartItemQuantity(productId, change) {
  const item = app.cart.find(item => item.id === productId);
  if (!item) return;

  item.quantity += change;

  if (item.quantity <= 0) {
    removeFromCart(productId);
  } else {
    saveCartToStorage();
    updateCartUI();
    renderSidebarCartItems();
    updateSidebarCartTotal();
    renderCartItems();
    updateCartSummary();
  }
}

function removeFromCart(productId) {
  app.cart = app.cart.filter(item => item.id !== productId);
  saveCartToStorage();
  updateCartUI();
  renderSidebarCartItems();
  updateSidebarCartTotal();
  renderCartItems();
  updateCartSummary();
}

function updateCartUI() {
  const totalItems = app.cart.reduce((total, item) => total + item.quantity, 0);
  elements.cartCount.textContent = totalItems;
}

function updateSidebarCartTotal() {
  const total = calculateCartTotal();
  document.getElementById('sidebar-cart-total').textContent = `฿${total.toFixed(2)}`;
}

function updateCartSummary() {
  const subtotal = calculateCartTotal();
  const shipping = 50.00; // Default shipping
  const total = subtotal + shipping;

  document.getElementById('cart-subtotal').textContent = `฿${subtotal.toFixed(2)}`;
  document.getElementById('cart-shipping').textContent = `฿${shipping.toFixed(2)}`;
  document.getElementById('cart-total').textContent = `฿${total.toFixed(2)}`;
}

function calculateCartTotal() {
  return app.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
}

function saveCartToStorage() {
  localStorage.setItem('monochrome_cart', JSON.stringify(app.cart));
}

function loadCartFromStorage() {
  const savedCart = localStorage.getItem('monochrome_cart');
  if (savedCart) {
    app.cart = JSON.parse(savedCart);
  }
}

// Checkout
function renderCheckoutItems() {
  const container = document.getElementById('checkout-items');
  if (!container) return;

  container.innerHTML = app.cart.map(item => `
        <div class="summary-item">
            <div class="summary-item-image">
                <img src="${item.image}" alt="${item.name}">
            </div>
            <div class="summary-item-info">
                <div class="summary-item-name">${item.name}</div>
                <div class="summary-item-price">฿${item.price.toFixed(2)} × ${item.quantity}</div>
            </div>
        </div>
    `).join('');
}

function updateCheckoutSummary() {
  const subtotal = calculateCartTotal();
  const shippingOption = document.querySelector('input[name="shipping"]:checked');
  const shippingCost = shippingOption.value === 'express' ? 100.00 : 50.00;
  const total = subtotal + shippingCost;

  document.getElementById('checkout-subtotal').textContent = `฿${subtotal.toFixed(2)}`;
  document.getElementById('checkout-shipping').textContent = `฿${shippingCost.toFixed(2)}`;
  document.getElementById('checkout-total').textContent = `฿${total.toFixed(2)}`;
}

async function processCheckout(e) {
  e.preventDefault();

  if (app.cart.length === 0) {
    showToast('ตะกร้าสินค้าว่างเปล่า');
    return;
  }

  // Get form data
  const formData = new FormData(e.target);
  const customerInfo = {
    name: formData.get('name') || document.getElementById('checkout-name').value,
    email: formData.get('email') || document.getElementById('checkout-email').value,
    phone: formData.get('phone') || document.getElementById('checkout-phone').value,
    address: formData.get('address') || document.getElementById('checkout-address').value,
    city: formData.get('city') || document.getElementById('checkout-city').value,
    postal: formData.get('postal') || document.getElementById('checkout-postal').value,
    country: formData.get('country') || document.getElementById('checkout-country').value
  };

  const shippingMethod = document.querySelector('input[name="shipping"]:checked').value;
  const paymentMethod = document.querySelector('input[name="payment"]:checked').value;

  const subtotal = calculateCartTotal();
  const shippingCost = shippingMethod === 'express' ? 100.00 : 50.00;
  const total = subtotal + shippingCost;

  // Create order object
  const order = {
    customer: customerInfo,
    items: app.cart,
    shipping: {
      method: shippingMethod,
      cost: shippingCost
    },
    payment: {
      method: paymentMethod,
      status: 'pending'
    },
    subtotal: subtotal,
    total: total,
    status: 'pending',
    date: new Date().toISOString()
  };

  try {
    // Call API to create order
    const response = await fetch('api/orders/create.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(order)
    });

    const result = await response.json();

    if (result.success) {
      // Store order data
      app.orderData = {
        ...order,
        orderNumber: result.orderNumber,
        orderId: result.orderId
      };

      // Clear cart
      app.cart = [];
      saveCartToStorage();
      updateCartUI();

      // Show order confirmation
      showOrderConfirmation();
    } else {
      showToast('ไม่สามารถสั่งซื้อได้ กรุณาลองใหม่อีกครั้ง');
    }
  } catch (error) {
    console.error('Error processing checkout:', error);
    showToast('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
  }
}

function showOrderConfirmation() {
  if (!app.orderData) return;

  // Update confirmation page
  document.getElementById('order-number').textContent = app.orderData.orderNumber;
  document.getElementById('order-date').textContent = new Date(app.orderData.date).toLocaleDateString('th-TH');
  document.getElementById('order-total').textContent = `฿${app.orderData.total.toFixed(2)}`;
  document.getElementById('order-payment').textContent = app.orderData.payment.method === 'promptpay' ? 'พร้อมเพย์ / QR Code' : app.orderData.payment.method;

  // Generate PromptPay QR Code
  if (app.orderData.payment.method === 'promptpay') {
    const promptpayId = '0812345678'; // เบอร์พร้อมเพย์ (ควรดึงจาก API)
    const amount = app.orderData.total.toFixed(2);
    const qrCodeUrl = `https://promptpay.io/${promptpayId}/${amount}.png`;

    const qrCodeImg = document.getElementById('qr-code-image');
    if (qrCodeImg) {
      qrCodeImg.src = qrCodeUrl;
      qrCodeImg.alt = `QR Code ชำระเงิน ฿${amount}`;
    }

    // Update QR amount display
    const qrAmount = document.getElementById('qr-amount');
    if (qrAmount) {
      qrAmount.textContent = `฿${amount}`;
    }
  }

  // Navigate to confirmation page
  navigateToPage('order-confirmation');
}

async function confirmPayment() {
  if (!app.orderData) return;

  try {
    // Call API to confirm payment
    const response = await fetch(`api/orders/{id}.php?id=${app.orderData.orderId}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        action: 'confirm_payment',
        email: app.orderData.customer.email
      })
    });

    const result = await response.json();

    if (result.success) {
      // Hide payment QR section
      document.getElementById('payment-qr').style.display = 'none';

      // Update order status
      app.orderData.payment.status = 'paid';
      app.orderData.status = 'processing';

      showToast('ยืนยันการชำระเงินแล้ว กำลังดำเนินการจัดส่ง');
    } else {
      showToast('ไม่สามารถยืนยันการชำระเงินได้ กรุณาลองใหม่อีกครั้ง');
    }
  } catch (error) {
    console.error('Error confirming payment:', error);
    showToast('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
  }
}

// Contact Form
async function submitContactForm(e) {
  e.preventDefault();

  const contactData = {
    name: document.getElementById('contact-name').value,
    email: document.getElementById('contact-email').value,
    phone: document.getElementById('contact-phone').value,
    subject: document.getElementById('contact-subject').value,
    message: document.getElementById('contact-message').value
  };

  // Validate
  if (!contactData.name || !contactData.email || !contactData.subject || !contactData.message) {
    showToast('กรุณากรอกข้อมูลให้ครบถ้วน');
    return;
  }

  // Show loading
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.textContent;
  submitBtn.textContent = 'กำลังส่ง...';
  submitBtn.disabled = true;

  try {
    const response = await fetch('api/contact.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(contactData)
    });

    const result = await response.json();

    if (response.ok && result.success) {
      showToast('✅ ' + result.message);
      e.target.reset();
    } else {
      showToast('❌ ' + (result.error || 'ไม่สามารถส่งข้อความได้'));
    }
  } catch (error) {
    console.error('Error submitting contact form:', error);
    showToast('❌ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
  } finally {
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
  }
}

// Toast Notifications
function showToast(message) {
  const toast = elements.toast || document.getElementById('toast');
  const toastMessage = elements.toastMessage || document.getElementById('toast-message');

  if (!toast || !toastMessage) {
    console.error('Toast elements not found');
    return;
  }

  toastMessage.textContent = message;
  toast.classList.add('show');

  // Auto hide after 3 seconds
  setTimeout(() => {
    hideToast();
  }, 3000);
}

function hideToast() {
  const toast = elements.toast || document.getElementById('toast');
  if (toast) {
    toast.classList.remove('show');
  }
}