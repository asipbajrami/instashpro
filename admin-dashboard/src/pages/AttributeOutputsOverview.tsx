import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useAttributesWithOutputs, type AttributeWithOutputs } from '@/api/attributes';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  ChevronDown,
  ChevronRight,
  Tag,
  Settings,
  ExternalLink,
  List,
} from 'lucide-react';

export function AttributeOutputsOverview() {
  const [search, setSearch] = useState('');
  const [expandedIds, setExpandedIds] = useState<Set<number>>(new Set());
  const { data: attributes, isLoading } = useAttributesWithOutputs();

  const toggleExpanded = (id: number) => {
    setExpandedIds((prev) => {
      const next = new Set(prev);
      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }
      return next;
    });
  };

  const expandAll = () => {
    if (attributes) {
      setExpandedIds(new Set(attributes.map((a) => a.id)));
    }
  };

  const collapseAll = () => {
    setExpandedIds(new Set());
  };

  const filteredAttributes = attributes?.filter((attr) => {
    const searchLower = search.toLowerCase();
    const matchesName = attr.name.toLowerCase().includes(searchLower);
    const matchesOutput = attr.structure_outputs?.some(
      (output) =>
        output.key.toLowerCase().includes(searchLower) ||
        output.description.toLowerCase().includes(searchLower)
    );
    return matchesName || matchesOutput;
  });

  // Group by: attributes with outputs, attributes without outputs
  const withOutputs = filteredAttributes?.filter(
    (a) => a.structure_outputs_count > 0
  );
  const withoutOutputs = filteredAttributes?.filter(
    (a) => a.structure_outputs_count === 0
  );

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Attributes & Structure Outputs</h1>
          <p className="text-muted-foreground mt-1">
            Overview of all attributes and their linked structure outputs
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" size="sm" onClick={expandAll}>
            Expand All
          </Button>
          <Button variant="outline" size="sm" onClick={collapseAll}>
            Collapse All
          </Button>
        </div>
      </div>

      <div className="flex gap-4">
        <Input
          placeholder="Search attributes or outputs..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="max-w-sm"
        />
      </div>

      {isLoading ? (
        <div className="flex items-center justify-center h-64">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        </div>
      ) : (
        <div className="space-y-6">
          {/* Summary Cards */}
          <div className="grid grid-cols-3 gap-4">
            <Card>
              <CardHeader className="pb-2">
                <CardDescription>Total Attributes</CardDescription>
                <CardTitle className="text-4xl">{attributes?.length || 0}</CardTitle>
              </CardHeader>
            </Card>
            <Card>
              <CardHeader className="pb-2">
                <CardDescription>With Structure Outputs</CardDescription>
                <CardTitle className="text-4xl text-green-600">
                  {withOutputs?.length || 0}
                </CardTitle>
              </CardHeader>
            </Card>
            <Card>
              <CardHeader className="pb-2">
                <CardDescription>Without Structure Outputs</CardDescription>
                <CardTitle className="text-4xl text-muted-foreground">
                  {withoutOutputs?.length || 0}
                </CardTitle>
              </CardHeader>
            </Card>
          </div>

          {/* Attributes with Outputs */}
          {withOutputs && withOutputs.length > 0 && (
            <div className="space-y-2">
              <h2 className="text-lg font-semibold flex items-center gap-2">
                <Settings className="h-5 w-5" />
                Attributes with Structure Outputs ({withOutputs.length})
              </h2>
              <div className="space-y-2">
                {withOutputs.map((attribute) => (
                  <AttributeCard
                    key={attribute.id}
                    attribute={attribute}
                    isExpanded={expandedIds.has(attribute.id)}
                    onToggle={() => toggleExpanded(attribute.id)}
                  />
                ))}
              </div>
            </div>
          )}

          {/* Attributes without Outputs */}
          {withoutOutputs && withoutOutputs.length > 0 && (
            <div className="space-y-2">
              <h2 className="text-lg font-semibold flex items-center gap-2 text-muted-foreground">
                <Tag className="h-5 w-5" />
                Attributes without Structure Outputs ({withoutOutputs.length})
              </h2>
              <Card>
                <CardContent className="pt-4">
                  <div className="flex flex-wrap gap-2">
                    {withoutOutputs.map((attr) => (
                      <Link key={attr.id} to={`/attributes/${attr.id}/values`}>
                        <Badge
                          variant="secondary"
                          className="cursor-pointer hover:bg-secondary/80"
                        >
                          {attr.name}
                          <span className="ml-1 text-xs text-muted-foreground">
                            ({attr.values_count || 0} values)
                          </span>
                        </Badge>
                      </Link>
                    ))}
                  </div>
                </CardContent>
              </Card>
            </div>
          )}

          {filteredAttributes?.length === 0 && (
            <div className="text-center py-12 text-muted-foreground">
              No attributes found matching "{search}"
            </div>
          )}
        </div>
      )}
    </div>
  );
}

interface AttributeCardProps {
  attribute: AttributeWithOutputs;
  isExpanded: boolean;
  onToggle: () => void;
}

function AttributeCard({ attribute, isExpanded, onToggle }: AttributeCardProps) {
  return (
    <Card>
      <div
        className="flex items-center justify-between p-4 cursor-pointer hover:bg-muted/50"
        onClick={onToggle}
      >
        <div className="flex items-center gap-3">
          {isExpanded ? (
            <ChevronDown className="h-5 w-5 text-muted-foreground" />
          ) : (
            <ChevronRight className="h-5 w-5 text-muted-foreground" />
          )}
          <div>
            <h3 className="font-medium">{attribute.name}</h3>
            <p className="text-sm text-muted-foreground">
              {attribute.slug && (
                <span className="font-mono text-xs">{attribute.slug}</span>
              )}
            </p>
          </div>
        </div>
        <div className="flex items-center gap-4">
          <Badge variant="outline" className="gap-1">
            <List className="h-3 w-3" />
            {attribute.values_count || 0} values
          </Badge>
          <Badge variant="default" className="gap-1">
            <Settings className="h-3 w-3" />
            {attribute.structure_outputs_count} outputs
          </Badge>
          <Link
            to={`/attributes/${attribute.id}/values`}
            onClick={(e) => e.stopPropagation()}
          >
            <Button variant="ghost" size="icon">
              <ExternalLink className="h-4 w-4" />
            </Button>
          </Link>
        </div>
      </div>

      {isExpanded && attribute.structure_outputs?.length > 0 && (
        <CardContent className="border-t pt-4">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Key</TableHead>
                <TableHead>Description</TableHead>
                <TableHead>Group</TableHead>
                <TableHead>Product Type</TableHead>
                <TableHead>Required</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {attribute.structure_outputs.map((output) => (
                <TableRow key={output.id}>
                  <TableCell className="font-mono text-sm">{output.key}</TableCell>
                  <TableCell className="max-w-xs truncate text-sm text-muted-foreground">
                    {output.description}
                  </TableCell>
                  <TableCell>
                    <Badge variant="outline">{output.parent_key}</Badge>
                  </TableCell>
                  <TableCell className="capitalize">{output.used_for}</TableCell>
                  <TableCell>
                    {output.required ? (
                      <Badge variant="default">Yes</Badge>
                    ) : (
                      <Badge variant="secondary">No</Badge>
                    )}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      )}
    </Card>
  );
}
