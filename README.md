# NavSpace — Panel de Administración

Backend y panel de administración de **NavSpace**, un sistema de navegación
interior en realidad mixta para el edificio DICI (Departamento de Ingeniería en
Computación e Informática) de la Universidad de Tarapacá, Arica, Chile.

Proyecto de título de Eduardo Apata y Fabián Orellana, guiado por el profesor
Raúl Herrera Acuña.

## Qué es este repositorio

NavSpace se compone de **dos repositorios**:

- **App del visor (repo Unity aparte):** corre en Meta Quest 3. Calibra el
  espacio físico con anclas espaciales, calcula rutas con A* y dibuja una línea
  de navegación que guía al usuario hasta su destino dentro del edificio real.
- **Este repositorio (monolito Laravel):** el backend y el panel de
  administración. Es la fuente de verdad de edificios, nodos de navegación y
  destinos. Expone una API REST que consume la app de Unity y ofrece un editor
  visual 3D para posicionar los nodos sobre el modelo del edificio.

NavSpace es un CMS genérico de espacios navegables: el panel permite registrar
cualquier espacio (edificio, casa, etc.) con su modelo 3D y sus nodos, y la app
de Unity tiene un selector que carga dinámicamente el espacio elegido.

## Responsabilidades

1. **CRUD** de edificios y, anidado en cada edificio, sus tipos de espacio,
   destinos, grupos de nodos y nodos, desde un panel web (Inertia + Vue).
2. **API REST** pública consumida por la app de Unity.
3. **Editor visual 3D** (Three.js) para colocar nodos de navegación haciendo
   clic sobre el modelo `.glb` del edificio.

## Stack

- **PHP 8.4** · **Laravel 13**
- **Inertia v3** + **Vue 3** + **TypeScript**
- **Tailwind CSS v4**
- **Laravel Fortify** (autenticación)
- **Three.js** (editor de nodos)
- **Pest** (tests) · **Larastan** · **Pint**
- Empaquetado con **Vite** y **pnpm**

## Modelo de datos

- **Building** — el espacio navegable (nombre, dirección, modelo `.glb`,
  corrección de orientación `model_yaw`, configuración de pathfinding).
- **SpaceType** — categoría de espacio del edificio (Salas, Oficinas, ...).
- **NodeGroup** — agrupación opcional de nodos (etiquetas organizativas, p. ej.
  "Piso 1").
- **Node** — posición XYZ de un nodo de navegación. Uno por edificio se marca
  como inicial (`is_initial`): es el punto de calibración con el que Unity
  alinea el modelo al espacio real.
- **Destination** — un lugar al que navegar. Su horario se resuelve por `type`:
  `academic` muestra una imagen de horario; `attention` muestra horario en
  texto más el responsable (nombre, contacto y foto).

Las conexiones entre nodos **no** se almacenan: Unity construye el grafo A* en
tiempo de ejecución a partir de las posiciones y el modelo.

## API para Unity

Endpoints públicos (sin autenticación) en `routes/api.php`:

- `GET /api/buildings` — lista de espacios para el selector.
- `GET /api/buildings/{id}` — espacio con modelo, nodos, destinos y config A*.
- `GET /api/destinations/{id}` — detalle del destino mostrado al llegar.

## Puesta en marcha con Docker (recomendado)

Es la vía más rápida: no requiere instalar PHP, Composer, Node ni pnpm en el
equipo, solo [Docker Desktop](https://www.docker.com/products/docker-desktop/).

```bash
git clone <url-del-repositorio>
cd navspace-panel
cp .env.example .env
docker compose up --build
```

El archivo `.env` es obligatorio y concentra toda la configuración; cada variable
viene explicada dentro de `.env.example`. Se edita antes de levantar, sobre todo
`APP_URL`. Si falta, Docker se detiene con un mensaje claro en lugar de arrancar
a medias.

Al terminar, el panel queda disponible en **http://localhost:8000** con estas
credenciales de acceso:

| Correo             | Contraseña |
| ------------------ | ---------- |
| `test@example.com` | `password` |

El primer arranque es el más lento porque compila las dependencias y los assets.
De forma automática crea el archivo `.env`, genera la clave de la aplicación,
crea la base de datos SQLite, ejecuta las migraciones y carga los datos de
ejemplo del DICI.

**Comandos útiles**

```bash
docker compose up -d          # levantar en segundo plano
docker compose logs -f        # ver los logs
docker compose down           # detener
docker compose down -v        # detener y borrar la base de datos y los modelos
```

### Configuración

Todo se define en el `.env`. Estas son las variables que hay que revisar antes de
desplegar:

| Variable          | Por defecto             | Para qué sirve                                             |
| ----------------- | ----------------------- | ---------------------------------------------------------- |
| `APP_URL`         | `http://localhost:8000` | Dirección desde la que el visor descarga los modelos.       |
| `APP_PORT`        | `8000`                  | Puerto por el que se publica el panel.                      |
| `APP_NAME`        | `NavSpace`              | Nombre que se muestra en el panel y en los correos.         |
| `DB_SEED`         | `true`                  | Carga los datos de ejemplo del DICI en el primer inicio.    |
| `MAIL_MAILER`     | `log`                   | `smtp` para enviar de verdad, `log` para solo registrarlos. |
| `MAIL_HOST`       | `smtp.gmail.com`        | Servidor de correo saliente.                                |
| `MAIL_PORT`       | `587`                   | Puerto del servidor de correo.                              |
| `MAIL_SCHEME`     | `tls`                   | Cifrado de la conexión con ese servidor.                    |
| `MAIL_USERNAME`   | vacío                   | Cuenta que envía los correos.                               |
| `MAIL_PASSWORD`   | vacío                   | Su contraseña. En Gmail, una contraseña de aplicación.      |
| `MAIL_FROM_ADDRESS` | `no-reply@navspace.cl` | Remitente que ve quien recibe el correo.                 |
| `MAIL_FROM_NAME`  | `NavSpace`              | Nombre que acompaña a ese remitente.                        |

El resto son ajustes del framework que funcionan tal como vienen:

| Variable                   | Por defecto  | Para qué sirve                                             |
| -------------------------- | ------------ | ---------------------------------------------------------- |
| `APP_KEY`                  | vacío        | Cifra sesiones y secretos de doble factor. Se genera sola.  |
| `APP_ENV`                  | `production` | Entorno de ejecución.                                       |
| `APP_DEBUG`                | `false`      | En producción no deben mostrarse los detalles de error.     |
| `APP_LOCALE`               | `es`         | Idioma de los mensajes del sistema.                         |
| `APP_FALLBACK_LOCALE`      | `es`         | Idioma de respaldo si falta una traducción.                 |
| `APP_FAKER_LOCALE`         | `es_ES`      | Idioma de los datos de prueba.                              |
| `APP_MAINTENANCE_DRIVER`   | `file`       | Cómo se activa el modo mantenimiento.                       |
| `BCRYPT_ROUNDS`            | `12`         | Coste del cifrado de contraseñas.                           |
| `DB_CONNECTION`            | `sqlite`     | Motor de base de datos.                                     |
| `LOG_CHANNEL` / `LOG_STACK`| `stack` / `single` | Dónde se escriben los registros.                      |
| `LOG_LEVEL`                | `error`      | Detalle de lo que se registra.                              |
| `LOG_DEPRECATIONS_CHANNEL` | `null`       | Canal para avisos de funciones obsoletas.                   |
| `SESSION_DRIVER`           | `database`   | Dónde se guardan las sesiones.                              |
| `SESSION_LIFETIME`         | `120`        | Minutos que dura la sesión sin actividad.                   |
| `SESSION_ENCRYPT`          | `false`      | Cifrado adicional de la sesión.                             |
| `SESSION_PATH`             | `/`          | Ruta para la que vale la cookie de sesión.                  |
| `SESSION_DOMAIN`           | `null`       | Dominio de esa cookie.                                      |
| `CACHE_STORE`              | `database`   | Almacén de la caché.                                        |
| `QUEUE_CONNECTION`         | `database`   | Almacén de los trabajos en cola.                            |
| `BROADCAST_CONNECTION`     | `log`        | Canal de difusión de eventos.                               |
| `FILESYSTEM_DISK`          | `local`      | Disco por defecto para los archivos.                        |
| `VITE_APP_NAME`            | `${APP_NAME}`| Nombre que usa la interfaz compilada.                       |

> **`APP_URL` es la que más se equivoca.** No necesita un dominio: sirve la IP del
> servidor, por ejemplo `http://192.168.1.50:9000`. Lo que importa es que el Meta
> Quest pueda alcanzar esa dirección, porque la API entrega en `model_url` e
> `image_url` rutas absolutas construidas con ella. Si queda en `localhost`, el
> visor la buscará en sí mismo y no encontrará los modelos.
>
> Al cambiar `APP_PORT` hay que **poner ese mismo puerto en `APP_URL`**, o el
> visor pedirá los modelos al puerto equivocado.

### Correo

La recuperación de contraseña y la verificación de cuenta necesitan un servidor
SMTP, que se define con las variables `MAIL_*` de `.env.docker`. Sirve cualquiera:
Gmail, el del hosting o uno en la nube como Brevo, Resend o Mailgun.

La plantilla trae el ejemplo de Gmail listo para completar con la cuenta y su
contraseña. En ese caso la contraseña no es la de la cuenta, sino una
**contraseña de aplicación** generada desde la configuración de seguridad de
Google, con la verificación en dos pasos activada.

Mientras no se configuren, los mensajes se escriben en el log en lugar de
enviarse, así que el panel funciona pero la recuperación de contraseña no llega
al destinatario.

### Límites de recursos

El contenedor viene acotado para que no pueda agotar los recursos del servidor:

| Recurso   | Límite            | Evita                                    |
| --------- | ----------------- | ---------------------------------------- |
| Memoria   | 1 GB              | Que una fuga consuma la RAM del servidor. |
| CPU       | 2 núcleos         | Que desplace a los demás servicios.       |
| Procesos  | 256               | Procesos descontrolados.                  |
| Registros | 10 MB × 3 archivos | Que los logs llenen el disco.            |

Se ajustan en `docker-compose.yml` según el servidor donde se despliegue.

### Almacenamiento

Docker crea automáticamente dos volúmenes al levantar; no hay que provisionar
nada a mano.

| Volumen             | Contiene                                    |
| ------------------- | ------------------------------------------- |
| `navspace-database` | La base de datos SQLite.                    |
| `navspace-storage`  | Modelos `.glb`, imágenes y horarios subidos. |

Los datos sobreviven a reinicios y a `docker compose down`. Solo se borran con
`docker compose down -v`.

**Espacio necesario:** la imagen ocupa cerca de 1 GB. Los datos crecen según lo
que se suba: cada modelo `.glb` admite hasta 50 MB y cada imagen hasta 5 MB.

**Limitar el espacio en un servidor compartido:** los volúmenes de Docker no
admiten cuota, así que por defecto crecen hasta ocupar el espacio libre del
servidor. En un servidor con otros servicios conviene alojarlos en un sistema de
archivos de tamaño fijo. Para eso existe el override
`docker-compose.storage.yml`, que trae las instrucciones paso a paso:

```bash
STORAGE_PATH=/mnt/navspace \
    docker compose -f docker-compose.yml -f docker-compose.storage.yml up -d
```

Así, si los datos llenan ese disco solo fallan las subidas del panel, sin
afectar al resto del servidor. Sin el override todo funciona igual, usando
volúmenes normales.

**Respaldo:**

```bash
docker run --rm -v navspace-panel_navspace-database:/data -v ${PWD}:/backup \
    alpine tar czf /backup/navspace-db.tar.gz -C /data .

docker run --rm -v navspace-panel_navspace-storage:/data -v ${PWD}:/backup \
    alpine tar czf /backup/navspace-storage.tar.gz -C /data .
```

## Puesta en marcha manual

Requiere **PHP 8.4**, **Composer**, **Node 22** y **pnpm** instalados.

```bash
composer install
pnpm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
pnpm run build      # o `pnpm run dev` en desarrollo
php artisan serve
```

Para desarrollo con recarga en caliente: `composer run dev`.

## Estructura

```
app/
  Http/Controllers/       CRUD del panel (Building, SpaceType, Destination, Node...)
  Http/Controllers/Api/   API pública consumida por Unity
  Http/Resources/         Transformadores JSON de la API
  Models/                 Entidades del dominio
  Support/GlbInspector    Detección de orientación del modelo .glb
database/
  migrations/  factories/  seeders/   Esquema, datos de prueba y semilla DICI
resources/js/
  pages/buildings/        Páginas del panel (índice, creación, edición, nodos)
  components/buildings/    Secciones y editor 3D de nodos
  components/data-table/   Tabla genérica reutilizable
routes/
  web.php   Panel   ·   api.php   API   ·   settings.php   Cuenta
tests/Feature/            Tests de dominio, panel y API (Pest)
```

## Tests

```bash
php artisan test --compact
```
