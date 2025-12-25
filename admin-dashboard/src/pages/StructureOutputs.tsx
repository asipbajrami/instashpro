import { useState } from 'react';
import {
  useStructureOutputs,
  useProductAttributes,
  useCreateStructureOutput,
  useUpdateStructureOutput,
  useDeleteStructureOutput,
} from '@/api/structure-outputs';
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Plus, Pencil, Trash2 } from 'lucide-react';
import type { StructureOutput } from '@/types';

const PARENT_KEYS = [
  { value: 'tech', label: 'Tech' },
  { value: 'car', label: 'Car' },
];

const USED_FOR_OPTIONS: Record<string, { value: string; label: string }[]> = {
  tech: [
    { value: 'phone', label: 'Phone' },
    { value: 'computer', label: 'Computer' },
    { value: 'display', label: 'Display' },
  ],
  car: [
    { value: 'car', label: 'Car' },
    { value: 'motorcycle', label: 'Motorcycle' },
  ],
};

const TYPE_OPTIONS = [
  { value: 'string', label: 'String' },
  { value: 'number', label: 'Number' },
  { value: 'boolean', label: 'Boolean' },
];

export function StructureOutputs() {
  const [search, setSearch] = useState('');
  const [parentKeyFilter, setParentKeyFilter] = useState<string>('');
  const [usedForFilter, setUsedForFilter] = useState<string>('');
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [isDeleteOpen, setIsDeleteOpen] = useState(false);
  const [selectedOutput, setSelectedOutput] = useState<StructureOutput | null>(null);
  const [formData, setFormData] = useState({
    key: '',
    type: 'string',
    description: '',
    parent_key: 'tech',
    used_for: 'phone',
    required: true,
    product_attribute_id: '',
  });

  const { data, isLoading } = useStructureOutputs({
    search: search || undefined,
    parent_key: parentKeyFilter || undefined,
    used_for: usedForFilter || undefined,
  });

  const { data: productAttributes } = useProductAttributes();
  const createMutation = useCreateStructureOutput();
  const updateMutation = useUpdateStructureOutput();
  const deleteMutation = useDeleteStructureOutput();

  const handleCreate = async () => {
    await createMutation.mutateAsync({
      key: formData.key,
      type: formData.type,
      description: formData.description,
      parent_key: formData.parent_key,
      used_for: formData.used_for,
      required: formData.required,
      enum_values: null,
      product_attribute_id: formData.product_attribute_id
        ? Number(formData.product_attribute_id)
        : null,
    });
    setIsCreateOpen(false);
    resetForm();
  };

  const handleEdit = (output: StructureOutput) => {
    setSelectedOutput(output);
    setFormData({
      key: output.key,
      type: output.type,
      description: output.description,
      parent_key: output.parent_key,
      used_for: output.used_for,
      required: output.required,
      product_attribute_id: output.product_attribute_id
        ? String(output.product_attribute_id)
        : '',
    });
    setIsEditOpen(true);
  };

  const handleUpdate = async () => {
    if (!selectedOutput) return;
    await updateMutation.mutateAsync({
      id: selectedOutput.id,
      key: formData.key,
      type: formData.type,
      description: formData.description,
      parent_key: formData.parent_key,
      used_for: formData.used_for,
      required: formData.required,
      product_attribute_id: formData.product_attribute_id
        ? Number(formData.product_attribute_id)
        : null,
    });
    setIsEditOpen(false);
    setSelectedOutput(null);
  };

  const handleDelete = async () => {
    if (!selectedOutput) return;
    await deleteMutation.mutateAsync(selectedOutput.id);
    setIsDeleteOpen(false);
    setSelectedOutput(null);
  };

  const resetForm = () => {
    setFormData({
      key: '',
      type: 'string',
      description: '',
      parent_key: 'tech',
      used_for: 'phone',
      required: true,
      product_attribute_id: '',
    });
  };

  const handleParentKeyChange = (value: string) => {
    setFormData({
      ...formData,
      parent_key: value,
      used_for: USED_FOR_OPTIONS[value][0].value,
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">Structure Outputs</h1>
        <Button onClick={() => setIsCreateOpen(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Add Output
        </Button>
      </div>

      <div className="flex gap-4">
        <Input
          placeholder="Search by key or description..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="max-w-xs"
        />
        <Select value={parentKeyFilter || "all"} onValueChange={(v) => setParentKeyFilter(v === "all" ? "" : v)}>
          <SelectTrigger className="w-32">
            <SelectValue placeholder="All groups" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All groups</SelectItem>
            {PARENT_KEYS.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        <Select value={usedForFilter || "all"} onValueChange={(v) => setUsedForFilter(v === "all" ? "" : v)}>
          <SelectTrigger className="w-40">
            <SelectValue placeholder="All types" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All types</SelectItem>
            {Object.values(USED_FOR_OPTIONS)
              .flat()
              .map((option) => (
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
              <TableHead>Key</TableHead>
              <TableHead>Description</TableHead>
              <TableHead>Group</TableHead>
              <TableHead>Product Type</TableHead>
              <TableHead>Attribute</TableHead>
              <TableHead>Required</TableHead>
              <TableHead className="w-24">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {data?.data.map((output) => (
              <TableRow key={output.id}>
                <TableCell className="font-mono text-sm">{output.key}</TableCell>
                <TableCell className="max-w-xs truncate">
                  {output.description}
                </TableCell>
                <TableCell>
                  <Badge variant="outline">{output.parent_key}</Badge>
                </TableCell>
                <TableCell className="capitalize">{output.used_for}</TableCell>
                <TableCell>
                  {output.product_attribute?.name || '-'}
                </TableCell>
                <TableCell>
                  {output.required ? (
                    <Badge variant="success">Yes</Badge>
                  ) : (
                    <Badge variant="secondary">No</Badge>
                  )}
                </TableCell>
                <TableCell>
                  <div className="flex gap-2">
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => handleEdit(output)}
                    >
                      <Pencil className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => {
                        setSelectedOutput(output);
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

      {/* Create/Edit Dialog */}
      <Dialog
        open={isCreateOpen || isEditOpen}
        onOpenChange={(open) => {
          if (!open) {
            setIsCreateOpen(false);
            setIsEditOpen(false);
            setSelectedOutput(null);
            resetForm();
          }
        }}
      >
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>
              {isEditOpen ? 'Edit Structure Output' : 'Add Structure Output'}
            </DialogTitle>
            <DialogDescription>
              {isEditOpen
                ? 'Update the structure output configuration.'
                : 'Add a new attribute for product extraction.'}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Key</Label>
                <Input
                  placeholder="e.g., color, storage"
                  value={formData.key}
                  onChange={(e) =>
                    setFormData({ ...formData, key: e.target.value })
                  }
                />
              </div>
              <div className="space-y-2">
                <Label>Type</Label>
                <Select
                  value={formData.type}
                  onValueChange={(value) =>
                    setFormData({ ...formData, type: value })
                  }
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {TYPE_OPTIONS.map((option) => (
                      <SelectItem key={option.value} value={option.value}>
                        {option.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="space-y-2">
              <Label>Description</Label>
              <Textarea
                placeholder="Description for the AI model..."
                value={formData.description}
                onChange={(e) =>
                  setFormData({ ...formData, description: e.target.value })
                }
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Group (Parent Key)</Label>
                <Select
                  value={formData.parent_key}
                  onValueChange={handleParentKeyChange}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {PARENT_KEYS.map((option) => (
                      <SelectItem key={option.value} value={option.value}>
                        {option.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>Product Type (Used For)</Label>
                <Select
                  value={formData.used_for}
                  onValueChange={(value) =>
                    setFormData({ ...formData, used_for: value })
                  }
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {USED_FOR_OPTIONS[formData.parent_key]?.map((option) => (
                      <SelectItem key={option.value} value={option.value}>
                        {option.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="space-y-2">
              <Label>Product Attribute (Optional)</Label>
              <Select
                value={formData.product_attribute_id || "none"}
                onValueChange={(value) =>
                  setFormData({ ...formData, product_attribute_id: value === "none" ? "" : value })
                }
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select attribute..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">None</SelectItem>
                  {productAttributes?.map((attr) => (
                    <SelectItem key={attr.id} value={String(attr.id)}>
                      {attr.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => {
                setIsCreateOpen(false);
                setIsEditOpen(false);
                resetForm();
              }}
            >
              Cancel
            </Button>
            <Button
              onClick={isEditOpen ? handleUpdate : handleCreate}
              disabled={createMutation.isPending || updateMutation.isPending}
            >
              {createMutation.isPending || updateMutation.isPending
                ? 'Saving...'
                : isEditOpen
                ? 'Save Changes'
                : 'Add Output'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Delete Confirmation */}
      <Dialog open={isDeleteOpen} onOpenChange={setIsDeleteOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Delete Structure Output</DialogTitle>
            <DialogDescription>
              Are you sure you want to delete "{selectedOutput?.key}"? This
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
