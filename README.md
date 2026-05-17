# Todo App — Vercel + SQLite / Supabase

A simple todo app you can deploy to Vercel. Backend switches between SQLite and Supabase via config.

## One-click Deploy

[![Deploy to Vercel](https://vercel.com/button)](https://vercel.com/new)

1. Click Deploy
2. Add env vars in Vercel dashboard:
   - `DB_DRIVER` — `sqlite` or `supabase`
   - `SUPABASE_URL` — your Supabase URL (if using Supabase)
   - `SUPABASE_KEY` — your Supabase anon key (if using Supabase)

## Local Dev

```bash
cd vercel-api
cp .env.example .env
php -S localhost:8083 -t .
```

Open `http://localhost:8083`

## API

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/todos?filter=all\|active\|completed` | List todos |
| POST | `/api/todos` | Create `{"title":"..."}` |
| PUT | `/api/todos/:id` | Update `{"title":"...","completed":0\|1}` |
| DELETE | `/api/todos/:id` | Delete |

## Sync Data

```bash
# SQLite → Supabase
php api/sync.php --direction=to-supabase

# Supabase → SQLite
php api/sync.php --direction=to-sqlite
```

## Supabase Setup

Run in Supabase SQL editor:

```sql
CREATE TABLE todos (
  id SERIAL PRIMARY KEY,
  title TEXT NOT NULL,
  completed INTEGER DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);
```
