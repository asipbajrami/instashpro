"use client"

import * as React from "react"
import {
  Root as CollapsibleRoot,
  Trigger as CollapsibleTriggerPrimitive,
  Content as CollapsibleContentPrimitive,
} from "@radix-ui/react-collapsible"

import { cn } from "@/lib/utils"

const Collapsible = CollapsibleRoot

const CollapsibleTrigger = CollapsibleTriggerPrimitive

const CollapsibleContent = React.forwardRef<
  React.ElementRef<typeof CollapsibleContentPrimitive>,
  React.ComponentPropsWithoutRef<typeof CollapsibleContentPrimitive>
>(({ className, children, ...props }, ref) => {
  return (
    <CollapsibleContentPrimitive
      ref={ref}
      {...props}
      className={cn(
        "overflow-hidden data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down",
        className
      )}
    >
      {children}
    </CollapsibleContentPrimitive>
  )
})

CollapsibleContent.displayName = "CollapsibleContent"

export { Collapsible, CollapsibleTrigger, CollapsibleContent }
