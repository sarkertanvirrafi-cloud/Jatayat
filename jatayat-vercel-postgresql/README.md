# Jatra — Vercel + PostgreSQL

Frontend: HTML, CSS, basic JavaScript  
Backend: PHP  
Database: PostgreSQL  
Deployment target: Vercel + hosted PostgreSQL

The original frontend design files are preserved. The backend has been converted from MySQL to PostgreSQL.

## Database
Run:
`database/postgresql/schema.sql`

Set this environment variable in Vercel:
`DATABASE_URL=postgresql://USER:PASSWORD@HOST:5432/DATABASE?sslmode=require`

## Deployment
See `VERCEL-DEPLOY.md`.

## Authentication
Sign Up has no OTP. Email addresses must end with:
`@bscse.uiu.ac.bd`
