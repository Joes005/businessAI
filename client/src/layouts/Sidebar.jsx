import React from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import {
  LayoutDashboard,
  Receipt,
  Package,
  Users,
  Wallet,
  BarChart3,
  Bot,
  Settings,
  Store,
  X,
  Sparkles,
} from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { useLanguage } from '../contexts/LanguageContext';
import './Sidebar.css';

export default function Sidebar({ isOpen, onClose }) {
  const location = useLocation();
  const { currentBusiness, user } = useAuth();
  const { t } = useLanguage();

  const navItems = [
    { name: t('sidebar.dashboard'), path: '/', icon: LayoutDashboard },
    { name: t('sidebar.billing'), path: '/billing', icon: Receipt, badge: 'Fast' },
    { name: t('sidebar.products'), path: '/products', icon: Package },
    { name: t('sidebar.customers'), path: '/customers', icon: Users },
    { name: t('sidebar.reports'), path: '/reports', icon: BarChart3 },
    { name: t('sidebar.copilot'), path: '/copilot', icon: Bot, isAi: true },
    { name: t('sidebar.settings'), path: '/settings', icon: Settings },
  ];

  return (
    <>
      {/* Mobile Backdrop */}
      {isOpen && <div className="sidebar-backdrop" onClick={onClose} />}

      <aside className={`app-sidebar ${isOpen ? 'sidebar-open' : ''}`}>
        {/* Brand Header */}
        <div className="sidebar-brand">
          <div className="brand-logo-container">
            <Sparkles className="brand-icon" size={22} />
          </div>
          <div className="brand-info">
            <h2 className="brand-title">{t('sidebar.brand_title')}</h2>
            <span className="brand-subtitle">{t('sidebar.brand_subtitle')}</span>
          </div>
          <button className="sidebar-close-btn" onClick={onClose} aria-label="Close sidebar">
            <X size={20} />
          </button>
        </div>

        {/* Current Active Business Card */}
        <div className="sidebar-business-card">
          <div className="business-avatar">
            <Store size={18} />
          </div>
          <div className="business-details">
            <span className="business-name">{currentBusiness?.name || 'My Business'}</span>
            <span className="business-type">
              {currentBusiness?.type ? `${currentBusiness.type.toUpperCase()} • ${currentBusiness.currency || 'INR'}` : 'Owner Mode'}
            </span>
          </div>
        </div>

        {/* Navigation Section */}
        <nav className="sidebar-nav">
          <span className="nav-section-title">MAIN MENU</span>
          <ul className="nav-list">
            {navItems.map((item) => {
              const Icon = item.icon;
              const isActive = location.pathname === item.path;

              return (
                <li key={item.path}>
                  <NavLink
                    to={item.path}
                    onClick={() => onClose && onClose()}
                    className={`nav-link ${isActive ? 'nav-link-active' : ''} ${item.isAi ? 'nav-link-ai' : ''}`}
                  >
                    <Icon size={18} className="nav-icon" />
                    <span className="nav-text">{item.name}</span>
                    {item.badge && <span className="nav-item-badge">{item.badge}</span>}
                    {item.isAi && <span className="nav-ai-sparkle">✨</span>}
                  </NavLink>
                </li>
              );
            })}
          </ul>
        </nav>

        {/* Sidebar Footer */}
        <div className="sidebar-footer">
          <div className="plan-badge">
            <span className="plan-title">{user?.name || 'Owner'}</span>
            <span className="plan-status">Multi-Tenant SaaS Ready</span>
          </div>
        </div>
      </aside>
    </>
  );
}
