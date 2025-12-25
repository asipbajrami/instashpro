export interface User {
  id: number;
  name: string;
  email: string;
}

export interface InstagramProfile {
  id: number;
  username: string;
  full_name: string | null;
  ig_id: string;
  status: 'active' | 'inactive' | 'suspended';
  biography: string | null;
  follower_count: number;
  following_count: number;
  media_count: number;
  local_post_count: number;
  profile_pic_url: string | null;
  is_private: boolean;
  is_verified: boolean;
  is_business: boolean;
  initial_scrape_done: boolean;
  initial_scrape_at: string | null;
  posts_per_request: number;
  scrape_interval_hours: number;
  last_scraped_at: string | null;
  next_scrape_at: string | null;
  coverage_percentage?: number;
  created_at: string;
  updated_at: string;
}

export interface StructureOutput {
  id: number;
  key: string;
  type: string;
  description: string;
  parent_key: string;
  used_for: string;
  required: boolean;
  enum_values: string | null;
  product_attribute_id: number | null;
  product_attribute?: ProductAttribute;
  created_at: string;
  updated_at: string;
}

export interface StructureOutputGroup {
  id: number;
  used_for: string;
  description: string;
  structure_outputs_count?: number;
  created_at: string;
  updated_at: string;
}

export interface ProductAttribute {
  id: number;
  name: string;
  slug?: string;
  type?: string;
  values_count?: number;
}

export interface ProductAttributeValue {
  id: number;
  product_attribute_id: number;
  value: string;
  ai_value: string;
  type_value: string;
  is_temp: boolean;
  score: number;
  created_at?: string;
  updated_at?: string;
}

export interface DashboardStats {
  profiles: {
    total: number;
    active: number;
    inactive: number;
    suspended: number;
    due_for_scrape: number;
  };
  structure_outputs: {
    total: number;
    by_group: Record<string, number>;
    by_used_for: Record<string, number>;
  };
  structure_groups: {
    total: number;
    groups: Array<{ id: number; used_for: string }>;
  };
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface InstagramScrapeRun {
  id: number;
  instagram_profile_id: number;
  type: 'posts' | 'continuation' | 'full_pipeline';
  status: 'pending' | 'running' | 'completed' | 'failed';
  posts_fetched: number;
  posts_new: number;
  posts_skipped: number;
  end_cursor: string | null;
  has_more_pages: boolean;
  started_at: string | null;
  completed_at: string | null;
  error_message: string | null;
  created_at: string;
  updated_at: string;
  profile?: {
    id: number;
    username: string;
    profile_pic_url: string | null;
  };
}

export interface InstagramProcessingRun {
  id: number;
  instagram_profile_id: number;
  status: 'pending' | 'running' | 'completed' | 'failed';
  posts_to_process: number;
  posts_processed: number;
  posts_failed: number;
  posts_skipped: number;
  started_at: string | null;
  completed_at: string | null;
  error_message: string | null;
  created_at: string;
  updated_at: string;
  profile?: {
    id: number;
    username: string;
    profile_pic_url: string | null;
  };
}

export interface ProfileRunsResponse {
  profile: {
    id: number;
    username: string;
    media_count: number;
    local_post_count: number;
    coverage_percentage: number;
    initial_scrape_done: boolean;
    initial_scrape_at: string | null;
    posts_per_request: number;
  };
  scrape_runs: InstagramScrapeRun[];
  processing_runs: InstagramProcessingRun[];
}
