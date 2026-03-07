<script setup>
import { Button } from "@/components/ui/button";

const props = defineProps({
  currentPage: {
    type: Number,
    required: true,
  },
  lastPage: {
    type: Number,
    required: true,
  },
});

const emit = defineEmits(["change-page"]);

function goToPrevious() {
  if (props.currentPage > 1) {
    emit("change-page", props.currentPage - 1);
  }
}

function goToNext() {
  if (props.currentPage < props.lastPage) {
    emit("change-page", props.currentPage + 1);
  }
}
</script>

<template>
  <div
    v-if="lastPage > 1"
    class="mt-6 flex items-center justify-between border-t pt-4"
  >
    <Button
      variant="outline"
      :disabled="currentPage === 1"
      @click="goToPrevious"
    >
      Previous
    </Button>

    <span class="text-sm text-muted-foreground">
      Page {{ currentPage }} of {{ lastPage }}
    </span>

    <Button
      variant="outline"
      :disabled="currentPage === lastPage"
      @click="goToNext"
    >
      Next
    </Button>
  </div>
</template>