import React, { useState, useEffect, useCallback } from 'react';
import {
  Settings,
  Building,
  MessageSquare,
  Zap,
  Save,
  RefreshCw,
  Sparkles,
  CheckCircle,
  FileText,
  Clock,
} from 'lucide-react';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Input from '../components/ui/Input';
import Badge from '../components/ui/Badge';
import { useAuth } from '../contexts/AuthContext';
import { useLanguage } from '../contexts/LanguageContext';
import { useToast } from '../components/ui/ToastContext';
import apiService from '../services/api';
import './SettingsPage.css';

export default function SettingsPage() {
  const { currentBusiness, user } = useAuth();
  const { t } = useLanguage();
  const toast = useToast();

  const [activeTab, setActiveTab] = useState('whatsapp'); // whatsapp, business, automation

  // WhatsApp Templates State
  const [templates, setTemplates] = useState({
    INVOICE: "Hi {customer_name},\nThank you for shopping at *{business_name}*!\n\nHere is your invoice *#{invoice_number}*:\nGrand Total: *{currency}{grand_total}*\nPayment Method: {payment_method}\nStatus: {payment_status}\n\nHave a great day!",
    DEBT_REMINDER: "Hi {customer_name},\nFriendly reminder from *{business_name}*.\n\nYour outstanding balance of *{currency}{balance_due}* is pending. Kindly clear the balance via UPI, Cash, or Card at your earliest convenience.\n\nThank you!",
    PAYMENT_RECEIPT: "Hi {customer_name},\nWe received your payment of *{currency}{amount}* at *{business_name}*.\nReceipt #: {payment_number}\n\nThank you!",
  });

  // Automation Logs State
  const [automationLogs, setAutomationLogs] = useState([]);
  const [loadingLogs, setLoadingLogs] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  // Fetch Templates
  const fetchTemplates = useCallback(async () => {
    try {
      const res = await apiService.whatsapp.getTemplates();
      if (res.success && res.data.templates) {
        const map = { ...templates };
        res.data.templates.forEach((t) => {
          map[t.type] = t.template_text;
        });
        setTemplates(map);
      }
    } catch (err) {
      console.error('Failed to load templates:', err);
    }
  }, []);

  // Fetch Automation Logs
  const fetchAutomationLogs = useCallback(async () => {
    setLoadingLogs(true);
    try {
      const res = await apiService.automation.getLogs();
      if (res.success) {
        setAutomationLogs(res.data.logs || []);
      }
    } catch (err) {
      toast.error('Failed to load automation logs.');
    } finally {
      setLoadingLogs(false);
    }
  }, [toast]);

  useEffect(() => {
    fetchTemplates();
    fetchAutomationLogs();
  }, [fetchTemplates, fetchAutomationLogs]);

  // Save WhatsApp Template
  const handleSaveTemplate = async (type) => {
    setSubmitting(true);
    try {
      const res = await apiService.whatsapp.updateTemplate({
        type,
        template_text: templates[type],
      });
      if (res.success) {
        toast.success(`WhatsApp template for '${type}' saved.`);
      }
    } catch (err) {
      toast.error(err.message || 'Could not save template.');
    } finally {
      setSubmitting(false);
    }
  };

  // Run Manual Automation Trigger
  const handleRunAutomation = async () => {
    setSubmitting(true);
    try {
      const res = await apiService.automation.run();
      if (res.success) {
        toast.success(res.message);
        fetchAutomationLogs();
      }
    } catch (err) {
      toast.error('Automation check failed.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="settings-page">
      {/* Top Header */}
      <div className="settings-header">
        <div>
          <h2>{t('settings.title')}</h2>
          <p>{t('settings.subtitle')}</p>
        </div>
      </div>

      {/* Tabs Selector */}
      <div className="settings-tabs-bar">
        <button
          className={`settings-tab ${activeTab === 'whatsapp' ? 'settings-tab-active' : ''}`}
          onClick={() => setActiveTab('whatsapp')}
        >
          <MessageSquare size={16} /> {t('settings.whatsapp_tab')}
        </button>
        <button
          className={`settings-tab ${activeTab === 'automation' ? 'settings-tab-active' : ''}`}
          onClick={() => setActiveTab('automation')}
        >
          <Zap size={16} /> {t('settings.automation_tab')}
        </button>
        <button
          className={`settings-tab ${activeTab === 'business' ? 'settings-tab-active' : ''}`}
          onClick={() => setActiveTab('business')}
        >
          <Building size={16} /> {t('settings.business_tab')}
        </button>
      </div>

      {/* TAB 1: WhatsApp Templates */}
      {activeTab === 'whatsapp' && (
        <div className="settings-content-space">
          {/* Invoice WhatsApp Template */}
          <Card title={t('settings.invoice_template')} subtitle="Message sent when sharing bill receipts with customers">
            <div className="template-editor-box">
              <textarea
                rows={5}
                value={templates.INVOICE}
                onChange={(e) => setTemplates((prev) => ({ ...prev, INVOICE: e.target.value }))}
                className="template-textarea"
              />
              <div className="placeholders-help">
                <span className="text-xs font-semibold text-slate-700">{t('settings.available_placeholders')}:</span>
                <div className="chips-row">
                  <span className="placeholder-chip">{`{customer_name}`}</span>
                  <span className="placeholder-chip">{`{invoice_number}`}</span>
                  <span className="placeholder-chip">{`{grand_total}`}</span>
                  <span className="placeholder-chip">{`{currency}`}</span>
                  <span className="placeholder-chip">{`{payment_method}`}</span>
                  <span className="placeholder-chip">{`{business_name}`}</span>
                </div>
              </div>

              <div className="template-actions">
                <Button variant="primary" icon={Save} isLoading={submitting} onClick={() => handleSaveTemplate('INVOICE')}>
                  {t('settings.save_template')}
                </Button>
              </div>
            </div>
          </Card>

          {/* Debt Reminder WhatsApp Template */}
          <Card title={t('settings.reminder_template')} subtitle="Message sent for collecting pending balances">
            <div className="template-editor-box">
              <textarea
                rows={5}
                value={templates.DEBT_REMINDER}
                onChange={(e) => setTemplates((prev) => ({ ...prev, DEBT_REMINDER: e.target.value }))}
                className="template-textarea"
              />
              <div className="placeholders-help">
                <span className="text-xs font-semibold text-slate-700">{t('settings.available_placeholders')}:</span>
                <div className="chips-row">
                  <span className="placeholder-chip">{`{customer_name}`}</span>
                  <span className="placeholder-chip">{`{balance_due}`}</span>
                  <span className="placeholder-chip">{`{currency}`}</span>
                  <span className="placeholder-chip">{`{business_name}`}</span>
                </div>
              </div>

              <div className="template-actions">
                <Button variant="primary" icon={Save} isLoading={submitting} onClick={() => handleSaveTemplate('DEBT_REMINDER')}>
                  {t('settings.save_template')}
                </Button>
              </div>
            </div>
          </Card>
        </div>
      )}

      {/* TAB 2: Automation Triggers & Logs */}
      {activeTab === 'automation' && (
        <div className="settings-content-space">
          <Card title={t('settings.automation_tab')} subtitle="Run background checks for low stock items and customer debt follow-ups">
            <div className="automation-trigger-bar">
              <div>
                <h4 className="font-semibold text-slate-900">Run Inventory & Debt Audit Trigger</h4>
                <p className="text-xs text-muted">Audits product stock levels and customer debts, and logs active alerts.</p>
              </div>
              <Button variant="primary" icon={Zap} isLoading={submitting} onClick={handleRunAutomation}>
                {t('settings.run_audit_now')}
              </Button>
            </div>
          </Card>

          <Card title={t('settings.automation_logs')} subtitle="History of automated system checks">
            <table className="custom-table" style={{ width: '100%' }}>
              <thead>
                <tr>
                  <th>Timestamp</th>
                  <th>Trigger Type</th>
                  <th>Recipient / Target</th>
                  <th>Alert Message</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                {automationLogs.length === 0 ? (
                  <tr><td colSpan={5} style={{ textAlign: 'center', padding: '24px' }}>No automation logs recorded yet. Click "Run Audit Now" to run checks.</td></tr>
                ) : (
                  automationLogs.map((log) => (
                    <tr key={log.id}>
                      <td className="text-xs">{new Date(log.created_at).toLocaleString()}</td>
                      <td>
                        <Badge variant={log.trigger_type === 'LOW_STOCK_ALERT' ? 'warning' : 'info'} size="sm">
                          {log.trigger_type}
                        </Badge>
                      </td>
                      <td className="font-semibold">{log.recipient || 'System'}</td>
                      <td className="text-xs text-slate-700">{log.message}</td>
                      <td><Badge variant="success" size="sm">{log.status}</Badge></td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </Card>
        </div>
      )}

      {/* TAB 3: Business Profile */}
      {activeTab === 'business' && (
        <div className="settings-content-space">
          <Card title="Active Business Information" subtitle="Managed shop details and currency symbol">
            <div className="business-info-display">
              <div className="info-item">
                <span className="text-xs text-muted">Business Name</span>
                <h4 className="font-bold text-slate-900">{currentBusiness?.name}</h4>
              </div>
              <div className="info-item">
                <span className="text-xs text-muted">Business Category / Type</span>
                <h4 className="font-bold text-slate-900">{currentBusiness?.type?.toUpperCase()}</h4>
              </div>
              <div className="info-item">
                <span className="text-xs text-muted">Business Currency</span>
                <h4 className="font-bold text-slate-900">{currentBusiness?.currency} ({currentBusiness?.currency === 'USD' ? '$' : '₹'})</h4>
              </div>
              <div className="info-item">
                <span className="text-xs text-muted">Contact Phone</span>
                <h4 className="font-bold text-slate-900">{currentBusiness?.phone || 'N/A'}</h4>
              </div>
            </div>
          </Card>
        </div>
      )}
    </div>
  );
}
