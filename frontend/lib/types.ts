// Product Types
export interface ProductImage {
  id: number;
  url: string;
  type: string;
  is_primary: boolean;
}

export interface ProductCategory {
  id: number;
  name: string;
  slug: string;
}

export interface ProductAttribute {
  attribute_id: number;
  name: string;
  value: string;
}

export interface Product {
  id: number;
  name: string;
  type: string;
  description: string;
  price: string;
  discount_price: string;
  monthly_price: string;
  currency: string;
  images: ProductImage[];
  categories: ProductCategory[];
  attributes: ProductAttribute[];
  instagram_link: string | null;
  seller_username: string | null;
  created_at: string;
}

// Category Types
export interface Category {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  product_count: number;
  children: Category[];
}

// Facet Types
export interface CategoryFacet {
  id: number;
  name: string;
  slug: string;
  count: number;
}

export interface AttributeValueFacet {
  id: number;
  value: string;
  count: number;
}

export interface AttributeFacet {
  id: number;
  name: string;
  group: string | null;
  values: AttributeValueFacet[];
}

export interface TypeFacet {
  value: string;
  label: string;
  count: number;
}

export interface CurrencyFacet {
  value: string;
  label: string;
  count: number;
}

export interface ProfileFacet {
  id: number;
  username: string;
  full_name: string | null;
  profile_pic_url: string | null;
  is_verified: boolean;
  product_count: number;
}

export interface PriceRange {
  min: number;
  max: number;
}

export interface Facets {
  categories: CategoryFacet[];
  attributes: AttributeFacet[];
  types: TypeFacet[];
  currencies: CurrencyFacet[];
  profiles: ProfileFacet[];
  price_range: PriceRange;
}

// Pagination
export interface Pagination {
  current_page: number;
  per_page: number;
  total: number;
  total_pages: number;
  has_more?: boolean;
}

// API Response Types
export interface ProductsResponse {
  success: boolean;
  data: {
    items: Product[];
    pagination: Pagination;
    facets: Facets;
  };
}

export interface ProductResponse {
  success: boolean;
  data: Product;
}

export interface CategoriesResponse {
  success: boolean;
  data: Category[];
}

export interface CategoryWithProductsResponse {
  success: boolean;
  data: {
    category: Category;
    products: {
      items: Product[];
      pagination: Pagination;
      facets: Facets | null;
    };
  };
}

// Filter Types
export interface ProductFilters {
  page?: number;
  per_page?: number;
  sort?: 'newest' | 'oldest' | 'price_asc' | 'price_desc';
  category?: string;
  category_id?: number;
  type?: string;
  currency?: string;
  group?: 'car' | 'tech';
  min_price?: number;
  max_price?: number;
  price_min?: number;
  price_max?: number;
  q?: string;
  filter?: Record<string, string[]>;
  attributes?: Record<string, string[]>;
  profile?: string;
  has_price?: boolean;
}
