# Review Queue — Full Stack Take-Home

A small full-stack application that allows submitting items to a queue and reviewing them.

The application includes:

- **Vue.js** frontend UI
- **Laravel** backend API
- **SQLite** persistence
- A simple moderation heuristic that assigns a risk score to items

The goal of this project is to demonstrate decision-making, architecture choices, and the ability to build a complete end-to-end solution within a limited timebox.

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

```text
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

```text
http://localhost:5173
```

The frontend communicates with the Laravel API through `/api` routes using the Vite dev proxy.

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
- preventing invalid re-review
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

The API is intentionally simple and REST-like.

### Endpoints

```text
GET    /api/items
POST   /api/items
GET    /api/items/{id}
POST   /api/items/{id}/review
```

### Query Parameters for `GET /api/items`

```text
state     optional: pending | approved | rejected
search    optional: free-text search in title/content
sort      optional: created_at | risk_score | reviewed_at | title
order     optional: asc | desc
per_page  optional: number of items per page
page      optional: page number
```

### Design choices

**GET /api/items**

Supports:

- filtering by state
- search in title/content
- sorting
- pagination

I chose query parameters for list browsing because they make the queue easy to control from the frontend while keeping the endpoint compact and predictable.

**POST /api/items**

Creates a new item and computes its risk score.

**GET /api/items/{id}**

Returns full details for one item, which is used by the review dialog.

**POST /api/items/{id}/review**

Allows a reviewer to approve or reject an item and optionally leave a note.

A reviewer cannot review the same item twice.

If attempted, the API returns:

```text
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

## Frontend UI and Styling Choice

The frontend is built with:

- **Vue 3**
- **Vite**
- **Tailwind CSS**
- **shadcn-vue**

### Why this choice

I chose Vue + Vite because it is lightweight and fast to set up for a single-page take-home project.

I chose Tailwind CSS and shadcn-vue because they allowed me to build a clean, consistent UI quickly without spending much time on custom styling. This helped keep the focus on the queue workflow, API integration, and review functionality instead of low-level CSS work.

The styling system also made it easy to keep spacing, buttons, cards, badges, dialogs, and loading states visually consistent across the application.

---

# Moderation Heuristic

The backend assigns a **risk score** when items are created.

The heuristic is intentionally simple and designed to provide a **signal to the reviewer**, not an automated decision.

### Rules

- **+70** if content contains high-risk phrases (for example, “free money” or “buy now”)
- **+8 to +25** depending on the ratio of ALL CAPS text
- **+8 per link** found in the content
- **+10** for repeated spam-like punctuation (for example, `!!!`)

The final score is capped at **100**.

Based on the score, the system provides a **suggested action**:

```text
risk_score >= 60 → reject
risk_score < 60 → approve
```

This suggestion is **not stored in the database** and is computed dynamically.

I intentionally treated high-risk phrases as a strong signal, while capitalization, links, and repeated punctuation act as supporting signals.

---

# Frontend UI

The frontend is implemented with **Vue + Vite**, and styled with **Tailwind CSS + shadcn-vue**.

The UI includes:

### Create Item Form

Users can submit a new item to the queue by entering:

- title
- content

Submitting the form creates the item through the backend API and refreshes the queue.

### Queue View

The queue view is the main screen of the application. It includes:

- the list of items currently returned from the backend
- each item's state
- risk score
- suggested action
- filtering by state
- free-text search
- sorting
- pagination
- loading and error states for the list

### Item Detail Modal

Allows a reviewer to:

- read the full item
- approve or reject
- optionally add a note

So in practice, the main page combines:
- item creation
- queue browsing
- filtering / sorting / pagination
- opening an item for review

I kept this as a single-page flow because it felt simpler and more appropriate for the timebox.

---

# 3. Assumptions

Some assumptions were made due to the intentionally open-ended nature of the assignment.

- A single reviewer is assumed, so authentication was not implemented
- Items are assumed to be simple text submissions
- The moderation heuristic is only a suggestion and does not automatically enforce decisions
- Only three review states exist (`pending`, `approved`, `rejected`)
- The reviewer can only review an item once
- The application is intended for local development/demo use rather than production deployment

---

# 4. Tradeoffs

## What I Optimized For

Within the timebox I optimized for:

- a working end-to-end solution
- clear structure
- understandable code
- meaningful backend testing
- a clean, consistent UI without spending excessive time on custom CSS

I focused on building something that could easily be discussed and modified during the follow-up interview.

---

## What I Intentionally Did Not Build

To keep the scope reasonable within the timebox, I did not implement:

- authentication
- user roles
- real-time updates
- complex moderation models
- frontend test suite
- production-grade infrastructure
- bulk actions
- optimistic updates

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

I also did not add a dedicated pagination test. Pagination was implemented as the optional stretch goal, but I chose to focus testing effort on the core business rules: creation, moderation scoring, review state transitions, and filtering.

In a production system, additional testing could include:

- frontend component tests
- API contract tests
- moderation heuristic unit tests
- dedicated pagination tests

---

# Optional Stretch Goal Implemented

Pagination was implemented in the queue view.

The backend supports these query parameters:

```text
per_page
page
```

and the frontend allows browsing through pages of results.

---

# Final Notes

The goal of this assignment was not to produce a production-ready system but to demonstrate:

- architectural decision making
- end-to-end ownership
- ability to explain tradeoffs
- ability to build a working full-stack application under a time constraint

The system was intentionally kept simple so it would be easy to reason about, explain, and modify during the follow-up interview.
