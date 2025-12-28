import { useState } from 'react';
import {
  useProfiles,
  useCreateProfile,
  useUpdateProfile,
  useDeleteProfile,
} from '@/api/profiles';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Plus, Pencil, Trash2, X } from 'lucide-react';
import type { InstagramProfile } from '@/types';

const STATUS_OPTIONS = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'suspended', label: 'Suspended' },
];

export function Profiles() {
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('');
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [isDeleteOpen, setIsDeleteOpen] = useState(false);
  const [selectedProfile, setSelectedProfile] = useState<InstagramProfile | null>(null);
  const [formData, setFormData] = useState({
    username: '',
    scrape_interval_hours: '24',
    status: 'active',
    scheduled_times: ['13:00', '17:00', '20:00'],
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
  });
  const [newTime, setNewTime] = useState('12:00');

  const { data, isLoading } = useProfiles({
    search: search || undefined,
    status: statusFilter || undefined,
  });

  const createMutation = useCreateProfile();
  const updateMutation = useUpdateProfile();
  const deleteMutation = useDeleteProfile();

  const handleCreate = async () => {
    await createMutation.mutateAsync({
      username: formData.username,
      scrape_interval_hours: Number(formData.scrape_interval_hours),
      status: formData.status,
      scheduled_times: formData.scheduled_times,
      timezone: formData.timezone,
    });
    setIsCreateOpen(false);
    setFormData({ 
      username: '', 
      scrape_interval_hours: '24', 
      status: 'active',
      scheduled_times: ['13:00', '17:00', '20:00'],
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
    });
  };

  const handleEdit = (profile: InstagramProfile) => {
    setSelectedProfile(profile);
    setFormData({
      username: profile.username,
      scrape_interval_hours: String(profile.scrape_interval_hours),
      status: profile.status,
      scheduled_times: profile.scheduled_times || ['13:00', '17:00', '20:00'],
      timezone: profile.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone,
    });
    setIsEditOpen(true);
  };

  const handleUpdate = async () => {
    if (!selectedProfile) return;
    await updateMutation.mutateAsync({
      id: selectedProfile.id,
      scrape_interval_hours: Number(formData.scrape_interval_hours),
      status: formData.status,
      scheduled_times: formData.scheduled_times,
      timezone: formData.timezone,
    });
    setIsEditOpen(false);
    setSelectedProfile(null);
  };

  const removeTime = (timeToRemove: string) => {
    setFormData(prev => ({
      ...prev,
      scheduled_times: prev.scheduled_times.filter(t => t !== timeToRemove)
    }));
  };

  const addTime = () => {
    if (!formData.scheduled_times.includes(newTime)) {
      setFormData(prev => ({
        ...prev,
        scheduled_times: [...prev.scheduled_times, newTime].sort()
      }));
    }
  };

  const handleDelete = async () => {
    if (!selectedProfile) return;
    await deleteMutation.mutateAsync(selectedProfile.id);
    setIsDeleteOpen(false);
    setSelectedProfile(null);
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'active':
        return <Badge variant="success">Active</Badge>;
      case 'inactive':
        return <Badge variant="secondary">Inactive</Badge>;
      case 'suspended':
        return <Badge variant="destructive">Suspended</Badge>;
      default:
        return <Badge>{status}</Badge>;
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">Instagram Profiles</h1>
        <Button onClick={() => setIsCreateOpen(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Add Profile
        </Button>
      </div>

      <div className="flex gap-4">
        <Input
          placeholder="Search by username..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="max-w-xs"
        />
        <Select value={statusFilter || "all"} onValueChange={(v) => setStatusFilter(v === "all" ? "" : v)}>
          <SelectTrigger className="w-40">
            <SelectValue placeholder="All statuses" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All statuses</SelectItem>
            {STATUS_OPTIONS.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {isLoading ? (
        <div className="flex items-center justify-center h-64">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        </div>
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Username</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Followers</TableHead>
              <TableHead>Schedule (Daily)</TableHead>
              <TableHead>Last Scraped</TableHead>
              <TableHead>Next Scrape</TableHead>
              <TableHead className="w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {data?.data.map((profile) => (
              <TableRow key={profile.id}>
                <TableCell className="font-medium">@{profile.username}</TableCell>
                <TableCell>{getStatusBadge(profile.status)}</TableCell>
                <TableCell>{profile.follower_count.toLocaleString()}</TableCell>
                <TableCell>
                  <div className="flex flex-wrap gap-1">
                    {(profile.scheduled_times || ['13:00', '17:00', '20:00']).map(time => (
                      <Badge key={time} variant="outline" className="text-[10px] px-1 py-0">
                        {time}
                      </Badge>
                    ))}
                  </div>
                  <div className="text-[9px] text-muted-foreground mt-1">
                    {profile.timezone}
                  </div>
                </TableCell>
                <TableCell>
                  {profile.last_scraped_at
                    ? new Date(profile.last_scraped_at).toLocaleString()
                    : 'Never'}
                </TableCell>
                <TableCell>
                  {profile.next_scrape_at
                    ? new Date(profile.next_scrape_at).toLocaleString()
                    : '-'}
                </TableCell>
                <TableCell>
                  <div className="flex gap-2">
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleEdit(profile)}
                    >
                      <Pencil className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => {
                        setSelectedProfile(profile);
                        setIsDeleteOpen(true);
                      }}
                    >
                      <Trash2 className="h-4 w-4 text-destructive" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      )}

      {/* Create Dialog */}
      <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Add Instagram Profile</DialogTitle>
            <DialogDescription>
              Enter the username to add a new profile for scraping.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="username">Username</Label>
              <Input
                id="username"
                placeholder="username"
                value={formData.username}
                onChange={(e) =>
                  setFormData({ ...formData, username: e.target.value })
                }
              />
            </div>
            <div className="space-y-2">
              <Label>Scraping Schedule</Label>
              <div className="flex flex-wrap gap-2 mb-2">
                {formData.scheduled_times.map((time) => (
                  <Badge key={time} variant="secondary" className="pl-2 pr-1 py-1 flex items-center gap-1">
                    {time}
                    <button 
                      type="button"
                      onClick={() => removeTime(time)}
                      className="hover:text-destructive rounded-full"
                    >
                      <X className="h-3 w-3" />
                    </button>
                  </Badge>
                ))}
              </div>
              <div className="flex gap-2">
                <Input
                  type="time"
                  value={newTime}
                  onChange={(e) => setNewTime(e.target.value)}
                  className="w-32"
                />
                <Button 
                  type="button" 
                  variant="outline" 
                  size="sm"
                  onClick={addTime}
                >
                  <Plus className="h-4 w-4 mr-1" /> Add Time
                </Button>
              </div>
              <p className="text-[10px] text-muted-foreground">
                Set specific times throughout the day for scraping (Timezone: {formData.timezone}).
              </p>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsCreateOpen(false)}>
              Cancel
            </Button>
            <Button onClick={handleCreate} disabled={createMutation.isPending}>
              {createMutation.isPending ? 'Adding...' : 'Add Profile'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Edit Dialog */}
      <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Profile</DialogTitle>
            <DialogDescription>
              Update the scrape settings for @{selectedProfile?.username}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div className="space-y-2">
              <Label>Status</Label>
              <Select
                value={formData.status}
                onValueChange={(value) =>
                  setFormData({ ...formData, status: value })
                }
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {STATUS_OPTIONS.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Scraping Schedule</Label>
              <div className="flex flex-wrap gap-2 mb-2">
                {formData.scheduled_times.map((time) => (
                  <Badge key={time} variant="secondary" className="pl-2 pr-1 py-1 flex items-center gap-1">
                    {time}
                    <button 
                      type="button"
                      onClick={() => removeTime(time)}
                      className="hover:text-destructive rounded-full"
                    >
                      <X className="h-3 w-3" />
                    </button>
                  </Badge>
                ))}
              </div>
              <div className="flex gap-2">
                <Input
                  type="time"
                  value={newTime}
                  onChange={(e) => setNewTime(e.target.value)}
                  className="w-32"
                />
                <Button 
                  type="button" 
                  variant="outline" 
                  size="sm"
                  onClick={addTime}
                >
                  <Plus className="h-4 w-4 mr-1" /> Add Time
                </Button>
              </div>
              <p className="text-[10px] text-muted-foreground">
                Set specific times throughout the day for scraping (Timezone: {formData.timezone}).
              </p>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsEditOpen(false)}>
              Cancel
            </Button>
            <Button onClick={handleUpdate} disabled={updateMutation.isPending}>
              {updateMutation.isPending ? 'Saving...' : 'Save Changes'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Delete Confirmation Dialog */}
      <Dialog open={isDeleteOpen} onOpenChange={setIsDeleteOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Delete Profile</DialogTitle>
            <DialogDescription>
              Are you sure you want to delete @{selectedProfile?.username}? This
              action cannot be undone.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsDeleteOpen(false)}>
              Cancel
            </Button>
            <Button
              variant="destructive"
              onClick={handleDelete}
              disabled={deleteMutation.isPending}
            >
              {deleteMutation.isPending ? 'Deleting...' : 'Delete'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
