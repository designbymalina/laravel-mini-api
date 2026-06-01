# Media Expert - Booking API

## Opis

Prosty backendowy system rezerwacji terminów przygotowany w framework Laravel.

System udostępnia REST API umożliwiające:

* pobieranie dostępnych slotów dla wybranego dnia,
* tworzenie rezerwacji,
* anulowanie rezerwacji.

Założenia biznesowe:

* slot trwa 30 minut,
* system działa w strefie czasowej Europe/Warsaw,
* poniedziałek–piątek: 09:00–17:00,
* sobota: 10:00–14:20,
* niedziela: dzień wolny,
* dni wolne są przechowywane w tabeli `holidays`,
* anulowana rezerwacja nie blokuje slotu,
* nie można utworzyć dwóch aktywnych rezerwacji dla tego samego slotu,
* system obsługuje jedną lokalizację.

---

## Wymagania

* PHP 8.3+
* MariaDB / MySQL
* Composer

Rozwiązanie zostało przygotowane na istniejącej instalacji Laravel 11 z PHP 8.2 oraz MariaDB 10.11.  
Kod nie wykorzystuje funkcjonalności specyficznych dla PHP 8.2 i pozostaje kompatybilny z PHP 8.3.

---

## Uruchomienie

Instalacja zależności:

```bash
composer install
```

Konfiguracja środowiska:

```bash
cp .env.example .env
```

Generowanie klucza aplikacji:

```bash
php artisan key:generate
```

Uruchomienie migracji:

```bash
php artisan migrate
```

Uruchomienie aplikacji:

```bash
php artisan serve
```

lub przy użyciu Dockera/Sail zgodnie z konfiguracją środowiska.

---

## Endpointy

### Pobranie dostępnych slotów

```http
GET /api/slots?date=2026-06-08
```

Przykładowa odpowiedź:

```json
[
    {
        "start": "2026-06-08T09:00:00+02:00",
        "end": "2026-06-08T09:30:00+02:00"
    }
]
```

---

### Utworzenie rezerwacji

```http
POST /api/bookings
```

Przykładowe dane:

```json
{
    "customer_name": "Jan Kowalski",
    "customer_email": "jan@example.com",
    "slot_start": "2026-06-08T09:00:00+02:00"
}
```

---

### Anulowanie rezerwacji

```http
DELETE /api/bookings/{booking}
```

---

## Testy

Uruchomienie wszystkich testów:

```bash
php artisan test --env=testing
```

Zaimplementowane testy:

* returns no slots on sunday
* returns no slots on holiday
* returns saturday slots that fit working hours
* creates booking
* cannot create two active bookings for same slot
* cannot book outside working hours
* cancelled booking releases slot

---

## Założenia projektowe

Dostępne sloty są wyliczane dynamicznie na podstawie godzin pracy oraz aktywnych rezerwacji zapisanych w bazie danych.

Nie są przechowywane gotowe sloty w osobnej tabeli.

Takie podejście eliminuje konieczność utrzymywania dużej liczby rekordów i upraszcza zarządzanie kalendarzem.

Dni wolne są przechowywane w tabeli `holidays` i mogą być dodawane przez migracje, seeder lub prosty panel administracyjny.

---

## Odporność na równoległe rezerwacje

System wykorzystuje:

* unikalny indeks bazy danych,
* transakcję podczas tworzenia rezerwacji,
* obsługę błędów naruszenia ograniczeń unikalności.

Dzięki temu nawet przy równoległych żądaniach tylko jedna aktywna rezerwacja może zostać utworzona dla danego slotu.

---

## Indeksy

Tabela `bookings`:

```sql
INDEX(slot_start)
INDEX(status)

UNIQUE(slot_start, status)
```

### Uzasadnienie

`slot_start`

Przyspiesza wyszukiwanie rezerwacji dla konkretnego dnia podczas generowania dostępnych slotów.

`status`

Przyspiesza filtrowanie aktywnych rezerwacji.

`UNIQUE(slot_start, status)`

Zapobiega utworzeniu dwóch aktywnych rezerwacji dla tego samego terminu.

W PostgreSQL zastosowałbym częściowy indeks unikalny:

```sql
UNIQUE(slot_start)
WHERE status = 'active'
```

MariaDB nie wspiera tego mechanizmu, dlatego wykorzystano unikalność `(slot_start, status)`.

---

## Skalowanie i wydajność

Przy setkach tysięcy rezerwacji kluczowe znaczenie ma indeksowanie.

Podczas pobierania dostępnych slotów wykonywane jest pojedyncze zapytanie pobierające wyłącznie aktywne rezerwacje dla konkretnego dnia.

Dzięki indeksowi na `slot_start` liczba rekordów analizowanych przez bazę pozostaje ograniczona niezależnie od całkowitej liczby rezerwacji w systemie.

W kolejnych iteracjach można rozważyć:

* partycjonowanie tabel po dacie,
* cache dla najczęściej odczytywanych dni,
* osobny model lokalizacji,
* mechanizm przesuwania rezerwacji,
* paginowaną listę rezerwacji,
* API administracyjne do zarządzania dniami wolnymi.

---

## Co zmieniłbym w kolejnej iteracji

* pełna obsługa wielu lokalizacji,
* endpoint zmiany terminu rezerwacji,
* dedykowane DTO oraz warstwa API Resources,
* obsługa wyjątków biznesowych przez własne klasy domenowe,
* OpenAPI / Swagger,
* cache dla odczytu dostępności,
* monitoring i metryki aplikacyjne,
* pełna konfiguracja Docker Compose dla środowiska developerskiego.
