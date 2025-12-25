import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  useAttribute,
  useAttributeValues,
  useCreateAttributeValue,
  useUpdateAttributeValue,
  useDeleteAttributeValue,
} from '@/api/attributes';
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
import { Switch } from '@/components/ui/switch';
import { ArrowLeft, Plus, Pencil, Trash2 } from 'lucide-react';
import type { ProductAttributeValue } from '@/types';

export function AttributeValues() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const attributeId = Number(id);

  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [isDeleteOpen, setIsDeleteOpen] = useState(false);
  const [selectedValue, setSelectedValue] = useState<ProductAttributeValue | null>(null);
  const [formData, setFormData] = useState({
    value: '',
    ai_value: '',
    score: 10,
    is_temp: false,
  });

  const { data: attribute, isLoading: attributeLoading } = useAttribute(attributeId);
  const { data: values, isLoading: valuesLoading } = useAttributeValues(attributeId);
  const createMutation = useCreateAttributeValue();
  const updateMutation = useUpdateAttributeValue();
  const deleteMutation = useDeleteAttributeValue();

  const isLoading = attributeLoading || valuesLoading;

  const handleCreate = async () => {
    await createMutation.mutateAsync({
      attributeId,
      value: formData.value,
      ai_value: formData.ai_value || formData.value.toLowerCase(),
      score: formData.score,
      is_temp: formData.is_temp,
    });
    setIsCreateOpen(false);
    resetForm();
  };

  const handleEdit = (val: ProductAttributeValue) => {
    setSelectedValue(val);
    setFormData({
      value: val.value,
      ai_value: val.ai_value,
      score: val.score,
      is_temp: val.is_temp,
    });
    setIsEditOpen(true);
  };

  const handleUpdate = async () => {
    if (!selectedValue) return;
    await updateMutation.mutateAsync({
      attributeId,
      valueId: selectedValue.id,
      value: formData.value,
      ai_value: formData.ai_value,
      score: formData.score,
      is_temp: formData.is_temp,
    });
    setIsEditOpen(false);
    setSelectedValue(null);
  };

  const handleDelete = async () => {
    if (!selectedValue) return;
    await deleteMutation.mutateAsync({
      attributeId,
      valueId: selectedValue.id,
    });
    setIsDeleteOpen(false);
    setSelectedValue(null);
  };

  const resetForm = () => {
    setFormData({ value: '', ai_value: '', score: 10, is_temp: false });
  };

  const generateAiValue = (value: string) => {
    return value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" onClick={() => navigate('/attributes')}>
          <ArrowLeft className="h-5 w-5" />
        </Button>
        <div className="flex-1">
          <h1 className="text-3xl font-bold">{attribute?.name} Values</h1>
          <p className="text-muted-foreground mt-1">
            Manage values for the {attribute?.name} attribute
          </p>
        </div>
        <Button onClick={() => setIsCreateOpen(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Add Value
        </Button>
      </div>

      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Value</TableHead>
            <TableHead>AI Value</TableHead>
            <TableHead>Score</TableHead>
            <TableHead>Status</TableHead>
            <TableHead className="w-24">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {values?.map((val) => (
            <TableRow key={val.id}>
              <TableCell className="font-medium">{val.value}</TableCell>
              <TableCell className="font-mono text-sm text-muted-foreground">
                {val.ai_value}
              </TableCell>
              <TableCell>{val.score}</TableCell>
              <TableCell>
                {val.is_temp ? (
                  <Badge variant="secondary">Temporary</Badge>
                ) : (
                  <Badge variant="default">Permanent</Badge>
                )}
              </TableCell>
              <TableCell>
                <div className="flex gap-2">
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => handleEdit(val)}
                  >
                    <Pencil className="h-4 w-4" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => {
                      setSelectedValue(val);
                      setIsDeleteOpen(true);
                    }}
                  >
                    <Trash2 className="h-4 w-4 text-destructive" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          ))}
          {values?.length === 0 && (
            <TableRow>
              <TableCell colSpan={5} className="text-center text-muted-foreground py-8">
                No values found. Add your first value to get started.
              </TableCell>
            </TableRow>
          )}
        </TableBody>
      </Table>

      {/* Create Dialog */}
      <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Add Value</DialogTitle>
            <DialogDescription>
              Add a new value to the {attribute?.name} attribute.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div className="space-y-2">
              <Label>Display Value</Label>
              <Input
                placeholder="e.g., Red, 128GB, Manual"
                value={formData.value}
                onChange={(e) => {
                  setFormData({
                    ...formData,
                    value: e.target.value,
                    ai_value: generateAiValue(e.target.value),
                  });
                }}
              />
            </div>
            <div className="space-y-2">
              <Label>AI Value (for matching)</Label>
              <Input
                placeholder="e.g., red, 128gb, manual"
                value={formData.ai_value}
                onChange={(e) => setFormData({ ...formData, ai_value: e.target.value })}
              />
              <p className="text-xs text-muted-foreground">
                Lowercase value used for AI matching
              </p>
            </div>
            <div className="space-y-2">
              <Label>Score (0-100)</Label>
              <Input
                type="number"
                min={0}
                max={100}
                value={formData.score}
                onChange={(e) => setFormData({ ...formData, score: Number(e.target.value) })}
              />
              <p className="text-xs text-muted-foreground">
                Higher scores appear first in dropdowns
              </p>
            </div>
            <div className="flex items-center justify-between">
              <div className="space-y-0.5">
                <Label>Temporary Value</Label>
                <p className="text-xs text-muted-foreground">
                  Temporary values can be cleaned up later
                </p>
              </div>
              <Switch
                checked={formData.is_temp}
                onCheckedChange={(checked) => setFormData({ ...formData, is_temp: checked })}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsCreateOpen(false)}>
              Cancel
            </Button>
            <Button onClick={handleCreate} disabled={createMutation.isPending || !formData.value}>
              {createMutation.isPending ? 'Adding...' : 'Add Value'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Edit Dialog */}
      <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Value</DialogTitle>
            <DialogDescription>
              Update the value details.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div className="space-y-2">
              <Label>Display Value</Label>
              <Input
                value={formData.value}
                onChange={(e) => setFormData({ ...formData, value: e.target.value })}
              />
            </div>
            <div className="space-y-2">
              <Label>AI Value (for matching)</Label>
              <Input
                value={formData.ai_value}
                onChange={(e) => setFormData({ ...formData, ai_value: e.target.value })}
              />
            </div>
            <div className="space-y-2">
              <Label>Score (0-100)</Label>
              <Input
                type="number"
                min={0}
                max={100}
                value={formData.score}
                onChange={(e) => setFormData({ ...formData, score: Number(e.target.value) })}
              />
            </div>
            <div className="flex items-center justify-between">
              <div className="space-y-0.5">
                <Label>Temporary Value</Label>
                <p className="text-xs text-muted-foreground">
                  Temporary values can be cleaned up later
                </p>
              </div>
              <Switch
                checked={formData.is_temp}
                onCheckedChange={(checked) => setFormData({ ...formData, is_temp: checked })}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsEditOpen(false)}>
              Cancel
            </Button>
            <Button onClick={handleUpdate} disabled={updateMutation.isPending || !formData.value}>
              {updateMutation.isPending ? 'Saving...' : 'Save Changes'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Delete Confirmation */}
      <Dialog open={isDeleteOpen} onOpenChange={setIsDeleteOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Delete Value</DialogTitle>
            <DialogDescription>
              Are you sure you want to delete "{selectedValue?.value}"? This action cannot be undone.
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
