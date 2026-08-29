import React, { useState, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Search,
  LayoutDashboard,
  Receipt,
  Package,
  Users,
  BarChart3,
  Bot,
  Settings,
  ArrowRight,
  Sparkles,
  X,
} from 'lucide-react';
import './CommandPaletteModal.css';

export default function CommandPaletteModal({ isOpen, onClose }) {
  const [query, setQuery] = useState('');
  const inputRef = useRef(null);
  const navigate = useNavigate();

  const commands = [
    { id: 'dashboard', title: 'Go to Dashboard', category: 'Navigation', icon: LayoutDashboard, path: '/' },
    { id: 'billing', title: 'Open POS Quick Billing', category: 'Actions', icon: Receipt, path: '/billing', badge: 'Fast POS' },
    { id: 'products', title: 'Manage Product Inventory', category: 'Navigation', icon: Package, path: '/products' },
    { id: 'customers', title: 'View Customers & Receivables', category: 'Navigation', icon: Users, path: '/customers' },
    { id: 'reports', title: 'Business Financial Analytics', category: 'Reports', icon: BarChart3, path: '/reports' },
    { id: 'copilot', title: 'Ask AI Employee (Copilot)', category: 'AI Tools', icon: Bot, path: '/copilot', isAi: true },
    { id: 'settings', title: 'Store Settings & Tax Config', category: 'Navigation', icon: Settings, path: '/settings' },
  ];

  const filteredCommands = commands.filter((cmd) =>
    cmd.title.toLowerCase().includes(query.toLowerCase()) ||
    cmd.category.toLowerCase().includes(query.toLowerCase())
  );

  useEffect(() => {
    if (isOpen) {
      setTimeout(() => inputRef.current?.focus(), 50);
    } else {
      setQuery('');
    }
  }, [isOpen]);

  // Global Ctrl + K listener
  useEffect(() => {
    const handleKeyDown = (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        if (isOpen) onClose();
        else openPalette();
      }
      if (e.key === 'Escape' && isOpen) {
        onClose();
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isOpen]);

  const openPalette = () => {
    // Parent handles state opening
  };

  const handleSelect = (path) => {
    navigate(path);
    onClose();
  };

  if (!isOpen) return null;

  return (
    <div className="cmd-backdrop" onClick={onClose}>
      <div className="cmd-modal glass-card animate-fade-in" onClick={(e) => e.stopPropagation()}>
        {/* Input Bar */}
        <div className="cmd-header">
          <Search className="cmd-search-icon" size={18} />
          <input
            ref={inputRef}
            type="text"
            className="cmd-input"
            placeholder="Type a command or search page (e.g. POS, Products, Copilot)..."
            value={query}
            onChange={(e) => setQuery(e.target.value)}
          />
          <button className="cmd-close-btn" onClick={onClose}>
            <X size={16} />
          </button>
        </div>

        {/* Results List */}
        <div className="cmd-body">
          {filteredCommands.length === 0 ? (
            <div className="cmd-empty">No matching commands found.</div>
          ) : (
            <div className="cmd-list">
              {filteredCommands.map((cmd) => {
                const Icon = cmd.icon;
                return (
                  <div
                    key={cmd.id}
                    className={`cmd-item ${cmd.isAi ? 'cmd-item-ai' : ''}`}
                    onClick={() => handleSelect(cmd.path)}
                  >
                    <div className="cmd-item-left">
                      <div className="cmd-icon-wrapper">
                        <Icon size={16} />
                      </div>
                      <span className="cmd-item-title">{cmd.title}</span>
                    </div>

                    <div className="cmd-item-right">
                      {cmd.badge && <span className="cmd-badge">{cmd.badge}</span>}
                      {cmd.isAi && <Sparkles size={14} className="cmd-sparkle" />}
                      <span className="cmd-category">{cmd.category}</span>
                      <ArrowRight size={14} className="cmd-arrow" />
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>

        {/* Modal Footer */}
        <div className="cmd-footer">
          <span>Tip: Use <kbd>Ctrl</kbd> + <kbd>K</kbd> to open anytime</span>
          <span>Press <kbd>Esc</kbd> to exit</span>
        </div>
      </div>
    </div>
  );
}
