import React, { useState, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { Menu, Search, Plus, Bell, User, ChevronDown, LogOut, Globe, Sun, Moon, Sparkles } from 'lucide-react';
import Button from '../components/ui/Button';
import Badge from '../components/ui/Badge';
import CommandPaletteModal from '../components/ui/CommandPaletteModal';
import { useAuth } from '../contexts/AuthContext';
import { useLanguage } from '../contexts/LanguageContext';
import { useTheme } from '../contexts/ThemeContext';
import { useToast } from '../components/ui/ToastContext';
import apiService from '../services/api';
import './Header.css';

export default function Header({ onToggleSidebar, pageTitle = 'Dashboard' }) {
  const { user, currentBusiness, logout } = useAuth();
  const { language, toggleLanguage, t } = useLanguage();
  const { theme, toggleTheme } = useTheme();
  const toast = useToast();
  const navigate = useNavigate();

  const [apiStatus, setApiStatus] = useState({ online: false, checking: true, message: 'Checking...' });
  const [showProfileMenu, setShowProfileMenu] = useState(false);
  const [isCmdOpen, setIsCmdOpen] = useState(false);
  const profileMenuRef = useRef(null);

  const checkHealth = async () => {
    try {
      const res = await apiService.getHealth();
      if (res.success) {
        setApiStatus({ online: true, checking: false, message: 'Laravel API Connected' });
      } else {
        setApiStatus({ online: false, checking: false, message: 'API Responded with error' });
      }
    } catch (err) {
      setApiStatus({ online: false, checking: false, message: 'API Disconnected' });
    }
  };

  useEffect(() => {
    checkHealth();
    const interval = setInterval(checkHealth, 15000);
    return () => clearInterval(interval);
  }, []);

  // Close profile dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (profileMenuRef.current && !profileMenuRef.current.contains(event.target)) {
        setShowProfileMenu(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleLogout = async () => {
    await logout();
    toast.info('Logged out successfully.');
    navigate('/login');
  };

  return (
    <>
      <header className="app-header glass-header">
        {/* Left Area: Mobile Toggle & Page Title */}
        <div className="header-left">
          <button className="mobile-toggle-btn" onClick={onToggleSidebar} aria-label="Toggle menu">
            <Menu size={22} />
          </button>
          <div className="header-title-wrapper">
            <h1 className="header-page-title">{pageTitle}</h1>
          </div>
        </div>

        {/* Center Spotlight Command Palette Search Trigger */}
        <div className="header-search" onClick={() => setIsCmdOpen(true)}>
          <Search className="search-icon" size={15} />
          <span className="search-placeholder-text">{t('header.search_placeholder')}</span>
          <kbd className="cmd-shortcut-badge">Ctrl K</kbd>
        </div>

        {/* Right Area: Theme Switcher, Language, Status, POS trigger, Notifications, User Menu */}
        <div className="header-right">
          {/* Theme Toggle Button */}
          <button
            className="theme-toggle-btn"
            onClick={toggleTheme}
            title={theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode'}
          >
            {theme === 'dark' ? <Sun size={17} className="text-warning" /> : <Moon size={17} className="text-primary" />}
          </button>

          {/* Language Switcher */}
          <button
            className="language-toggle-btn"
            onClick={toggleLanguage}
            title="Switch Language (English / தமிழ்)"
          >
            <Globe size={15} />
            <span className="lang-text">{language === 'en' ? 'EN' : 'தமிழ்'}</span>
          </button>

          {/* Live Backend Connection Indicator */}
          <div className="api-status-indicator" title={apiStatus.message}>
            {apiStatus.checking ? (
              <Badge variant="neutral" dot size="sm">{t('header.api_connecting')}</Badge>
            ) : apiStatus.online ? (
              <Badge variant="success" dot size="sm">{t('header.api_online')}</Badge>
            ) : (
              <Badge variant="danger" dot size="sm">{t('header.api_offline')}</Badge>
            )}
          </div>

          {/* Quick Action POS Button */}
          <div className="header-new-invoice-btn">
            <Button
              variant="primary"
              size="sm"
              icon={Plus}
              onClick={() => navigate('/billing')}
            >
              {t('header.new_invoice')}
            </Button>
          </div>

          {/* Notification Bell */}
          <button className="header-icon-btn" aria-label="Notifications">
            <Bell size={18} />
            <span className="notification-dot" />
          </button>

          {/* User Profile Dropdown Menu */}
          <div className="user-profile-wrapper" ref={profileMenuRef}>
            <div
              className="user-profile-menu"
              onClick={() => setShowProfileMenu((prev) => !prev)}
            >
              <div className="user-avatar">
                <User size={18} />
              </div>
              <div className="user-info-text">
                <span className="user-name">{user?.name || 'Owner'}</span>
                <span className="user-role">{currentBusiness?.name || 'My Shop'}</span>
              </div>
              <ChevronDown size={14} className={`user-menu-arrow ${showProfileMenu ? 'arrow-rotated' : ''}`} />
            </div>

            {showProfileMenu && (
              <div className="profile-dropdown-card glass-card">
                <div className="dropdown-user-header">
                  <span className="dropdown-user-name">{user?.name || 'User'}</span>
                  <span className="dropdown-user-email">{user?.email || 'owner@example.com'}</span>
                </div>
                <div className="dropdown-divider" />
                <button
                  className="dropdown-item text-danger"
                  onClick={handleLogout}
                >
                  <LogOut size={16} />
                  <span>{t('sidebar.logout')}</span>
                </button>
              </div>
            )}
          </div>
        </div>
      </header>

      {/* Global Spotlight Search Modal */}
      <CommandPaletteModal isOpen={isCmdOpen} onClose={() => setIsCmdOpen(false)} />
    </>
  );
}
