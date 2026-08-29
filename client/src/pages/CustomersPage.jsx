import React, { useState, useEffect, useCallback } from 'react';
import {
  Users,
  UserPlus,
  Search,
  DollarSign,
  Bell,
  FileText,
  Phone,
  Calendar,
  Sparkles,
  Edit,
  Trash2,
  TrendingUp,
  AlertCircle,
} from 'lucide-react';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Input from '../components/ui/Input';
import Badge from '../components/ui/Badge';
import Modal from '../components/ui/Modal';
import { useAuth } from '../contexts/AuthContext';
import { useLanguage } from '../contexts/LanguageContext';
import { useToast } from '../components/ui/ToastContext';
import apiService from '../services/api';
import './CustomersPage.css';

export default function CustomersPage() {
  const { currentBusiness } = useAuth();
  const { t } = useLanguage();
  const toast = useToast();

  const currencySymbol = currentBusiness?.currency === 'USD' ? '$' : currentBusiness?.currency === 'EUR' ? '€' : '₹';

  // Customers State
  const [customers, setCustomers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [debtorsFilter, setDebtorsFilter] = useState(false);

  // Summary Metrics
  const [totalCustomers, setTotalCustomers] = useState(0);
  const [totalOutstanding, setTotalOutstanding] = useState(0);

  // Add / Edit Customer Modal State
  const [isCustomerModalOpen, setIsCustomerModalOpen] = useState(false);
  const [editingCustomer, setEditingCustomer] = useState(null);
  const [submittingCustomer, setSubmittingCustomer] = useState(false);

  const [customerFormData, setCustomerFormData] = useState({
    name: '',
    phone: '',
    email: '',
    address: '',
    opening_balance: '0',
    notes: '',
  });

  // Collect Payment Modal State
  const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false);
  const [paymentCustomer, setPaymentCustomer] = useState(null);
  const [paymentFormData, setPaymentFormData] = useState({
    amount: '',
    payment_method: 'CASH',
    reference_no: '',
    notes: '',
  });
  const [submittingPayment, setSubmittingPayment] = useState(false);

  // Schedule Reminder Modal State
  const [isReminderModalOpen, setIsReminderModalOpen] = useState(false);
  const [reminderCustomer, setReminderCustomer] = useState(null);
  const [reminderFormData, setReminderFormData] = useState({
    reminder_date: '',
    amount: '',
    notes: '',
  });
  const [submittingReminder, setSubmittingReminder] = useState(false);

  // Ledger Drawer State
  const [isLedgerDrawerOpen, setIsLedgerDrawerOpen] = useState(false);
  const [ledgerCustomer, setLedgerCustomer] = useState(null);
  const [ledgerData, setLedgerData] = useState(null);
  const [loadingLedger, setLoadingLedger] = useState(false);

  // Fetch Customers
  const fetchCustomers = useCallback(async () => {
    setLoading(true);
    try {
      const res = await apiService.customers.getAll({
        search: searchQuery,
        debtors_only: debtorsFilter ? 'true' : 'false',
        per_page: 50,
      });

      if (res.success && res.data) {
        setCustomers(res.data.customers?.data || []);
        setTotalCustomers(res.data.total_customers || 0);
        setTotalOutstanding(res.data.total_outstanding || 0);
      }
    } catch (err) {
      toast.error('Failed to load customer directory.');
    } finally {
      setLoading(false);
    }
  }, [searchQuery, debtorsFilter, toast]);

  useEffect(() => {
    const timer = setTimeout(() => {
      fetchCustomers();
    }, 300);
    return () => clearTimeout(timer);
  }, [fetchCustomers]);

  // Open Customer Modal
  const openCustomerModal = (customer = null) => {
    if (customer) {
      setEditingCustomer(customer);
      setCustomerFormData({
        name: customer.name,
        phone: customer.phone || '',
        email: customer.email || '',
        address: customer.address || '',
        opening_balance: customer.opening_balance || '0',
        notes: customer.notes || '',
      });
    } else {
      setEditingCustomer(null);
      setCustomerFormData({
        name: '',
        phone: '',
        email: '',
        address: '',
        opening_balance: '0',
        notes: '',
      });
    }
    setIsCustomerModalOpen(true);
  };

  // Submit Customer Form
  const handleCustomerSubmit = async (e) => {
    e.preventDefault();
    setSubmittingCustomer(true);

    try {
      if (editingCustomer) {
        const res = await apiService.customers.update(editingCustomer.id, customerFormData);
        if (res.success) {
          toast.success(`Customer '${customerFormData.name}' updated!`);
          setIsCustomerModalOpen(false);
          fetchCustomers();
        }
      } else {
        const res = await apiService.customers.create(customerFormData);
        if (res.success) {
          toast.success(`Customer '${customerFormData.name}' created!`);
          setIsCustomerModalOpen(false);
          fetchCustomers();
        }
      }
    } catch (err) {
      toast.error(err.message || 'Could not save customer.');
    } finally {
      setSubmittingCustomer(false);
    }
  };

  // Open Payment Collection Modal
  const openPaymentModal = (customer) => {
    setPaymentCustomer(customer);
    setPaymentFormData({
      amount: customer.outstanding_amount ? customer.outstanding_amount.toString() : '',
      payment_method: 'CASH',
      reference_no: '',
      notes: '',
    });
    setIsPaymentModalOpen(true);
  };

  // Submit Payment Collection
  const handlePaymentSubmit = async (e) => {
    e.preventDefault();
    if (!paymentCustomer) return;

    setSubmittingPayment(true);

    try {
      const payload = {
        customer_id: paymentCustomer.id,
        amount: parseFloat(paymentFormData.amount) || 0,
        payment_method: paymentFormData.payment_method,
        reference_no: paymentFormData.reference_no,
        notes: paymentFormData.notes,
      };

      const res = await apiService.payments.create(payload);
      if (res.success) {
        toast.success(`Payment of ${currencySymbol}${payload.amount} recorded for ${paymentCustomer.name}!`);
        setIsPaymentModalOpen(false);
        fetchCustomers();
      }
    } catch (err) {
      toast.error(err.message || 'Could not record payment.');
    } finally {
      setSubmittingPayment(false);
    }
  };

  // Open Ledger Statement Drawer
  const openLedgerDrawer = async (customer) => {
    setLedgerCustomer(customer);
    setIsLedgerDrawerOpen(true);
    setLoadingLedger(true);

    try {
      const res = await apiService.customers.getLedger(customer.id);
      if (res.success && res.data) {
        setLedgerData(res.data);
      }
    } catch (err) {
      toast.error('Failed to load ledger statement.');
    } finally {
      setLoadingLedger(false);
    }
  };

  return (
    <div className="customers-page">
      {/* Header Banner */}
      <div className="customers-header">
        <div>
          <h2>{t('customers.title')}</h2>
          <p>{t('customers.subtitle')}</p>
        </div>

        <Button variant="primary" icon={UserPlus} onClick={() => openCustomerModal(null)}>
          {t('customers.add_customer')}
        </Button>
      </div>

      {/* KPI Cards Grid */}
      <div className="customers-kpi-grid">
        <Card padding="compact" className="kpi-card">
          <div className="kpi-icon-badge bg-primary">
            <Users size={22} />
          </div>
          <div className="kpi-content">
            <span className="kpi-label">Total Customers</span>
            <h3 className="kpi-value">{totalCustomers}</h3>
          </div>
        </Card>

        <Card padding="compact" className="kpi-card">
          <div className="kpi-icon-badge bg-amber">
            <DollarSign size={22} />
          </div>
          <div className="kpi-content">
            <span className="kpi-label">{t('customers.outstanding_amount')}</span>
            <h3 className="kpi-value">{currencySymbol}{totalOutstanding.toLocaleString()}</h3>
          </div>
        </Card>
      </div>

      {/* Directory Card */}
      <Card padding="none">
        {/* Search & Filter Bar */}
        <div className="customers-filter-bar">
          <div className="search-input-box">
            <Search size={18} className="search-icon" />
            <input
              type="text"
              placeholder={t('customers.search_customers')}
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="search-input"
            />
          </div>

          <button
            className={`filter-btn-toggle ${debtorsFilter ? 'toggle-active' : ''}`}
            onClick={() => setDebtorsFilter(!debtorsFilter)}
          >
            <DollarSign size={16} />
            <span>{t('customers.debtors_only')}</span>
          </button>
        </div>

        {/* Table View */}
        <div className="table-responsive">
          <table className="custom-table">
            <thead>
              <tr>
                <th>{t('customers.customer_name')}</th>
                <th>{t('customers.phone')}</th>
                <th>{t('customers.total_purchased')}</th>
                <th>{t('customers.total_paid')}</th>
                <th>{t('customers.outstanding_amount')}</th>
                <th>{t('customers.last_purchase')}</th>
                <th style={{ textAlign: 'right' }}>{t('common.actions')}</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={7} className="text-center py-6">
                    {t('common.loading')}
                  </td>
                </tr>
              ) : customers.length === 0 ? (
                <tr>
                  <td colSpan={7} className="text-center py-6 text-muted">
                    No customers found. Click '{t('customers.add_customer')}' to add your first customer.
                  </td>
                </tr>
              ) : (
                customers.map((cust) => (
                  <tr key={cust.id}>
                    <td>
                      <span className="font-semibold text-slate-900">{cust.name}</span>
                    </td>
                    <td className="text-xs font-mono">{cust.phone || '—'}</td>
                    <td className="font-medium text-slate-700">
                      {currencySymbol}{cust.total_purchased ? cust.total_purchased.toLocaleString() : '0'}
                    </td>
                    <td className="font-medium text-slate-700">
                      {currencySymbol}{cust.total_paid ? cust.total_paid.toLocaleString() : '0'}
                    </td>
                    <td>
                      {cust.outstanding_amount > 0 ? (
                        <span className="font-bold text-danger">
                          {currencySymbol}{cust.outstanding_amount.toLocaleString()}
                        </span>
                      ) : (
                        <span className="text-xs text-muted">Cleared</span>
                      )}
                    </td>
                    <td className="text-xs text-slate-500">{cust.last_purchase_date || 'No purchases'}</td>
                    <td>
                      <div className="table-actions-cell" style={{ justifyContent: 'flex-end' }}>
                        {cust.outstanding_amount > 0 && (
                          <>
                            <Button
                              variant="primary"
                              size="sm"
                              icon={DollarSign}
                              onClick={() => openPaymentModal(cust)}
                            >
                              {t('customers.collect_payment')}
                            </Button>
                            <Button
                              variant="secondary"
                              size="sm"
                              icon={Sparkles}
                              onClick={async () => {
                                try {
                                  const res = await apiService.whatsapp.getReminderLink({ customer_id: cust.id });
                                  if (res.success && res.data?.whatsapp_url) {
                                    window.open(res.data.whatsapp_url, '_blank');
                                  }
                                } catch (err) {
                                  toast.error('Could not generate WhatsApp link.');
                                }
                              }}
                            >
                              WhatsApp
                            </Button>
                          </>
                        )}
                        <Button
                          variant="ghost"
                          size="sm"
                          icon={FileText}
                          onClick={() => openLedgerDrawer(cust)}
                          title="View Ledger Statement"
                        />
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </Card>

      {/* 1. Add / Edit Customer Modal */}
      {isCustomerModalOpen && (
        <Modal
          isOpen={isCustomerModalOpen}
          onClose={() => setIsCustomerModalOpen(false)}
          title={editingCustomer ? 'Edit Customer Details' : 'Add New Customer'}
        >
          <form onSubmit={handleCustomerSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
            <Input
              label="Customer Full Name *"
              value={customerFormData.name}
              onChange={(e) => setCustomerFormData({ ...customerFormData, name: e.target.value })}
              placeholder="e.g. Ramesh Kumar"
              required
            />

            <div className="form-grid-2">
              <Input
                label="Phone Number (WhatsApp) *"
                value={customerFormData.phone}
                onChange={(e) => setCustomerFormData({ ...customerFormData, phone: e.target.value })}
                placeholder="+91 98765 43210"
                required
              />

              <Input
                label="Email Address"
                type="email"
                value={customerFormData.email}
                onChange={(e) => setCustomerFormData({ ...customerFormData, email: e.target.value })}
                placeholder="customer@example.com"
              />
            </div>

            {!editingCustomer && (
              <Input
                label="Opening Balance / Existing Debt"
                type="number"
                step="0.01"
                value={customerFormData.opening_balance}
                onChange={(e) => setCustomerFormData({ ...customerFormData, opening_balance: e.target.value })}
                placeholder="0.00"
              />
            )}

            <div className="input-field-group">
              <label className="input-label">Customer Address</label>
              <textarea
                rows={2}
                className="custom-textarea"
                value={customerFormData.address}
                onChange={(e) => setCustomerFormData({ ...customerFormData, address: e.target.value })}
                placeholder="Shop address or delivery location..."
              />
            </div>

            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '12px', marginTop: '12px' }}>
              <Button type="button" variant="ghost" onClick={() => setIsCustomerModalOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" variant="primary" isLoading={submittingCustomer}>
                {editingCustomer ? 'Save Changes' : 'Create Customer'}
              </Button>
            </div>
          </form>
        </Modal>
      )}

      {/* 2. Collect Payment Modal */}
      {isPaymentModalOpen && paymentCustomer && (
        <Modal
          isOpen={isPaymentModalOpen}
          onClose={() => setIsPaymentModalOpen(false)}
          title={`Collect Payment - ${paymentCustomer.name}`}
        >
          <form onSubmit={handlePaymentSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
            <div style={{ padding: '12px', backgroundColor: 'var(--amber-light)', borderRadius: 'var(--radius-md)', border: '1px solid rgba(245,158,11,0.2)' }}>
              <span style={{ fontSize: '0.85rem', color: 'var(--amber-hover)', fontWeight: '600' }}>
                Total Outstanding Debt: {currencySymbol}{paymentCustomer.outstanding_amount?.toLocaleString() || 0}
              </span>
            </div>

            <Input
              label="Payment Amount Received (*)"
              type="number"
              step="0.01"
              min="0.01"
              value={paymentFormData.amount}
              onChange={(e) => setPaymentFormData({ ...paymentFormData, amount: e.target.value })}
              required
            />

            <div className="form-grid-2">
              <div className="input-field-group">
                <label className="input-label">Payment Method (*)</label>
                <select
                  className="custom-select"
                  value={paymentFormData.payment_method}
                  onChange={(e) => setPaymentFormData({ ...paymentFormData, payment_method: e.target.value })}
                >
                  <option value="CASH">CASH</option>
                  <option value="UPI">UPI / GPay / PhonePe</option>
                  <option value="CARD">Debit / Credit Card</option>
                  <option value="BANK_TRANSFER">Bank Transfer / NEFT</option>
                  <option value="CHEQUE">Cheque</option>
                </select>
              </div>

              <Input
                label="Reference / Transaction ID"
                value={paymentFormData.reference_no}
                onChange={(e) => setPaymentFormData({ ...paymentFormData, reference_no: e.target.value })}
                placeholder="UPI ref #, Cheque #"
              />
            </div>

            <div className="input-field-group">
              <label className="input-label">Notes / Remarks</label>
              <textarea
                rows={2}
                className="custom-textarea"
                value={paymentFormData.notes}
                onChange={(e) => setPaymentFormData({ ...paymentFormData, notes: e.target.value })}
                placeholder="Payment receipt note..."
              />
            </div>

            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '12px', marginTop: '12px' }}>
              <Button type="button" variant="ghost" onClick={() => setIsPaymentModalOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" variant="primary" isLoading={submittingPayment}>
                Record Payment Received
              </Button>
            </div>
          </form>
        </Modal>
      )}

      {/* 3. Customer Ledger Statement Modal */}
      {isLedgerDrawerOpen && ledgerCustomer && (
        <Modal
          isOpen={isLedgerDrawerOpen}
          onClose={() => setIsLedgerDrawerOpen(false)}
          title={`Account Ledger Statement - ${ledgerCustomer.name}`}
        >
          <div style={{ maxHeight: '450px', overflowY: 'auto' }}>
            {loadingLedger ? (
              <p style={{ textAlign: 'center', padding: '24px' }}>Loading customer statement...</p>
            ) : !ledgerData ? (
              <p style={{ textAlign: 'center', padding: '24px', color: 'var(--slate-500)' }}>No transactions found.</p>
            ) : (
              <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
                {/* Balance Summary Header */}
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '12px', padding: '12px', background: 'var(--slate-50)', borderRadius: 'var(--radius-md)' }}>
                  <div>
                    <span style={{ fontSize: '0.75rem', color: 'var(--slate-500)' }}>Total Invoiced</span>
                    <h4 style={{ fontWeight: '700' }}>{currencySymbol}{ledgerData.total_purchased?.toLocaleString()}</h4>
                  </div>
                  <div>
                    <span style={{ fontSize: '0.75rem', color: 'var(--slate-500)' }}>Total Paid</span>
                    <h4 style={{ fontWeight: '700', color: 'var(--emerald)' }}>{currencySymbol}{ledgerData.total_paid?.toLocaleString()}</h4>
                  </div>
                  <div>
                    <span style={{ fontSize: '0.75rem', color: 'var(--slate-500)' }}>Balance Due</span>
                    <h4 style={{ fontWeight: '700', color: ledgerData.outstanding_amount > 0 ? 'var(--rose)' : 'inherit' }}>
                      {currencySymbol}{ledgerData.outstanding_amount?.toLocaleString()}
                    </h4>
                  </div>
                </div>

                {/* Invoices List */}
                <h4 style={{ fontSize: '0.9rem', fontWeight: '600' }}>Invoices History</h4>
                <table className="custom-table">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Invoice #</th>
                      <th>Total</th>
                      <th>Paid</th>
                      <th>Due</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {(ledgerData.invoices || []).map((inv) => (
                      <tr key={inv.id}>
                        <td className="text-xs font-mono">{inv.date}</td>
                        <td className="font-semibold">{inv.invoice_number}</td>
                        <td>{currencySymbol}{inv.grand_total}</td>
                        <td>{currencySymbol}{inv.amount_paid}</td>
                        <td className="font-bold text-danger">{currencySymbol}{inv.balance_due}</td>
                        <td>
                          <Badge variant={inv.payment_status === 'PAID' ? 'success' : 'warning'} size="sm">
                            {inv.payment_status}
                          </Badge>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>

                {/* Payments List */}
                <h4 style={{ fontSize: '0.9rem', fontWeight: '600', marginTop: '12px' }}>Payments History</h4>
                <table className="custom-table">
                  <thead>
                    <tr>
                      <th>Date & Time</th>
                      <th>Receipt #</th>
                      <th>Method</th>
                      <th>Amount</th>
                    </tr>
                  </thead>
                  <tbody>
                    {(ledgerData.payments || []).map((pay) => (
                      <tr key={pay.id}>
                        <td className="text-xs font-mono">{new Date(pay.created_at).toLocaleString()}</td>
                        <td>{pay.payment_number}</td>
                        <td>{pay.payment_method}</td>
                        <td className="font-bold text-emerald">{currencySymbol}{pay.amount}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </Modal>
      )}
    </div>
  );
}
