const API_BASE = "/api";

/**
 * Small helper to handle fetch responses consistently.
 */
async function handleResponse(response) {
  const contentType = response.headers.get("content-type") || "";
  const isJson = contentType.includes("application/json");

  const data = isJson ? await response.json() : await response.text();

  if (!response.ok) {
    const message =
      (isJson && data?.message) ||
      `Request failed with status ${response.status}`;

    const error = new Error(message);
    error.status = response.status;
    error.data = data;
    throw error;
  }

  return data;
}

/**
 * Build a query string from an object.
 * Ignores null, undefined, and empty-string values.
 */
function buildQuery(params = {}) {
  const searchParams = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (value === null || value === undefined || value === "") {
      return;
    }

    searchParams.append(key, String(value));
  });

  const queryString = searchParams.toString();
  return queryString ? `?${queryString}` : "";
}

/**
 * GET /api/items
 * Supports:
 * - state
 * - search
 * - sort
 * - order
 * - per_page
 * - page
 */
export async function getItems(params = {}) {
  const query = buildQuery(params);
  const response = await fetch(`${API_BASE}/items${query}`, {
    method: "GET",
    headers: {
      Accept: "application/json",
    },
  });

  return handleResponse(response);
}

/**
 * GET /api/items/:id
 */
export async function getItem(id) {
  const response = await fetch(`${API_BASE}/items/${id}`, {
    method: "GET",
    headers: {
      Accept: "application/json",
    },
  });

  return handleResponse(response);
}

/**
 * POST /api/items
 * body: { title, content }
 */
export async function createItem(payload) {
  const response = await fetch(`${API_BASE}/items`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify(payload),
  });

  return handleResponse(response);
}

/**
 * POST /api/items/:id/review
 * body: { action, note }
 */
export async function reviewItem(id, payload) {
  const response = await fetch(`${API_BASE}/items/${id}/review`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify(payload),
  });

  return handleResponse(response);
}