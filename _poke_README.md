# Pokemon Info Service

Serwis HTTP (REST) umożliwiający pobieranie informacji o Pokemonach z PokeAPI (https://pokeapi.co/) oraz rozszerzony o dodatkowe funkcje:

- zarządzanie listą zakazanych Pokemonów (/api/banned) - chronione nagłówkiem X-SUPER-SECRET-KEY,
- pobieranie informacji o wielu Pokemonach naraz (/api/info) z pominięciem zakazanych,
- zarządzanie własnymi (niestandardowymi) Pokemonami (/api/custom-pokemons) - CRUD,
- cacheowanie odpowiedzi PokeAPI do najbliższej godziny 12:00 UTC+1.

## Wymagania środowiskowe

- PHP 8.2+
- Composer
- MySQL / PostgreSQL
- Redis (zalecany do cache)
- Laravel 10+
- Opcjonalnie: Docker + Docker Compose (zalecane)


## Instalacja i uruchomienie

Opcja A - lokalnie

Pobierz repozytorium:

```bash
git clone <repo>
cd repo
```

Zainstaluj zależności:

```bash
composer install
```

Skopiuj konfigurację:

```bash
cp .env.example .env
php artisan key:generate
```

W pliku .env ustaw:

```bash
DB_* wartości
CACHE_DRIVER=redis (lub database)
SUPER_SECRET_KEY=twojsekretnyklucz
POKEAPI_BASE_URL=https://pokeapi.co/api/v2
```

Uruchom migracje:

```bash
php artisan migrate
```

Start aplikacji:

```bash
php artisan serve
```

Aplikacja dostępna pod: http://127.0.0.1:8000

Opcja B - Docker

```bash
docker compose up -d
docker exec -it laravel_app php artisan migrate
```

## Autoryzacja

Wybrane endpointy wymagają nagłówka:

```bash
X-SUPER-SECRET-KEY: <wartość z .env>
```

Chronione ścieżki:
- /api/banned/*
- /api/custom-pokemons/*

Brak lub błędna wartość → 401 Unauthorized.

## Dokumentacja API (Swagger/OpenAPI)

Dostępna pod adresem:

```bash
/api/docs
```

Plik OpenAPI znajduje się w:

```bash
public/docs/openapi.yaml
```

SwaggerUI jest wczytywany automatycznie i pokazuje wszystkie kontrakty API.

## Endpointy API

1. Rejestr Zakazanych Pokemonów (/api/banned) (chronione nagłówkiem)

**GET /api/banned**

Zwraca listę zakazanych Pokemonów.

Response 200

```bash
[
  {"id":1,"name":"mewtwo","created_at":"...","updated_at":"..."}
]
```

**POST /api/banned**

Body:

```bash
{"name":"mewtwo"}
```

Response 201 - created

Zwraca utworzony obiekt.

**DELETE /api/banned/{name}**

Response 204 No Content

2. Pobieranie informacji — /api/info

**POST /api/info**

Body:

```bash
{"names": ["pikachu", "mewtwo", "my-custom"]}
```

Response 200:

```bash
{
  "requested": ["pikachu", "mewtwo", "my-custom"],
  "banned": ["mewtwo"],
  "results": [
    {"name": "pikachu", "source": "official", "data": { ... }, "found": true},
    {"name": "my-custom", "source": "custom", "data": { ... }}
  ]
}
```

3. Własne Pokemony (/api/custom-pokemons) (chronione nagłówkiem)

**GET /api/custom-pokemons**

Zwraca listę własnych Pokemonów.

**POST /api/custom-pokemons**

Body:
```bash
{
  "name": "my-mon",
  "data": {"level": 10, "abilities": ["fly"]},
  "created_by": "admin",
  "notes": "fan-made"
}
```

Walidacja:

- nazwa musi być unikatowa
- nie może duplikować nazwy z PokeAPI

Cache silnika PokeAPI

- Cache oparty na CACHE_DRIVER (Redis / database / file)
- Każdy Pokemon ma TTL ustawiony tak, aby wygasł:

przy najbliższym 12:00 (UTC+1)

Dzięki temu odświeżanie PokeAPI raz dziennie powoduje automatyczne wygasanie danych.

## Cache

Cache jest przechowywany w driverze wskazanym w CACHE_DRIVER (zalecany Redis). Dla oficjalnych pokemonów TTL ustawione jest tak, żeby klucz wygasł przy najbliższym 12:00 (UTC+1). Dzięki temu codzienna aktualizacja PokeAPI powoduje odświeżenie po czasie.

### Testy i dalsze kroki

- Dodać testy integracyjne (Feature tests) dla endpointów.  
- Obsługa limitów rate-limit PokeAPI (retry/backoff).  
- Ulepszyć paginację i filtrowanie /banned i /custom-pokemons.  

PokeApiService - komunikacja z PokeAPI + cache
Repositories - logika dla custom Pokemonów
Middleware - weryfikacja X-SUPER-SECRET-KEY

### Uwagi implementacyjne / wskazówki

- Wszystkie nazwy pokemonów są normalizowane do lowercase (łatwiej porównywać).  
- TTL cache jest wyliczane tak, by wygasło przy następnym 12:00 w strefie UTC+1 (ogarnięte w PokeApiService::ttlSeconds).  
- Użyłem Http::get() z Laravel. Nie wrappery PokeAPI (zgodnie z wymaganiem).  
- Można zmienić CACHE_DRIVER w .env na database jeśli nie chcemy Redis - wówczas wykonać php artisan cache:table i migracje.  
- W celu lepszej produkcyjnej jakości: dodać rate-limiting, retry/backoff, logowanie odpowiedzi PokeAPI w wypadku błędów.  

## Licencja

Projekt udostępniony na licencji MIT.  
