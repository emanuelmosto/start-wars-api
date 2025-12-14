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
cd start-wars-api
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

Additional Commands:


```
make logs                       -> View container logs

make shell                      -> Open a shell inside the Laravel container

make migrate                    -> Run database migrations

make artisan cmd="route:list"   -> Run any Artisan command

make npm-install                -> Install frontend dependencies manually (if needed)

make dev                        -> Start only the Vite dev server

make build                      -> Build frontend assets (production-like)
```

In order to Run and test this app, just:

## Run the application
```
make up

http://localhost
```
