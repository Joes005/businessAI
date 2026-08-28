import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { User, Mail, Lock, Phone, ArrowRight } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { useToast } from '../components/ui/ToastContext';
import Input from '../components/ui/Input';
import Button from '../components/ui/Button';

export default function RegisterPage() {
  const { register } = useAuth();
  const toast = useToast();
  const navigate = useNavigate();

  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    password: '',
  });

  const [isLoading, setIsLoading] = useState(false);
  const [errors, setErrors] = useState({});

  const handleChange = (e) => {
    const { id, value } = e.target;
    setFormData((prev) => ({ ...prev, [id]: value }));
    if (errors[id]) setErrors((prev) => ({ ...prev, [id]: null }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsLoading(true);
    setErrors({});

    try {
      const res = await register(formData);
      if (res.success) {
        toast.success(`Account created! Let's setup your business.`);
        navigate('/business-setup');
      } else {
        toast.error(res.message || 'Registration failed.');
        if (res.errors) setErrors(res.errors);
      }
    } catch (err) {
      toast.error(err.message || 'Validation error.');
      if (err.errors) setErrors(err.errors);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="auth-form">
      <div style={{ marginBottom: '20px' }}>
        <h2 style={{ fontSize: '1.25rem', fontWeight: 700, color: 'var(--slate-900)' }}>Create Owner Account</h2>
        <p style={{ fontSize: '0.875rem', color: 'var(--slate-500)' }}>Start managing your business in 60 seconds.</p>
      </div>

      <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
        <Input
          id="name"
          label="Owner Full Name *"
          type="text"
          value={formData.name}
          onChange={handleChange}
          placeholder="Alex Morgan"
          icon={User}
          error={errors.name ? errors.name[0] : null}
          required
        />
        <Input
          id="email"
          label="Email Address *"
          type="email"
          value={formData.email}
          onChange={handleChange}
          placeholder="owner@mybusiness.com"
          icon={Mail}
          error={errors.email ? errors.email[0] : null}
          required
        />
        <Input
          id="phone"
          label="Phone Number"
          type="text"
          value={formData.phone}
          onChange={handleChange}
          placeholder="+91 98765 43210"
          icon={Phone}
          error={errors.phone ? errors.phone[0] : null}
        />
        <Input
          id="password"
          label="Password (min 8 characters) *"
          type="password"
          value={formData.password}
          onChange={handleChange}
          placeholder="••••••••"
          icon={Lock}
          error={errors.password ? errors.password[0] : null}
          required
        />
      </div>

      <div style={{ marginTop: '24px' }}>
        <Button type="submit" variant="primary" fullWidth isLoading={isLoading} icon={ArrowRight}>
          Continue to Business Setup
        </Button>
      </div>

      <div style={{ textAlign: 'center', marginTop: '20px', fontSize: '0.875rem', color: 'var(--slate-600)' }}>
        Already have an account?{' '}
        <Link to="/login" style={{ fontWeight: 600, color: 'var(--primary-600)' }}>
          Sign In
        </Link>
      </div>
    </form>
  );
}
