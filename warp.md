# Warp Guide for This Project

This document explains how to work with this Laravel API + Vue project using Warp.

## Prerequisites

- PHP and Composer installed
- Node.js and npm or yarn installed
- A local web server stack (e.g. XAMPP) or equivalent
- Git for version control

These are standard project requirements and do not change the application runtime.

## Common Tasks

### 1. Install PHP dependencies

```bash path=null start=null
composer install
```

### 2. Install frontend dependencies

```bash path=null start=null
npm install
# or
yarn install
```

### 3. Environment setup

1. Copy the example environment file:

```bash path=null start=null
cp .env.example .env
```

2. Generate the application key:

```bash path=null start=null
php artisan key:generate
```

### 4. Run database migrations

```bash path=null start=null
php artisan migrate
```

### 5. Start development servers

Backend (Laravel):

```bash path=null start=null
php artisan serve
```

Frontend (Vue):

```bash path=null start=null
npm run dev
# or
yarn dev
```

## Using Warp Effectively

- Use the integrated terminal to run the commands above.
- Use multiple panes or tabs to keep backend and frontend servers running in parallel.
- When asking the AI agent for help, reference specific files (e.g. `routes/api.php`, `resources/js` components) so it can assist precisely.

## Notes

- This file is documentation-only and does not affect the build or runtime behavior of the project.
- Update this document as your workflows or commands change.
