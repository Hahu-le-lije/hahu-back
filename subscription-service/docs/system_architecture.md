# System Architecture & Data Flow

This document provides a technical overview of the Subscription Service, detailing how data flows from entry points to background processing and external service dependencies.

## 1. API & Web Entry Points

The system exposes a RESTful API primarily focused on payment initialization and subscription management.

### Routes & Access Control
| Endpoint | Method | Controller Action | Middleware | Description |
| :--- | :--- | :--- | :--- | :--- |
| `/initialize-payment` | POST | `PaymentController@initializePayment` | `ClerkAuthMiddleware` | Initiates a transaction with Chapa. |
| `/subscriptions/add-child/{subscription}/{child}` | PUT | `SubscriptionController@addChildToSubscription` | `ClerkAuthMiddleware` | Associates a child with an active subscription. |
| `/subscriptions/list` | GET | `SubscriptionController@listUserSubscriptions` | `ClerkAuthMiddleware` | Returns a list of the authenticated user's subscriptions. |
| `/subscriptions/{subscription}` | GET | `SubscriptionController@getSubscriptionDetails` | `ClerkAuthMiddleware` | Detailed view of a specific subscription. |
| `/subscriptions/create` | POST | `SubscriptionController@createSubscription` | None | Processes subscription creation (often used as a webhook callback). |

### Middleware Stack
*   **ClerkAuthMiddleware**: Validates external JWT tokens issued by Clerk. It fetches and caches Clerk's JWKS (JSON Web Key Set) for 24 hours to verify signatures. Upon success, it hydrates a `GenericUser` into Laravel's `Auth` guard.
*   **JwtAuthenticate**: An alternative middleware for validating internal JWT tokens signed with a shared secret (`HS256`). Used for inter-service authentication where Clerk tokens may not be present.

---

## 2. Service Logic & Dependencies

Business logic is abstracted into services to maintain a clean separation between the HTTP layer and the data layer.

### Primary Services
*   **InternalUserService**: Manages communication with the centralized User Service.
    *   **Communication Pattern**: HTTP (formerly RabbitMQ RPC).
    *   **Authentication**: Outgoing requests include a Bearer token (`INTERNAL_SERVICE_TOKEN`).
    *   **Responsibilities**: Validating parent/user IDs and fetching child metadata from the external user service.
*   **SubscriptionManager**: A utility service for plan-related calculations.
    *   **Responsibilities**: Calculating plan amounts based on tiers (basic, premium, ultimate) and slot counts, and fetching plan durations from `config/subscriptiontype.php`.

---

## 3. Asynchronous Communication (Jobs & Queues)

The system leverages asynchronous processing to handle cross-service side effects and daily bulk operations.

### Queue Configuration
*   **Default Driver**: `database` (local tasks).
*   **External Driver**: `rabbitmq` (inter-service communication).

### Jobs & Dispatches
| Job Class | Trigger | Queue Driver | Queue Name | Intent |
| :--- | :--- | :--- | :--- | :--- |
| `ProcessActiveSubscriptions` | `DispatchDailySubscriptions` Command | `rabbitmq` | `ars_subscriptions_queue` | Pushes active subscription data to the ARS service for daily synchronization. |
| `LinkChildSubscription` | Background Logic | `rabbitmq` | `subscription_to_user` | Pushes child-to-subscription linking updates to the User Service. |

---

## 4. Automation & Command Layer

Custom Artisan commands handle scheduled tasks and lifecycle management.

### Custom Commands
*   **`subscriptions:dispatch-daily`**
    *   **Intent**: Identifies all "active" and non-expired subscriptions in the database.
    *   **Flow**: It chunks records (50 at a time) and dispatches the `ProcessActiveSubscriptions` job to the `ars_subscriptions_queue` on RabbitMQ. This acts as the primary synchronization heartbeat between the Subscription Service and the Active Retrieval Service (ARS).

---

## 5. System Flow Summary (Top-Down)

1.  **Request**: A client sends a request to add a child to a subscription.
2.  **Authentication**: `ClerkAuthMiddleware` verifies the client's identity against Clerk.
3.  **Validation**: `SubscriptionController` calls `InternalUserService@getChild` (HTTP) to verify the child exists in the remote User Service.
4.  **Transaction**: Local database records are updated to decrement available slots in the `subscriptions` table.
5.  **Synchronization**: The `subscriptions:dispatch-daily` command runs daily via a cron job, pushing all active subscription states into RabbitMQ for downstream services (like ARS) to consume.
