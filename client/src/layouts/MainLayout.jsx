import React, { useState } from 'react';
import { Outlet } from 'react-router-dom';
import Sidebar from './Sidebar';
import Header from './Header';
import VoiceWidget from '../components/voice/VoiceWidget';
import './MainLayout.css';

export default function MainLayout({ pageTitle }) {
  const [sidebarOpen, setSidebarOpen] = useState(false);

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
        <Header onToggleSidebar={toggleSidebar} pageTitle={pageTitle} />

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
