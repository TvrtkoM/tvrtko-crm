# Tvrtko CRM - simple CRM system with Laravel

## Running application locally

1. Clone the repository

```
git clone https://github.com/TvrtkoM/tvrtko-crm.git
cd tvrtko-crm
```

2. Install dependencies

```
composer install && npm ci
```

3. Set up environment

```
cp .env.example .env
php artisan key:generate
```

4. Create local SQLite database

```
touch database/database.sqlite
php artisan migrate:fresh --seed
```

5. Run the application

```
composer run dev
```

## Entity relationship

Following diagram shows relationship beetween application entities.

                          ┌───────────┐
                          │  COMPANY  │
                          └──┬─────┬──┘
                           1 │     │ 1
          company_id (opt)   │     │   company_id (req)
          nullOnDelete       │     │   cascadeOnDelete
                           N │     │ N
                  ┌──────────┘     └──────────┐
                  ▼                           ▼
           ┌───────────┐   1        N   ┌───────────┐
           │  CONTACT  │───────────────▶│   DEAL    │
           └───────────┘  contact_id    └─────┬─────┘
                          (opt,               │ 1
                          nullOnDelete)       │ deal_id (req)
                          "primary contact"   │ cascadeOnDelete
                                            N │
                                              ▼
                                        ┌───────────┐
                                        │   OFFER   │
                                        └─────┬─────┘
                                              │ 1
                                              │ offer_id (req)
                                              │ cascadeOnDelete
                                            N │
                                              ▼
                                        ┌────────────┐
                                        │ OFFER_ITEM │
                                        └────────────┘
