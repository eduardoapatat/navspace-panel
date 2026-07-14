<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/vue3 (INERTIA_VUE) - v3
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `pnpm run build`, `pnpm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `pnpm run build` or ask the user to run `pnpm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>

# NavSpace — Project Context

Everything above this line is generated and maintained by Laravel Boost. Do not
hand-edit it; running `php artisan boost:install` may regenerate it. Everything below
is the NavSpace domain context. If Boost ever overwrites this file, paste this section
back in.

## What this project is

NavSpace is a mixed reality indoor navigation system for the DICI building
(Departamento de Ingeniería en Computación e Informática) at Universidad de Tarapacá,
Arica, Chile. It is a thesis project by Eduardo and Fabián Orellana, advised by
professor Raúl Herrera Acuña.

The full system spans two separate codebases:

- **Headset app (separate Unity repo)**: runs on Meta Quest 3. It calibrates the
  physical space with spatial anchors, computes routes with the A* Pathfinding
  Project, and draws a navigation line that guides the user to a destination inside
  the real building.
- **This repository (Laravel monolith)**: the backend and admin panel. It is the
  source of truth for buildings, floors, space types, destinations, and navigation
  nodes. It exposes a REST API consumed by the Unity app, and hosts a 3D visual editor
  for placing navigation nodes on the building model.

## Responsibilities of this repository

1. CRUD of Building, Floor, SpaceType, Destination, Node, Person, and Schedule from a
   web panel (Inertia + Vue pages). Floor/SpaceType/Destination/Schedule are managed
   nested inside the Building detail (Show) page, not as standalone menu modules.
2. REST API consumed by the Unity app, mainly to fetch destination info.
3. A Three.js visual editor, embedded as a Vue component, where the admin loads the
   building `.glb` model and places navigation nodes by clicking on the model.

## Data model

Seven entities. Field names below are the agreed names for this project.

### Building

- `id`
- `name`
- `address`
- `model_url` (nullable) — URL of the building `.glb`

### Floor

- `id`
- `building_id` (FK)
- `floor_number`
- `name`

### SpaceType

- `id`
- `building_id` (FK)
- `name` — e.g. Salas, Oficinas, Otros
- `icon`

### Destination

- `id`
- `space_type_id` (FK)
- `floor_id` (FK)
- `node_id` (FK, nullable)
- `display_name`
- `description`

Note: the old `schedule` and `responsible` string fields were removed and replaced
by the `Schedule` and `Person` entities below (a destination `hasMany` schedules and
`hasOne` responsible).

### Node

- `id`
- `floor_id` (FK)
- `position_x`
- `position_y`
- `position_z`
- `is_initial` (boolean)

### Person

Responsible people and teachers, a single reusable entity. The same person can be
responsible for an office and also teach in several academic rooms.

- `id`
- `name`
- `email` (nullable)
- `phone` (nullable)
- `office_destination_id` (FK → destinations, nullable) — the office they run.
  Nullable because a teacher who only lectures has no office.

### Schedule

A schedule block of a destination. Several blocks make a full schedule; a lunch break
is just the gap between two blocks. `days` is a JSON array so one row covers several
days when the time range is the same.

- `id`
- `destination_id` (FK) — the schedule always hangs off the destination (uniform API).
- `type` — enum `ScheduleType` { Attention, Academic }. A fixed system value, NOT
  creatable, independent from SpaceType (which is free/modular). The admin picks it
  from a fixed select; do not derive behavior from the SpaceType name.
- `days` — JSON array, e.g. `["mon","wed","fri"]`
- `start_time`, `end_time`
- `person_id` (FK → people, nullable) — attendant (attention) or teacher (academic)
- `subject` (nullable), `semester` (nullable, free text like `2025-1`) — academic only

The three cases: office = Attention + person (responsible); helper room = Attention +
no person (open hours); academic room = Academic + teacher + subject + semester.

### Relationships

```
Building    hasMany       Floor
Building    hasMany       SpaceType
Building    hasManyThrough Destination (via Floor)
Floor       hasMany       Node
Floor       hasMany       Destination
SpaceType   hasMany       Destination
Destination belongsTo     Floor
Destination belongsTo     SpaceType
Destination belongsTo     Node        (nullable)
Destination hasMany       Schedule
Destination hasOne        Person       (responsible, via office_destination_id)
Node        belongsTo     Floor
Schedule    belongsTo     Destination
Schedule    belongsTo     Person       (nullable)
Person      belongsTo     Destination  (office, nullable, via office_destination_id)
Person      hasMany       Schedule
```

## Architecture decisions (do not change without asking)

- **There is no NodeConnection table.** Edges between nodes are NOT stored in the
  database. The Unity app builds the graph at runtime and A* computes connections by
  raycasting against the walls of the `.glb` model. The web only stores each node's
  position. Do not add an edge entity or a persisted graph.

- **`is_initial` defines the calibration node.** The node flagged `is_initial` is the
  anchor point: in the app, the building model is positioned so that node matches the
  user's feet during calibration. This replaces the old manual `buildingOffset`. There
  should be only one active `is_initial` node per building; validate this at the
  application level.

- **SpaceType belongs to Building, not Floor.** A space type like "Salas" exists for
  the whole building. Floor 1 has salas and floor 2 has salas, and it is the same
  SpaceType. That is why it hangs off Building.

- **`Destination.node_id` is intentionally nullable.** In the current MVP the nodes
  are hardcoded inside Unity and the web editor is not in use yet, so destinations can
  exist without an associated node. Once the Three.js editor is operational the node
  gets assigned. Do not make it required.

- **`Schedule.type` is a fixed enum, not derived from SpaceType.** SpaceType is
  admin-created and free (its name can be anything), so it must NOT drive system
  behavior. Whether a schedule is `attention` or `academic` is a fixed system rule the
  admin picks from a select, so the app and Unity always know how to read it. Keep them
  independent.

- **Schedule hangs off Destination, never off Person.** Even for offices (where the
  hours are "the person's"), the schedule belongs to the destination and points to the
  person via `person_id`. This keeps the Unity API uniform: always "give me the
  schedules of this destination".

## Coordinate system (verify, this is delicate)

The Three.js editor captures XYZ coordinates by clicking on the same `.glb` that Unity
imports. The intent is that those coordinates match on both sides because they share
the file's scale and origin. Keep in mind Three.js uses a right-handed coordinate
system and Unity a left-handed one, so loading the same `.glb` may flip the sign of
one axis (typically Z or X). This must be tested by placing a known node and checking
it appears in the same spot in the app before treating the conversion as correct. The
Y component represents floor level.

## API contract for Unity

Public, unauthenticated endpoints in `routes/api.php`, consumed by the Unity app.
All are already implemented (multi-space, see the multi-space vision below).

### Implemented

- `GET /api/buildings` — list of navigable spaces for the Unity space selector
  (`id`, `name`, `address`, `model_url`). `BuildingResource`.
- `GET /api/buildings/{id}` — a space with its `model_url`, `floors`,
  `destinations` and `pathfinding_config` (the A* Point Graph parameters the
  Unity app uses: `max_distance`, `thick_radius`, `check_walls`; defaults to
  `{max_distance:5, thick_radius:0, check_walls:true}` when unset). Saved from
  the 3D editor via `PUT /buildings/{building}/pathfinding`.
  `BuildingDetailResource`.
- `GET /api/destinations/{id}` — destination detail shown on arrival:
  `display_name`, `description`, `space_type`, `responsible` (Person object or null),
  `schedules` (array of blocks: `type`, `days`, `start_time`, `end_time`, `subject`,
  `semester`, `person`), and `node` (position, only when set). `DestinationResource`.

Note: NavSpace is a generic multi-space CMS, not DICI-only. The panel is where admins
register any navigable space (building, apartment, house...) with its model and nodes,
and Unity has a selector that dynamically loads the chosen one. That is why the
buildings list/detail endpoints are first-class, not "future".

### Future (once the node editor is ready)

- Endpoints for the app to download the building `.glb` and the floor's node JSON, and
  build the Point Graph dynamically. Do not build this yet unless explicitly asked.

## Three.js editor (later phase)

A Vue component that:

1. Loads the building `.glb` into a Three.js scene.
2. Uses `Raycaster` to capture the exact XYZ position where the admin clicks on the
   model.
3. Places a visual node (sphere) at that position.
4. Allows moving nodes with `TransformControls`.
5. Saves positions against the Node API endpoint.

The first click on the main entrance defines the `is_initial` node.

## Suggested work order

1. Migrations, models, factories, and seeders for the five entities.
2. DICI seed: one Building, its real floors, the SpaceTypes (Salas, Oficinas), and the
   Destinations that already exist in Unity (Licancabur, Socompa, etc.).
3. Panel CRUD (Inertia + Vue) for Building, Floor, SpaceType, and Destination.
4. `GET /api/destinations/{id}` endpoint with an API Resource.
5. Three.js editor for Node (isolated phase, the most complex part).
6. Unity integration so it consumes the real API.

## Code convention

Class and method names in English, with a brief one-line comment above each method
when it adds clarity. The rest of the conventions (PHP 8.4, explicit return types,
Pest, Pint) are in the Boost guidelines above.
