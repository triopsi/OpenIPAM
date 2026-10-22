# API tests

See the [development guide](docs/development.md) for the current test setup. API regression tests are in `tests/Feature/Api/V1`; run them with:

```sh
docker compose exec app php artisan test tests/Feature/Api/V1
```
