<script setup>
import { onMounted, reactive, ref } from "vue";
import { createItem, getItem, getItems, reviewItem } from "@/api/items";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Spinner } from "@/components/ui/spinner";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

const items = ref([]);
const loading = ref(false);
const error = ref("");

const creating = ref(false);
const createError = ref("");

const reviewing = ref(false);
const reviewError = ref("");

const selectedItem = ref(null);
const isDialogOpen = ref(false);

const filters = reactive({
  state: "",
  search: "",
  sort: "created_at",
  order: "desc",
  per_page: 10,
  page: 1,
});

const pagination = reactive({
  currentPage: 1,
  lastPage: 1,
  perPage: 10,
  total: 0,
});

const createForm = reactive({
  title: "",
  content: "",
});

const reviewForm = reactive({
  note: "",
});

async function fetchItems() {
  loading.value = true;
  error.value = "";

  try {
    const result = await getItems(filters);

    items.value = result.data ?? [];
    pagination.currentPage = result.current_page ?? 1;
    pagination.lastPage = result.last_page ?? 1;
    pagination.perPage = Number(result.per_page ?? filters.per_page);
    pagination.total = result.total ?? 0;
  } catch (err) {
    error.value = err.message || "Failed to load items.";
  } finally {
    loading.value = false;
  }
}

async function handleCreateItem() {
  createError.value = "";

  if (!createForm.title.trim() || !createForm.content.trim()) {
    createError.value = "Title and content are required.";
    return;
  }

  creating.value = true;

  try {
    await createItem({
      title: createForm.title.trim(),
      content: createForm.content.trim(),
    });

    createForm.title = "";
    createForm.content = "";
    filters.page = 1;

    await fetchItems();
  } catch (err) {
    createError.value = err.message || "Failed to create item.";
  } finally {
    creating.value = false;
  }
}

async function openItem(item) {
  reviewError.value = "";
  reviewForm.note = "";

  try {
    const result = await getItem(item.id);
    selectedItem.value = result.item;
    isDialogOpen.value = true;
  } catch (err) {
    error.value = err.message || "Failed to load item details.";
  }
}

function closeDialog() {
  isDialogOpen.value = false;
  selectedItem.value = null;
  reviewError.value = "";
  reviewForm.note = "";
}

async function handleReview(action) {
  if (!selectedItem.value) return;

  reviewError.value = "";
  reviewing.value = true;

  try {
    const result = await reviewItem(selectedItem.value.id, {
      action,
      note: reviewForm.note,
    });

    selectedItem.value = result.item;
    await fetchItems();
  } catch (err) {
    reviewError.value = err.message || "Failed to review item.";
  } finally {
    reviewing.value = false;
  }
}

function applyFilters() {
  filters.page = 1;
  fetchItems();
}

function resetFilters() {
  filters.state = "";
  filters.search = "";
  filters.sort = "created_at";
  filters.order = "desc";
  filters.per_page = 10;
  filters.page = 1;
  fetchItems();
}

function changePage(page) {
  if (page < 1 || page > pagination.lastPage) return;
  filters.page = page;
  fetchItems();
}

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

onMounted(() => {
  fetchItems();
});
</script>

<template>
  <div class="min-h-screen bg-muted/30">
    <div class="container mx-auto max-w-6xl px-4 py-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold tracking-tight">Review Queue</h1>
        <p class="mt-2 text-sm text-muted-foreground">
          Submit items, browse the queue, and review pending content.
        </p>
      </div>

      <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
        <!-- Create item -->
        <Card class="h-fit">
          <CardHeader>
            <CardTitle>Submit New Item</CardTitle>
            <CardDescription>
              Create a new queue item for review.
            </CardDescription>
          </CardHeader>

          <CardContent>
            <form class="space-y-4" @submit.prevent="handleCreateItem">
              <div class="space-y-2">
                <label class="text-sm font-medium">Title</label>
                <Input v-model="createForm.title" placeholder="Enter a title" />
              </div>

              <div class="space-y-2">
                <label class="text-sm font-medium">Content</label>
                <Textarea
                  v-model="createForm.content"
                  placeholder="Write the item content"
                  rows="6"
                />
              </div>

              <p v-if="createError" class="text-sm text-destructive">
                {{ createError }}
              </p>

              <Button type="submit" class="w-full" :disabled="creating">
                <Spinner v-if="creating" class="mr-2 size-4" />
                {{ creating ? "Submitting..." : "Submit Item" }}
              </Button>
            </form>
          </CardContent>
        </Card>

        <div class="space-y-6">
          <!-- Filters -->
          <Card>
            <CardHeader>
              <CardTitle>Browse Queue</CardTitle>
              <CardDescription>
                Filter, search, sort, and paginate through items.
              </CardDescription>
            </CardHeader>

            <CardContent class="space-y-4">
              <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="space-y-2">
                  <label class="text-sm font-medium">State</label>
                  <select
                    v-model="filters.state"
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
                    v-model="filters.search"
                    placeholder="Search title or content"
                  />
                </div>

                <div class="space-y-2">
                  <label class="text-sm font-medium">Sort</label>
                  <select
                    v-model="filters.sort"
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
                    v-model="filters.order"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
                  >
                    <option value="desc">Descending</option>
                    <option value="asc">Ascending</option>
                  </select>
                </div>
              </div>

              <div class="flex flex-wrap gap-3">
                <Button @click="applyFilters">Apply Filters</Button>
                <Button variant="outline" @click="resetFilters">Reset</Button>
              </div>
            </CardContent>
          </Card>

          <!-- List -->
          <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0">
              <div>
                <CardTitle>Items</CardTitle>
                <CardDescription>
                  Total items: {{ pagination.total }}
                </CardDescription>
              </div>
            </CardHeader>

            <CardContent>
              <div v-if="loading" class="flex items-center gap-3 py-8 text-sm text-muted-foreground">
                <Spinner class="size-5" />
                Loading items...
              </div>

              <div v-else-if="error" class="rounded-md border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive">
                {{ error }}
              </div>

              <div
                v-else-if="items.length === 0"
                class="rounded-md border border-dashed p-8 text-center text-sm text-muted-foreground"
              >
                No items found.
              </div>

              <div v-else class="space-y-4">
                <Card v-for="item in items" :key="item.id">
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

                      <Button variant="outline" @click="openItem(item)">
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
              </div>

              <div
                v-if="pagination.lastPage > 1"
                class="mt-6 flex items-center justify-between border-t pt-4"
              >
                <Button
                  variant="outline"
                  :disabled="pagination.currentPage === 1"
                  @click="changePage(pagination.currentPage - 1)"
                >
                  Previous
                </Button>

                <span class="text-sm text-muted-foreground">
                  Page {{ pagination.currentPage }} of {{ pagination.lastPage }}
                </span>

                <Button
                  variant="outline"
                  :disabled="pagination.currentPage === pagination.lastPage"
                  @click="changePage(pagination.currentPage + 1)"
                >
                  Next
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>

    <!-- Review dialog -->
    <Dialog :open="isDialogOpen" @update:open="(value) => { if (!value) closeDialog() }">
      <DialogContent class="sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>
            {{ selectedItem?.title }}
          </DialogTitle>
          <DialogDescription>
            Review the selected item and choose an action.
          </DialogDescription>
        </DialogHeader>

        <div v-if="selectedItem" class="space-y-6">
          <div class="flex flex-wrap gap-2">
            <Badge :variant="stateVariant(selectedItem.state)">
              {{ selectedItem.state }}
            </Badge>

            <Badge variant="outline">
              Risk: {{ selectedItem.risk_score }}
            </Badge>

            <Badge :variant="suggestionVariant(selectedItem.suggested_action)">
              Suggest: {{ selectedItem.suggested_action }}
            </Badge>
          </div>

          <div class="rounded-md border bg-muted/30 p-4 text-sm leading-6">
            {{ selectedItem.content }}
          </div>

          <div v-if="selectedItem.state === 'pending'" class="space-y-3">
            <div class="space-y-2">
              <label class="text-sm font-medium">Review Note</label>
              <Textarea
                v-model="reviewForm.note"
                rows="4"
                placeholder="Optional note"
              />
            </div>

            <p v-if="reviewError" class="text-sm text-destructive">
              {{ reviewError }}
            </p>

            <DialogFooter class="gap-2 sm:justify-end">
              <Button
                variant="outline"
                :disabled="reviewing"
                @click="handleReview('approve')"
              >
                <Spinner v-if="reviewing" class="mr-2 size-4" />
                Approve
              </Button>

              <Button
                variant="destructive"
                :disabled="reviewing"
                @click="handleReview('reject')"
              >
                <Spinner v-if="reviewing" class="mr-2 size-4" />
                Reject
              </Button>
            </DialogFooter>
          </div>

          <div v-else class="space-y-2 rounded-md border bg-muted/30 p-4 text-sm">
            <p class="font-medium">This item was already reviewed.</p>
            <p v-if="selectedItem.review_note" class="text-muted-foreground">
              Note: {{ selectedItem.review_note }}
            </p>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </div>
</template>