import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Store, Phone, MapPin, DollarSign, Tag, ArrowRight, Sparkles, Building2 } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { useToast } from '../components/ui/ToastContext';
import Input from '../components/ui/Input';
import Button from '../components/ui/Button';
import './BusinessSetupPage.css';

export default function BusinessSetupPage() {
  const { createBusiness, user } = useAuth();
  const toast = useToast();
  const navigate = useNavigate();
  const [isLoading, setIsLoading] = useState(false);
  const [errors, setErrors] = useState({});

  const [formData, setFormData] = useState({
    name: '',
    type: 'retail',
    category: 'Grocery & General Store',
    phone: user?.phone || '',
    currency: 'INR',
    address: '',
    city: '',
    state: '',
    pincode: '',
  });

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: null }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsLoading(true);
    setErrors({});

    try {
      const res = await createBusiness(formData);
      if (res.success) {
        toast.success(`Welcome! ${formData.name} is ready for business.`);
        navigate('/');
      } else {
        toast.error(res.message || 'Could not create business.');
        if (res.errors) setErrors(res.errors);
      }
    } catch (err) {
      toast.error(err.message || 'Server validation error.');
      if (err.errors) setErrors(err.errors);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="setup-container">
      <div className="setup-card">
        <div className="setup-header">
          <div className="setup-icon-badge">
            <Building2 size={26} />
          </div>
          <span className="setup-step-tag">
            <Sparkles size={12} /> STEP 2 OF 2 • BUSINESS ONBOARDING
          </span>
          <h1 className="setup-title">Setup Your Business</h1>
          <p className="setup-subtitle">
            Tell us about your shop or enterprise so your AI Copilot can manage your bills and stock.
          </p>
        </div>

        <form onSubmit={handleSubmit} className="setup-form">
          <div className="form-section">
            <h3 className="section-title">Core Details</h3>
            <div className="form-grid-2">
              <Input
                label="Business Name *"
                name="name"
                value={formData.name}
                onChange={handleChange}
                placeholder="e.g. Metro Retail Store"
                icon={Store}
                error={errors.name ? errors.name[0] : null}
                required
              />

              <div className="input-field-group">
                <label className="input-label">Business Type *</label>
                <select
                  name="type"
                  value={formData.type}
                  onChange={handleChange}
                  className="custom-select"
                >
                  <option value="retail">Retail Shop / Counter POS</option>
                  <option value="wholesale">Wholesale & Distribution</option>
                  <option value="service">Service & Repair Business</option>
                  <option value="restaurant">Restaurant / Bakery / Cafe</option>
                  <option value="other">Other Business</option>
                </select>
              </div>
            </div>

            <div className="form-grid-2">
              <Input
                label="Industry Category"
                name="category"
                value={formData.category}
                onChange={handleChange}
                placeholder="e.g. Grocery, Mobile Shop, Apparel"
                icon={Tag}
              />

              <Input
                label="Contact Phone"
                name="phone"
                value={formData.phone}
                onChange={handleChange}
                placeholder="+91 98765 43210"
                icon={Phone}
              />
            </div>
          </div>

          <div className="form-section">
            <h3 className="section-title">Currency & Location</h3>
            <div className="form-grid-2">
              <div className="input-field-group">
                <label className="input-label">Primary Currency *</label>
                <select
                  name="currency"
                  value={formData.currency}
                  onChange={handleChange}
                  className="custom-select"
                >
                  <option value="INR">INR (₹) - Indian Rupee</option>
                  <option value="USD">USD ($) - US Dollar</option>
                  <option value="EUR">EUR (€) - Euro</option>
                  <option value="GBP">GBP (£) - British Pound</option>
                  <option value="AED">AED (د.إ) - UAE Dirham</option>
                </select>
              </div>

              <Input
                label="City / Town"
                name="city"
                value={formData.city}
                onChange={handleChange}
                placeholder="e.g. Mumbai, New Delhi"
                icon={MapPin}
              />
            </div>

            <Input
              label="Shop Address"
              name="address"
              value={formData.address}
              onChange={handleChange}
              placeholder="e.g. Shop No. 12, Main Market Road"
              icon={MapPin}
            />
          </div>

          <div className="setup-footer">
            <Button
              type="submit"
              variant="primary"
              size="lg"
              fullWidth
              isLoading={isLoading}
              icon={ArrowRight}
            >
              Launch My Business Copilot
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
}
