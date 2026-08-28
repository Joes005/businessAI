import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import apiService from '../services/api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(localStorage.getItem('auth_token'));
  const [currentBusiness, setCurrentBusiness] = useState(null);
  const [businesses, setBusinesses] = useState([]);
  const [isLoading, setIsLoading] = useState(true);

  // Load user profile if token exists
  const checkAuth = useCallback(async () => {
    const savedToken = localStorage.getItem('auth_token');
    if (!savedToken) {
      setUser(null);
      setCurrentBusiness(null);
      setBusinesses([]);
      setIsLoading(false);
      return;
    }

    try {
      const res = await apiService.auth.me();
      if (res.success && res.data) {
        setUser(res.data.user);
        setCurrentBusiness(res.data.current_business);
        setBusinesses(res.data.user.businesses || []);
      }
    } catch (err) {
      console.warn('Auth token expired or invalid:', err);
      localStorage.removeItem('auth_token');
      setToken(null);
      setUser(null);
      setCurrentBusiness(null);
      setBusinesses([]);
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    checkAuth();
  }, [checkAuth]);

  // Handle Registration
  const register = async (formData) => {
    const res = await apiService.auth.register(formData);
    if (res.success && res.data) {
      const newToken = res.data.token;
      localStorage.setItem('auth_token', newToken);
      setToken(newToken);
      setUser(res.data.user);
      setCurrentBusiness(null);
      setBusinesses([]);
    }
    return res;
  };

  // Handle Login
  const login = async (credentials) => {
    const res = await apiService.auth.login(credentials);
    if (res.success && res.data) {
      const newToken = res.data.token;
      localStorage.setItem('auth_token', newToken);
      setToken(newToken);
      setUser(res.data.user);
      setCurrentBusiness(res.data.current_business);
      setBusinesses(res.data.user?.businesses || []);
    }
    return res;
  };

  // Handle Logout
  const logout = async () => {
    try {
      await apiService.auth.logout();
    } catch (err) {
      console.error('Logout error:', err);
    } finally {
      localStorage.removeItem('auth_token');
      setToken(null);
      setUser(null);
      setCurrentBusiness(null);
      setBusinesses([]);
    }
  };

  // Create Business (Onboarding)
  const createBusiness = async (businessData) => {
    const res = await apiService.businesses.create(businessData);
    if (res.success && res.data) {
      const newBusiness = res.data.business;
      setCurrentBusiness(newBusiness);
      if (res.data.user) {
        setUser(res.data.user);
        setBusinesses(res.data.user.businesses || []);
      } else {
        setBusinesses((prev) => [...prev, newBusiness]);
      }
    }
    return res;
  };

  // Switch Active Business Context
  const switchBusiness = async (businessId) => {
    const res = await apiService.businesses.switch(businessId);
    if (res.success && res.data) {
      setCurrentBusiness(res.data.current_business);
    }
    return res;
  };

  const value = {
    user,
    token,
    currentBusiness,
    businesses,
    isAuthenticated: !!user,
    hasBusiness: !!currentBusiness || (businesses && businesses.length > 0),
    isLoading,
    register,
    login,
    logout,
    createBusiness,
    switchBusiness,
    checkAuth,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
