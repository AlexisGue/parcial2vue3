# parcial2Vue3 — Sistema de Gestión Médica (Parcial II)

Vue 3 + PrimeVue + Pinia + Vue Router + Axios  
Laravel API + Sanctum (tokens) + L5-Swagger

## Arranque local

### Backend
```bash
cd backend
cp .env.example .env   # configura MySQL
php artisan key:generate
php artisan migrate --seed
php artisan l5-swagger:generate
php artisan serve
```

Swagger UI: http://localhost:8000/api/documentation

### Frontend
```bash
cd frontend
# .env
# VITE_API_URL=http://localhost:8000/api
npm install
npm run dev
```

## Endpoints del enunciado
- `POST /api/register`
- `POST /api/login` + `POST /api/login/verify-2fa`
- `POST /api/logout`
- CRUD `/api/pacientes`, `/api/doctores`, `/api/citas`
- `GET /api/reportes/citas-por-estado`
- `GET /api/reportes/citas-por-doctor`
- `POST /api/contacto`
