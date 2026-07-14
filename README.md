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

## Puesta en marcha

```bash
composer install
pnpm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
pnpm run build      # o `pnpm run dev` en desarrollo
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
