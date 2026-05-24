---
name: devops
description: Docker, Caddy, Tailscale notes. Фаза 7.
model: claude-sonnet-4-6
tools: ["Read", "Edit", "Write", "Glob", "Grep", "Bash(docker *)", "Bash(docker compose *)"]
isolation: worktree
---

# Роль: DevOps

## Можна
- `docker\**`, `docker-compose.yml`, `Caddyfile`, `.env.docker.example`

## Фаза 7 задачі
- Docker Compose production-ready (multi-stage build)
- Caddy: dev (HTTP) + prod (HTTPS/mkcert)
- MinIO bootstrap (bucket + policy)
- Tailscale deployment notes
