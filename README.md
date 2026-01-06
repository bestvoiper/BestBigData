<div align="center">

# 📞 DetectNUM v2.0

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

**Sistema de Consulta Telefónica con Arquitectura MVC**

Busca números telefónicos en múltiples bases de datos CDR con gestión de usuarios, saldos y búsqueda masiva.

[🚀 Instalación](#-instalación) •
[📖 Documentación](#-estructura-mvc) •
[🔗 API](#-api) •
[⚙️ Configuración](#️-configuración)

</div>

---

## ✨ Características

| Característica | Descripción |
|:---:|---|
| 🔐 | **Sistema de Usuarios** - Roles de Admin y Cliente con permisos diferenciados |
| 💰 | **Gestión de Saldos** - Cobro automático por resultado encontrado |
| 🗄️ | **Multi-Base de Datos** - Conexión simultánea a múltiples servidores CDR |
| 📁 | **Búsqueda Masiva** - Sube archivos CSV/TXT con millones de números |
| 📊 | **Historial Completo** - Registro de búsquedas y transacciones |
| 🔌 | **API REST** - Endpoints para integración externa |
| 🎨 | **Interfaz Moderna** - Bootstrap 5 con diseño responsivo |

---

## 🏗️ Estructura MVC

```
DetectNUM/
│
├── 📁 app/
│   ├── 📁 config/
│   │   └── config.php              # Configuración principal
│   │
│   ├── 📁 controllers/
│   │   ├── AdminController.php     # Panel de administración
│   │   ├── AuthController.php      # Login/Logout
│   │   ├── ClientController.php    # Panel de cliente
│   │   └── HomeController.php      # Página principal
│   │
│   ├── 📁 core/
│   │   ├── App.php                 # Router principal
│   │   ├── Controller.php          # Controlador base
│   │   ├── Model.php               # Modelo base
│   │   └── Session.php             # Manejo de sesiones
│   │
│   ├── 📁 models/
│   │   ├── Conexion.php            # 🔌 Conexiones centralizadas
│   │   ├── Search.php              # Modelo de búsquedas
│   │   ├── Setting.php             # Modelo de configuración
│   │   ├── Transaction.php         # Modelo de transacciones
│   │   └── User.php                # Modelo de usuarios
│   │
│   └── 📁 views/
│       ├── 📁 admin/               # Vistas del admin
│       ├── 📁 auth/                # Vistas de autenticación
│       ├── 📁 client/              # Vistas del cliente
│       ├── 📁 partials/            # Componentes reutilizables
│       └── helpers.php             # Funciones helper
│
├── 📁 assets/
│   ├── 📁 css/
│   │   ├── main.css                # Estilos principales
│   │   ├── auth.css                # Estilos de login
│   │   ├── admin.css               # Estilos del admin
│   │   └── client.css              # Estilos del cliente
│   └── 📁 js/
│       └── main.js                 # JavaScript principal
│
├── 📁 database/
│   ├── schema.sql                  # Estructura de BD
│   └── sample_data.sql             # Datos de ejemplo
│
├── .htaccess                       # Reescritura URLs
├── index.php                       # Punto de entrada
└── diagnostico.php                 # Verificar conexiones
```

---

## 🚀 Instalación

### Requisitos

| Requisito | Versión |
|-----------|---------|
| PHP | 7.4+ |
| MySQL | 5.7+ |
| Apache | mod_rewrite habilitado |
| Extensiones PHP | PDO, pdo_mysql |

### Pasos

#### 1️⃣ Clonar el repositorio

```bash
git clone https://github.com/tu-usuario/DetectNUM.git
cd DetectNUM
```

#### 2️⃣ Crear la base de datos

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/sample_data.sql  # Opcional
```

#### 3️⃣ Configurar conexiones

Edita `app/config/config.php`:

```php
// Base de datos principal
define('DB_HOST', 'localhost');
define('DB_NAME', 'detectnum');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_password');
```

Edita `app/models/Conexion.php` para las bases CDR:

```php
private static function getCdrConfig()
{
    return [
        'sw1' => [
            'host' => 'servidor1.example.com',
            'name' => 'vos3000',
            'user' => 'usuario',
            'pass' => 'password',
            'prefix' => 'e_cdr_'
        ],
        // Agregar más servidores...
    ];
}
```

#### 4️⃣ Acceder al sistema

```
http://localhost/DetectNUM/
```

**Credenciales de prueba:**

| Rol | Email | Password |
|-----|-------|----------|
| 👑 Admin | admin@detectnum.com | admin123 |
| 👤 Cliente | cliente@detectnum.com | cliente123 |

---

## 🔗 URLs del Sistema

### Rutas Públicas

| Ruta | Descripción |
|------|-------------|
| `/` | Página principal |
| `/auth/login` | Iniciar sesión |
| `/auth/logout` | Cerrar sesión |
| `/diagnostico.php` | Verificar conexiones |

### Panel Administrador 👑

| Ruta | Descripción |
|------|-------------|
| `/admin` | Dashboard principal |
| `/admin/users` | Gestión de usuarios |
| `/admin/transactions` | Historial de transacciones |
| `/admin/searches` | Historial de búsquedas |
| `/admin/settings` | Configuración del sistema |

### Panel Cliente 👤

| Ruta | Descripción |
|------|-------------|
| `/client` | Dashboard del cliente |
| `/client/search` | Buscar número individual |
| `/client/bulkSearch` | 📁 Búsqueda masiva (CSV/TXT) |
| `/client/history` | Historial de búsquedas |
| `/client/profile` | Perfil y transacciones |

---

## 📁 Búsqueda Masiva

Sube archivos **CSV** o **TXT** con múltiples números de teléfono.

### Formatos soportados

**CSV:**
```csv
5551234567,5559876543
5551111111,5552222222
```

**TXT:**
```
5551234567
5559876543
5551111111
```

### Límites

| Parámetro | Valor |
|-----------|-------|
| Tamaño máximo | 100 MB |
| Números por archivo | Sin límite |
| Mínimo dígitos | 7 |

---

## 🔌 API

### Buscar Número

```http
GET /api/search.php?phone=5551234567&start_date=2025-01-01&end_date=2025-01-31
```

**Respuesta exitosa:**
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
GET /api/search.php?phone=5551234567&preview=1
```

### Info Usuario

```http
GET /api/user.php
```

---

## ⚙️ Configuración

| Parámetro | Descripción | Default |
|-----------|-------------|:-------:|
| `cost_per_result` | Costo por resultado encontrado | $1.00 |
| `min_balance_alert` | Saldo mínimo para alerta | $10.00 |
| `max_results_per_search` | Límite de resultados | 1000 |
| `search_date_range_days` | Rango máximo de días | 365 |

---

## 🔒 Seguridad

- ✅ Contraseñas hasheadas con **bcrypt**
- ✅ Sesiones con timeout automático
- ✅ Validación de inputs
- ✅ Protección contra **SQL Injection** con PDO
- ✅ Control de acceso por roles
- ✅ Cookies seguras (httponly, secure)

---

## 📊 Estructura de Tablas CDR

Las tablas CDR siguen la nomenclatura `e_cdr_YYYYMMDD`:

```sql
CREATE TABLE e_cdr_20250106 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calldate DATETIME NOT NULL,
    callere164 VARCHAR(50),      -- Número origen
    calleee164 VARCHAR(50),      -- Número destino
    callduration INT DEFAULT 0,
    disposition VARCHAR(50),
    INDEX idx_caller (callere164),
    INDEX idx_callee (calleee164)
);
```

---

<div align="center">

## 📞 Soporte

¿Encontraste un bug? ¿Tienes una sugerencia?

[Abrir Issue](../../issues) • [Pull Request](../../pulls)

---

**DetectNUM** © 2025-2026 | Sistema de Consulta Telefónica

Desarrollado con ❤️ usando PHP + Bootstrap

</div>
