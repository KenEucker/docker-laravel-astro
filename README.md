# Docker Laravel + Astro Starter

A Docker-based starter project for building a **headless Laravel backend with Breeze authentication** and an **Astro frontend**. This repository follows the conventions of official Astro starters while providing a production-ready authentication foundation suitable for dashboards and protected APIs.

[![License: AGPL-3.0](https://img.shields.io/badge/License-AGPL%203.0-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)

---

## 📖 Table of Contents

- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Project Structure](#-project-structure)
- [Prerequisites](#-prerequisites)
- [Getting Started](#-getting-started)
- [Environment Configuration](#-environment-configuration)
- [Local URLs](#-local-urls)
- [Authentication](#-authentication)
- [Development Workflow](#-development-workflow)
- [Database Management](#-database-management)
- [Testing](#-testing)
- [Building for Production](#-building-for-production)
- [Troubleshooting](#-troubleshooting)
- [Contributing](#-contributing)
- [License](#-license)

---

## ✨ Features

- **Docker Compose–based** local development with hot reload
- **Headless Laravel backend** with RESTful API architecture
- **Laravel Breeze + Sanctum** authentication for SPA
- **Astro 4.x frontend** with TailwindCSS 4.x
- **MySQL 8.0** database with health checks
- Login / register / logout flow ready to extend
- **Cookie-based session authentication** between frontend and backend
- Foundation for authenticated dashboards and protected APIs
- **Dark mode** support out of the box
- Persistent volumes for database and Laravel app state
- CORS and session domain configuration included

---

## 🧱 Tech Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| **Backend** | Laravel | Latest |
| **Authentication** | Laravel Breeze + Sanctum | - |
| **Frontend** | Astro | 4.16+ |
| **Styling** | TailwindCSS | 4.1+ |
| **Database** | MySQL | 8.0 |
| **Containers** | Docker + Docker Compose | v2+ |
| **Web Server** | PHP Built-in (dev) | - |

---

## 📁 Project Structure

```text
docker-laravel-astro/
├── backend/
│   ├── app/              # Laravel application code (mounted as overrides)
│   ├── database/         # Migrations, seeders, factories
│   ├── routes/           # API routes
│   ├── config/           # Configuration overrides
│   ├── Dockerfile        # Laravel container image
│   └── entrypoint.sh     # Container startup script
├── frontend/
│   ├── src/
│   │   ├── pages/        # Astro pages (routes)
│   │   ├── components/   # Reusable UI components
│   │   ├── layouts/      # Page layouts
│   │   └── lib/          # Utilities (API client, auth helpers)
│   ├── public/           # Static assets
│   ├── Dockerfile        # Astro container image
│   └── package.json      # Node dependencies
├── docker-compose.yaml   # Service orchestration
├── .env.example          # Example environment variables
├── .gitignore
├── LICENSE
└── README.md
```

---

## ✅ Prerequisites

Before you begin, ensure you have the following installed:

- **Docker Desktop** (or Docker Engine + Docker Compose v2+)
  - [Install Docker Desktop](https://www.docker.com/products/docker-desktop)
- **Git**
  - [Install Git](https://git-scm.com/downloads)

**System Requirements:**
- 4GB RAM minimum (8GB recommended)
- 10GB free disk space

---

## 🚀 Getting Started

### 1. Clone the repository

```bash
git clone https://github.com/KenEucker/docker-laravel-astro.git
cd docker-laravel-astro
```

### 2. Set up environment files

Copy the example environment files:

```bash
cp .env.example .env
```

**Important:** Edit `.env` to customize your setup. At minimum, review:
- Database credentials (`DB_USERNAME`, `DB_PASSWORD`)
- Default admin user credentials (`DEFAULT_USER_EMAIL`, `DEFAULT_USER_PASSWORD`)
- Session domain settings (`SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN`)

### 3. Start the application

Build and start all services:

```bash
docker compose up -d
```

This will:
1. Build the Laravel and Astro Docker images
2. Start MySQL database with health checks
3. Run Laravel migrations and seeders
4. Start the Laravel API server (port 8000)
5. Start the Astro dev server with hot reload (port 3000)

**First-time setup takes 2-5 minutes** while images are built and dependencies are installed.

### 4. Verify the installation

Check that all services are running:

```bash
docker compose ps
```

All services should show status as "Up" or "healthy".

### 5. Access the application

Open your browser and navigate to:
- **Frontend:** http://localhost:3000
- **Backend API:** http://localhost:8000

---

## ⚙️ Environment Configuration

### Root `.env` File

The main `.env` file controls Docker Compose and is shared between services:

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_ENV` | `local` | Application environment |
| `APP_DEBUG` | `true` | Enable debug mode |
| `APP_URL` | `http://localhost:8000` | Backend URL |
| `PUBLIC_API_URL` | `http://localhost:8000` | API URL for browser |
| `DB_HOST` | `db` | Database host (Docker service name) |
| `DB_PORT` | `3306` | Database port (internal) |
| `DB_DATABASE` | `app` | Database name |
| `DB_USERNAME` | `app` | Database user |
| `DB_PASSWORD` | `secret` | Database password |
| `SANCTUM_STATEFUL_DOMAINS` | `localhost:3000` | Allowed frontend domains |
| `SESSION_DOMAIN` | `localhost` | Cookie session domain |
| `SEED_DEFAULT_USER` | `true` | Create default admin on first run |
| `DEFAULT_USER_EMAIL` | `admin@example.com` | Default admin email |
| `DEFAULT_USER_PASSWORD` | `change-me` | Default admin password |

### Docker Compose Port Mapping

You can change the exposed ports by setting these in `.env`:

```bash
DB_HOST_PORT=3307          # MySQL external port (default: 3307)
# Frontend runs on 3000
# Backend runs on 8000
```

---

## 🌐 Local URLs

| Service | URL | Notes |
|---------|-----|-------|
| **Astro Frontend** | http://localhost:3000 | Hot reload enabled |
| **Laravel API** | http://localhost:8000 | RESTful API endpoints |
| **MySQL Database** | `localhost:3307` | Connect via MySQL client |

### Default Login Credentials

If `SEED_DEFAULT_USER=true` in `.env`:
- **Email:** `admin@example.com` (or value of `DEFAULT_USER_EMAIL`)
- **Password:** `change-me` (or value of `DEFAULT_USER_PASSWORD`)

**⚠️ Change these credentials in production!**

---

## 🔐 Authentication

This starter uses **Laravel Breeze** with **Laravel Sanctum** for SPA authentication.

### Authentication Flow

1. **Frontend** sends login request to `/login` endpoint
2. **Backend** validates credentials and creates session
3. **Session cookie** is returned to frontend (httpOnly, secure in prod)
4. **Subsequent requests** include session cookie automatically
5. **Protected routes** verify session via Sanctum middleware

### Key Features

- ✅ Cookie-based session authentication
- ✅ CSRF protection
- ✅ User registration and email validation
- ✅ Password reset functionality
- ✅ Remember me option
- ✅ Logout and session invalidation

### API Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/register` | Create new user | No |
| POST | `/login` | Authenticate user | No |
| POST | `/logout` | End session | Yes |
| GET | `/api/user` | Get authenticated user | Yes |

### Extending Authentication

The setup is ready to be extended with:
- OAuth providers (Google, GitHub, etc.)
- Two-factor authentication (2FA)
- API token authentication
- Role-based access control (RBAC)

---

## 🛠️ Development Workflow

### View Logs

View logs from all services:
```bash
docker compose logs -f
```

View logs from a specific service:
```bash
docker compose logs -f laravel
docker compose logs -f astro
docker compose logs -f db
```

### Execute Commands in Containers

**Laravel (Artisan commands):**
```bash
docker compose exec laravel php artisan migrate
docker compose exec laravel php artisan make:model Post -m
docker compose exec laravel php artisan tinker
```

**Frontend (NPM commands):**
```bash
docker compose exec astro npm install <package>
docker compose exec astro npm run build
```

### Restart Services

Restart all services:
```bash
docker compose restart
```

Restart a specific service:
```bash
docker compose restart laravel
```

### Stop and Remove Containers

Stop all services:
```bash
docker compose down
```

Stop and remove volumes (⚠️ deletes database data):
```bash
docker compose down -v
```

### Hot Reload

Both frontend and backend support hot reload:
- **Astro:** File changes in `frontend/src/` trigger automatic reload
- **Laravel:** Mounted volumes allow code changes without rebuild
  - PHP changes are reflected immediately
  - Config/route changes may require cache clear

### Clear Laravel Cache

```bash
docker compose exec laravel php artisan config:clear
docker compose exec laravel php artisan cache:clear
docker compose exec laravel php artisan route:clear
docker compose exec laravel php artisan view:clear
```

---

## 🗄️ Database Management

### Run Migrations

Apply database migrations:
```bash
docker compose exec laravel php artisan migrate
```

Rollback last migration:
```bash
docker compose exec laravel php artisan migrate:rollback
```

Fresh migration (⚠️ drops all tables):
```bash
docker compose exec laravel php artisan migrate:fresh
```

### Seed Database

Run seeders:
```bash
docker compose exec laravel php artisan db:seed
```

Run specific seeder:
```bash
docker compose exec laravel php artisan db:seed --class=UserSeeder
```

Fresh migration with seeding:
```bash
docker compose exec laravel php artisan migrate:fresh --seed
```

### Access MySQL Console

```bash
docker compose exec db mysql -uapp -psecret app
```

Or connect from your host machine:
```bash
mysql -h127.0.0.1 -P3307 -uapp -psecret app
```

### Backup Database

```bash
docker compose exec db mysqldump -uapp -psecret app > backup.sql
```

### Restore Database

```bash
docker compose exec -T db mysql -uapp -psecret app < backup.sql
```

---

## 🧪 Testing

### Run Laravel Tests

```bash
docker compose exec laravel php artisan test
```

With coverage:
```bash
docker compose exec laravel php artisan test --coverage
```

### Run Frontend Tests

(If you add testing to Astro)
```bash
docker compose exec astro npm test
```

---

## 📦 Building for Production

### Build Astro for Production

```bash
docker compose exec astro npm run build
```

The built files will be in `frontend/dist/`.

### Optimize Laravel for Production

```bash
docker compose exec laravel php artisan config:cache
docker compose exec laravel php artisan route:cache
docker compose exec laravel php artisan view:cache
docker compose exec laravel php artisan optimize
```

### Environment Variables for Production

Update `.env` for production:
```bash
APP_ENV=production
APP_DEBUG=false
SESSION_DRIVER=database  # or redis
SESSION_SECURE_COOKIE=true
```

### Deployment Checklist

- [ ] Change default admin credentials
- [ ] Set strong database password
- [ ] Configure proper `SESSION_DOMAIN` and `SANCTUM_STATEFUL_DOMAINS`
- [ ] Enable HTTPS
- [ ] Set `APP_DEBUG=false`
- [ ] Use production database (not localhost MySQL)
- [ ] Configure mail service for password resets
- [ ] Set up log monitoring
- [ ] Configure backup strategy
- [ ] Review CORS settings

---

## 🐛 Troubleshooting

### Containers won't start

**Check logs:**
```bash
docker compose logs
```

**Rebuild containers:**
```bash
docker compose down
docker compose build --no-cache
docker compose up -d
```

### Database connection errors

**Verify database is healthy:**
```bash
docker compose ps
```

**Check database credentials** in `.env` match `docker-compose.yaml`.

**Wait for database to be ready:**
The Laravel container waits for the database health check, but you can manually check:
```bash
docker compose exec db mysqladmin ping -h localhost -uapp -psecret
```

### Authentication not working

**Check session domain configuration:**
Ensure `SANCTUM_STATEFUL_DOMAINS` includes your frontend domain (e.g., `localhost:3000`).

**Clear browser cookies** and try again.

**Check CORS settings** in `backend/config/cors.php` (if present).

### Port already in use

**Change ports** in `docker-compose.yaml` or set in `.env`:
```yaml
ports:
  - "3001:3000"  # Change host port
```

### Frontend can't reach backend

**Check `PUBLIC_API_URL`** in `.env` matches your backend URL.

**Verify backend is running:**
```bash
curl http://localhost:8000/api/user
```

### Hot reload not working

**Restart the Astro container:**
```bash
docker compose restart astro
```

**Check file permissions** (on Linux/Mac):
```bash
sudo chown -R $USER:$USER frontend/
```

### Laravel storage permissions

```bash
docker compose exec laravel chmod -R 775 storage bootstrap/cache
```

---

## 🤝 Contributing

Contributions are welcome! Here are some ways you can help:

### Areas for Improvement

- Enhanced authentication strategies (OAuth, 2FA)
- Dashboard UI templates and components
- Additional API endpoints and resources
- CI/CD pipeline examples
- Docker production optimizations
- Testing examples (PHPUnit, Playwright)
- Admin panel functionality
- API documentation (OpenAPI/Swagger)

### How to Contribute

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Commit your changes: `git commit -m 'Add amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

### Development Guidelines

- Follow PSR-12 coding standards for PHP
- Use ESLint/Prettier for JavaScript/TypeScript
- Write tests for new features
- Update documentation for significant changes
- Keep commits focused and well-described

---

## 📄 License

This project is licensed under the **AGPL-3.0** license. See the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- [Laravel](https://laravel.com/) - The PHP Framework for Web Artisans
- [Astro](https://astro.build/) - The web framework for content-driven websites
- [TailwindCSS](https://tailwindcss.com/) - A utility-first CSS framework

---

**Built with ❤️ by [KenEucker](https://github.com/KenEucker)**

For questions or support, please [open an issue](https://github.com/KenEucker/docker-laravel-astro/issues).
