# laravel-vue-docker-starter

A reusable starter template for **Laravel 12 + Vue 3 (Vite)** running in **Docker**, designed for coding challenges and small full-stack projects.

The main goal of this repository is to allow anyone to clone the project and have a fully working Laravel + Vue development environment running with **a single command**.



## Stack Overview

- **Backend:** Laravel 12 (PHP 8.5)
- **Frontend:** Vue 3 + Vite
- **Database:** MySQL (Docker)
- **Cache / Sessions:** Redis
- **Containerization:** Docker Compose (Laravel Sail stack)
- **Task Runner:** Makefile



## Requirements

You need the following tools installed **on your machine**:

- **Docker** (Docker Desktop recommended)
- **Docker Compose v2**
- **GNU Make**
- A terminal (bash, zsh, PowerShell, etc.)

### Installing `make` on Windows

If `make` is not available, the easiest way is using **Scoop**:

```powershell
scoop install make
```

## Installation & Quick Start

Clone the repository
```
git clone <repository-url>
cd laravel-vue-docker-starter
```

Enviroment variables
```
cp .env.example .env
```


### Local environment
Available Make Commands

This repository exposes a simple and consistent Makefile interface

Start everything (build + frontend)
```
make up
```

Stop all containers
```
make down
```

View container logs
```
make logs
```

Open a shell inside the Laravel container
```
make shell
```

Run database migrations
```
make migrate
```

Run any Artisan command
```
make artisan cmd="route:list"
```

Install frontend dependencies manually (if needed)
```
make npm-install
```

Start only the Vite dev server
```
make dev
```

Build frontend assets (production-like)
```
make build
```

Open the application
```
http://localhost
```