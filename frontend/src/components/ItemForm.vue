<script setup>
import { reactive } from "vue";

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
import { Spinner } from "@/components/ui/spinner";

const props = defineProps({
  loading: Boolean,
  error: String,
});

const emit = defineEmits(["submit"]);

const form = reactive({
  title: "",
  content: "",
});

function handleSubmit() {
  emit("submit", {
    title: form.title,
    content: form.content,
  });

  form.title = "";
  form.content = "";
}
</script>

<template>
  <Card class="h-fit">
    <CardHeader>
      <CardTitle>Submit New Item</CardTitle>
      <CardDescription>
        Create a new queue item for review.
      </CardDescription>
    </CardHeader>

    <CardContent>
      <form class="space-y-4" @submit.prevent="handleSubmit">
        <div class="space-y-2">
          <label class="text-sm font-medium">Title</label>
          <Input v-model="form.title" placeholder="Enter a title" />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">Content</label>
          <Textarea
            v-model="form.content"
            placeholder="Write the item content"
            rows="6"
          />
        </div>

        <p v-if="error" class="text-sm text-destructive">
          {{ error }}
        </p>

        <Button type="submit" class="w-full" :disabled="loading">
          <Spinner v-if="loading" class="mr-2 size-4" />
          {{ loading ? "Submitting..." : "Submit Item" }}
        </Button>
      </form>
    </CardContent>
  </Card>
</template>