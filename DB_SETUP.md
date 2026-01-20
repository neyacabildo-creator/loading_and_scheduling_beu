# Database Setup

This project uses Laravel migrations and seeders to set up the database schema and initial data. You do **not** need a separate SQL dump file; simply run the following commands after configuring your `.env` file:

## 1. Configure your database
- Edit the `.env` file in the project root and set your database credentials:
  ```env
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=your_database_name
  DB_USERNAME=your_username
  DB_PASSWORD=your_password
  ```

## 2. Run migrations
- This will create all necessary tables:
  ```sh
  php artisan migrate
  ```

## 3. Run seeders (optional, for demo data)
- To populate roles, faculty loads, and other demo data:
  ```sh
  php artisan db:seed
  ```

## 4. (Optional) Factories
- You can use factories for generating test users:
  ```sh
  php artisan tinker
  >>> \App\Models\User::factory()->count(10)->create();
  ```

---

**Migrations found:**
- database/migrations/*.php
- database/migrations/*.sql (if present)

**Seeders found:**
- database/seeders/*.php

**Factories found:**
- database/factories/*.php

If you need a raw SQL dump, you can generate one after running migrations using your MySQL tool (e.g., `mysqldump`).
