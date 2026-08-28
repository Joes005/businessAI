import React from 'react';
import { TrendingUp, TrendingDown } from 'lucide-react';
import './StatCard.css';

export default function StatCard({
  title,
  value,
  subtitle,
  icon: Icon,
  trend = null, // e.g. { direction: 'up' | 'down', value: '+12.5%' }
  color = 'primary', // 'primary' | 'emerald' | 'amber' | 'rose'
}) {
  return (
    <div className={`stat-card stat-card-${color}`}>
      <div className="stat-card-header">
        <span className="stat-card-title">{title}</span>
        {Icon && (
          <div className="stat-card-icon">
            <Icon size={20} />
          </div>
        )}
      </div>

      <div className="stat-card-body">
        <span className="stat-card-value">{value}</span>

        <div className="stat-card-footer">
          {trend && (
            <span className={`stat-trend stat-trend-${trend.direction}`}>
              {trend.direction === 'up' ? <TrendingUp size={14} /> : <TrendingDown size={14} />}
              {trend.value}
            </span>
          )}
          {subtitle && <span className="stat-card-subtitle">{subtitle}</span>}
        </div>
      </div>
    </div>
  );
}
