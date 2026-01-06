# DetectNUM v2.0

Sistema de Consulta Telefónica con arquitectura MVC.

## 🏗️ Estructura MVC

```
DetectNUM/
├── app/
│   ├── config/
│   │   └── config.php          # Configuración principal
│   ├── controllers/
│   │   ├── AdminController.php # Panel de administración
│   │   ├── AuthController.php  # Login/Logout
│   │   ├── ClientController.php # Panel de cliente
│   │   └── HomeController.php  # Página principal
│   ├── core/
│   │   ├── App.php            # Router principal
│   │   ├── Controller.php     # Controlador base
│   │   ├── Model.php          # Modelo base
│   │   └── Session.php        # Manejo de sesiones
│   ├── models/
│   │   ├── Search.php         # Modelo de búsquedas
│   │   ├── Setting.php        # Modelo de configuración
│   │   ├── Transaction.php    # Modelo de transacciones
│   │   └── User.php           # Modelo de usuarios
│   └── views/
│       ├── admin/             # Vistas del admin
│       ├── auth/              # Vistas de autenticación
│       ├── client/            # Vistas del cliente
│       ├── layouts/           # Layouts principales
│       ├── partials/          # Componentes reutilizables
│       └── helpers.php        # Funciones helper
├── assets/
│   ├── css/
│   │   ├── main.css           # Estilos principales
│   │   ├── auth.css           # Estilos de login
│   │   ├── admin.css          # Estilos del admin
│   │   └── client.css         # Estilos del cliente
│   └── js/
│       └── main.js            # JavaScript principal
├── database/
│   ├── schema.sql             # Estructura de BD
│   └── sample_data.sql        # Datos de ejemplo
├── .htaccess                  # Reescritura URLs
└── index.php                  # Punto de entrada
```

## 🚀 Instalación

1. **Clonar o copiar** el proyecto en tu servidor web

2. **Crear la base de datos** ejecutando los scripts SQL

3. **Configurar** en `app/config/config.php`

4. **Acceder** a `http://localhost/DetectNUM/`

## 🔗 URLs del Sistema

| URL | Descripción |
|-----|-------------|
| `/auth/login` | Iniciar sesión |
| `/admin` | Panel de administrador |
| `/admin/users` | Gestión de usuarios |
| `/client` | Panel de cliente |
| `/client/search` | Buscar número |

---

# DetectNUM (Versión Original) - Sistema de Consulta Telefónica

Sistema de búsqueda de números telefónicos en múltiples bases de datos CDR con gestión de usuarios y saldos.

## Características

- **Sistema de Usuarios**: Admin y Clientes con diferentes permisos
- **Gestión de Saldos**: Los clientes tienen saldo que se descuenta por cada búsqueda
- **Múltiples Bases de Datos**: Conexión a 4 bases de datos CDR simultáneamente
- **Búsqueda Avanzada**: Busca en columnas `callere164` y `calleee164`
- **Historial Completo**: Registro de todas las búsquedas y transacciones
- **API REST**: Endpoints para integración con otros sistemas

## Requisitos

- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx con mod_rewrite
- Extensión PDO habilitada

## Instalación

### 1. Configurar Base de Datos Principal

```bash
# Importar el esquema
mysql -u root -p < database/schema.sql

# (Opcional) Importar datos de prueba CDR
mysql -u root -p < database/sample_data.sql
```

### 2. Configurar Conexiones

Edita el archivo `config/database.php`:

```php
// Base de datos principal
define('DB_HOST', 'localhost');
define('DB_NAME', 'detectnum');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_password');

// Bases de datos CDR
$cdr_databases = [
    'cdr_db1' => [
        'host' => 'servidor1',
        'name' => 'cdr_database_1',
        'user' => 'usuario',
        'pass' => 'password',
        'prefix' => 'e_cdr_'
    ],
    // ... configurar las 4 bases de datos
];
```

### 3. Acceder al Sistema

- URL: `http://localhost/DetectNUM/`
- Admin: `admin@detectnum.com` / `admin123`
- Cliente: `cliente@detectnum.com` / `cliente123`

## Estructura del Proyecto

```
DetectNUM/
├── admin/                  # Panel de administración
│   ├── dashboard.php
│   ├── users.php          # Gestión de usuarios
│   ├── transactions.php   # Historial de transacciones
│   ├── searches.php       # Historial de búsquedas
│   └── settings.php       # Configuración del sistema
├── cliente/               # Panel de clientes
│   ├── dashboard.php
│   ├── search.php         # Búsqueda de números
│   ├── history.php        # Historial personal
│   └── profile.php        # Perfil del usuario
├── api/                   # API REST
│   ├── search.php         # Endpoint de búsqueda
│   └── user.php           # Info del usuario
├── config/
│   ├── database.php       # Configuración de BD
│   └── session.php        # Configuración de sesiones
├── includes/
│   ├── functions.php      # Funciones generales
│   ├── header.php         # Header HTML común
│   └── footer.php         # Footer HTML común
├── database/
│   ├── schema.sql         # Esquema de la BD
│   └── sample_data.sql    # Datos de prueba
├── index.php              # Redirección inicial
├── login.php              # Página de login
├── logout.php             # Cerrar sesión
└── README.md
```

## Uso del Sistema

### Panel de Administrador

1. **Dashboard**: Vista general con estadísticas
2. **Usuarios**: Crear, editar, eliminar usuarios y cargar saldos
3. **Transacciones**: Ver todos los movimientos de saldo
4. **Búsquedas**: Historial de todas las consultas
5. **Configuración**: Ajustar costo por resultado, límites, etc.

### Panel de Cliente

1. **Dashboard**: Saldo actual y búsquedas recientes
2. **Buscar Número**: Realizar consultas en las bases CDR
3. **Historial**: Ver búsquedas anteriores
4. **Perfil**: Actualizar datos y ver movimientos

## Estructura de Tablas CDR

Las tablas CDR deben seguir la nomenclatura `e_cdr_YYYYMMDD`:

```sql
CREATE TABLE e_cdr_20250121 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calldate DATETIME NOT NULL,
    callere164 VARCHAR(50),      -- Número origen
    calleee164 VARCHAR(50),      -- Número destino
    duration INT DEFAULT 0,
    disposition VARCHAR(50),
    INDEX idx_caller (callere164),
    INDEX idx_callee (calleee164)
);
```

## API

### Buscar Número

```http
GET /DetectNUM/api/search.php?phone=5551234567&start_date=2025-01-01&end_date=2025-01-31
```

**Respuesta:**
```json
{
    "success": true,
    "phone": "5551234567",
    "results": [...],
    "total_results": 15,
    "cost": 15.00,
    "balance_after": 85.00
}
```

### Preview (sin cobro)

```http
GET /DetectNUM/api/search.php?phone=5551234567&preview=1
```

### Info Usuario

```http
GET /DetectNUM/api/user.php
```

## Configuración

| Parámetro | Descripción | Default |
|-----------|-------------|---------|
| `cost_per_result` | Costo en pesos por resultado | 1.00 |
| `min_balance_alert` | Saldo mínimo para alerta | 10.00 |
| `max_results_per_search` | Límite de resultados | 1000 |
| `search_date_range_days` | Rango máximo de días | 365 |

## Seguridad

- Contraseñas hasheadas con bcrypt
- Sesiones con timeout automático
- Validación de inputs en todas las entradas
- Protección contra SQL Injection con PDO
- Control de acceso por roles

## Soporte

Para reportar bugs o solicitar nuevas características, contacta al administrador del sistema.

---

DetectNUM © 2025 - Sistema de Consulta Telefónica
#   B e s t B i g D a t a  
 #   B e s t B i g D a t a  
 