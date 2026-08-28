import React from 'react';
import { Outlet } from 'react-router-dom';
import { Sparkles, ShieldCheck } from 'lucide-react';
import './AuthLayout.css';

export default function AuthLayout() {
  return (
    <div className="auth-layout">
      <div className="auth-card-wrapper">
        <div className="auth-brand-header">
          <div className="auth-logo-badge">
            <Sparkles size={28} />
          </div>
          <h1 className="auth-app-name">AI BUSINESS COPILOT</h1>
          <p className="auth-app-tagline">Your Digital Business Employee</p>
        </div>

        <div className="auth-form-card">
          <Outlet />
        </div>

        <div className="auth-footer-note">
          <ShieldCheck size={14} />
          <span>Multi-Tenant Business Architecture • Bank-Grade Security</span>
        </div>
      </div>
    </div>
  );
}
