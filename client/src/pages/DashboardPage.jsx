import React, { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  TrendingUp,
  DollarSign,
  Package,
  AlertTriangle,
  Receipt,
  Users,
  Plus,
  ArrowRight,
  Sparkles,
  ShoppingBag,
  Clock,
  BarChart2,
} from 'lucide-react';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Badge from '../components/ui/Badge';
import { useAuth } from '../contexts/AuthContext';
import { useLanguage } from '../contexts/LanguageContext';
import { useToast } from '../components/ui/ToastContext';
import apiService from '../services/api';
import './DashboardPage.css';

export default function DashboardPage() {
  const { currentBusiness, user } = useAuth();
  const { t } = useLanguage();
  const navigate = useNavigate();
  const toast = useToast();

  const [kpis, setKpis] = useState(null);
  const [chartData, setChartData] = useState([]);
  const [topProducts, setTopProducts] = useState([]);
  const [recentActivity, setRecentActivity] = useState([]);
  const [alerts, setAlerts] = useState([]);
  const [loading, setLoading] = useState(true);

  const currencySymbol = currentBusiness?.currency === 'USD' ? '$' : currentBusiness?.currency === 'EUR' ? '€' : '₹';

  const fetchDashboardData = useCallback(async () => {
    setLoading(true);
    try {
      const res = await apiService.dashboard.get();
      if (res.success && res.data) {
        setKpis(res.data.kpis);
        setChartData(res.data.sales_chart || []);
        setTopProducts(res.data.top_products || []);
        setRecentActivity(res.data.recent_activity || []);
        setAlerts(res.data.alerts || []);
      }
    } catch (err) {
      toast.error('Failed to load dashboard metrics.');
    } finally {
      setLoading(false);
    }
  }, [toast]);

  useEffect(() => {
    fetchDashboardData();
  }, [fetchDashboardData]);

  // Max value for 7-day chart height normalization
  const maxChartVal = Math.max(...chartData.map((d) => d.sales), 100);

  return (
    <div className="dashboard-page">
      {/* Hero Welcome Banner */}
      <div className="welcome-banner">
        <div className="welcome-text">
          <h2>{t('dashboard.welcome_title')} {user?.name || 'Owner'}!</h2>
          <p>{t('dashboard.welcome_subtitle')} ({currentBusiness?.name})</p>
        </div>

        <div className="banner-actions">
          <Button
            variant="secondary"
            icon={Receipt}
            onClick={() => navigate('/billing')}
          >
            {t('dashboard.open_pos')}
          </Button>
          <Button
            variant="primary"
            icon={Plus}
            onClick={() => navigate('/products')}
          >
            {t('dashboard.add_product')}
          </Button>
        </div>
      </div>

      {/* Urgent Alerts Banner */}
      {alerts.length > 0 && (
        <div className="dashboard-alerts-container">
          {alerts.map((alert, idx) => (
            <div key={idx} className={`alert-banner-item alert-${alert.type}`}>
              <AlertTriangle size={18} />
              <span>{alert.message}</span>
              <button
                className="alert-action-btn"
                onClick={() => navigate(alert.type === 'stock' ? '/products' : '/customers')}
              >
                Resolve <ArrowRight size={12} />
              </button>
            </div>
          ))}
        </div>
      )}

      {/* KPI Cards 4 Grid */}
      <div className="kpi-grid">
        <Card padding="compact" className="kpi-card">
          <div className="kpi-icon-badge bg-primary">
            <Receipt size={22} />
          </div>
          <div className="kpi-content">
            <span className="kpi-label">{t('dashboard.today_sales')}</span>
            <h3 className="kpi-value">{currencySymbol}{kpis?.today_sales ? kpis.today_sales.toLocaleString() : '0'}</h3>
            <span className="kpi-subtext">{kpis?.today_invoices_count || 0} bills issued today</span>
          </div>
        </Card>

        <Card padding="compact" className="kpi-card">
          <div className="kpi-icon-badge bg-emerald">
            <TrendingUp size={22} />
          </div>
          <div className="kpi-content">
            <span className="kpi-label">{t('dashboard.month_profit')}</span>
            <h3 className="kpi-value">{currencySymbol}{kpis?.month_profit ? kpis.month_profit.toLocaleString() : '0'}</h3>
            <span className="kpi-subtext">COGS Profit Calculation</span>
          </div>
        </Card>

        <Card padding="compact" className="kpi-card">
          <div className="kpi-icon-badge bg-amber">
            <DollarSign size={22} />
          </div>
          <div className="kpi-content">
            <span className="kpi-label">{t('dashboard.money_to_collect')}</span>
            <h3 className="kpi-value">{currencySymbol}{kpis?.outstanding_receivables ? kpis.outstanding_receivables.toLocaleString() : '0'}</h3>
            <span className="kpi-subtext">Pending Customer Debts</span>
          </div>
        </Card>

        <Card padding="compact" className="kpi-card">
          <div className="kpi-icon-badge bg-rose">
            <Package size={22} />
          </div>
          <div className="kpi-content">
            <span className="kpi-label">{t('dashboard.low_stock_alerts')}</span>
            <h3 className="kpi-value">{kpis?.low_stock_count || 0}</h3>
            <span className="kpi-subtext">Items below min threshold</span>
          </div>
        </Card>
      </div>

      {/* Main Grid: 7-Day Chart & Top Bestsellers */}
      <div className="dashboard-grid-2">
        {/* 7-Day Revenue & Profit Chart */}
        <Card title={t('dashboard.sales_chart_title')} subtitle="Daily comparison of gross sales vs profit">
          <div className="visual-chart-container">
            <div className="chart-bars-row">
              {chartData.map((day, idx) => {
                const heightPercent = Math.max(10, Math.min(100, (day.sales / maxChartVal) * 100));
                const profitPercent = Math.max(5, Math.min(100, (day.profit / maxChartVal) * 100));

                return (
                  <div key={idx} className="chart-bar-group">
                    <div className="bars-stack">
                      <div
                        className="bar-item bar-sales"
                        style={{ height: `${heightPercent}%` }}
                        title={`Sales: ${currencySymbol}${day.sales}`}
                      />
                      <div
                        className="bar-item bar-profit"
                        style={{ height: `${profitPercent}%` }}
                        title={`Profit: ${currencySymbol}${day.profit}`}
                      />
                    </div>
                    <span className="bar-day-label">{day.date}</span>
                  </div>
                );
              })}
            </div>

            <div className="chart-legend-row">
              <div className="legend-item">
                <span className="legend-color bg-primary-bar" />
                <span className="legend-text">Sales Revenue</span>
              </div>
              <div className="legend-item">
                <span className="legend-color bg-emerald-bar" />
                <span className="legend-text">Net Profit</span>
              </div>
            </div>
          </div>
        </Card>

        {/* Top Bestselling Products */}
        <Card title={t('dashboard.top_bestsellers')} subtitle="Top products by sales volume this month">
          <div className="bestsellers-list">
            {topProducts.length === 0 ? (
              <p className="empty-state-text">{t('dashboard.no_bestsellers')}</p>
            ) : (
              topProducts.map((prod, idx) => (
                <div key={idx} className="bestseller-item">
                  <div className="bestseller-rank">#{idx + 1}</div>
                  <div className="bestseller-info">
                    <span className="bestseller-name">{prod.name}</span>
                    <span className="bestseller-sold">{prod.total_sold} units sold</span>
                  </div>
                  <div className="bestseller-revenue font-bold text-slate-900">
                    {currencySymbol}{prod.total_revenue.toLocaleString()}
                  </div>
                </div>
              ))
            )}
          </div>
        </Card>
      </div>

      {/* Recent Activity Stream Card */}
      <Card title={t('dashboard.recent_activity')} subtitle="Live history of sales, payments, and inventory restocks">
        <div className="activity-stream-list">
          {recentActivity.length === 0 ? (
            <p className="empty-state-text">{t('dashboard.no_activity')}</p>
          ) : (
            recentActivity.map((act, idx) => (
              <div key={idx} className="activity-row">
                <div className="activity-icon-col">
                  {act.type === 'sale' ? (
                    <ShoppingBag size={18} className="text-primary" />
                  ) : act.type === 'payment' ? (
                    <DollarSign size={18} className="text-success" />
                  ) : (
                    <Package size={18} className="text-amber" />
                  )}
                </div>
                <div className="activity-details">
                  <span className="activity-desc">{act.description}</span>
                  <span className="activity-time">{act.time}</span>
                </div>
                {act.amount && (
                  <div className="activity-amount font-semibold text-slate-900">
                    {currencySymbol}{act.amount.toLocaleString()}
                  </div>
                )}
              </div>
            ))
          )}
        </div>
      </Card>
    </div>
  );
}
