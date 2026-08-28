import React from 'react';
import './LoadingSpinner.css';

export default function LoadingSpinner({ size = 'md', text = null }) {
  return (
    <div className="spinner-container">
      <div className={`spinner spinner-${size}`} />
      {text && <span className="spinner-text">{text}</span>}
    </div>
  );
}
