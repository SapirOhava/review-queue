<script setup>
import { reactive, watch } from "vue";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

const props = defineProps({
  filters: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["apply", "reset"]);

const localFilters = reactive({
  state: "",
  search: "",
  sort: "created_at",
  order: "desc",
});

watch(
  () => props.filters,
  (value) => {
    localFilters.state = value.state ?? "";
    localFilters.search = value.search ?? "";
    localFilters.sort = value.sort ?? "created_at";
    localFilters.order = value.order ?? "desc";
  },
  { immediate: true, deep: true }
);

function handleApply() {
  emit("apply", {
    state: localFilters.state,
    search: localFilters.search,
    sort: localFilters.sort,
    order: localFilters.order,
  });
}

function handleReset() {
  localFilters.state = "";
  localFilters.search = "";
  localFilters.sort = "created_at";
  localFilters.order = "desc";

  emit("reset");
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Browse Queue</CardTitle>
      <CardDescription>
        Filter, search, and sort items in the review queue.
      </CardDescription>
    </CardHeader>

    <CardContent class="space-y-4">
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="space-y-2">
          <label class="text-sm font-medium">State</label>
          <select
            v-model="localFilters.state"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
          >
            <option value="">All</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">Search</label>
          <Input
            v-model="localFilters.search"
            placeholder="Search title or content"
          />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">Sort</label>
          <select
            v-model="localFilters.sort"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
          >
            <option value="created_at">Created At</option>
            <option value="risk_score">Risk Score</option>
            <option value="reviewed_at">Reviewed At</option>
            <option value="title">Title</option>
          </select>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">Order</label>
          <select
            v-model="localFilters.order"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
          >
            <option value="desc">Descending</option>
            <option value="asc">Ascending</option>
          </select>
        </div>
      </div>

      <div class="flex flex-wrap gap-3">
        <Button @click="handleApply">Apply Filters</Button>
        <Button variant="outline" @click="handleReset">Reset</Button>
      </div>
    </CardContent>
  </Card>
</template>