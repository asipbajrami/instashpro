import axios from 'axios';
import {
  ProductsResponse,
  ProductResponse,
  CategoriesResponse,
  CategoryWithProductsResponse,
  ProductFilters,
} from './types';

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL ? `${process.env.NEXT_PUBLIC_API_URL}/api` : 'http://localhost:8000/api/v1',
  headers: {
    'Content-Type': 'application/json',
  },
});

// Products API
export async function getProducts(filters: ProductFilters = {}): Promise<ProductsResponse> {
  const params = new URLSearchParams();

  if (filters.page) params.append('page', String(filters.page));
  if (filters.per_page) params.append('per_page', String(filters.per_page));
  if (filters.sort) params.append('sort', filters.sort);
  if (filters.category) params.append('category', filters.category);
  if (filters.category_id) params.append('category_id', String(filters.category_id));
  if (filters.type) params.append('type', filters.type);
  if (filters.currency) params.append('currency', filters.currency);
  if (filters.group) params.append('group', filters.group);
  if (filters.min_price !== undefined) params.append('price_min', String(filters.min_price));
  if (filters.max_price !== undefined) params.append('price_max', String(filters.max_price));
  if (filters.price_min) params.append('price_min', String(filters.price_min));
  if (filters.price_max) params.append('price_max', String(filters.price_max));
  if (filters.q) params.append('q', filters.q);
  if (filters.profile) params.append('profile', filters.profile);
  if (filters.has_price) params.append('has_price', '1');

  // Handle attribute filters (supports both 'filter' and 'attributes' keys)
  const attributeFilters = filters.filter || filters.attributes;
  if (attributeFilters) {
    Object.entries(attributeFilters).forEach(([key, values]) => {
      values.forEach(value => {
        params.append(`filter[${key}][]`, value);
      });
    });
  }

  const { data } = await api.get<ProductsResponse>(`/products?${params.toString()}`);
  return data;
}

export async function getProduct(id: number): Promise<ProductResponse> {
  const { data } = await api.get<ProductResponse>(`/products/${id}`);
  return data;
}

export async function searchProducts(query: string, perPage = 24, group?: 'car' | 'tech'): Promise<ProductsResponse> {
  const params: Record<string, string | number> = { q: query, per_page: perPage };
  if (group) params.group = group;

  const { data } = await api.get<ProductsResponse>('/products/search', { params });
  return data;
}

// Advanced Search API
export interface AdvancedSearchParams {
  q?: string;           // General search term (searches all fields)
  name?: string;        // Search in product name
  description?: string; // Search in description
  type?: string;        // Filter by product type
  seller?: string;      // Search by seller username
  min_price?: number;   // Minimum price
  max_price?: number;   // Maximum price
  category_id?: number; // Filter by category
  attr?: Record<string, string[]>; // Attribute filters (Brand: ['Apple', 'Samsung'])
  attr_value?: string;  // Search across any attribute value
  has_price?: boolean;  // Only show products with price
  group?: 'car' | 'tech';
  sort?: 'newest' | 'oldest' | 'price_asc' | 'price_desc';
  per_page?: number;
  page?: number;
}

export async function advancedSearchProducts(params: AdvancedSearchParams): Promise<ProductsResponse> {
  const urlParams = new URLSearchParams();

  if (params.q) urlParams.append('q', params.q);
  if (params.name) urlParams.append('name', params.name);
  if (params.description) urlParams.append('description', params.description);
  if (params.type) urlParams.append('type', params.type);
  if (params.seller) urlParams.append('seller', params.seller);
  if (params.min_price !== undefined) urlParams.append('min_price', String(params.min_price));
  if (params.max_price !== undefined) urlParams.append('max_price', String(params.max_price));
  if (params.category_id) urlParams.append('category_id', String(params.category_id));
  if (params.attr_value) urlParams.append('attr_value', params.attr_value);
  if (params.has_price) urlParams.append('has_price', '1');
  if (params.group) urlParams.append('group', params.group);
  if (params.sort) urlParams.append('sort', params.sort);
  if (params.per_page) urlParams.append('per_page', String(params.per_page));
  if (params.page) urlParams.append('page', String(params.page));

  // Handle attribute filters
  if (params.attr) {
    Object.entries(params.attr).forEach(([attrName, values]) => {
      if (values.length > 0) {
        urlParams.append(`attr[${attrName}]`, values.join(','));
      }
    });
  }

  const { data } = await api.get<ProductsResponse>(`/products/advanced-search?${urlParams.toString()}`);
  return data;
}

// Attributes API
export interface AttributeValuesResponse {
  success: boolean;
  data: {
    id: number;
    name: string;
    slug: string;
    type: string;
    values: { id: number; value: string; is_temp?: boolean }[];
    total_count: number;
  };
}

export async function getAttributeValues(attributeId: number, search?: string, category?: string): Promise<AttributeValuesResponse> {
  const params = new URLSearchParams();
  if (search) params.append('search', search);
  if (category) params.append('category', category);
  const { data } = await api.get<AttributeValuesResponse>(`/attributes/${attributeId}?${params.toString()}`);
  return data;
}

// Categories API
export async function getCategories(group?: 'car' | 'tech'): Promise<CategoriesResponse> {
  const params: Record<string, string> = {};
  if (group) params.group = group;

  const { data } = await api.get<CategoriesResponse>('/categories', { params });
  return data;
}

export async function getCategoryWithProducts(
  slug: string,
  page = 1,
  perPage = 24
): Promise<CategoryWithProductsResponse> {
  const { data } = await api.get<CategoryWithProductsResponse>(`/categories/${slug}`, {
    params: { page, per_page: perPage },
  });
  return data;
}

export default api;
