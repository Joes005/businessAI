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
  Zap,
} from 'lucide-react';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Badge from '../components/ui/Badge';
import StatCard from '../components/ui/StatCard';
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
  const maxBestsellerRevenue = Math.max(...topProducts.map((p) => p.total_revenue), 1);

  return (
    <div className="dashboard-page animate-fade-in">
      {/* Hero Welcome Banner */}
      <div className="welcome-banner glow-border">
        <div className="welcome-banner-content">
          <div className="ai-status-chip">
            <Sparkles size={14} className="sparkle-pulse" />
            <span>AI Business Copilot 2.0 Active</span>
          </div>
          <h2 className="banner-heading">
            {t('dashboard.welcome_title')} <span className="animated-gradient-text">{user?.name || 'Owner'}</span>!
          </h2>
          <p className="banner-subtitle">
            Here's what's happening at <strong>{currentBusiness?.name || 'your store'}</strong> today.
          </p>
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
            <div key={idx} className={`alert-banner-item alert-${alert.type} glass-card`}>
              <div className="alert-text-group">
                <AlertTriangle size={18} className="alert-icon" />
                <span>{alert.message}</span>
              </div>
              <button
                className="alert-action-btn"
                onClick={() => navigate(alert.type === 'stock' ? '/products' : '/customers')}
              >
                Resolve <ArrowRight size={14} />
              </button>
            </div>
          ))}
        </div>
      )}

      {/* KPI StatCards Grid */}
      <div className="kpi-grid">
        <StatCard
          title={t('dashboard.today_sales')}
          value={`${currencySymbol}${kpis?.today_sales ? kpis.today_sales.toLocaleString() : '0'}`}
          subtitle={`${kpis?.today_invoices_count || 0} bills issued today`}
          icon={Receipt}
          color="primary"
          trend={{ direction: 'up', value: '+14.2% vs avg' }}
        />

        <StatCard
          title={t('dashboard.month_profit')}
          value={`${currencySymbol}${kpis?.month_profit ? kpis.month_profit.toLocaleString() : '0'}`}
          subtitle="Net margin post-COGS"
          icon={TrendingUp}
          color="emerald"
          trend={{ direction: 'up', value: '+18.5%' }}
        />

        <StatCard
          title={t('dashboard.money_to_collect')}
          value={`${currencySymbol}${kpis?.outstanding_receivables ? kpis.outstanding_receivables.toLocaleString() : '0'}`}
          subtitle="Pending Customer Debts"
          icon={DollarSign}
          color="amber"
          trend={{ direction: 'down', value: 'Requires Action' }}
        />

        <StatCard
          title={t('dashboard.low_stock_alerts')}
          value={kpis?.low_stock_count || 0}
          subtitle="Items below min threshold"
          icon={Package}
          color="rose"
          trend={{ direction: 'down', value: 'Stock Warning' }}
        />
      </div>

      {/* Main Grid: 7-Day Chart & Top Bestsellers */}
      <div className="dashboard-grid-2">
        {/* 7-Day Revenue & Profit Chart */}
        <Card
          title={t('dashboard.sales_chart_title')}
          subtitle="Daily sales revenue vs net profit visualizer"
          className="chart-card glass-card"
        >
          <div className="visual-chart-container">
            <div className="chart-bars-row">
              {chartData.map((day, idx) => {
                const heightPercent = Math.max(12, Math.min(100, (day.sales / maxChartVal) * 100));
                const profitPercent = Math.max(8, Math.min(100, (day.profit / maxChartVal) * 100));

                return (
                  <div key={idx} className="chart-bar-group">
                    <div className="bars-stack">
                      <div
                        className="bar-item bar-sales"
                        style={{ height: `${heightPercent}%` }}
                      >
                        <div className="bar-tooltip">Sales: {currencySymbol}{day.sales}</div>
                      </div>
                      <div
                        className="bar-item bar-profit"
                        style={{ height: `${profitPercent}%` }}
                      >
                        <div className="bar-tooltip">Profit: {currencySymbol}{day.profit}</div>
                      </div>
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
        <Card
          title={t('dashboard.top_bestsellers')}
          subtitle="Top products by sales volume & revenue share"
          className="bestsellers-card glass-card"
        >
          <div className="bestsellers-list">
            {topProducts.length === 0 ? (
              <p className="empty-state-text">{t('dashboard.no_bestsellers')}</p>
            ) : (
              topProducts.map((prod, idx) => {
                const revenuePercent = Math.round((prod.total_revenue / maxBestsellerRevenue) * 100);
                return (
                  <div key={idx} className="bestseller-item">
                    <div className="bestseller-header">
                      <div className="bestseller-rank-badge">#{idx + 1}</div>
                      <div className="bestseller-info">
                        <span className="bestseller-name">{prod.name}</span>
                        <span className="bestseller-sold">{prod.total_sold} units sold</span>
                      </div>
                      <div className="bestseller-revenue font-bold">
                        {currencySymbol}{prod.total_revenue.toLocaleString()}
                      </div>
                    </div>
                    <div className="bestseller-progress-track">
                      <div
                        className="bestseller-progress-fill"
                        style={{ width: `${revenuePercent}%` }}
                      />
                    </div>
                  </div>
                );
              })
            )}
          </div>
        </Card>
      </div>

      {/* Recent Activity Stream Card */}
      <Card
        title={t('dashboard.recent_activity')}
        subtitle="Real-time transaction log for sales, payments, and stock updates"
        className="activity-card glass-card"
      >
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
                    <Package size={18} className="text-warning" />
                  )}
                </div>
                <div className="activity-details">
                  <span className="activity-desc">{act.description}</span>
                  <span className="activity-time">
                    <Clock size={12} /> {act.time}
                  </span>
                </div>
                {act.amount && (
                  <div className="activity-amount font-semibold">
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
