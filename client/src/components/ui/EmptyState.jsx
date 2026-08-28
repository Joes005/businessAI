import React from 'react';
import { PackageOpen } from 'lucide-react';
import './EmptyState.css';

export default function EmptyState({
  icon: Icon = PackageOpen,
  title = 'No Items Available',
  description = 'Get started by creating a new entry.',
  action = null,
}) {
  return (
    <div className="empty-state">
      <div className="empty-state-icon">
        <Icon size={36} />
      </div>
      <h4 className="empty-state-title">{title}</h4>
      <p className="empty-state-desc">{description}</p>
      {action && <div className="empty-state-action">{action}</div>}
    </div>
  );
}
