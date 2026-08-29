import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Mail, Lock, LogIn } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { useToast } from '../components/ui/ToastContext';
import Input from '../components/ui/Input';
import Button from '../components/ui/Button';

export default function LoginPage() {
  const { login } = useAuth();
  const toast = useToast();
  const navigate = useNavigate();

  const [formData, setFormData] = useState({
    email: 'owner@mybusiness.com',
    password: 'password123',
  });

  const [isLoading, setIsLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  const handleChange = (e) => {
    const { id, value } = e.target;
    setFormData((prev) => ({ ...prev, [id]: value }));
    setErrorMsg('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsLoading(true);
    setErrorMsg('');

    try {
      const res = await login(formData);
      if (res.success) {
        toast.success(`Welcome back, ${res.data.user.name}!`);
        if (res.data.has_business) {
          navigate('/');
        } else {
          navigate('/business-setup');
        }
      } else {
        setErrorMsg(res.message || 'Login failed.');
        toast.error(res.message || 'Invalid credentials.');
      }
    } catch (err) {
      setErrorMsg(err.message || 'Invalid email or password.');
      toast.error(err.message || 'Login error occurred.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="auth-form">
      <div style={{ marginBottom: '20px' }}>
        <h2 style={{ fontSize: '1.25rem', fontWeight: 700, color: 'var(--text-primary)' }}>Owner Login</h2>
        <p style={{ fontSize: '0.875rem', color: 'var(--text-secondary)' }}>Sign in to manage your business.</p>
      </div>

      {errorMsg && (
        <div style={{
          padding: '10px 14px',
          borderRadius: 'var(--radius-md)',
          backgroundColor: 'var(--danger-soft)',
          color: 'var(--danger)',
          fontSize: '0.875rem',
          fontWeight: 500,
          marginBottom: '16px',
        }}>
          {errorMsg}
        </div>
      )}

      <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
        <Input
          id="email"
          label="Email Address"
          type="email"
          value={formData.email}
          onChange={handleChange}
          placeholder="owner@mybusiness.com"
          icon={Mail}
          required
        />
        <Input
          id="password"
          label="Password"
          type="password"
          value={formData.password}
          onChange={handleChange}
          placeholder="••••••••"
          icon={Lock}
          required
        />
      </div>

      <div style={{ marginTop: '24px' }}>
        <Button type="submit" variant="primary" fullWidth isLoading={isLoading} icon={LogIn}>
          Log In to Dashboard
        </Button>
      </div>

      <div style={{ textAlign: 'center', marginTop: '20px', fontSize: '0.875rem', color: 'var(--text-secondary)' }}>
        Don't have a business account?{' '}
        <Link to="/register" style={{ fontWeight: 600, color: 'var(--brand-primary)' }}>
          Create Account
        </Link>
      </div>
    </form>
  );
}
