# System Architecture & Data Flow Documentation

## Overview
The **Analysis & Recommendation Service (ARS)** is a specialized microservice designed to provide AI-driven educational insights for parents. It functions as a **Backend-for-Frontend (BFF)**, orchestrating data from multiple source services to deliver high-value recommendations and analytical dashboards.

---

## 1. API & Web Entry Points

The system exposes a structured API layer under the `parents/children/{childId}` prefix, focused on guardian-facing data.

### Request Pipeline
Every request to the protected API routes passes through a rigorous multi-stage middleware stack:

1.  **ClerkAuthMiddleware (The Identity Guard)**
    *   **Function:** Validates the Clerk JWT provided in the Bearer token.
    *   **Logic:** Fetches JWKS from Clerk (cached for 24h), converts to PEM, and decodes the JWT.
    *   **Transformation:** Injects a `GenericUser` into the Laravel `Auth` container, making `Auth::user()` available for downstream logic.

2.  **GuardianVerifyAuth (The Ownership Guard)**
    *   **Function:** Ensures the authenticated parent has legal access to the requested `childId`.
    *   **Logic:** Correlates the `Auth::id()` with the child's `parent_id` by querying the **Child Service**.
    *   **Transformation:** Caches the parent-child relationship for 7 days to optimize performance.

### Endpoint Registry

| Endpoint | Method | Purpose | Auth Requirements |
|:---|:---|:---|:---|
| `/recommendation` | `GET` | Retrieves the latest AI recommendation text. | Clerk JWT + Guardian Verified |
| `/dashboard-status` | `GET` | Aggregates learning health scores and metrics. | Clerk JWT + Guardian Verified |
| `/recommendation/history` | `GET` | Retrieves past recommendations (Limit 10). | Clerk JWT + Guardian Verified + Premium/Ultimate Tier |

---

## 2. Service Logic & Dependencies

The `app/Services` layer acts as the system's brain, abstracting complex external communications and AI logic.

*   **SyncServiceClient:** The primary data bridge to the learning environment. It fetches analytics overviews, feature snapshots, and recent event logs for children.
*   **UserServiceClient:** Manages core child metadata and active status lists. It utilizes a 12-hour cache to minimize cross-service latency.
*   **ChildServiceClient:** Specifically handles child-to-parent relationship verification and subscription status checks.
*   **AiRecommendationService:** Encapsulates the prompt engineering logic. It consumes raw metrics and generates natural language recommendations tailored to the child's subscription tier.
*   **ServiceJwtService:** Responsible for generating internal machine-to-machine JWTs for secure communication between microservices.

---

## 3. Asynchronous Communication

ARS leverages background processing to handle heavy AI generation tasks without blocking user requests or system commands.

### Subscription Processing Flow
*   **Job:** `ProcessActiveSubscriptions`
*   **Dispatch Mechanism:** Typically triggered in batches when subscription updates are detected.
*   **Queue Driver:** `rabbitmq` (Configured as a primary driver for robust cross-service messaging).
*   **Queue Name:** `ars_subscriptions_queue`
*   **Behavior:** For each subscription in the batch, the job fetches child details, checks if a recommendation is due based on the tier (Daily for Ultimate, 3-day for Premium, 14-day for Basic), and invokes the AI pipeline.

---

## 4. Automation & Command Layer

Custom Artisan commands provide the manual and scheduled entry points for system maintenance and mass-generation.

### GenerateRecommendations Command
*   **Command:** `php artisan app:generate-recommendations`
*   **Intent:** A scheduled lifecycle command that scans all active children in the system.
*   **Workflow:**
    1.  Fetches all active children from the User Service.
    2.  Calculates "recency" of the last recommendation.
    3.  If a new recommendation is due, it aggregates data from the Sync Service and generates a new entry via the AI Service.
    4.  Updates the `next_update_expected_at` timestamp to schedule the next logical iteration.

---

## 5. Data Flow Summary (Top-Down)

1.  **Ingress:** A Parent requests a dashboard update via the API.
2.  **Security:** Middleware validates their session and verifies they own the child profile.
3.  **Aggregation:** The `ParentDashboardController` calls `SyncServiceClient` to pull metrics from the data layer.
4.  **BFF Transformation:** Raw scores (e.g., 78%) are transformed into human-readable labels (e.g., "Excellent") within the controller.
5.  **Persistence:** The recommendation engine (via Job or Command) saves generated insights into the local `recommendations` table for instant retrieval.
