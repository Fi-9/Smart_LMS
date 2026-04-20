# Docker Deployment

This project can be deployed with Docker using PHP 8.3, which is also the runtime required for Laravel 13.

## Services

- `app`: Apache + PHP 8.3 application container
- `queue`: queue worker for `ai-scan` and `default`
- `scheduler`: runs `php artisan schedule:run` every minute

## First deploy

```bash
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

## Useful commands

```bash
docker compose -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.prod.yml logs -f queue
docker compose -f docker-compose.prod.yml exec app php artisan about
docker compose -f docker-compose.prod.yml exec app php artisan test
```

## Notes

- Put your production values in `.env` before starting the stack.
- The stack persists `storage/` and `bootstrap/cache/` via named volumes.
- Build assets are compiled during image build with Vite.
- This image targets PHP 8.3 so it can be used for the Laravel 13 upgrade path.
