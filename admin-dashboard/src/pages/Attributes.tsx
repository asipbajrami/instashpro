import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  useAttributes,
  useCreateAttribute,
  useUpdateAttribute,
  useDeleteAttribute,
} from '@/api/attributes';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { Plus, Pencil, Trash2, List } from 'lucide-react';
import type { ProductAttribute } from '@/types';

export function Attributes() {
  const navigate = useNavigate();
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [isDeleteOpen, setIsDeleteOpen] = useState(false);
  const [selectedAttribute, setSelectedAttribute] = useState<ProductAttribute | null>(null);
  const [formData, setFormData] = useState({
    name: '',
    slug: '',
  });

  const { data: attributes, isLoading } = useAttributes();
  const createMutation = useCreateAttribute();
  const updateMutation = useUpdateAttribute();
  const deleteMutation = useDeleteAttribute();

  const handleCreate = async () => {
    await createMutation.mutateAsync({
      name: formData.name,
      slug: formData.slug || undefined,
    });
    setIsCreateOpen(false);
    resetForm();
  };

  const handleEdit = (attribute: ProductAttribute) => {
    setSelectedAttribute(attribute);
    setFormData({
      name: attribute.name,
      slug: attribute.slug || '',
    });
    setIsEditOpen(true);
  };

  const handleUpdate = async () => {
    if (!selectedAttribute) return;
    await updateMutation.mutateAsync({
      id: selectedAttribute.id,
      name: formData.name,
      slug: formData.slug || undefined,
    });
    setIsEditOpen(false);
    setSelectedAttribute(null);
  };

  const handleDelete = async () => {
    if (!selectedAttribute) return;
    await deleteMutation.mutateAsync(selectedAttribute.id);
    setIsDeleteOpen(false);
    setSelectedAttribute(null);
  };

  const resetForm = () => {
    setFormData({ name: '', slug: '' });
  };

  const generateSlug = (name: string) => {
    return name
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^_|_$/g, '');
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Product Attributes</h1>
          <p className="text-muted-foreground mt-1">
            Manage attributes that can be linked to structure outputs
          </p>
        </div>
        <Button onClick={() => setIsCreateOpen(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Add Attribute
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
              <TableHead>ID</TableHead>
              <TableHead>Name</TableHead>
              <TableHead>Slug</TableHead>
              <TableHead>Values Count</TableHead>
              <TableHead className="w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {attributes?.map((attribute) => (
              <TableRow key={attribute.id}>
                <TableCell className="font-mono text-sm">{attribute.id}</TableCell>
                <TableCell className="font-medium">{attribute.name}</TableCell>
                <TableCell className="font-mono text-sm text-muted-foreground">
                  {attribute.slug || '-'}
                </TableCell>
                <TableCell>
                  <Button
                    variant="link"
                    className="p-0 h-auto font-normal"
                    onClick={() => navigate(`/attributes/${attribute.id}/values`)}
                  >
                    {attribute.values_count ?? 0} values
                  </Button>
                </TableCell>
                <TableCell>
                  <div className="flex gap-2">
                    <Button
                      variant="ghost"
                      size="icon"
                      title="View Values"
                      onClick={() => navigate(`/attributes/${attribute.id}/values`)}
                    >
                      <List className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      title="Edit Attribute"
                      onClick={() => handleEdit(attribute)}
                    >
                      <Pencil className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      title="Delete Attribute"
                      onClick={() => {
                        setSelectedAttribute(attribute);
                        setIsDeleteOpen(true);
                      }}
                    >
                      <Trash2 className="h-4 w-4 text-destructive" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            ))}
            {attributes?.length === 0 && (
              <TableRow>
                <TableCell colSpan={5} className="text-center text-muted-foreground py-8">
                  No attributes found. Create your first attribute to get started.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      )}

      {/* Create Dialog */}
      <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Add Product Attribute</DialogTitle>
            <DialogDescription>
              Create a new attribute that can be linked to structure outputs.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div className="space-y-2">
              <Label>Name</Label>
              <Input
                placeholder="e.g., Color, Storage, RAM"
                value={formData.name}
                onChange={(e) => {
                  setFormData({
                    name: e.target.value,
                    slug: generateSlug(e.target.value),
                  });
                }}
              />
            </div>
            <div className="space-y-2">
              <Label>Slug (auto-generated)</Label>
              <Input
                placeholder="color, storage, ram"
                value={formData.slug}
                onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
              />
              <p className="text-xs text-muted-foreground">
                Used for URL-friendly identifiers
              </p>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsCreateOpen(false)}>
              Cancel
            </Button>
            <Button onClick={handleCreate} disabled={createMutation.isPending || !formData.name}>
              {createMutation.isPending ? 'Creating...' : 'Create Attribute'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Edit Dialog */}
      <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Attribute</DialogTitle>
            <DialogDescription>
              Update the attribute details.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div className="space-y-2">
              <Label>Name</Label>
              <Input
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              />
            </div>
            <div className="space-y-2">
              <Label>Slug</Label>
              <Input
                value={formData.slug}
                onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsEditOpen(false)}>
              Cancel
            </Button>
            <Button onClick={handleUpdate} disabled={updateMutation.isPending || !formData.name}>
              {updateMutation.isPending ? 'Saving...' : 'Save Changes'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Delete Confirmation */}
      <Dialog open={isDeleteOpen} onOpenChange={setIsDeleteOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Delete Attribute</DialogTitle>
            <DialogDescription>
              Are you sure you want to delete "{selectedAttribute?.name}"? This may affect
              structure outputs that use this attribute.
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
