# Docker Laravel + Astro Starter

A Docker-based starter project for building a **headless Laravel backend with Breeze authentication** and an **Astro frontend**. This repository follows the conventions of official Astro starters while providing a production-ready authentication foundation suitable for dashboards and protected APIs.

---

## ✨ Features

- Docker Compose–based local development
- Headless Laravel backend
- Authentication scaffolding via Laravel Breeze
- Astro frontend scaffolded like official Astro starters
- Login / register flow ready to extend
- Foundation for authenticated dashboards and APIs

---

## 🧱 Tech Stack

- **Backend:** Laravel
- **Authentication:** Laravel Breeze
- **Frontend:** Astro
- **Containers:** Docker + Docker Compose
- **Database:** Defined via Docker Compose

---

## 📁 Project Structure

```text
.
├── backend/              # Laravel backend (API + auth)
├── frontend/             # Astro frontend
├── docker-compose.yml
├── .env.example
└── README.md
```

---

## ✅ Prerequisites

- Docker Desktop (or Docker Engine + Docker Compose)
- Git

---

## 🚀 Getting Started

### 1. Clone the repository

```bash
git clone https://github.com/KenEucker/docker-laravel-astro.git
cd docker-laravel-astro
```

### 2. Copy environment files

```bash
cp .env.example .env
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
```

Update environment variables as needed (database credentials, API URLs, etc.).

---

### 3. Start Docker containers

```bash
docker compose up -d
```

This will start all services defined in `docker-compose.yml`, including:

- Laravel backend
- Astro frontend
- Database service

---

## 🌐 Local URLs

Exact ports may vary depending on your Docker configuration.

| Service | URL |
|--------|-----|
| Astro Frontend | http://localhost:3000 |
| Laravel Backend | http://localhost:8000 |

---

## 🔐 Authentication

This starter uses **Laravel Breeze** to provide:

- User registration
- Login and logout
- Session handling
- Protected routes

The backend is designed to be **headless**, exposing authentication and protected API endpoints that the Astro frontend consumes.

You may extend this setup to use Laravel Sanctum, JWT, or OAuth depending on your project needs.

---

## 📦 Building for Production

### Astro

```bash
docker compose exec frontend npm run build
```

### Laravel

Build and deploy the backend container using your preferred hosting platform.

---

## 🤝 Contributing

Contributions are welcome. Suggested areas for improvement:

- Enhanced authentication strategies
- Dashboard UI templates
- Additional API endpoints
- CI/CD pipelines

---

## 📄 License

Licensed under the **AGPL-3.0** license. See the `LICENSE` file for more information.
