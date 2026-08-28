import React, { useState, useEffect, useCallback } from 'react';
import {
  Package,
  Plus,
  Search,
  Filter,
  AlertTriangle,
  Edit,
  Trash2,
  TrendingUp,
  History,
  Layers,
  Sparkles,
  ArrowUpRight,
  ArrowDownRight,
} from 'lucide-react';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Input from '../components/ui/Input';
import Badge from '../components/ui/Badge';
import Modal from '../components/ui/Modal';
import { useAuth } from '../contexts/AuthContext';
import { useLanguage } from '../contexts/LanguageContext';
import { useToast } from '../components/ui/ToastContext';
import apiService from '../services/api';
import './ProductsPage.css';

export default function ProductsPage() {
  const { currentBusiness } = useAuth();
  const { t } = useLanguage();
  const toast = useToast();

  const currencySymbol = currentBusiness?.currency === 'USD' ? '$' : currentBusiness?.currency === 'EUR' ? '€' : '₹';

  // Products Data State
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('');
  const [lowStockFilter, setLowStockFilter] = useState(false);

  // Summary Metrics
  const [totalProducts, setTotalProducts] = useState(0);
  const [totalStockValue, setTotalStockValue] = useState(0);
  const [lowStockCount, setLowStockCount] = useState(0);

  // Product Add / Edit Modal State
  const [isProductModalOpen, setIsProductModalOpen] = useState(false);
  const [editingProduct, setEditingProduct] = useState(null);
  const [submittingProduct, setSubmittingProduct] = useState(false);
  const [productFormErrors, setProductFormErrors] = useState({});

  const [productFormData, setProductFormData] = useState({
    name: '',
    category_id: '',
    sku: '',
    barcode: '',
    unit: 'pcs',
    purchase_price: '',
    selling_price: '',
    stock_quantity: '0',
    low_stock_threshold: '5',
    description: '',
  });

  // Category Add Modal State
  const [isCategoryModalOpen, setIsCategoryModalOpen] = useState(false);
  const [categoryName, setCategoryName] = useState('');
  const [submittingCategory, setSubmittingCategory] = useState(false);

  // Stock Adjustment Modal State
  const [isAdjustModalOpen, setIsAdjustModalOpen] = useState(false);
  const [adjustProduct, setAdjustProduct] = useState(null);
  const [adjustFormData, setAdjustFormData] = useState({
    type: 'PURCHASE',
    quantity: '1',
    unit_cost: '',
    notes: '',
  });
  const [submittingAdjust, setSubmittingAdjust] = useState(false);

  // Traceable Movement Log Drawer State
  const [isLogDrawerOpen, setIsLogDrawerOpen] = useState(false);
  const [logProduct, setLogProduct] = useState(null);
  const [movementsLog, setMovementsLog] = useState([]);
  const [loadingLog, setLoadingLog] = useState(false);

  // Fetch Products with filters
  const fetchProducts = useCallback(async () => {
    setLoading(true);
    try {
      const res = await apiService.products.getAll({
        search: searchQuery,
        category_id: selectedCategory,
        low_stock: lowStockFilter ? 'true' : 'false',
        per_page: 50,
      });

      if (res.success && res.data) {
        setProducts(res.data.products?.data || []);
        setTotalProducts(res.data.total_products || 0);
        setTotalStockValue(res.data.total_stock_value || 0);
        setLowStockCount(res.data.low_stock_count || 0);
      }
    } catch (err) {
      toast.error('Failed to load products list.');
    } finally {
      setLoading(false);
    }
  }, [searchQuery, selectedCategory, lowStockFilter, toast]);

  // Fetch Categories
  const fetchCategories = useCallback(async () => {
    try {
      const res = await apiService.categories.getAll();
      if (res.success && res.data) {
        setCategories(res.data.categories || []);
      }
    } catch (err) {
      console.error('Failed to load categories:', err);
    }
  }, []);

  useEffect(() => {
    fetchCategories();
  }, [fetchCategories]);

  useEffect(() => {
    const timer = setTimeout(() => {
      fetchProducts();
    }, 300);
    return () => clearTimeout(timer);
  }, [fetchProducts]);

  // Open Product Modal for Create or Edit
  const openProductModal = (product = null) => {
    if (product) {
      setEditingProduct(product);
      setProductFormData({
        name: product.name,
        category_id: product.category_id || '',
        sku: product.sku || '',
        barcode: product.barcode || '',
        unit: product.unit || 'pcs',
        purchase_price: product.purchase_price || '',
        selling_price: product.selling_price || '',
        stock_quantity: product.stock_quantity || '0',
        low_stock_threshold: product.low_stock_threshold || '5',
        description: product.description || '',
      });
    } else {
      setEditingProduct(null);
      setProductFormData({
        name: '',
        category_id: '',
        sku: '',
        barcode: '',
        unit: 'pcs',
        purchase_price: '',
        selling_price: '',
        stock_quantity: '0',
        low_stock_threshold: '5',
        description: '',
      });
    }
    setProductFormErrors({});
    setIsProductModalOpen(true);
  };

  // Submit Product Form
  const handleProductSubmit = async (e) => {
    e.preventDefault();
    setSubmittingProduct(true);
    setProductFormErrors({});

    try {
      if (editingProduct) {
        const res = await apiService.products.update(editingProduct.id, productFormData);
        if (res.success) {
          toast.success(`Product '${productFormData.name}' updated!`);
          setIsProductModalOpen(false);
          fetchProducts();
        }
      } else {
        const res = await apiService.products.create(productFormData);
        if (res.success) {
          toast.success(`Product '${productFormData.name}' added to inventory!`);
          setIsProductModalOpen(false);
          fetchProducts();
        }
      }
    } catch (err) {
      toast.error(err.message || 'Could not save product.');
      if (err.errors) setProductFormErrors(err.errors);
    } finally {
      setSubmittingProduct(false);
    }
  };

  // Delete Product
  const handleDeleteProduct = async (id, name) => {
    if (!window.confirm(`Are you sure you want to delete '${name}'?`)) return;

    try {
      const res = await apiService.products.delete(id);
      if (res.success) {
        toast.success(`Product '${name}' deleted.`);
        fetchProducts();
      }
    } catch (err) {
      toast.error(err.message || 'Could not delete product.');
    }
  };

  // Create Category
  const handleCategorySubmit = async (e) => {
    e.preventDefault();
    if (!categoryName.trim()) return;

    setSubmittingCategory(true);
    try {
      const res = await apiService.categories.create({ name: categoryName });
      if (res.success) {
        toast.success(`Category '${categoryName}' created!`);
        setCategoryName('');
        setIsCategoryModalOpen(false);
        fetchCategories();
      }
    } catch (err) {
      toast.error(err.message || 'Could not create category.');
    } finally {
      setSubmittingCategory(false);
    }
  };

  // Open Stock Adjustment Modal
  const openAdjustModal = (product) => {
    setAdjustProduct(product);
    setAdjustFormData({
      type: 'PURCHASE',
      quantity: '1',
      unit_cost: product.purchase_price || '',
      notes: '',
    });
    setIsAdjustModalOpen(true);
  };

  // Submit Stock Adjustment
  const handleAdjustSubmit = async (e) => {
    e.preventDefault();
    if (!adjustProduct) return;

    setSubmittingAdjust(true);
    try {
      const payload = {
        product_id: adjustProduct.id,
        type: adjustFormData.type,
        quantity: parseFloat(adjustFormData.quantity) || 0,
        unit_cost: parseFloat(adjustFormData.unit_cost) || 0,
        notes: adjustFormData.notes,
      };

      const res = await apiService.stock.adjust(payload);
      if (res.success) {
        toast.success(`Stock adjusted for '${adjustProduct.name}'.`);
        setIsAdjustModalOpen(false);
        fetchProducts();
      }
    } catch (err) {
      toast.error(err.message || 'Could not adjust stock.');
    } finally {
      setSubmittingAdjust(false);
    }
  };

  // Open Movement Log Drawer
  const openLogDrawer = async (product) => {
    setLogProduct(product);
    setIsLogDrawerOpen(true);
    setLoadingLog(true);

    try {
      const res = await apiService.stock.getMovements(product.id);
      if (res.success && res.data) {
        setMovementsLog(res.data.movements || []);
      }
    } catch (err) {
      toast.error('Failed to load stock movements log.');
    } finally {
      setLoadingLog(false);
    }
  };

  return (
    <div className="products-page">
      {/* Header Banner */}
      <div className="products-header">
        <div>
          <h2>{t('products.title')}</h2>
          <p>{t('products.subtitle')}</p>
        </div>

        <div className="products-actions">
          <Button variant="secondary" icon={Layers} onClick={() => setIsCategoryModalOpen(true)}>
            + Category
          </Button>
          <Button variant="primary" icon={Plus} onClick={() => openProductModal(null)}>
            {t('products.add_product')}
          </Button>
        </div>
      </div>

      {/* KPI Cards Grid */}
      <div className="products-kpi-grid">
        <Card padding="compact" className="kpi-card">
          <div className="kpi-icon-badge bg-primary">
            <Package size={22} />
          </div>
          <div className="kpi-content">
            <span className="kpi-label">{t('products.total_products')}</span>
            <h3 className="kpi-value">{totalProducts}</h3>
          </div>
        </Card>

        <Card padding="compact" className="kpi-card">
          <div className="kpi-icon-badge bg-emerald">
            <TrendingUp size={22} />
          </div>
          <div className="kpi-content">
            <span className="kpi-label">{t('products.total_stock_value')}</span>
            <h3 className="kpi-value">{currencySymbol}{totalStockValue.toLocaleString()}</h3>
          </div>
        </Card>

        <Card padding="compact" className="kpi-card">
          <div className="kpi-icon-badge bg-rose">
            <AlertTriangle size={22} />
          </div>
          <div className="kpi-content">
            <span className="kpi-label">{t('products.low_stock')}</span>
            <h3 className="kpi-value">{lowStockCount}</h3>
          </div>
        </Card>
      </div>

      {/* Products Directory Card */}
      <Card padding="none">
        {/* Search & Filter Bar */}
        <div className="products-filter-bar">
          <div className="search-input-box">
            <Search size={18} className="search-icon" />
            <input
              type="text"
              placeholder={t('products.search_products')}
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="search-input"
            />
          </div>

          <div className="filters-row">
            <select
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
              className="custom-select-sm"
            >
              <option value="">{t('products.all_categories')}</option>
              {categories.map((cat) => (
                <option key={cat.id} value={cat.id}>
                  {cat.name}
                </option>
              ))}
            </select>

            <button
              className={`filter-btn-toggle ${lowStockFilter ? 'toggle-active' : ''}`}
              onClick={() => setLowStockFilter(!lowStockFilter)}
            >
              <AlertTriangle size={16} />
              <span>{t('products.low_stock_only')}</span>
            </button>
          </div>
        </div>

        {/* Table View */}
        <div className="table-responsive">
          <table className="custom-table">
            <thead>
              <tr>
                <th>{t('products.product_name')}</th>
                <th>{t('products.sku')}</th>
                <th>{t('products.category')}</th>
                <th>{t('products.purchase_price')}</th>
                <th>{t('products.selling_price')}</th>
                <th>{t('products.stock_quantity')}</th>
                <th>{t('products.status')}</th>
                <th style={{ textAlign: 'right' }}>{t('common.actions')}</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={8} className="text-center py-6">
                    {t('common.loading')}
                  </td>
                </tr>
              ) : products.length === 0 ? (
                <tr>
                  <td colSpan={8} className="text-center py-6 text-muted">
                    No products found in inventory. Click '{t('products.add_product')}' to add your first item.
                  </td>
                </tr>
              ) : (
                products.map((prod) => (
                  <tr key={prod.id}>
                    <td>
                      <div className="product-name-cell">
                        <span className="font-semibold text-slate-900">{prod.name}</span>
                        {prod.barcode && <span className="product-barcode-tag">{prod.barcode}</span>}
                      </div>
                    </td>
                    <td className="font-mono text-xs">{prod.sku || '—'}</td>
                    <td>{prod.category ? prod.category.name : 'Uncategorized'}</td>
                    <td className="font-medium text-slate-700">
                      {currencySymbol}{prod.purchase_price ? prod.purchase_price.toLocaleString() : '0'}
                    </td>
                    <td className="font-bold text-slate-900">
                      {currencySymbol}{prod.selling_price ? prod.selling_price.toLocaleString() : '0'}
                    </td>
                    <td>
                      <span className="font-semibold">
                        {prod.stock_quantity} {prod.unit}
                      </span>
                    </td>
                    <td>
                      {prod.stock_quantity <= 0 ? (
                        <Badge variant="danger" size="sm">{t('products.out_of_stock')}</Badge>
                      ) : prod.is_low_stock ? (
                        <Badge variant="warning" size="sm">{t('products.low_stock')}</Badge>
                      ) : (
                        <Badge variant="success" size="sm">{t('products.in_stock')}</Badge>
                      )}
                    </td>
                    <td>
                      <div className="table-actions-cell" style={{ justifyContent: 'flex-end' }}>
                        <Button
                          variant="outline"
                          size="sm"
                          icon={TrendingUp}
                          onClick={() => openAdjustModal(prod)}
                          title="Adjust Stock"
                        >
                          Stock
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          icon={History}
                          onClick={() => openLogDrawer(prod)}
                          title="View Audit Log"
                        />
                        <Button
                          variant="ghost"
                          size="sm"
                          icon={Edit}
                          onClick={() => openProductModal(prod)}
                          title="Edit Product"
                        />
                        <Button
                          variant="ghost"
                          size="sm"
                          icon={Trash2}
                          className="text-danger"
                          onClick={() => handleDeleteProduct(prod.id, prod.name)}
                          title="Delete Product"
                        />
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  );
}
