# Hahu Lelije Subscription Service API

This service manages payments and user subscriptions for the Hahu Lelije platform. It integrates with Chapa for payment processing and Clerk for authentication.

## Authentication

All protected endpoints require a valid Clerk JWT passed in the `Authorization` header.

```http
Authorization: Bearer <clerk_jwt_token>
```

---

## API Reference

### 1. Initialize Payment
Starts a new payment transaction with Chapa.

- **URL**: `/api/initialize-payment`
- **Method**: `POST`
- **Auth Required**: Yes
- **Request Body**:
  ```json
  {
    "plan_type": "premium",
    "max_slots": 3
  }
  ```
- **Success Response**:
  - **Code**: 200 OK
  - **Content**:
    ```json
    {
      "status": "success",
      "data": {
        "checkout_url": "https://checkout.chapa.co/checkout/payment/..."
      }
    }
    ```

### 2. Add Child to Subscription
Associates a child with an active subscription slot.

- **URL**: `/api/subscriptions/add-child/{subscription_id}/{child_id}`
- **Method**: `PUT`
- **Auth Required**: Yes
- **URL Params**:
  - `subscription_id` (Integer): The ID of the subscription.
  - `child_id` (String): The UUID/ID of the child from the User Service.
- **Success Response**:
  - **Code**: 200 OK
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Child added to subscription successfully"
    }
    ```

### 3. List User Subscriptions
Retrieves the most recent subscriptions for the authenticated user.

- **URL**: `/api/subscriptions/list`
- **Method**: `GET`
- **Auth Required**: Yes
- **Success Response**:
  - **Code**: 200 OK
  - **Content**:
    ```json
    {
      "status": "success",
      "data": {
        "subscriptions": [...]
      }
    }
    ```

### 4. Get Subscription Details
Retrieves details for a specific subscription.

- **URL**: `/api/subscriptions/{subscription_id}`
- **Method**: `GET`
- **Auth Required**: Yes
- **Success Response**:
  - **Code**: 200 OK
  - **Content**:
    ```json
    {
      "status": "success",
      "data": {
        "subscription": {
          "id": 1,
          "plan_type": "premium",
          "available_slots": 2,
          ...
        }
      }
    }
    ```

### 5. Create Subscription (Webhook)
Internal callback endpoint used by Chapa to confirm payment and create the subscription.

- **URL**: `/api/subscriptions/create`
- **Method**: `POST`
- **Auth Required**: No (Verified via Chapa signature/reference)
- **Request Body**:
  ```json
  {
    "trx_ref": "HahuSub_...",
    "ref_id": "...",
    "status": "success"
  }
  ```
- **Success Response**:
  - **Code**: 201 Created
  - **Content**:
    ```json
    {
      "status": "success",
      "message": "Subscription created successfully",
      "data": {
        "subscription": { ... }
      }
    }
    ```

---

## Technical Architecture

For detailed information about the system flow, middleware, and background jobs, please refer to [doc/system_architecture.md](doc/system_architecture.md).
