import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
  Search,
  ShoppingCart,
  Plus,
  Minus,
  Trash2,
  UserCheck,
  CreditCard,
  Banknote,
  QrCode,
  Calendar,
  CheckCircle,
  Printer,
  Sparkles,
  X,
  Tag,
  Receipt,
  User,
  ArrowRight,
} from 'lucide-react';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Badge from '../components/ui/Badge';
import Input from '../components/ui/Input';
import Modal from '../components/ui/Modal';
import { useAuth } from '../contexts/AuthContext';
import { useLanguage } from '../contexts/LanguageContext';
import { useToast } from '../components/ui/ToastContext';
import apiService from '../services/api';
import './BillingPage.css';

export default function BillingPage() {
  const { currentBusiness } = useAuth();
  const { t } = useLanguage();
  const toast = useToast();
  const searchInputRef = useRef(null);

  const currencySymbol = currentBusiness?.currency === 'USD' ? '$' : currentBusiness?.currency === 'EUR' ? '€' : '₹';

  // Catalog State
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [selectedCategory, setSelectedCategory] = useState('ALL');
  const [searchQuery, setSearchQuery] = useState('');
  const [loadingProducts, setLoadingProducts] = useState(true);

  // Customer State
  const [customers, setCustomers] = useState([]);
  const [selectedCustomerId, setSelectedCustomerId] = useState('');
  const [guestName, setGuestName] = useState('');
  const [guestPhone, setGuestPhone] = useState('');

  // Cart & Checkout State
  const [cart, setCart] = useState([]);
  const [discountType, setDiscountType] = useState('FIXED'); // 'FIXED' or 'PERCENT'
  const [discountValue, setDiscountValue] = useState(0);
  const [taxRate, setTaxRate] = useState(currentBusiness?.tax_rate || 5);
  const [paymentMethod, setPaymentMethod] = useState('cash'); // cash, upi, card, credit
  const [notes, setNotes] = useState('');
  const [submittingInvoice, setSubmittingInvoice] = useState(false);

  // Receipt Modal State
  const [createdInvoice, setCreatedInvoice] = useState(null);
  const [showReceiptModal, setShowReceiptModal] = useState(false);

  // Load Products & Customers
  const loadPOSData = useCallback(async () => {
    setLoadingProducts(true);
    try {
      const [prodRes, custRes] = await Promise.all([
        apiService.products.getAll(),
        apiService.customers.getAll(),
      ]);

      if (prodRes.success && prodRes.data) {
        const prodList = Array.isArray(prodRes.data?.products?.data)
          ? prodRes.data.products.data
          : Array.isArray(prodRes.data?.products)
          ? prodRes.data.products
          : Array.isArray(prodRes.data)
          ? prodRes.data
          : [];
        setProducts(prodList);
        // Extract unique category names safely (handles string or object categories)
        const catNames = prodList
          .map((p) => (typeof p.category === 'object' && p.category !== null ? p.category.name : p.category))
          .filter(Boolean);
        const cats = Array.from(new Set(catNames));
        setCategories(cats);
      }

      if (custRes.success && custRes.data) {
        const custList = Array.isArray(custRes.data?.customers?.data)
          ? custRes.data.customers.data
          : Array.isArray(custRes.data?.customers)
          ? custRes.data.customers
          : Array.isArray(custRes.data)
          ? custRes.data
          : [];
        setCustomers(custList);
      }
    } catch (err) {
      toast.error('Failed to load POS data.');
    } finally {
      setLoadingProducts(false);
    }
  }, [toast]);

  useEffect(() => {
    loadPOSData();
  }, [loadPOSData]);

  // Focus search box on load
  useEffect(() => {
    searchInputRef.current?.focus();
  }, []);

  // Filtered Products Catalog
  const safeProductsList = Array.isArray(products) ? products : [];
  const filteredProducts = safeProductsList.filter((p) => {
    const pCatName = typeof p.category === 'object' && p.category !== null ? p.category.name : p.category;
    const matchesCategory = selectedCategory === 'ALL' || pCatName === selectedCategory || p.category_id === selectedCategory;
    const matchesSearch =
      (p.name && p.name.toLowerCase().includes(searchQuery.toLowerCase())) ||
      (p.sku && p.sku.toLowerCase().includes(searchQuery.toLowerCase())) ||
      (p.barcode && p.barcode.toLowerCase().includes(searchQuery.toLowerCase()));
    return matchesCategory && matchesSearch;
  });

  // Cart Operations
  const addToCart = (product) => {
    if (product.stock_quantity <= 0) {
      toast.warning(`${product.name} is currently out of stock.`);
      return;
    }

    setCart((prev) => {
      const existing = prev.find((item) => item.product_id === product.id);
      if (existing) {
        if (existing.quantity >= product.stock_quantity) {
          toast.warning(`Cannot exceed available stock (${product.stock_quantity}).`);
          return prev;
        }
        return prev.map((item) =>
          item.product_id === product.id ? { ...item, quantity: item.quantity + 1 } : item
        );
      }
      return [
        ...prev,
        {
          product_id: product.id,
          name: product.name,
          unit_price: parseFloat(product.selling_price),
          quantity: 1,
          max_stock: product.stock_quantity,
        },
      ];
    });
  };

  const updateQuantity = (productId, delta) => {
    setCart((prev) =>
      prev
        .map((item) => {
          if (item.product_id === productId) {
            const newQty = item.quantity + delta;
            if (newQty <= 0) return null;
            if (newQty > item.max_stock) {
              toast.warning(`Maximum available stock reached.`);
              return item;
            }
            return { ...item, quantity: newQty };
          }
          return item;
        })
        .filter(Boolean)
    );
  };

  const removeFromCart = (productId) => {
    setCart((prev) => prev.filter((item) => item.product_id !== productId));
  };

  const clearCart = () => {
    setCart([]);
    setDiscountValue(0);
    setNotes('');
  };

  // Calculation Math
  const subtotal = cart.reduce((acc, item) => acc + item.unit_price * item.quantity, 0);
  
  let calculatedDiscount = 0;
  if (discountType === 'FIXED') {
    calculatedDiscount = Math.min(subtotal, parseFloat(discountValue) || 0);
  } else {
    calculatedDiscount = (subtotal * (parseFloat(discountValue) || 0)) / 100;
  }

  const taxableAmount = Math.max(0, subtotal - calculatedDiscount);
  const taxAmount = (taxableAmount * (parseFloat(taxRate) || 0)) / 100;
  const grandTotal = Math.round(taxableAmount + taxAmount);

  // Submit Invoice Handler
  const handleCheckout = async () => {
    if (cart.length === 0) {
      toast.error('Cart is empty. Add products to create a bill.');
      return;
    }

    setSubmittingInvoice(true);
    try {
      const selectedCustomer = customers.find((c) => c.id === parseInt(selectedCustomerId));

      const payload = {
        customer_id: selectedCustomerId ? parseInt(selectedCustomerId) : null,
        customer_name: selectedCustomer ? selectedCustomer.name : guestName || 'Walk-in Customer',
        customer_phone: selectedCustomer ? selectedCustomer.phone : guestPhone || '',
        items: cart.map((item) => ({
          product_id: item.product_id,
          name: item.name,
          quantity: item.quantity,
          unit_price: item.unit_price,
        })),
        subtotal,
        discount_amount: calculatedDiscount,
        tax_amount: taxAmount,
        total_amount: grandTotal,
        payment_method: paymentMethod,
        notes,
      };

      const res = await apiService.billing.create(payload);
      if (res.success && res.data) {
        toast.success(`Bill #${res.data.invoice_number} created successfully!`);
        setCreatedInvoice(res.data);
        setShowReceiptModal(true);
        clearCart();
        loadPOSData(); // Reload inventory stock
      }
    } catch (err) {
      toast.error('Failed to issue bill.');
    } finally {
      setSubmittingInvoice(false);
    }
  };

  return (
    <div className="billing-page animate-fade-in">
      {/* POS Top Header Bar */}
      <div className="pos-header-bar glass-card">
        <div className="pos-header-info">
          <Receipt size={22} className="text-primary" />
          <div>
            <h2 className="pos-title">{t('billing.pos_title')}</h2>
            <span className="pos-subtitle">Fast counter checkout & inventory deduction</span>
          </div>
        </div>
        <div className="banner-actions">
          <Button variant="outline" size="sm" icon={Trash2} onClick={clearCart} disabled={cart.length === 0}>
            Clear Cart
          </Button>
        </div>
      </div>

      {/* Main Split Layout: Catalog Left vs Checkout Right */}
      <div className="pos-split-container">
        {/* LEFT PANEL: Products Catalog */}
        <div className="catalog-panel">
          <Card className="catalog-card glass-card" padding="none">
            {/* Catalog Search & Category Filters */}
            <div className="catalog-search-bar">
              <div className="search-input-wrapper">
                <Search size={18} className="search-icon" />
                <input
                  ref={searchInputRef}
                  type="text"
                  placeholder="Search products by name, SKU, barcode..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="catalog-search-input"
                />
                {searchQuery && (
                  <button className="clear-search-btn" onClick={() => setSearchQuery('')}>
                    <X size={16} />
                  </button>
                )}
              </div>

              {/* Categories Scroll Pills */}
              <div className="category-pills-bar">
                <button
                  className={`category-pill ${selectedCategory === 'ALL' ? 'pill-active' : ''}`}
                  onClick={() => setSelectedCategory('ALL')}
                >
                  All Items ({products.length})
                </button>
                {categories.map((cat, idx) => {
                  const catLabel = typeof cat === 'object' && cat !== null ? cat.name || String(cat.id) : String(cat);
                  const catKey = typeof cat === 'object' && cat !== null ? cat.id || cat.name || idx : cat;
                  return (
                    <button
                      key={catKey}
                      className={`category-pill ${selectedCategory === catLabel ? 'pill-active' : ''}`}
                      onClick={() => setSelectedCategory(catLabel)}
                    >
                      {catLabel}
                    </button>
                  );
                })}
              </div>
            </div>

            {/* Products Grid */}
            <div className="catalog-grid-scroll">
              {loadingProducts ? (
                <div className="catalog-loading">Loading product catalog...</div>
              ) : filteredProducts.length === 0 ? (
                <div className="catalog-empty">No products match your search filter.</div>
              ) : (
                <div className="products-card-grid">
                  {filteredProducts.map((prod) => {
                    const isOutOfStock = prod.stock_quantity <= 0;
                    return (
                      <div
                        key={prod.id}
                        className={`pos-product-card ${isOutOfStock ? 'prod-disabled' : ''}`}
                        onClick={() => !isOutOfStock && addToCart(prod)}
                      >
                        <div className="product-card-top">
                          <span className="product-card-name">{prod.name}</span>
                          {prod.sku && <span className="product-card-sku">SKU: {prod.sku}</span>}
                        </div>
                        <div className="product-card-bottom">
                          <span className="product-card-price">{currencySymbol}{parseFloat(prod.selling_price).toLocaleString()}</span>
                          <span className={`product-stock-tag ${isOutOfStock ? 'tag-warning' : 'tag-normal'}`}>
                            {isOutOfStock ? 'Out of Stock' : `${prod.stock_quantity} left`}
                          </span>
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          </Card>
        </div>

        {/* RIGHT PANEL: Cart & Financials */}
        <div className="checkout-panel">
          <Card className="checkout-card glass-card" padding="compact">
            {/* Customer Select Row */}
            <div className="customer-selector-section">
              <div className="customer-input-header">
                <User size={16} className="text-muted" />
                <span className="input-label">Select Customer:</span>
              </div>
              <div className="customer-select-row">
                <select
                  value={selectedCustomerId}
                  onChange={(e) => setSelectedCustomerId(e.target.value)}
                  className="custom-select-sm"
                >
                  <option value="">-- Walk-in Guest --</option>
                  {customers.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.name} ({c.phone || 'No phone'})
                    </option>
                  ))}
                </select>
              </div>

              {!selectedCustomerId && (
                <div className="customer-name-inputs">
                  <input
                    type="text"
                    placeholder="Guest Name (Optional)"
                    value={guestName}
                    onChange={(e) => setGuestName(e.target.value)}
                    className="input-xs"
                  />
                  <input
                    type="text"
                    placeholder="Phone (Optional)"
                    value={guestPhone}
                    onChange={(e) => setGuestPhone(e.target.value)}
                    className="input-xs"
                  />
                </div>
              )}
            </div>

            {/* Cart Items List */}
            <div className="cart-items-section">
              <div className="cart-header">
                <span className="cart-title">
                  <ShoppingCart size={16} /> Bill Cart ({cart.reduce((a, b) => a + b.quantity, 0)})
                </span>
                {cart.length > 0 && (
                  <button className="clear-cart-link" onClick={clearCart}>
                    Clear
                  </button>
                )}
              </div>

              <div className="cart-items-scroll">
                {cart.length === 0 ? (
                  <div className="cart-empty-placeholder">
                    <ShoppingCart size={28} />
                    <span>Cart is empty. Tap items on left to add.</span>
                  </div>
                ) : (
                  cart.map((item) => (
                    <div key={item.product_id} className="cart-item-row">
                      <div className="cart-item-info">
                        <span className="cart-item-name">{item.name}</span>
                        <span className="cart-item-unit-price">{currencySymbol}{item.unit_price.toLocaleString()} each</span>
                      </div>

                      <div className="cart-item-controls">
                        <div className="qty-counter">
                          <button onClick={() => updateQuantity(item.product_id, -1)}>
                            <Minus size={12} />
                          </button>
                          <span>{item.quantity}</span>
                          <button onClick={() => updateQuantity(item.product_id, 1)}>
                            <Plus size={12} />
                          </button>
                        </div>
                        <span className="cart-item-total">{currencySymbol}{(item.unit_price * item.quantity).toLocaleString()}</span>
                        <button className="remove-item-btn" onClick={() => removeFromCart(item.product_id)}>
                          <X size={14} />
                        </button>
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>

            {/* Cart Financial Summary & Checkout */}
            <div className="cart-financials-section">
              <div className="financial-row">
                <span>Subtotal:</span>
                <span>{currencySymbol}{subtotal.toLocaleString()}</span>
              </div>

              <div className="financial-row">
                <span>Discount:</span>
                <div className="discount-inputs">
                  <select
                    value={discountType}
                    onChange={(e) => setDiscountType(e.target.value)}
                    className="select-mini"
                  >
                    <option value="FIXED">{currencySymbol}</option>
                    <option value="PERCENT">%</option>
                  </select>
                  <input
                    type="number"
                    value={discountValue}
                    onChange={(e) => setDiscountValue(e.target.value)}
                    className="input-mini"
                    min="0"
                  />
                </div>
              </div>

              <div className="financial-row">
                <span>GST/Tax ({taxRate}%):</span>
                <span>{currencySymbol}{taxAmount.toLocaleString(undefined, { maximumFractionDigits: 2 })}</span>
              </div>

              {/* Grand Total Highlight */}
              <div className="grand-total-row">
                <span className="grand-total-label">Net Payable Amount</span>
                <span className="grand-total-amount">{currencySymbol}{grandTotal.toLocaleString()}</span>
              </div>

              {/* Payment Method Tabs */}
              <div className="payment-methods-grid">
                <button
                  className={`pm-btn ${paymentMethod === 'cash' ? 'pm-active' : ''}`}
                  onClick={() => setPaymentMethod('cash')}
                >
                  <Banknote size={16} /> Cash
                </button>
                <button
                  className={`pm-btn ${paymentMethod === 'upi' ? 'pm-active' : ''}`}
                  onClick={() => setPaymentMethod('upi')}
                >
                  <QrCode size={16} /> UPI
                </button>
                <button
                  className={`pm-btn ${paymentMethod === 'card' ? 'pm-active' : ''}`}
                  onClick={() => setPaymentMethod('card')}
                >
                  <CreditCard size={16} /> Card
                </button>
                <button
                  className={`pm-btn ${paymentMethod === 'credit' ? 'pm-active' : ''}`}
                  onClick={() => setPaymentMethod('credit')}
                >
                  <UserCheck size={16} /> Debt/Udhar
                </button>
              </div>

              <Button
                variant="primary"
                size="lg"
                className="checkout-btn"
                disabled={cart.length === 0}
                isLoading={submittingInvoice}
                onClick={handleCheckout}
              >
                Complete Bill ({currencySymbol}{grandTotal.toLocaleString()})
              </Button>
            </div>
          </Card>
        </div>
      </div>

      {/* Thermal Receipt Print Modal */}
      {showReceiptModal && createdInvoice && (
        <div className="receipt-modal-backdrop animate-fade-in" onClick={() => setShowReceiptModal(false)}>
          <div className="receipt-modal-container glass-card" onClick={(e) => e.stopPropagation()}>
            <div className="printable-receipt-paper" id="printable-receipt">
              <div className="receipt-header-center">
                <div className="receipt-shop-name">{currentBusiness?.name || 'STORE RECEIPT'}</div>
                <div className="receipt-shop-info">{currentBusiness?.address || 'Tax Invoice / Cash Memo'}</div>
                <div className="receipt-bill-number">Bill #{createdInvoice.invoice_number}</div>
                <div className="receipt-date">{createdInvoice.date}</div>
              </div>

              <div className="receipt-divider" />

              <table className="receipt-table">
                <thead>
                  <tr>
                    <th style={{ textAlign: 'left' }}>Item</th>
                    <th style={{ textAlign: 'center' }}>Qty</th>
                    <th style={{ textAlign: 'right' }}>Total</th>
                  </tr>
                </thead>
                <tbody>
                  {createdInvoice.items.map((item, idx) => (
                    <tr key={idx}>
                      <td>{item.name}</td>
                      <td style={{ textAlign: 'center' }}>{item.quantity}</td>
                      <td style={{ textAlign: 'right' }}>{currencySymbol}{(item.quantity * item.unit_price).toLocaleString()}</td>
                    </tr>
                  ))}
                </tbody>
              </table>

              <div className="receipt-divider" />

              <div className="receipt-totals-block">
                <div className="receipt-total-row">
                  <span>Subtotal:</span>
                  <span>{currencySymbol}{createdInvoice.subtotal}</span>
                </div>
                {createdInvoice.discount_amount > 0 && (
                  <div className="receipt-total-row">
                    <span>Discount:</span>
                    <span>-{currencySymbol}{createdInvoice.discount_amount}</span>
                  </div>
                )}
                <div className="receipt-total-row">
                  <span>Tax:</span>
                  <span>{currencySymbol}{createdInvoice.tax_amount}</span>
                </div>
                <div className="receipt-total-row receipt-grand-total">
                  <span>TOTAL PAID ({createdInvoice.payment_method.toUpperCase()}):</span>
                  <span>{currencySymbol}{createdInvoice.total_amount}</span>
                </div>
              </div>

              <div className="receipt-footer-center" style={{ marginTop: '12px', fontSize: '10px' }}>
                Thank you for your visit! Powered by AI Copilot.
              </div>
            </div>

            <div style={{ display: 'flex', gap: '10px', marginTop: '16px' }}>
              <Button variant="secondary" size="md" className="btn-full" onClick={() => setShowReceiptModal(false)}>
                Done / New Bill
              </Button>
              <Button variant="primary" size="md" icon={Printer} className="btn-full" onClick={() => window.print()}>
                Print Bill
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
