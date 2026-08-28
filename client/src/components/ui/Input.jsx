import React from 'react';
import './Input.css';

export default function Input({
  label,
  error,
  helperText,
  icon: Icon = null,
  type = 'text',
  id,
  className = '',
  disabled = false,
  ...props
}) {
  const inputId = id || (label ? label.toLowerCase().replace(/\s+/g, '-') : undefined);

  return (
    <div className={`input-field-group ${className}`}>
      {label && (
        <label htmlFor={inputId} className="input-label">
          {label}
        </label>
      )}
      <div className="input-wrapper">
        {Icon && <Icon className="input-icon" size={18} />}
        <input
          id={inputId}
          type={type}
          disabled={disabled}
          className={`input-element ${Icon ? 'input-has-icon' : ''} ${error ? 'input-error' : ''}`}
          {...props}
        />
      </div>
      {error ? (
        <p className="input-error-msg">{error}</p>
      ) : helperText ? (
        <p className="input-helper-msg">{helperText}</p>
      ) : null}
    </div>
  );
}
