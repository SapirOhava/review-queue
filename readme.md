
# Review Queue — Full Stack Take-Home

A small full‑stack application that allows submitting items to a queue and reviewing them.

The application includes:

- **Vue.js** frontend UI
- **Laravel** backend API
- **SQLite** persistence
- A simple moderation heuristic that assigns a risk score to items

The goal of this project is to demonstrate decision‑making, architecture choices, and the ability to build a complete end‑to‑end solution within a limited timebox.

---

# 1. How to Run the Project

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+
- npm
- SQLite

---

# Backend (Laravel)

Navigate to the backend folder:

```bash
cd backend
```

Install dependencies:

```bash
composer install
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

Run database migrations:

```bash
php artisan migrate
```

Start the backend server:

```bash
php artisan serve
```

The API will run at:

```
http://127.0.0.1:8000
```

---

# Frontend (Vue + Vite)

Navigate to the frontend folder:

```bash
cd frontend
```

Install dependencies:

```bash
npm install
```

Run the development server:

```bash
npm run dev
```

The frontend will run at:

```
http://localhost:5173
```

The frontend communicates with the Laravel API through `/api` routes.

---

# Running Tests

Backend tests can be executed with:

```bash
php artisan test
```

Tests cover the most meaningful backend flows such as:

- item creation
- moderation scoring
- review actions
- preventing invalid re‑review
- filtering items

---

# 2. Key Decisions

## Data Model

The core entity in the system is an **Item**.

### Fields

| Field | Purpose |
|------|---------|
| id | Primary identifier |
| title | Short item title |
| content | Main item text |
| state | Review state (`pending`, `approved`, `rejected`) |
| review_note | Optional note left by reviewer |
| risk_score | Moderation heuristic score |
| reviewed_at | Timestamp when item was reviewed |
| created_at | Item creation timestamp |
| updated_at | Last modification timestamp |

### Why this model

The model intentionally separates:

- **review state**
- **moderation signal (risk score)**

This allows the system to **assist reviewers without automatically enforcing decisions**.

The reviewer still has the final decision.

---

## API Design

The API is intentionally simple and REST‑like.

### Endpoints

```
GET    /api/items
POST   /api/items
GET    /api/items/{id}
POST   /api/items/{id}/review
```

### Design choices

**GET /items**

Supports:

- filtering by state
- search in title/content
- sorting
- pagination

**POST /items**

Creates a new item and computes its risk score.

**POST /items/{id}/review**

Allows a reviewer to approve or reject an item and optionally leave a note.

A reviewer cannot review the same item twice.

If attempted, the API returns:

```
409 Conflict
```

This protects the integrity of the review state.

---

## Persistence Choice

The project uses **SQLite**.

### Why SQLite

SQLite was chosen because:

- it requires no external setup
- it works well within the 4–6 hour timebox
- Laravel integrates with it easily

For production, this would likely be replaced with:

- PostgreSQL or MySQL
- database migrations and indexing for scalability

---

# Moderation Heuristic

The backend assigns a **risk score** when items are created.

The heuristic is intentionally simple and designed to provide a **signal to the reviewer**, not an automated decision.

### Rules

- **+70** if content contains high‑risk phrases (e.g. “free money”, “buy now”)
- **+8–25** depending on the ratio of ALL CAPS text
- **+8 per link** found in the content
- **+10** for repeated spam‑like punctuation (e.g. `!!!`)

The final score is capped at **100**.

Based on the score, the system provides a **suggested action**:

```
risk_score >= 60 → reject
risk_score < 60 → approve
```

This suggestion is **not stored in the database** and is computed dynamically.

---

# Frontend UI

The frontend is implemented with **Vue + Vite**.

The UI includes:

### Queue View

Displays items in the review queue including:

- title
- state
- risk score
- suggested action

### Item Detail Modal

Allows a reviewer to:

- read the full item
- approve or reject
- optionally add a note

### Additional UI features

- filtering by state
- text search
- sorting
- pagination
- loading indicators
- error handling

Styling uses **Tailwind CSS and shadcn‑vue components**.

---

# 3. Assumptions

Some assumptions were made due to the intentionally open‑ended nature of the assignment.

- A single reviewer is assumed (no authentication implemented)
- Items are assumed to be simple text submissions
- The moderation heuristic is only a suggestion and does not automatically enforce decisions
- Only three review states exist (`pending`, `approved`, `rejected`)
- The reviewer can only review an item once

---

# 4. Tradeoffs

## What I Optimized For

Within the timebox I optimized for:

- a working end‑to‑end solution
- clear structure
- understandable code
- meaningful backend testing

I focused on building something that could easily be discussed and modified during the follow‑up interview.

---

## What I Intentionally Did Not Build

To keep the scope reasonable within the timebox, I did not implement:

- authentication
- user roles
- real‑time updates
- complex moderation models
- frontend test suite
- production‑grade infrastructure

These features would normally be considered in a production environment.

---

# 5. Testing

A small number of backend tests were implemented to cover the most important logic.

### Tested Areas

- creating an item computes and stores the risk score
- reviewing an item updates its state and stores the review note
- attempting to review an already reviewed item returns a **409 Conflict**
- filtering items by state

These tests validate the **core business logic of the system**.

### What Was Not Tested

Frontend tests were intentionally not implemented due to time constraints.

In a production system, additional testing could include:

- frontend component tests
- API contract tests
- moderation heuristic unit tests

---

# Optional Stretch Goal Implemented

Pagination was implemented in the queue view.

The backend supports:

```
page
per_page
```

parameters, and the frontend allows browsing through pages of results.

---

# Final Notes

The goal of this assignment was not to produce a production‑ready system but to demonstrate:

- architectural decision making
- end‑to‑end ownership
- ability to explain tradeoffs
- ability to build a working full‑stack application under a time constraint.

The system was intentionally kept simple to make it easy to reason about and modify during the follow‑up interview.
