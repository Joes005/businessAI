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
    </div>
  );
}
