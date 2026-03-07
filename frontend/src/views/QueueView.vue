<script setup>
import { onMounted, reactive, ref } from "vue";
import { getItem, getItems, createItem, reviewItem } from "@/api/items";

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Spinner } from "@/components/ui/spinner";

import ItemForm from "@/components/ItemForm.vue";
import ItemCard from "@/components/ItemCard.vue";
import ItemDetailDialog from "@/components/ItemDetailDialog.vue";
import PaginationControls from "@/components/PaginationControls.vue";
import QueueToolbar from "@/components/QueueToolbar.vue";

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

async function handleCreateItem(data) {
  createError.value = "";

  if (!data.title.trim() || !data.content.trim()) {
    createError.value = "Title and content are required.";
    return;
  }

  creating.value = true;

  try {
    await createItem({
      title: data.title.trim(),
      content: data.content.trim(),
    });

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
}

async function handleApprove(note) {
  if (!selectedItem.value) return;

  reviewError.value = "";
  reviewing.value = true;

  try {
    const result = await reviewItem(selectedItem.value.id, {
      action: "approve",
      note,
    });

    selectedItem.value = result.item;
    await fetchItems();
  } catch (err) {
    reviewError.value = err.message || "Failed to approve item.";
  } finally {
    reviewing.value = false;
  }
}

async function handleReject(note) {
  if (!selectedItem.value) return;

  reviewError.value = "";
  reviewing.value = true;

  try {
    const result = await reviewItem(selectedItem.value.id, {
      action: "reject",
      note,
    });

    selectedItem.value = result.item;
    await fetchItems();
  } catch (err) {
    reviewError.value = err.message || "Failed to reject item.";
  } finally {
    reviewing.value = false;
  }
}

function applyFilters(newFilters) {
  filters.state = newFilters.state;
  filters.search = newFilters.search;
  filters.sort = newFilters.sort;
  filters.order = newFilters.order;
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
        <ItemForm
          :loading="creating"
          :error="createError"
          @submit="handleCreateItem"
        />

        <div class="space-y-6">
          <QueueToolbar
            :filters="filters"
            @apply="applyFilters"
            @reset="resetFilters"
          />

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
              <div
                v-if="loading"
                class="flex items-center gap-3 py-8 text-sm text-muted-foreground"
              >
                <Spinner class="size-5" />
                Loading items...
              </div>

              <div
                v-else-if="error"
                class="rounded-md border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive"
              >
                {{ error }}
              </div>

              <div
                v-else-if="items.length === 0"
                class="rounded-md border border-dashed p-8 text-center text-sm text-muted-foreground"
              >
                No items found.
              </div>

              <div v-else class="space-y-4">
                <ItemCard
                  v-for="item in items"
                  :key="item.id"
                  :item="item"
                  @open="openItem"
                />
              </div>

              <PaginationControls
                :current-page="pagination.currentPage"
                :last-page="pagination.lastPage"
                @change-page="changePage"
              />
            </CardContent>
          </Card>
        </div>
      </div>
    </div>

    <ItemDetailDialog
      :open="isDialogOpen"
      :item="selectedItem"
      :reviewing="reviewing"
      :error="reviewError"
      @close="closeDialog"
      @approve="handleApprove"
      @reject="handleReject"
    />
  </div>
</template>