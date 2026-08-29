import React, { useState } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import Sidebar from './Sidebar';
import Header from './Header';
import VoiceWidget from '../components/voice/VoiceWidget';
import './MainLayout.css';

export default function MainLayout() {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const location = useLocation();

  // Route Title Mapping
  const routeTitles = {
    '/': 'Dashboard Overview',
    '/billing': 'POS Billing Counter',
    '/products': 'Products & Inventory',
    '/customers': 'Customers & Debtors',
    '/reports': 'Financial Reports',
    '/copilot': 'AI Copilot Assistant',
    '/settings': 'Settings & Automation',
  };

  const currentTitle = routeTitles[location.pathname] || 'Business Copilot';

  const toggleSidebar = () => {
    setSidebarOpen((prev) => !prev);
  };

  return (
    <div className="app-layout">
      {/* Navigation Sidebar */}
      <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />

      {/* Main Content Wrapper */}
      <div className="layout-content-wrapper">
        {/* Top Header */}
        <Header onToggleSidebar={toggleSidebar} pageTitle={currentTitle} />

        {/* Dynamic Page Content */}
        <main className="main-content-container">
          <Outlet />
        </main>
      </div>

      {/* Global Floating Voice Command Widget */}
      <VoiceWidget />
    </div>
  );
}
