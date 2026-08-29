import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
  Receipt,
  Search,
  ShoppingCart,
  Plus,
  Minus,
  Trash2,
  Printer,
  Sparkles,
  User,
  CreditCard,
  QrCode,
  Banknote,
  Building,
  Clock,
  CheckCircle,
  X,
  AlertCircle,
  Barcode,
} from 'lucide-react';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Input from '../components/ui/Input';
import Badge from '../components/ui/Badge';
import { useAuth } from '../contexts/AuthContext';
import { useLanguage } from '../contexts/LanguageContext';
import { useToast } from '../components/ui/ToastContext';
import apiService from '../services/api';
import './BillingPage.css';

export default function BillingPage() {
  const { currentBusiness, user } = useAuth();
  const { t } = useLanguage();
  const toast = useToast();

  const currencySymbol = currentBusiness?.currency === 'USD' ? '$' : currentBusiness?.currency === 'EUR' ? '€' : '₹';

  // Catalog State
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [selectedCategory, setSelectedCategory] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [loadingProducts, setLoadingProducts] = useState(false);

  // Cart & Invoice Form State
  const [cart, setCart] = useState([]);
  const [customers, setCustomers] = useState([]);
  const [selectedCustomer, setSelectedCustomer] = useState(null);
  const [customerName, setCustomerName] = useState('Walk-in Customer');
  const [customerPhone, setCustomerPhone] = useState('');

  // Financial Inputs
  const [discountType, setDiscountType] = useState('flat'); // flat, percent
  const [discountValue, setDiscountValue] = useState(0);
  const [taxPercent, setTaxPercent] = useState(0);
  const [paymentMethod, setPaymentMethod] = useState('CASH');
  const [amountPaidInput, setAmountPaidInput] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Completed Receipt Modal
  const [completedInvoice, setCompletedInvoice] = useState(null);
  const [isPrintModalOpen, setIsPrintModalOpen] = useState(false);

  // Search input ref for barcode focus
  const searchInputRef = useRef(null);

  // Load Catalog Products
  const fetchProducts = useCallback(async () => {
    setLoadingProducts(true);
    try {
      const res = await apiService.products.getAll({
        search: searchQuery,
        category_id: selectedCategory,
        per_page: 100,
      });
      if (res.success && res.data) {
        setProducts(res.data.products?.data || []);
      }
    } catch (err) {
      toast.error('Failed to load products catalog.');
    } finally {
      setLoadingProducts(false);
    }
  }, [searchQuery, selectedCategory, toast]);

  // Load Categories & Customers
  const fetchAuxData = useCallback(async () => {
    try {
      const [catRes, custRes] = await Promise.all([
        apiService.categories.getAll(),
        apiService.customers.getAll({ per_page: 100 }),
      ]);
      if (catRes.success) setCategories(catRes.data.categories || []);
      if (custRes.success) setCustomers(custRes.data.customers?.data || []);
    } catch (err) {
      console.error('Failed to load aux billing data:', err);
    }
  }, []);

  useEffect(() => {
    fetchAuxData();
  }, [fetchAuxData]);

  useEffect(() => {
    const timer = setTimeout(() => {
      fetchProducts();
    }, 300);
    return () => clearTimeout(timer);
  }, [fetchProducts]);

  // Cart Handlers
  const addToCart = (product) => {
    if (product.stock_quantity <= 0) {
      toast.error(`'${product.name}' is out of stock!`);
      return;
    }

    setCart((prevCart) => {
      const existing = prevCart.find((item) => item.product_id === product.id);
      if (existing) {
        if (existing.quantity >= product.stock_quantity) {
          toast.warning(`Cannot add more. Available stock: ${product.stock_quantity}`);
          return prevCart;
        }
        return prevCart.map((item) =>
          item.product_id === product.id ? { ...item, quantity: item.quantity + 1 } : item
        );
      } else {
        return [
          ...prevCart,
          {
            product_id: product.id,
            name: product.name,
            unit_price: parseFloat(product.selling_price) || 0,
            unit_price_raw: parseFloat(product.selling_price) || 0,
            stock_available: product.stock_quantity,
            quantity: 1,
            unit: product.unit || 'unit',
          },
        ];
      }
    });
  };

  const updateCartQuantity = (productId, newQty) => {
    if (newQty <= 0) {
      removeFromCart(productId);
      return;
    }
    setCart((prev) =>
      prev.map((item) => {
        if (item.product_id === productId) {
          if (newQty > item.stock_available) {
            toast.warning(`Maximum available stock is ${item.stock_available}`);
            return { ...item, quantity: item.stock_available };
          }
          return { ...item, quantity: newQty };
        }
        return item;
      })
    );
  };

  const removeFromCart = (productId) => {
    setCart((prev) => prev.filter((item) => item.product_id !== productId));
  };

  const clearCart = () => {
    setCart([]);
    setSelectedCustomer(null);
    setCustomerName('Walk-in Customer');
    setCustomerPhone('');
    setDiscountValue(0);
    setTaxPercent(0);
    setAmountPaidInput('');
    setPaymentMethod('CASH');
  };

  // Financial Calculations
  const subtotal = cart.reduce((sum, item) => sum + item.quantity * item.unit_price_raw, 0);

  let calculatedDiscount = 0;
  if (discountType === 'percent') {
    calculatedDiscount = (subtotal * Math.min(100, Math.max(0, parseFloat(discountValue) || 0))) / 100;
  } else {
    calculatedDiscount = Math.min(subtotal, Math.max(0, parseFloat(discountValue) || 0));
  }

  const taxableTotal = Math.max(0, subtotal - calculatedDiscount);
  const calculatedTax = (taxableTotal * Math.min(100, Math.max(0, parseFloat(taxPercent) || 0))) / 100;
  const grandTotal = Math.max(0, taxableTotal + calculatedTax);

  const amountPaid = amountPaidInput === '' ? grandTotal : Math.max(0, parseFloat(amountPaidInput) || 0);
  const balanceDue = Math.max(0, grandTotal - amountPaid);

  // Complete Billing Checkout
  const handleCheckout = async () => {
    if (cart.length === 0) {
      toast.error('Cart is empty. Add products before checkout.');
      return;
    }

    setIsSubmitting(true);

    try {
      const payload = {
        customer_id: selectedCustomer ? selectedCustomer.id : null,
        customer_name: customerName,
        customer_phone: customerPhone,
        date: new Date().toISOString().split('T')[0],
        discount_type: discountType,
        discount_value: parseFloat(discountValue) || 0,
        tax_percent: parseFloat(taxPercent) || 0,
        amount_paid: amountPaid,
        payment_method: paymentMethod,
        items: cart.map((item) => ({
          product_id: item.product_id,
          quantity: item.quantity,
          unit_price: item.unit_price_raw,
        })),
      };

      const res = await apiService.invoices.create(payload);

      if (res.success && res.data?.invoice) {
        toast.success(`Bill #${res.data.invoice.invoice_number} completed!`);
        setCompletedInvoice(res.data.invoice);
        setIsPrintModalOpen(true);
        clearCart();
        fetchProducts(); // Refresh stock levels in catalog
      } else {
        toast.error(res.message || 'Could not complete checkout.');
      }
    } catch (err) {
      toast.error(err.message || 'Validation error during checkout.');
    } finally {
      setIsSubmitting(false);
    }
  };

  // Select existing customer
  const handleCustomerSelect = (customer) => {
    if (customer) {
      setSelectedCustomer(customer);
      setCustomerName(customer.name);
      setCustomerPhone(customer.phone || '');
    } else {
      setSelectedCustomer(null);
      setCustomerName('Walk-in Customer');
      setCustomerPhone('');
    }
  };

  // Thermal Receipt Print Handler
  const handlePrintInvoice = () => {
    window.print();
  };

  return (
    <div className="billing-page">
      {/* POS Top Bar Header */}
      <div className="pos-header-bar">
        <div className="pos-header-info">
          <Receipt size={24} className="text-primary" />
          <div>
            <h2 className="pos-title">{t('billing.pos_title')}</h2>
            <p className="pos-subtitle">{t('billing.pos_subtitle')} • {currentBusiness?.name}</p>
          </div>
        </div>

        <div className="pos-header-actions">
          <Badge variant="primary" size="md">
            <Sparkles size={14} /> Fast Mode Active
          </Badge>
        </div>
      </div>

      {/* Split Billing Screen Container */}
      <div className="pos-split-container">
        {/* Left Side: Product Catalog Search */}
        <div className="catalog-panel">
          <Card className="catalog-card">
            {/* Search Input Bar */}
            <div className="catalog-search-bar">
              <div className="search-input-wrapper">
                <Search size={18} className="search-icon" />
                <input
                  ref={searchInputRef}
                  type="text"
                  placeholder={t('billing.search_catalog')}
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

              {/* Category Pills Bar */}
              <div className="category-pills-bar">
                <button
                  className={`category-pill ${selectedCategory === '' ? 'pill-active' : ''}`}
                  onClick={() => setSelectedCategory('')}
                >
                  {t('billing.all_categories')}
                </button>
                {categories.map((cat) => (
                  <button
                    key={cat.id}
                    className={`category-pill ${selectedCategory === cat.id ? 'pill-active' : ''}`}
                    onClick={() => setSelectedCategory(cat.id)}
                  >
                    {cat.name}
                  </button>
                ))}
              </div>
            </div>

            {/* Product Cards Grid */}
            <div className="catalog-grid-scroll">
              {loadingProducts ? (
                <div className="catalog-loading">{t('common.loading')}</div>
              ) : products.length === 0 ? (
                <div className="catalog-empty">No products found matching query.</div>
              ) : (
                <div className="products-card-grid">
                  {products.map((prod) => (
                    <div
                      key={prod.id}
                      className={`pos-product-card ${prod.stock_quantity <= 0 ? 'prod-disabled' : ''}`}
                      onClick={() => addToCart(prod)}
                    >
                      <div className="product-card-top">
                        <span className="product-card-name">{prod.name}</span>
                        {prod.sku && <span className="product-card-sku">SKU: {prod.sku}</span>}
                      </div>

                      <div className="product-card-bottom">
                        <span className="product-card-price">
                          {currencySymbol}{prod.selling_price ? prod.selling_price.toLocaleString() : '0'}
                        </span>
                        <span className={`product-stock-tag ${prod.is_low_stock ? 'tag-warning' : 'tag-normal'}`}>
                          {prod.stock_quantity} {prod.unit}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </Card>
        </div>

        {/* Right Side: Live Checkout Cart & Receipt Builder */}
        <div className="checkout-panel">
          <Card className="checkout-card">
            {/* Customer Selector Bar */}
            <div className="customer-selector-section">
              <div className="customer-input-header">
                <User size={16} className="text-primary" />
                <span className="font-semibold text-xs text-slate-700">Customer Details</span>
              </div>

              <div className="customer-select-row">
                <select
                  className="custom-select-sm"
                  value={selectedCustomer ? selectedCustomer.id : ''}
                  onChange={(e) => {
                    const found = customers.find((c) => c.id === parseInt(e.target.value));
                    handleCustomerSelect(found || null);
                  }}
                >
                  <option value="">-- {t('billing.walk_in_customer')} --</option>
                  {customers.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.name} ({c.phone || 'No phone'})
                    </option>
                  ))}
                </select>
              </div>

              {!selectedCustomer && (
                <div className="customer-name-inputs">
                  <input
                    type="text"
                    placeholder="Customer Name"
                    value={customerName}
                    onChange={(e) => setCustomerName(e.target.value)}
                    className="input-xs"
                  />
                  <input
                    type="text"
                    placeholder="Phone (WhatsApp)"
                    value={customerPhone}
                    onChange={(e) => setCustomerPhone(e.target.value)}
                    className="input-xs"
                  />
                </div>
              )}
            </div>

            {/* Cart Items Stream */}
            <div className="cart-items-section">
              <div className="cart-header">
                <span className="cart-title">
                  <ShoppingCart size={16} /> {t('billing.cart_title')} ({cart.length} {t('billing.items_count')})
                </span>
                {cart.length > 0 && (
                  <button className="clear-cart-link" onClick={clearCart}>
                    <Trash2 size={14} /> {t('billing.clear_cart')}
                  </button>
                )}
              </div>

              <div className="cart-items-scroll">
                {cart.length === 0 ? (
                  <div className="cart-empty-placeholder">
                    <ShoppingCart size={32} className="empty-cart-icon" />
                    <p>{t('billing.cart_empty')}</p>
                  </div>
                ) : (
                  cart.map((item) => (
                    <div key={item.product_id} className="cart-item-row">
                      <div className="cart-item-info">
                        <span className="cart-item-name">{item.name}</span>
                        <span className="cart-item-unit-price">
                          {currencySymbol}{item.unit_price_raw} / {item.unit}
                        </span>
                      </div>

                      <div className="cart-item-controls">
                        <div className="qty-counter">
                          <button onClick={() => updateCartQuantity(item.product_id, item.quantity - 1)}>
                            <Minus size={12} />
                          </button>
                          <span>{item.quantity}</span>
                          <button onClick={() => updateCartQuantity(item.product_id, item.quantity + 1)}>
                            <Plus size={12} />
                          </button>
                        </div>

                        <span className="cart-item-total">
                          {currencySymbol}{(item.quantity * item.unit_price_raw).toFixed(2)}
                        </span>

                        <button className="remove-item-btn" onClick={() => removeFromCart(item.product_id)}>
                          <X size={14} />
                        </button>
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>

            {/* Financial Summary & Checkout Options */}
            <div className="cart-financials-section">
              <div className="financial-row">
                <span>{t('billing.subtotal')}</span>
                <span className="font-semibold">{currencySymbol}{subtotal.toFixed(2)}</span>
              </div>

              {/* Discount Input Row */}
              <div className="financial-row discount-row">
                <span className="text-xs text-slate-600">{t('billing.discount')}</span>
                <div className="discount-inputs">
                  <select
                    value={discountType}
                    onChange={(e) => setDiscountType(e.target.value)}
                    className="select-mini"
                  >
                    <option value="flat">{currencySymbol} Flat</option>
                    <option value="percent">% Off</option>
                  </select>
                  <input
                    type="number"
                    min="0"
                    value={discountValue}
                    onChange={(e) => setDiscountValue(e.target.value)}
                    className="input-mini"
                  />
                </div>
              </div>

              {/* Grand Total Bar */}
              <div className="financial-row grand-total-row">
                <span className="grand-total-label">{t('billing.grand_total')}</span>
                <span className="grand-total-amount">{currencySymbol}{grandTotal.toFixed(2)}</span>
              </div>

              {/* Payment Method Selector Grid */}
              <div className="payment-methods-grid">
                <button
                  type="button"
                  className={`pm-btn ${paymentMethod === 'CASH' ? 'pm-active' : ''}`}
                  onClick={() => setPaymentMethod('CASH')}
                >
                  <Banknote size={16} /> {t('billing.cash')}
                </button>
                <button
                  type="button"
                  className={`pm-btn ${paymentMethod === 'UPI' ? 'pm-active' : ''}`}
                  onClick={() => setPaymentMethod('UPI')}
                >
                  <QrCode size={16} /> {t('billing.upi')}
                </button>
                <button
                  type="button"
                  className={`pm-btn ${paymentMethod === 'CARD' ? 'pm-active' : ''}`}
                  onClick={() => setPaymentMethod('CARD')}
                >
                  <CreditCard size={16} /> {t('billing.card')}
                </button>
                <button
                  type="button"
                  className={`pm-btn ${paymentMethod === 'CREDIT' ? 'pm-active' : ''}`}
                  onClick={() => setPaymentMethod('CREDIT')}
                >
                  <Clock size={16} /> {t('billing.credit')}
                </button>
              </div>

              {/* Checkout Action Button */}
              <Button
                variant="primary"
                size="lg"
                className="checkout-btn"
                disabled={cart.length === 0}
                isLoading={isSubmitting}
                onClick={handleCheckout}
              >
                {t('billing.complete_billing')} ({currencySymbol}{grandTotal.toFixed(2)})
              </Button>
            </div>
          </Card>
        </div>
      </div>

      {/* Completed Thermal Receipt Modal */}
      {isPrintModalOpen && completedInvoice && (
        <div className="receipt-modal-backdrop">
          <div className="receipt-modal-container">
            {/* Printable Receipt Paper */}
            <div className="printable-receipt-paper" id="printable-receipt">
              <div className="receipt-header-center">
                <h3 className="receipt-shop-name">{currentBusiness?.name}</h3>
                <p className="receipt-shop-info">{currentBusiness?.address || 'Main Market Store'}</p>
                <p className="receipt-shop-info">Phone: {currentBusiness?.phone || 'N/A'}</p>
                <div className="receipt-divider" />
                <h4 className="receipt-bill-number">TAX INVOICE #{completedInvoice.invoice_number}</h4>
                <p className="receipt-date">Date: {completedInvoice.date}</p>
                <p className="receipt-date">Customer: {completedInvoice.customer_name}</p>
              </div>

              <div className="receipt-divider" />

              {/* Receipt Items Table */}
              <table className="receipt-table">
                <thead>
                  <tr>
                    <th style={{ textAlign: 'left' }}>Item</th>
                    <th style={{ textAlign: 'center' }}>Qty</th>
                    <th style={{ textAlign: 'right' }}>Price</th>
                    <th style={{ textAlign: 'right' }}>Total</th>
                  </tr>
                </thead>
                <tbody>
                  {completedInvoice.items?.map((item, idx) => (
                    <tr key={idx}>
                      <td style={{ textAlign: 'left' }}>{item.product_name}</td>
                      <td style={{ textAlign: 'center' }}>{item.quantity}</td>
                      <td style={{ textAlign: 'right' }}>{item.unit_price}</td>
                      <td style={{ textAlign: 'right' }}>{item.total}</td>
                    </tr>
                  ))}
                </tbody>
              </table>

              <div className="receipt-divider" />

              {/* Financial Totals */}
              <div className="receipt-totals-block">
                <div className="receipt-total-row">
                  <span>Subtotal:</span>
                  <span>{currencySymbol}{completedInvoice.subtotal}</span>
                </div>
                {completedInvoice.discount_amount > 0 && (
                  <div className="receipt-total-row">
                    <span>Discount:</span>
                    <span>-{currencySymbol}{completedInvoice.discount_amount}</span>
                  </div>
                )}
                <div className="receipt-total-row receipt-grand-total">
                  <span>GRAND TOTAL:</span>
                  <span>{currencySymbol}{completedInvoice.grand_total}</span>
                </div>
                <div className="receipt-total-row">
                  <span>Paid ({completedInvoice.payment_method}):</span>
                  <span>{currencySymbol}{completedInvoice.amount_paid}</span>
                </div>
                {completedInvoice.balance_due > 0 && (
                  <div className="receipt-total-row text-danger font-bold">
                    <span>BALANCE DUE:</span>
                    <span>{currencySymbol}{completedInvoice.balance_due}</span>
                  </div>
                )}
              </div>

              <div className="receipt-divider" />
              <div className="receipt-footer-center">
                <p>Thank you for your business!</p>
                <span className="text-xs text-muted">Powered by AI Business Copilot</span>
              </div>
            </div>

            {/* Print & WhatsApp Action Triggers */}
            <div className="receipt-modal-actions no-print" style={{ marginTop: '24px', display: 'flex', justifyContent: 'flex-end', gap: '12px' }}>
              <Button variant="ghost" onClick={() => setIsPrintModalOpen(false)}>
                {t('common.close')}
              </Button>
              <Button
                variant="secondary"
                icon={Sparkles}
                onClick={async () => {
                  try {
                    const res = await apiService.whatsapp.getInvoiceLink(completedInvoice.id);
                    if (res.success && res.data?.whatsapp_url) {
                      window.open(res.data.whatsapp_url, '_blank');
                    }
                  } catch (err) {
                    toast.error('Could not generate WhatsApp link.');
                  }
                }}
              >
                {t('billing.share_whatsapp')}
              </Button>
              <Button variant="primary" icon={Printer} onClick={handlePrintInvoice}>
                {t('billing.print_receipt')}
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
