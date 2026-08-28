import React, { useState, useEffect, useCallback } from 'react';
import {
  BarChart3,
  TrendingUp,
  Download,
  Calendar,
  DollarSign,
  FileSpreadsheet,
  Layers,
  ArrowUpRight,
  ArrowDownRight,
  PieChart,
} from 'lucide-react';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Badge from '../components/ui/Badge';
import { useAuth } from '../contexts/AuthContext';
import { useLanguage } from '../contexts/LanguageContext';
import { useToast } from '../components/ui/ToastContext';
import apiService from '../services/api';
import './ReportsPage.css';

export default function ReportsPage() {
  const { currentBusiness } = useAuth();
  const { t } = useLanguage();
  const toast = useToast();

  const currencySymbol = currentBusiness?.currency === 'USD' ? '$' : currentBusiness?.currency === 'EUR' ? '€' : '₹';

  const [datePreset, setDatePreset] = useState('this_month'); // today, yesterday, last_7_days, this_month
  const [activeReportTab, setActiveReportTab] = useState('pnl'); // pnl, sales, inventory, debtors

  // Data States
  const [salesReport, setSalesReport] = useState(null);
  const [pnlReport, setPnlReport] = useState(null);
  const [inventoryReport, setInventoryReport] = useState(null);
  const [debtorsReport, setDebtorsReport] = useState(null);
  const [loading, setLoading] = useState(false);

  // Fetch Report Data based on preset
  const fetchReportsData = useCallback(async () => {
    setLoading(true);
    try {
      if (activeReportTab === 'pnl') {
        const res = await apiService.reports.getProfitLoss({ preset: datePreset });
        if (res.success) setPnlReport(res.data);
      } else if (activeReportTab === 'sales') {
        const res = await apiService.reports.getSales({ preset: datePreset });
        if (res.success) setSalesReport(res.data);
      } else if (activeReportTab === 'inventory') {
        const res = await apiService.reports.getInventory();
        if (res.success) setInventoryReport(res.data);
      } else if (activeReportTab === 'debtors') {
        const res = await apiService.reports.getDebtors();
        if (res.success) setDebtorsReport(res.data);
      }
    } catch (err) {
      toast.error('Failed to load report data.');
    } finally {
      setLoading(false);
    }
  }, [activeReportTab, datePreset, toast]);

  useEffect(() => {
    fetchReportsData();
  }, [fetchReportsData]);

  // CSV Download Handler
  const handleExportCSV = () => {
    let filename = `${activeReportTab}_report.csv`;
    let rows = [];

    if (activeReportTab === 'pnl' && pnlReport) {
      filename = `PnL_Report_${datePreset}.csv`;
      rows = [
        ['Metric', 'Amount'],
        ['Gross Sales Revenue', pnlReport.revenue],
        ['Cost of Goods Sold (COGS)', pnlReport.cogs],
        ['Gross Profit', pnlReport.gross_profit],
        ['Profit Margin %', `${pnlReport.profit_margin}%`],
      ];
    } else if (activeReportTab === 'inventory' && inventoryReport) {
      filename = 'Inventory_Valuation_Report.csv';
      rows = [
        ['Product Name', 'Stock Qty', 'Purchase Price', 'Stock Valuation'],
        ...(inventoryReport.products || []).map((p) => [
          p.name,
          p.stock_quantity,
          p.purchase_price,
          (p.stock_quantity * p.purchase_price).toFixed(2),
        ]),
      ];
    } else if (activeReportTab === 'debtors' && debtorsReport) {
      filename = 'Customer_Debtors_Report.csv';
      rows = [
        ['Customer Name', 'Phone', 'Outstanding Debt'],
        ...(debtorsReport.debtors || []).map((d) => [
          d.name,
          d.phone || 'N/A',
          d.outstanding_amount,
        ]),
      ];
    }

    if (rows.length === 0) {
      toast.error('No data available to export.');
      return;
    }

    const csvContent = 'data:text/csv;charset=utf-8,' + rows.map((e) => e.join(',')).join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    toast.success(`Exported ${filename} successfully!`);
  };

  return (
    <div className="reports-page">
      {/* Top Header */}
      <div className="reports-header">
        <div>
          <h2>{t('reports.title')}</h2>
          <p>{t('reports.subtitle')}</p>
        </div>

        {/* Date Range Preset Selector */}
        <div className="preset-selector-row">
          <button
            className={`preset-btn ${datePreset === 'today' ? 'preset-active' : ''}`}
            onClick={() => setDatePreset('today')}
          >
            {t('reports.today')}
          </button>
          <button
            className={`preset-btn ${datePreset === 'yesterday' ? 'preset-active' : ''}`}
            onClick={() => setDatePreset('yesterday')}
          >
            {t('reports.yesterday')}
          </button>
          <button
            className={`preset-btn ${datePreset === 'last_7_days' ? 'preset-active' : ''}`}
            onClick={() => setDatePreset('last_7_days')}
          >
            {t('reports.last_7_days')}
          </button>
          <button
            className={`preset-btn ${datePreset === 'this_month' ? 'preset-active' : ''}`}
            onClick={() => setDatePreset('this_month')}
          >
            {t('reports.this_month')}
          </button>
        </div>
      </div>

      {/* Report Type Tabs */}
      <div className="report-tabs-bar">
        <button
          className={`report-tab ${activeReportTab === 'pnl' ? 'tab-active' : ''}`}
          onClick={() => setActiveReportTab('pnl')}
        >
          <TrendingUp size={16} /> {t('reports.pnl_statement')}
        </button>
        <button
          className={`report-tab ${activeReportTab === 'sales' ? 'tab-active' : ''}`}
          onClick={() => setActiveReportTab('sales')}
        >
          <BarChart3 size={16} /> {t('reports.sales_summary')}
        </button>
        <button
          className={`report-tab ${activeReportTab === 'inventory' ? 'tab-active' : ''}`}
          onClick={() => setActiveReportTab('inventory')}
        >
          <Layers size={16} /> {t('reports.inventory_report')}
        </button>
        <button
          className={`report-tab ${activeReportTab === 'debtors' ? 'tab-active' : ''}`}
          onClick={() => setActiveReportTab('debtors')}
        >
          <DollarSign size={16} /> {t('reports.debtors_report')}
        </button>
      </div>

      {/* TAB 1: Profit & Loss Statement */}
      {activeReportTab === 'pnl' && (
        <Card title={t('reports.pnl_statement')} subtitle="Gross Profit calculated using exact Cost of Goods Sold (COGS)">
          <div className="pnl-grid">
            <div className="pnl-stat-card">
              <span className="stat-label">{t('reports.gross_revenue')}</span>
              <h3 className="stat-val font-bold text-slate-900">
                {currencySymbol}{pnlReport?.revenue ? pnlReport.revenue.toLocaleString() : '0'}
              </h3>
            </div>

            <div className="pnl-stat-card">
              <span className="stat-label">{t('reports.total_cogs')}</span>
              <h3 className="stat-val font-bold text-slate-700">
                {currencySymbol}{pnlReport?.cogs ? pnlReport.cogs.toLocaleString() : '0'}
              </h3>
            </div>

            <div className="pnl-stat-card bg-emerald-light">
              <span className="stat-label text-emerald-800">{t('reports.gross_profit')}</span>
              <h3 className="stat-val font-bold text-emerald-900">
                {currencySymbol}{pnlReport?.gross_profit ? pnlReport.gross_profit.toLocaleString() : '0'}
              </h3>
            </div>

            <div className="pnl-stat-card">
              <span className="stat-label">{t('reports.profit_margin')}</span>
              <h3 className="stat-val font-bold text-primary">
                {pnlReport?.profit_margin || 0}%
              </h3>
            </div>
          </div>
        </Card>
      )}

      {/* Export Action Bar */}
      <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: '16px' }}>
        <Button variant="secondary" icon={Download} onClick={handleExportCSV}>
          {t('reports.export_csv')}
        </Button>
      </div>
    </div>
  );
}
