<script setup>
import { reactive, watch } from "vue";

import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Textarea } from "@/components/ui/textarea";
import { Spinner } from "@/components/ui/spinner";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  item: {
    type: Object,
    default: null,
  },
  reviewing: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: "",
  },
});

const emit = defineEmits(["close", "approve", "reject"]);

const reviewForm = reactive({
  note: "",
});

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) {
      reviewForm.note = "";
    }
  }
);

watch(
  () => props.item,
  () => {
    reviewForm.note = "";
  }
);

function stateVariant(state) {
  if (state === "approved") return "default";
  if (state === "rejected") return "destructive";
  return "secondary";
}

function suggestionVariant(action) {
  if (action === "reject") return "destructive";
  return "secondary";
}

function handleApprove() {
  emit("approve", reviewForm.note);
}

function handleReject() {
  emit("reject", reviewForm.note);
}

function handleOpenChange(value) {
  if (!value) {
    emit("close");
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="handleOpenChange">
    <DialogContent class="sm:max-w-2xl">
      <DialogHeader>
        <DialogTitle>
          {{ item?.title }}
        </DialogTitle>
        <DialogDescription>
          Review the selected item and choose an action.
        </DialogDescription>
      </DialogHeader>

      <div v-if="item" class="space-y-6">
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

        <div class="rounded-md border bg-muted/30 p-4 text-sm leading-6">
          {{ item.content }}
        </div>

        <div v-if="item.state === 'pending'" class="space-y-3">
          <div class="space-y-2">
            <label class="text-sm font-medium">Review Note</label>
            <Textarea
              v-model="reviewForm.note"
              rows="4"
              placeholder="Optional note"
            />
          </div>

          <p v-if="error" class="text-sm text-destructive">
            {{ error }}
          </p>

          <DialogFooter class="gap-2 sm:justify-end">
            <Button
              variant="outline"
              :disabled="reviewing"
              @click="handleApprove"
            >
              <Spinner v-if="reviewing" class="mr-2 size-4" />
              Approve
            </Button>

            <Button
              variant="destructive"
              :disabled="reviewing"
              @click="handleReject"
            >
              <Spinner v-if="reviewing" class="mr-2 size-4" />
              Reject
            </Button>
          </DialogFooter>
        </div>

        <div v-else class="space-y-2 rounded-md border bg-muted/30 p-4 text-sm">
          <p class="font-medium">This item was already reviewed.</p>
          <p v-if="item.review_note" class="text-muted-foreground">
            Note: {{ item.review_note }}
          </p>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>