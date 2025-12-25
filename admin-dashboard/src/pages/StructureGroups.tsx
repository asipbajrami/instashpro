import { useState } from 'react';
import {
  useStructureOutputGroups,
  useCreateStructureOutputGroup,
  useUpdateStructureOutputGroup,
  useDeleteStructureOutputGroup,
} from '@/api/structure-groups';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
import { Plus, Pencil, Trash2 } from 'lucide-react';
import type { StructureOutputGroup } from '@/types';

export function StructureGroups() {
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [isDeleteOpen, setIsDeleteOpen] = useState(false);
  const [selectedGroup, setSelectedGroup] = useState<StructureOutputGroup | null>(null);
  const [formData, setFormData] = useState({
    used_for: '',
    description: '',
  });

  const { data: groups, isLoading } = useStructureOutputGroups();
  const createMutation = useCreateStructureOutputGroup();
  const updateMutation = useUpdateStructureOutputGroup();
  const deleteMutation = useDeleteStructureOutputGroup();

  const handleCreate = async () => {
    await createMutation.mutateAsync({
      used_for: formData.used_for,
      description: formData.description,
    });
    setIsCreateOpen(false);
    resetForm();
  };

  const handleEdit = (group: StructureOutputGroup) => {
    setSelectedGroup(group);
    setFormData({
      used_for: group.used_for,
      description: group.description,
    });
    setIsEditOpen(true);
  };

  const handleUpdate = async () => {
    if (!selectedGroup) return;
    await updateMutation.mutateAsync({
      id: selectedGroup.id,
      description: formData.description,
    });
    setIsEditOpen(false);
    setSelectedGroup(null);
  };

  const handleDelete = async () => {
    if (!selectedGroup) return;
    await deleteMutation.mutateAsync(selectedGroup.id);
    setIsDeleteOpen(false);
    setSelectedGroup(null);
  };

  const resetForm = () => {
    setFormData({ used_for: '', description: '' });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Structure Output Groups</h1>
          <p className="text-muted-foreground mt-1">
            Groups used for Typesense CLIP-based image classification
          </p>
        </div>
        <Button onClick={() => setIsCreateOpen(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Add Group
        </Button>
      </div>

      {isLoading ? (
        <div className="flex items-center justify-center h-64">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        </div>
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Used For</TableHead>
              <TableHead>Description</TableHead>
              <TableHead>Outputs Count</TableHead>
              <TableHead className="w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {groups?.map((group) => (
              <TableRow key={group.id}>
                <TableCell>
                  <Badge variant="outline" className="text-base">
                    {group.used_for}
                  </Badge>
                </TableCell>
                <TableCell className="max-w-lg">
                  <p className="text-sm text-muted-foreground line-clamp-2">
                    {group.description}
                  </p>
                </TableCell>
                <TableCell>
                  <Badge>{group.structure_outputs_count || 0}</Badge>
                </TableCell>
                <TableCell>
                  <div className="flex gap-2">
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleEdit(group)}
                    >
                      <Pencil className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => {
                        setSelectedGroup(group);
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
            <DialogTitle>Add Structure Output Group</DialogTitle>
            <DialogDescription>
              Create a new group for categorizing products. The description is
              used by Typesense CLIP for image classification.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div className="space-y-2">
              <Label>Group Name (used_for)</Label>
              <Input
                placeholder="e.g., electronics, fashion"
                value={formData.used_for}
                onChange={(e) =>
                  setFormData({ ...formData, used_for: e.target.value })
                }
              />
            </div>
            <div className="space-y-2">
              <Label>Description (for CLIP classification)</Label>
              <Textarea
                placeholder="Detailed description of products in this group for image classification..."
                rows={4}
                value={formData.description}
                onChange={(e) =>
                  setFormData({ ...formData, description: e.target.value })
                }
              />
              <p className="text-xs text-muted-foreground">
                Include product types, brands, and keywords that help classify
                images into this category.
              </p>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsCreateOpen(false)}>
              Cancel
            </Button>
            <Button onClick={handleCreate} disabled={createMutation.isPending}>
              {createMutation.isPending ? 'Creating...' : 'Create Group'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Edit Dialog */}
      <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Structure Output Group</DialogTitle>
            <DialogDescription>
              Update the description for "{selectedGroup?.used_for}"
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div className="space-y-2">
              <Label>Group Name</Label>
              <Input value={formData.used_for} disabled />
            </div>
            <div className="space-y-2">
              <Label>Description</Label>
              <Textarea
                rows={4}
                value={formData.description}
                onChange={(e) =>
                  setFormData({ ...formData, description: e.target.value })
                }
              />
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

      {/* Delete Confirmation */}
      <Dialog open={isDeleteOpen} onOpenChange={setIsDeleteOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Delete Structure Output Group</DialogTitle>
            <DialogDescription>
              Are you sure you want to delete "{selectedGroup?.used_for}"? This
              will not delete associated structure outputs, but they will become
              orphaned.
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
