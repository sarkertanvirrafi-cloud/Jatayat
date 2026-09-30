# Jatra — Vercel + PostgreSQL Deployment

## 1. Create a hosted PostgreSQL database
Recommended for a simple student deployment: Neon.

1. Create a Neon project.
2. Copy the PostgreSQL connection string. It looks like:
   `postgresql://USER:PASSWORD@HOST:5432/DATABASE?sslmode=require`
3. Open Neon's SQL Editor.
4. Run the complete file: `database/postgresql/schema.sql`.

## 2. Push this folder to GitHub
The repository root must directly contain `index.html`, `vercel.json`, `api/`, `features/`, and `shared/`.

## 3. Import the GitHub repository into Vercel
- Framework Preset: Other
- Root Directory: repository root
- Build Command: leave empty
- Output Directory: leave empty

## 4. Add the database environment variable
Vercel → Project → Settings → Environment Variables

Add:
- Name: `DATABASE_URL`
- Value: your full PostgreSQL connection string
- Select Production (and Preview/Development too if desired)

If you add/change environment variables after deploying, redeploy the project.

## 5. Deploy and test
First open:
`https://YOUR-PROJECT.vercel.app/api/test-db.php`

Success response includes:
`"message":"PostgreSQL connected"`

Then open:
`https://YOUR-PROJECT.vercel.app/`

## 6. Test the application
1. Sign up with an email ending in `@bscse.uiu.ac.bd`.
2. Create a Customer ride request and verify Rider → Available Request.
3. Create a Rider schedule ride and verify Customer → Available Schedule Ride.
4. Create a Customer schedule ride and verify Rider → Customers Active Schedule Ride.
5. Test Accept/Decline and Request History.

## Security note
After database connectivity is confirmed, you may remove `api/test-db.php` and redeploy.
Never commit a real `.env` or database password to GitHub.
