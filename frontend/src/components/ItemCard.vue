<script setup>
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["open"]);

function formatDate(value) {
  if (!value) return "-";
  return new Date(value).toLocaleString();
}

function stateVariant(state) {
  if (state === "approved") return "default";
  if (state === "rejected") return "destructive";
  return "secondary";
}

function suggestionVariant(action) {
  if (action === "reject") return "destructive";
  return "secondary";
}

function handleOpen() {
  emit("open", props.item);
}
</script>

<template>
  <Card>
    <CardHeader class="pb-3">
      <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div class="min-w-0 space-y-2">
          <CardTitle class="text-lg">
            {{ item.title }}
          </CardTitle>

          <div class="flex flex-wrap gap-2">
            <Badge :variant="stateVariant(item.state)">
              {{ item.state }}
            </Badge>

            <Badge variant="outline">
              Risk: {{ item.risk_score }}
            </Badge>

            <Badge :variant="suggestionVariant(item.suggested_action)">
              Suggest: {{ item.suggested_action }}
            </Badge>
          </div>
        </div>

        <Button variant="outline" @click="handleOpen">
          Open
        </Button>
      </div>
    </CardHeader>

    <CardContent class="space-y-3">
      <p class="line-clamp-2 text-sm text-muted-foreground">
        {{ item.content }}
      </p>

      <div class="flex flex-wrap gap-4 text-xs text-muted-foreground">
        <span>Created: {{ formatDate(item.created_at) }}</span>
        <span v-if="item.reviewed_at">
          Reviewed: {{ formatDate(item.reviewed_at) }}
        </span>
      </div>
    </CardContent>
  </Card>
</template>