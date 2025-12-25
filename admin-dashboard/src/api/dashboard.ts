import { useQuery } from '@tanstack/react-query';
import { apiClient } from './client';
import type { DashboardStats } from '@/types';

export function useDashboardStats() {
  return useQuery({
    queryKey: ['dashboard', 'stats'],
    queryFn: async (): Promise<DashboardStats> => {
      const response = await apiClient.get('/admin/dashboard/stats');
      return response.data;
    },
  });
}
