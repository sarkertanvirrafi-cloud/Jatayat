# Feature Folder Map

Each user-facing feature is isolated in its own folder while all features use the same shared stylesheet, assets, JavaScript helpers, authentication token, and PostgreSQL API.

- `features/auth/` — Log In, Sign Up, UIU email validation
- `features/view-selection/` — Rider View / Customer View selection
- `features/rider-dashboard/` — Rider main options
- `features/customer-dashboard/` — Customer main options
- `features/ride-request/` — Customer ride request + Rider available requests
- `features/schedule-ride/` — Rider and Customer scheduled ride flows
- `features/request-history/` — Shared Request History page for both roles
- `features/active-ride/` — Ride Details and active ride interaction
- `features/payment/` — Cash, bKash, Nagad, Rocket UI

Backend feature folders mirror the frontend responsibilities under `api/`.
