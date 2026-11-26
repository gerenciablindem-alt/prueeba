# Aportes Simple 2.0

Aplicación web para gestionar afiliaciones a seguridad social (EPS, ARL, CCF y AFP), traslados e incapacidades de las empresas cliente.

Hecha en Laravel 13 (PHP 8.3) con vistas Blade, sin paso de compilación de front-end.

## Módulos

- **Panel**: radicados por estado y entidad, incapacidades por cobrar y lo que requiere atención hoy.
- **Alertas**: radicados devueltos o estancados, incapacidades sin radicar o rechazadas, incapacidades que se acercan al día 180, traslados sin movimiento y empresas con datos faltantes.
- **FUA / Pre-afiliación**: asistente de 5 pasos (tipo, empresa, afiliado, afiliación, confirmar). Crea un radicado `PA-AAAA-NNNNNN` por cada entidad. Si el documento ya existe, completa los datos y muestra sus afiliaciones.
- **Radicados**: filtros por estado, entidad y empresa, días en el estado actual, cambio de estado con motivo obligatorio al devolver o rechazar, documentos e historial.
- **Consulta de afiliados**, **Traslados** con seguimiento, **Reportes** con estadísticas y descarga CSV para Excel.
- **Incapacidades**: registro, prórrogas con días acumulados, cálculo de lo que paga el empleador y lo que se recobra, seguimiento del cobro (radicada, aprobada, pagada, rechazada) y soportes.
- **Ventas y cobro**: planes para independientes (EPS, AFP, ARL, CCF), tarifario anual por nivel de riesgo calculado desde el salario mínimo, ventas, cuotas mensuales, recibos de caja imprimibles con consecutivo por sede, registro de pagos, anulaciones y retiros. Los porcentajes están en `config/aportes.php`.
- **Asistente de IA** (Claude, de Anthropic): responde preguntas sobre los datos de la app con herramientas de solo lectura que respetan los permisos de cada usuario. Se activa pegando una clave de API en Configuración (se guarda cifrada) o con `ANTHROPIC_API_KEY` en `.env`. Límite diario en `APORTES_IA_LIMITE_DIARIO` (150 por defecto).
- **Búsqueda global** con `Ctrl K`: personas, radicados, incapacidades, empresas y ventas, más accesos rápidos y preguntas a la IA. Modo claro y oscuro.
- **Calculadora de incapacidades**.
- **Sedes y empresas**: NIT, CIIU, cargos con nivel de riesgo ARL y entidades habituales.
- **Usuarios y roles**: administrador, operador y empresa cliente (solo ve lo de su empresa).
- **Auditoría**: cada creación, cambio y eliminación con el valor anterior y el nuevo.
- **Configuración**: salario mínimo usado en los cálculos y catálogo de entidades.

> Las reglas de cálculo de incapacidades están en `app/Services/CalculadoraIncapacidad.php` y deben validarse con el equipo antes de usarlas para cobrar.

## Instalación local

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed            # crea entidades, una sede y el administrador
php artisan serve
```

El administrador inicial se define con `ADMIN_EMAIL`, `ADMIN_NAME` y `ADMIN_PASSWORD` en `.env`. Si no hay contraseña, se genera una y se muestra en la consola.

Para probar con datos ficticios:

```bash
php artisan migrate:fresh --seeder=DemoSeeder
# demo@example.com / demo12345 (administrador) · empresa@example.com / demo12345 (empresa cliente)
```

## Producción

1. Base de datos MySQL o PostgreSQL: configura `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
2. `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` con https.
3. Correo (`MAIL_*`) para el enlace de "¿Olvidaste tu contraseña?".
4. `composer install --no-dev --optimize-autoloader && php artisan migrate --force --seed && php artisan config:cache && php artisan route:cache && php artisan view:cache`.
5. Los documentos se guardan en `storage/app/private` (no quedan públicos). Haz copia de seguridad de esa carpeta y de la base de datos.

## Pruebas

```bash
php artisan test
```

## Publicar en Hostinger (plan Business)

1. **Sitio**: en hPanel crea un sitio vacío (PHP/HTML) para `app.aportecimple.com`.
2. **PHP**: en *Avanzado > Configuración de PHP* elige PHP 8.3 o superior.
3. **Base de datos**: en *Bases de datos > MySQL* crea una base, un usuario y una contraseña.
4. **Código**: en *Avanzado > GIT* conecta `git@github.com:gerenciablindem-alt/odigos.git` (rama `main`) con la carpeta vacía `public_html`. Copia la llave SSH que muestra hPanel y agrégala en GitHub, en *Settings > Deploy keys* del repositorio (solo lectura).
5. **Instalar**: activa *Avanzado > Acceso SSH*, conéctate y corre:
   ```bash
   cd ~/domains/app.aportecimple.com/public_html
   bash scripts/instalar-hostinger.sh
   ```
   El script pide los datos de la base y del administrador, instala dependencias, crea las tablas y deja la app lista.

El archivo `.htaccess` de la raíz envía todo a `public/`, así que `.env` y el código no quedan accesibles desde internet.

Para **actualizar** después de cambios: en hPanel *GIT > Implementar*, y luego por SSH `bash scripts/instalar-hostinger.sh` (solo corre migraciones y limpia cachés).
