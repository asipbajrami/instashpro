import { useDashboardStats } from '@/api/dashboard';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Instagram, Settings, Layers, Clock } from 'lucide-react';

export function Dashboard() {
  const { data: stats, isLoading, error } = useDashboardStats();

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="text-center text-destructive">
        Failed to load dashboard stats
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Dashboard</h1>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Profiles</CardTitle>
            <Instagram className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.profiles.total || 0}</div>
            <div className="flex gap-2 mt-2">
              <Badge variant="success">{stats?.profiles.active || 0} active</Badge>
              <Badge variant="secondary">{stats?.profiles.inactive || 0} inactive</Badge>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Due for Scrape</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.profiles.due_for_scrape || 0}</div>
            <p className="text-xs text-muted-foreground mt-2">
              Profiles ready to be scraped
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Structure Outputs</CardTitle>
            <Settings className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.structure_outputs.total || 0}</div>
            <div className="flex gap-2 mt-2">
              {stats?.structure_outputs.by_group && Object.entries(stats.structure_outputs.by_group).map(([key, count]) => (
                <Badge key={key} variant="outline">{key}: {count}</Badge>
              ))}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Structure Groups</CardTitle>
            <Layers className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.structure_groups.total || 0}</div>
            <div className="flex gap-2 mt-2">
              {stats?.structure_groups.groups?.map((group) => (
                <Badge key={group.id} variant="outline">{group.used_for}</Badge>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Outputs by Product Type</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              {stats?.structure_outputs.by_used_for && Object.entries(stats.structure_outputs.by_used_for).map(([type, count]) => (
                <div key={type} className="flex items-center justify-between">
                  <span className="capitalize">{type}</span>
                  <Badge>{count}</Badge>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Profile Status Breakdown</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <span>Active</span>
                <Badge variant="success">{stats?.profiles.active || 0}</Badge>
              </div>
              <div className="flex items-center justify-between">
                <span>Inactive</span>
                <Badge variant="secondary">{stats?.profiles.inactive || 0}</Badge>
              </div>
              <div className="flex items-center justify-between">
                <span>Suspended</span>
                <Badge variant="destructive">{stats?.profiles.suspended || 0}</Badge>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
