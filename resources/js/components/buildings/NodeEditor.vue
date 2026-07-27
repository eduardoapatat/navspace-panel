<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    Check,
    Compass,
    Copy,
    Info,
    Pencil,
    Plus,
    RotateCcw,
    SlidersHorizontal,
    Trash2,
    Waypoints,
} from '@lucide/vue';
import CameraControls from 'camera-controls';
import * as THREE from 'three';
import { TransformControls } from 'three/examples/jsm/controls/TransformControls.js';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    shallowRef,
    watch,
} from 'vue';
import NodeGroupController from '@/actions/App/Http/Controllers/NodeGroupController';
import type {
    Building,
    ConnectionOverrideType,
} from '@/components/buildings/building';
import InputError from '@/components/InputError.vue';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Slider } from '@/components/ui/slider';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

// camera-controls needs a subset of THREE injected once.
CameraControls.install({ THREE });

interface Props {
    building: Building;
    modelUrl: string | null;
}

const props = defineProps<Props>();

// A node held locally in the editor before it is saved. New nodes get a
// temporary negative id; saved nodes keep their server id (unused on save
// because the whole set is synced at once).
interface LocalNode {
    localId: number;
    label: string;
    position_x: number;
    position_y: number;
    position_z: number;
    is_initial: boolean;
    // Facing direction (degrees). Only meaningful on the initial node.
    heading: number;
    // Optional group this node belongs to (null = ungrouped).
    node_group_id: number | null;
}

const canvasHost = ref<HTMLDivElement | null>(null);
const loading = ref(true);
const loadError = ref<string | null>(null);
const saving = ref(false);
// Whether there are unsaved changes.
const dirty = ref(false);
// The node currently selected (drives the side panel and gizmo).
const selectedId = ref<number | null>(null);
// The node whose name is being edited inline (via the pencil button).
const editingLabelId = ref<number | null>(null);

// Prevents closing or reloading the editor while local changes are pending.
function handleBeforeUnload(event: BeforeUnloadEvent): void {
    if (!dirty.value) {
        return;
    }

    event.preventDefault();
    event.returnValue = '';
}

// Enter/leave inline name editing for a node.
function startEditingLabel(id: number): void {
    editingLabelId.value = id;
    void nextTick(() => {
        const input = document.getElementById(`node-label-${id}`);
        (input as HTMLInputElement | null)?.focus();
        (input as HTMLInputElement | null)?.select();
    });
}

function stopEditingLabel(): void {
    editingLabelId.value = null;
}

// When a node gets selected (e.g. clicked in the 3D scene), expand its group so
// its card renders and shows the selected highlight. No auto-scroll on purpose:
// with many nodes in a group it was unreliable, and highlighting is enough.
watch(selectedId, (id) => {
    if (id === null) {
        return;
    }

    const node = localNodes.value.find((n) => n.localId === id);

    if (!node) {
        return;
    }

    const key = sectionKeyOfNode(node);

    if (!openSections.value.includes(key)) {
        openSections.value = [...openSections.value, key];
    }
});

// The editable set of nodes. Seeded once from props; never overwritten by
// the server afterwards, so the view (camera, model) never resets.
const localNodes = ref<LocalNode[]>(
    (props.building.nodes ?? []).map((node) => ({
        localId: node.id,
        label: node.label ?? '',
        position_x: node.position_x,
        position_y: node.position_y,
        position_z: node.position_z,
        is_initial: node.is_initial,
        heading: node.heading ?? 0,
        node_group_id: node.node_group_id ?? null,
    })),
);

// Manual exceptions to the automatic linking, kept in localId space like the
// nodes above (an existing node's localId is its database id).
interface LocalOverride {
    a: number;
    b: number;
    type: ConnectionOverrideType;
}

const localOverrides = ref<LocalOverride[]>(
    (props.building.connection_overrides ?? []).map((override) => ({
        a: override.node_a_id,
        b: override.node_b_id,
        type: override.type,
    })),
);

// The second node of a pair, picked with Ctrl+click, so a rule can be created
// between it and the currently selected node.
const pairSelection = ref<number | null>(null);

// Order-independent key for a pair, mirroring how the server normalizes it.
function pairKey(a: number, b: number): string {
    return a < b ? `${a}-${b}` : `${b}-${a}`;
}

const overrideByPair = computed(() => {
    const map = new Map<string, LocalOverride>();

    for (const override of localOverrides.value) {
        map.set(pairKey(override.a, override.b), override);
    }

    return map;
});

// Create or replace the rule for a pair.
function setOverride(a: number, b: number, type: ConnectionOverrideType): void {
    const key = pairKey(a, b);
    const existing = localOverrides.value.find(
        (override) => pairKey(override.a, override.b) === key,
    );

    if (existing) {
        existing.type = type;
    } else {
        localOverrides.value.push({ a, b, type });
    }

    markDirty();
}

function removeOverride(a: number, b: number): void {
    const key = pairKey(a, b);
    localOverrides.value = localOverrides.value.filter(
        (override) => pairKey(override.a, override.b) !== key,
    );
    markDirty();
}

// Clicking an edge cuts it; clicking an already cut one restores it.
function toggleBlockedEdge(a: number, b: number): void {
    if (overrideByPair.value.get(pairKey(a, b))?.type === 'blocked') {
        removeOverride(a, b);
    } else {
        setOverride(a, b, 'blocked');
    }
}

// Display name of a node, matching the list ("Nodo 3" when it has no label).
function nodeName(localId: number): string {
    const node = localNodes.value.find((n) => n.localId === localId);

    return node ? node.label || `Nodo ${nodeNumber(node)}` : `#${localId}`;
}

// The building's node groups, for the per-node group select. Reactive to the
// building prop so newly created groups appear without a full reload.
const nodeGroups = computed(() => props.building.node_groups ?? []);

// The node list split into sections: one per group (in group order) that has
// nodes, plus a trailing "Sin grupo" section for loose nodes. Grouping is not a
// restriction — nodes without a group are perfectly valid and shown last.
const groupedNodes = computed(() => {
    const sections: { id: number | null; name: string; nodes: LocalNode[] }[] =
        [];

    for (const group of nodeGroups.value) {
        const nodes = localNodes.value.filter(
            (n) => n.node_group_id === group.id,
        );

        if (nodes.length) {
            sections.push({ id: group.id, name: group.name, nodes });
        }
    }

    // Loose nodes: no group, or a group that no longer exists.
    const loose = localNodes.value.filter(
        (n) =>
            n.node_group_id === null ||
            !nodeGroups.value.some((g) => g.id === n.node_group_id),
    );

    if (loose.length) {
        sections.push({ id: null, name: 'Sin grupo', nodes: loose });
    }

    return sections;
});

// Accordion item value for a section (group id, or 'none' for loose nodes).
function groupKey(id: number | null): string {
    return id === null ? 'none' : String(id);
}

// Open accordion sections (group keys). Starts empty so all groups are collapsed;
// selecting a node in the 3D scene opens its group (see the selectedId watch).
const openSections = ref<string[]>([]);

// Accordion key of the section a node lives in: its group when that group still
// exists, otherwise the "Sin grupo" bucket. Mirrors the grouping in groupedNodes.
function sectionKeyOfNode(node: LocalNode): string {
    const gid = node.node_group_id;
    const inRealGroup =
        gid !== null && nodeGroups.value.some((g) => g.id === gid);

    return inRealGroup ? String(gid) : 'none';
}

// Group create/edit dialog, managed inline from the accordion so groups are
// handled where their nodes live (no separate CRUD panel). Node-to-group
// assignment still happens on each node card.
const groupDialogOpen = ref(false);
// Id of the group being edited; null means we are creating a new one.
const editingGroupId = ref<number | null>(null);
const groupForm = useForm({ name: '' });

function openCreateGroup(): void {
    editingGroupId.value = null;
    groupForm.reset();
    groupForm.clearErrors();
    groupDialogOpen.value = true;
}

function openEditGroup(id: number, name: string): void {
    editingGroupId.value = id;
    groupForm.clearErrors();
    groupForm.name = name;
    groupDialogOpen.value = true;
}

function submitGroup(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            groupDialogOpen.value = false;
            editingGroupId.value = null;
            groupForm.reset();
        },
    };

    if (editingGroupId.value !== null) {
        groupForm.put(
            NodeGroupController.update(editingGroupId.value).url,
            options,
        );
    } else {
        groupForm.post(
            NodeGroupController.store(props.building.id).url,
            options,
        );
    }
}

function destroyGroup(id: number, name: string): void {
    if (
        !confirm(
            `¿Eliminar el grupo "${name}"? Sus nodos y destinos quedarán sin agrupar.`,
        )
    ) {
        return;
    }

    router.delete(NodeGroupController.destroy(id).url, {
        preserveScroll: true,
    });
}

// Position of each node in the full list, precomputed so nodeNumber() is O(1).
// Without this, rendering a section with many cards would call indexOf() per
// card, turning the render into O(n²).
const nodeIndexById = computed(() => {
    const map = new Map<number, number>();
    localNodes.value.forEach((node, index) => map.set(node.localId, index));

    return map;
});

// Stable display number per node (its position in the full list), used as the
// fallback name when a node has no label — unique regardless of grouping.
function nodeNumber(node: LocalNode): number {
    return (nodeIndexById.value.get(node.localId) ?? 0) + 1;
}

let nextTempId = -1;

// Three.js objects are kept out of Vue's reactivity to avoid proxy overhead.
const scene = shallowRef<THREE.Scene>();
const camera = shallowRef<THREE.PerspectiveCamera>();
const renderer = shallowRef<THREE.WebGLRenderer>();
const orbit = shallowRef<CameraControls>();
const transform = shallowRef<TransformControls>();
const raycaster = new THREE.Raycaster();
const pointer = new THREE.Vector2();
// Frame delta (seconds) for camera-controls easing, timed manually to avoid the
// deprecated THREE.Clock.
let lastFrameTime = performance.now();

// On-demand rendering: the loop only draws while something is changing, instead
// of a constant 60 FPS. requestRender() keeps drawing for a short window so the
// change (and any camera easing that follows) is shown; the loop then idles.
// Camera motion and gizmo dragging are detected in animate() directly. Starts
// with a window so the first frames (model load, initial framing) always draw.
let renderUntil = performance.now() + 1500;
function requestRender(): void {
    renderUntil = performance.now() + 300;
}

// The loaded building model, used as the raycasting target.
let modelRoot: THREE.Object3D | null = null;
// Diagonal size and center of the model, used to frame/reset the view.
let modelSize = 20;
let modelCenter = new THREE.Vector3();
// Maps each node's localId to its sphere mesh in the scene.
const nodeMeshes = new Map<number, THREE.Mesh>();
let animationId = 0;
// Watches the viewport box so the canvas follows its size, including layout
// changes that don't fire a window resize (e.g. the 16:9 box height shifting).
let resizeObserver: ResizeObserver | null = null;
// Distinguishes a click (place a node) from a drag (orbit the camera).
let pointerDownAt = { x: 0, y: 0 };

// The group holding the simulated A* graph edges (preview only, not saved).
let graphGroup: THREE.Group | null = null;
const graphRaycaster = new THREE.Raycaster();
// The drawn edges, kept apart from the labels so clicks can hit only them.
let edgeMeshes: THREE.Mesh[] = [];

// Right-click context menu: position, plus what it targets. On a node it offers
// duplicate/delete; on empty model space it offers create. null = hidden.
const contextMenu = ref<{
    x: number;
    y: number;
    kind: 'node' | 'create';
    nodeId: number | null;
} | null>(null);
let pendingPoint: THREE.Vector3 | null = null;

// The node currently hovered by the cursor (drives the hover highlight).
const hoveredId = ref<number | null>(null);

const NORMAL_COLOR = 0x3b82f6;
const INITIAL_COLOR = 0xf59e0b;
const SELECTED_COLOR = 0x22c55e;
// The other end of a pair, picked with Ctrl+click.
const PAIRED_COLOR = 0xa855f7;

// An eye-shaped cursor (Unity's orbit cursor) as an inline SVG data URI.
const EYE_CURSOR =
    "url(\"data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2'><path d='M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z'/><circle cx='12' cy='12' r='3' fill='white'/></svg>\") 12 12, auto";

// Set the canvas cursor for the current interaction.
function setCursor(kind: 'default' | 'orbit' | 'pan' | 'pointer'): void {
    const canvas = renderer.value?.domElement;

    if (!canvas) {
        return;
    }

    if (kind === 'orbit') {
        canvas.style.cursor = EYE_CURSOR;
    } else if (kind === 'pan') {
        canvas.style.cursor = 'grabbing';
    } else if (kind === 'pointer') {
        canvas.style.cursor = 'pointer';
    } else {
        canvas.style.cursor = 'default';
    }
}

// Point Graph simulation parameters (mirror the A* Pathfinding Project).
const graphParams = ref({
    // Max distance to connect two nodes (0 = unlimited).
    maxDistance: props.building.pathfinding_config?.max_distance ?? 5,
    // Max height (Y) difference to connect two nodes (0 = unlimited). Keeps the
    // graph from linking nodes across floors through a ceiling.
    maxYDistance: props.building.pathfinding_config?.max_y_distance ?? 1,
    // Wall raycast is disabled by default: it caused errors and is not used. Kept
    // in the payload for the API shape, but not editable from the UI.
    checkWalls: props.building.pathfinding_config?.check_walls ?? false,
    // Thick-raycast radius; 0 = thin ray. Not editable from the UI.
    thickRadius: props.building.pathfinding_config?.thick_radius ?? 0,
});
// Result summary after a simulation run.
const graphStats = ref<{ edges: number; isolated: number } | null>(null);
// Single toggle to show/hide the graph preview: both the drawn lines AND the
// stats readout are hidden together when off.
const showGraph = ref(true);

// The initial node (calibration anchor), if any. Its heading is what Unity uses
// to orient the model, and the arrow is drawn on top of it and follows it.
const initialNode = computed<LocalNode | null>(
    () => localNodes.value.find((n) => n.is_initial) ?? null,
);

// Heading (degrees, 0-360) of the initial node, editable from the side panel.
// Reading/writing goes straight to that node so the arrow and Unity stay in sync.
const heading = computed<number>({
    get: () => initialNode.value?.heading ?? 0,
    set: (value: number) => {
        if (initialNode.value) {
            initialNode.value.heading = value;
            // The arrow has its own watcher; the heading changes no edge.
            markUnsaved();
        }
    },
});

// The arrow mesh (group) that visualizes the heading, placed over the initial node.
let headingArrow: THREE.Object3D | null = null;
// Whether the heading arrow is shown in the scene (UI toggle).
const showHeadingArrow = ref(true);

// Live preview is always on: the graph redraws whenever nodes or params change,
// so the user always sees the resulting mesh without extra buttons.
watch(
    () => [
        graphParams.value.maxDistance,
        graphParams.value.maxYDistance,
        graphParams.value.checkWalls,
        graphParams.value.thickRadius,
    ],
    () => {
        if (!scene.value) {
            return;
        }

        // Params changed → unsaved and recompute the preview.
        dirty.value = true;
        scheduleGraph();
    },
);

// Redraw the heading arrow whenever the initial node changes: its identity,
// its position (so the arrow follows it), or its heading angle.
watch(
    () => {
        const n = initialNode.value;

        return n
            ? [n.localId, n.position_x, n.position_y, n.position_z, n.heading]
            : null;
    },
    () => updateHeadingArrow(),
    { deep: true },
);

// Show/hide the arrow when the toggle changes.
watch(showHeadingArrow, () => updateHeadingArrow());

// Redraw/clear the graph preview when its visibility toggle changes.
watch(showGraph, () => computeGraph());

// The three editable coordinate inputs shown in each node card.
const axes = [
    { key: 'position_x', label: 'X' },
    { key: 'position_y', label: 'Y' },
    { key: 'position_z', label: 'Z' },
] as const;

const initialCount = computed(
    () => localNodes.value.filter((n) => n.is_initial).length,
);

function initScene(): void {
    const host = canvasHost.value;

    if (!host) {
        return;
    }

    const width = host.clientWidth;
    const height = host.clientHeight;

    scene.value = new THREE.Scene();
    scene.value.background = new THREE.Color(0x0f172a);

    camera.value = new THREE.PerspectiveCamera(60, width / height, 0.1, 1000);
    camera.value.position.set(8, 8, 8);

    renderer.value = new THREE.WebGLRenderer({ antialias: true });
    // updateStyle=false: don't write inline px sizes on the canvas, so it can
    // shrink back via CSS (w-full/h-full) when leaving fullscreen.
    renderer.value.setSize(width, height, false);
    renderer.value.setPixelRatio(window.devicePixelRatio);
    host.appendChild(renderer.value.domElement);

    // Stop the browser from hijacking Alt+drag / right-click on the canvas,
    // which otherwise shows a "blocked" cursor and steals the gesture. Our own
    // context menu (create node) is handled in onContextMenu.
    const canvas = renderer.value.domElement;
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    canvas.style.display = 'block';
    canvas.style.touchAction = 'none';
    canvas.style.userSelect = 'none';
    canvas.addEventListener('contextmenu', onContextMenu);
    canvas.addEventListener('dragstart', (e) => e.preventDefault());

    // Hemisphere light fills faces from every angle (sky above, ground below)
    // so vertical slab edges are not left pitch black.
    scene.value.add(new THREE.HemisphereLight(0xffffff, 0xb0b8c4, 1.1));
    scene.value.add(new THREE.AmbientLight(0xffffff, 0.4));
    // Two opposing directional lights avoid dark sides where a single light
    // cannot reach (e.g. the underside/edges of floor slabs).
    const dir = new THREE.DirectionalLight(0xffffff, 0.9);
    dir.position.set(5, 10, 7);
    scene.value.add(dir);
    const dirBack = new THREE.DirectionalLight(0xffffff, 0.5);
    dirBack.position.set(-6, 4, -8);
    scene.value.add(dirBack);
    scene.value.add(new THREE.GridHelper(40, 40, 0x334155, 0x1e293b));
    scene.value.add(new THREE.AxesHelper(2));

    orbit.value = new CameraControls(camera.value, renderer.value.domElement);
    // Unity-like feel: smooth, dolly toward cursor, well-scaled speeds.
    orbit.value.dollyToCursor = true;
    orbit.value.dollySpeed = 0.6;
    orbit.value.truckSpeed = 1.4;
    orbit.value.azimuthRotateSpeed = 0.6;
    orbit.value.polarRotateSpeed = 0.6;
    orbit.value.smoothTime = 0.12;
    orbit.value.draggingSmoothTime = 0.06;
    // Default mapping: left free for create/select, middle-drag trucks (pan),
    // wheel dollies (zoom), right orbits. Alt makes left orbit (see key handlers).
    applyDefaultMouseButtons();

    transform.value = new TransformControls(
        camera.value,
        renderer.value.domElement,
    );
    transform.value.addEventListener('dragging-changed', (event) => {
        // Freeze the camera while dragging a node.
        orbit.value!.enabled = !event.value;

        // Sync the reactive state (which re-renders the whole node list) only
        // when the drag ends, not on every frame — that per-frame update was
        // what made dragging feel laggy. The mesh itself moves smoothly because
        // TransformControls drives it directly.
        if (!event.value) {
            onGizmoMove();
        }
    });
    scene.value.add(transform.value.getHelper());

    renderer.value.domElement.addEventListener('pointerdown', onPointerDown);
    renderer.value.domElement.addEventListener('pointerup', onPointerUp);
    renderer.value.domElement.addEventListener('pointermove', onPointerHover);
    renderer.value.domElement.addEventListener('pointerleave', onPointerLeave);
    renderer.value.domElement.addEventListener('dblclick', onDoubleClick);
    window.addEventListener('resize', onResize);
    window.addEventListener('keydown', onKeyDown);
    window.addEventListener('keyup', onKeyUp);

    // Follow the container's own size changes, not just window resizes.
    if (canvasHost.value) {
        resizeObserver = new ResizeObserver(() => onResize());
        resizeObserver.observe(canvasHost.value);
    }

    animate();
}

// Double-click on the model recenters the orbit pivot there, so orbiting and
// zooming stay responsive instead of pivoting around a far-away point.
function onDoubleClick(event: MouseEvent): void {
    if (!modelRoot || !orbit.value) {
        return;
    }

    updatePointer(event as unknown as PointerEvent);
    raycaster.setFromCamera(pointer, camera.value!);
    const hit = raycaster.intersectObject(modelRoot, true)[0];

    if (hit) {
        // Orbit around the clicked point without moving the camera.
        orbit.value.setOrbitPoint(hit.point.x, hit.point.y, hit.point.z);
    }
}

// Mouse mapping with Alt released: left free (create/select), middle trucks
// (pan), right orbits, wheel dollies (zoom).
function applyDefaultMouseButtons(): void {
    const controls = orbit.value!;
    controls.mouseButtons.left = CameraControls.ACTION.NONE;
    controls.mouseButtons.middle = CameraControls.ACTION.TRUCK;
    controls.mouseButtons.right = CameraControls.ACTION.ROTATE;
    controls.mouseButtons.wheel = CameraControls.ACTION.DOLLY;
}

// Mouse mapping while Alt is held: left orbits (Unity style).
function applyAltMouseButtons(): void {
    const controls = orbit.value!;
    controls.mouseButtons.left = CameraControls.ACTION.ROTATE;
    controls.mouseButtons.middle = CameraControls.ACTION.TRUCK;
    controls.mouseButtons.right = CameraControls.ACTION.ROTATE;
    controls.mouseButtons.wheel = CameraControls.ACTION.DOLLY;
}

function loadModel(): void {
    if (!props.modelUrl) {
        loading.value = false;

        return;
    }

    new GLTFLoader().load(
        props.modelUrl,
        (gltf) => {
            modelRoot = gltf.scene;
            scene.value!.add(modelRoot);
            frameModel(modelRoot);
            buildHeadingArrow();
            loading.value = false;
            // Draw the live graph preview now that the model (for wall raycasts)
            // is available.
            computeGraph();
        },
        undefined,
        () => {
            loadError.value = 'No se pudo cargar el modelo .glb.';
            loading.value = false;
        },
    );
}

// Center the camera and orbit target on the loaded model's bounding box.
function frameModel(object: THREE.Object3D): void {
    const box = new THREE.Box3().setFromObject(object);
    modelCenter = box.getCenter(new THREE.Vector3());
    const size = box.getSize(new THREE.Vector3()).length();
    modelSize = size;

    camera.value!.near = size / 100;
    camera.value!.far = size * 10;
    camera.value!.updateProjectionMatrix();

    frameToModel(false);
}

// Build the heading arrow at the model origin: a short, FLAT arrow lying on the
// ground plane (like a decal painted on the floor) that points where the
// entrance faces. Rotating it around Y sets the heading angle.
function buildHeadingArrow(): void {
    if (!scene.value) {
        return;
    }

    if (headingArrow) {
        scene.value.remove(headingArrow);
        headingArrow = null;
    }

    // Small, slim arrow relative to the model.
    // `unit` sets the overall thickness/head size; `length` is the total length
    // decoupled from it, so the arrow can be shortened without getting thinner.
    const unit = Math.max(modelSize * 0.05, 0.18);
    const length = unit * 0.6;
    const shaftWidth = unit * 0.1;
    const headWidth = unit * 0.25;
    const headLength = unit * 0.2;
    const shaftLength = Math.max(length - headLength, headLength * 0.4);

    const material = new THREE.MeshBasicMaterial({
        color: 0xf59e0b,
        side: THREE.DoubleSide,
    });

    // A flat 2D arrow shape drawn in the XY plane, pointing along +Y, then laid
    // flat so it points along +Z on the ground (heading 0 = +Z).
    const shape = new THREE.Shape();
    shape.moveTo(-shaftWidth / 2, 0);
    shape.lineTo(shaftWidth / 2, 0);
    shape.lineTo(shaftWidth / 2, shaftLength);
    shape.lineTo(headWidth / 2, shaftLength);
    shape.lineTo(0, length);
    shape.lineTo(-headWidth / 2, shaftLength);
    shape.lineTo(-shaftWidth / 2, shaftLength);
    shape.lineTo(-shaftWidth / 2, 0);

    const arrow = new THREE.Mesh(new THREE.ShapeGeometry(shape), material);
    arrow.rotation.x = -Math.PI / 2; // lay the shape flat on the ground (XZ)

    const group = new THREE.Group();
    group.add(arrow);

    // Small base disc at the pivot so the origin reads clearly.
    const base = new THREE.Mesh(
        new THREE.CircleGeometry(shaftWidth * 0.9, 20),
        new THREE.MeshBasicMaterial({
            color: 0xf59e0b,
            transparent: true,
            opacity: 0.4,
            side: THREE.DoubleSide,
        }),
    );
    base.rotation.x = -Math.PI / 2;
    group.add(base);

    scene.value.add(group);
    headingArrow = group;

    updateHeadingArrow();
}

// Place the arrow over the initial node and orient it. Hidden when there is no
// initial node (nothing to anchor/orient from).
function updateHeadingArrow(): void {
    requestRender();

    if (!headingArrow) {
        return;
    }

    const node = initialNode.value;

    if (!node || !showHeadingArrow.value) {
        headingArrow.visible = false;

        return;
    }

    headingArrow.visible = true;
    // Sit slightly above the node so it reads as a floor decal at that point.
    const lift = Math.max(modelSize * 0.001, 0.01);
    headingArrow.position.set(
        node.position_x,
        node.position_y + lift,
        node.position_z,
    );
    headingArrow.rotation.y = THREE.MathUtils.degToRad(node.heading);
}

// Place the camera so the whole model fits, orbiting around its center.
function frameToModel(transition: boolean): void {
    if (!orbit.value) {
        return;
    }

    const offset = modelSize * 0.6;
    orbit.value.setLookAt(
        modelCenter.x + offset,
        modelCenter.y + offset,
        modelCenter.z + offset,
        modelCenter.x,
        modelCenter.y,
        modelCenter.z,
        transition,
    );
}

// Reset the camera to the general overview, clearing any stuck pivot/zoom.
function resetView(): void {
    deselect();
    frameToModel(true);
}

// Move the camera close to a node, keeping the current viewing direction.
function focusNode(id: number): void {
    const mesh = nodeMeshes.get(id);

    if (!mesh || !camera.value || !orbit.value) {
        return;
    }

    selectNode(id);

    const target = mesh.position;
    const distance = Math.max(modelSize * 0.08, 2);
    // Keep the current view direction, just move closer to the node.
    const direction = new THREE.Vector3();
    camera.value.getWorldDirection(direction);
    const position = target.clone().addScaledVector(direction, -distance);

    orbit.value.setLookAt(
        position.x,
        position.y,
        position.z,
        target.x,
        target.y,
        target.z,
        true,
    );
}

function updatePointer(event: PointerEvent): void {
    const rect = renderer.value!.domElement.getBoundingClientRect();
    pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
    pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
}

function onPointerDown(event: PointerEvent): void {
    pointerDownAt = { x: event.clientX, y: event.clientY };

    // Orbit gesture (Alt+left): move the pivot onto the surface under the
    // cursor first, so we rotate around what we're looking at (not a far point)
    // and don't fly out of the building when close.
    if (event.altKey && event.button === 0) {
        pivotOnCursor(event);
    }

    // Middle button = pan: show the grab (hand) cursor, like Unity.
    if (event.button === 1) {
        setCursor('pan');
    }
}

// Raycast under the cursor and, if it hits the model, orbit around that point.
function pivotOnCursor(event: PointerEvent): void {
    if (!modelRoot || !orbit.value || !camera.value) {
        return;
    }

    updatePointer(event);
    raycaster.setFromCamera(pointer, camera.value);
    const hit = raycaster.intersectObject(modelRoot, true)[0];

    if (hit) {
        orbit.value.setOrbitPoint(hit.point.x, hit.point.y, hit.point.z);
    }
}

function onPointerUp(event: PointerEvent): void {
    // Restore the cursor after a pan (or keep the eye if Alt is still held).
    if (event.button === 1) {
        setCursor(event.altKey ? 'orbit' : 'default');
    }

    // Any left-click dismisses an open context menu.
    if (event.button === 0 && contextMenu.value) {
        contextMenu.value = null;
    }

    // Left-click selects/deselects nodes. Right/middle drive the camera,
    // and Alt+left is the orbit gesture, so skip those.
    if (event.button !== 0 || event.altKey) {
        return;
    }

    const moved =
        Math.abs(event.clientX - pointerDownAt.x) +
        Math.abs(event.clientY - pointerDownAt.y);

    // Ignore drags (camera orbit) and clicks while transforming a node.
    if (moved > 4 || transform.value?.dragging) {
        return;
    }

    updatePointer(event);
    raycaster.setFromCamera(pointer, camera.value!);

    // A click on an existing node sphere selects it; clicking empty space
    // deselects. Creating a node is done from the right-click menu instead,
    // so left-clicking the model never spawns nodes by accident.
    const spheres = [...nodeMeshes.values()];
    const hitSphere = raycaster.intersectObjects(spheres, false)[0];

    if (hitSphere) {
        const id = hitSphere.object.userData.localId as number;

        // Ctrl+click picks the other end of a pair, to create a rule between
        // two nodes that the automatic linking would not connect (stairs).
        if ((event.ctrlKey || event.metaKey) && selectedId.value !== null) {
            pairSelection.value = id === selectedId.value ? null : id;
            refreshColors();
        } else {
            selectNode(id);
        }

        return;
    }

    // Clicking an edge cuts it (or restores it if it was already cut).
    const hitEdge = raycaster.intersectObjects(edgeMeshes, false)[0];

    if (hitEdge) {
        const edge = hitEdge.object.userData.edge as { a: number; b: number };
        toggleBlockedEdge(edge.a, edge.b);

        return;
    }

    deselect();
}

// Highlight the node under the cursor and switch to a pointer cursor, so nodes
// feel interactive. Skipped while orbiting/panning or dragging the gizmo.
function onPointerHover(event: PointerEvent): void {
    if (!camera.value || transform.value?.dragging) {
        return;
    }

    // Don't fight the orbit/pan cursors set elsewhere while a button is held.
    if (event.buttons !== 0) {
        return;
    }

    updatePointer(event);
    raycaster.setFromCamera(pointer, camera.value);
    const spheres = [...nodeMeshes.values()];
    const hit = raycaster.intersectObjects(spheres, false)[0];
    const id = hit ? (hit.object.userData.localId as number) : null;

    if (id !== hoveredId.value) {
        hoveredId.value = id;
        refreshColors();
    }

    // Edges are clickable too (to cut or restore a link), so hint that.
    const overEdge =
        id === null && raycaster.intersectObjects(edgeMeshes, false).length > 0;

    setCursor(id !== null || overEdge ? 'pointer' : 'default');
}

// Clear the hover highlight when the cursor leaves the canvas.
function onPointerLeave(): void {
    if (hoveredId.value !== null) {
        hoveredId.value = null;
        refreshColors();
    }
}

// Right-click opens a context menu. Over a node it offers duplicate/delete;
// over empty model space it offers create. If the right button was dragged
// (camera orbit), skip the menu.
function onContextMenu(event: MouseEvent): void {
    event.preventDefault();
    const moved =
        Math.abs(event.clientX - pointerDownAt.x) +
        Math.abs(event.clientY - pointerDownAt.y);

    if (moved > 4 || !modelRoot || !camera.value) {
        return;
    }

    updatePointer(event as unknown as PointerEvent);
    raycaster.setFromCamera(pointer, camera.value);

    const rect = renderer.value!.domElement.getBoundingClientRect();
    const menuPos = {
        x: event.clientX - rect.left,
        y: event.clientY - rect.top,
    };

    // Prefer a node under the cursor (duplicate/delete); else fall back to the
    // model surface (create a node there).
    const spheres = [...nodeMeshes.values()];
    const hitSphere = raycaster.intersectObjects(spheres, false)[0];

    if (hitSphere) {
        contextMenu.value = {
            ...menuPos,
            kind: 'node',
            nodeId: hitSphere.object.userData.localId as number,
        };

        return;
    }

    const hit = raycaster.intersectObject(modelRoot, true)[0];

    if (!hit) {
        contextMenu.value = null;

        return;
    }

    pendingPoint = hit.point.clone();
    contextMenu.value = { ...menuPos, kind: 'create', nodeId: null };
}

// Duplicate the given node a bit offset from the original, then select it.
// Only the position is copied: label and group are left empty so it takes the
// automatic node counter as its name (no "(copia) (copia)…" chains).
function duplicateNode(id: number): void {
    const node = localNodes.value.find((n) => n.localId === id);

    if (!node) {
        return;
    }

    const offset = Math.max(modelSize * 0.02, 0.1);
    const copy: LocalNode = {
        localId: nextTempId--,
        label: '',
        position_x: node.position_x + offset,
        position_y: node.position_y,
        position_z: node.position_z + offset,
        // A duplicate is never the initial node (only one allowed).
        is_initial: false,
        heading: 0,
        node_group_id: null,
    };
    localNodes.value.push(copy);
    addSphere(copy);
    selectNode(copy.localId);
    markDirty();
    contextMenu.value = null;
}

// Delete the node targeted by the context menu.
function deleteNodeFromMenu(id: number): void {
    deleteNode(id);
    contextMenu.value = null;
}

// Confirm node creation from the context menu.
function createNodeFromMenu(): void {
    if (pendingPoint) {
        createNode(pendingPoint);
    }

    contextMenu.value = null;
}

// Add a new node locally where the user clicked on the model.
function createNode(point: THREE.Vector3): void {
    const node: LocalNode = {
        localId: nextTempId--,
        label: '',
        position_x: point.x,
        position_y: point.y,
        position_z: point.z,
        is_initial: false,
        heading: 0,
        node_group_id: null,
    };
    localNodes.value.push(node);
    addSphere(node);
    markDirty();
    selectNode(node.localId);
}

// Check whether the straight segment between two points is blocked by the
// model, mirroring the A* Point Graph's obstacle raycast (thin or thick).
function segmentBlocked(a: THREE.Vector3, b: THREE.Vector3): boolean {
    if (!modelRoot) {
        return false;
    }

    const direction = b.clone().sub(a);
    const distance = direction.length();
    direction.normalize();

    if (graphParams.value.thickRadius > 0) {
        // Approximate a thick raycast with a few parallel thin rays.
        const up = new THREE.Vector3(0, 1, 0);
        const side = new THREE.Vector3()
            .crossVectors(direction, up)
            .normalize()
            .multiplyScalar(graphParams.value.thickRadius);
        const offsets = [new THREE.Vector3(), side, side.clone().negate()];

        return offsets.some((offset) => {
            graphRaycaster.set(a.clone().add(offset), direction);
            graphRaycaster.far = distance;

            return graphRaycaster.intersectObject(modelRoot!, true).length > 0;
        });
    }

    graphRaycaster.set(a, direction);
    graphRaycaster.far = distance;

    return graphRaycaster.intersectObject(modelRoot, true).length > 0;
}

// Draw one edge as a thin cylinder so it is clearly visible (WebGL ignores
// line width, so real "thick lines" need geometry).
function addEdgeCylinder(
    group: THREE.Group,
    a: THREE.Vector3,
    b: THREE.Vector3,
    material: THREE.Material,
    aId: number,
    bId: number,
): void {
    const length = a.distanceTo(b);
    const radius = Math.max(modelSize * 0.0008, 0.006);
    const geometry = new THREE.CylinderGeometry(radius, radius, length, 6);
    const cylinder = new THREE.Mesh(geometry, material);
    // Cylinders are Y-aligned by default; orient it along the edge.
    cylinder.position.copy(a).lerp(b, 0.5);
    cylinder.quaternion.setFromUnitVectors(
        new THREE.Vector3(0, 1, 0),
        b.clone().sub(a).normalize(),
    );
    // The pair behind this edge, so clicking it can cut or restore the link.
    cylinder.userData.edge = { a: aId, b: bId };
    group.add(cylinder);
    edgeMeshes.push(cylinder);
}

// Simulate the A* Point Graph: connect nodes within maxDistance that are not
// separated by a wall, and draw the edges as a preview (never persisted).
function computeGraph(): void {
    requestRender();
    clearGraph();

    // When the preview is toggled off, don't draw lines or compute stats.
    if (!showGraph.value) {
        return;
    }

    graphGroup = new THREE.Group();
    const edgeMaterial = new THREE.MeshBasicMaterial({ color: 0x22c55e });
    // Cut by hand (a wall): drawn red so it stays visible and clickable to undo.
    const blockedMaterial = new THREE.MeshBasicMaterial({ color: 0xef4444 });
    // Forced by hand (stairs): drawn amber to tell it apart from an automatic link.
    const forcedMaterial = new THREE.MeshBasicMaterial({ color: 0xf59e0b });

    const nodes = localNodes.value.map((n) => ({
        id: n.localId,
        pos: new THREE.Vector3(n.position_x, n.position_y, n.position_z),
    }));
    const positions = new Map(nodes.map((n) => [n.id, n.pos]));
    const maxDistance = graphParams.value.maxDistance;
    const maxY = graphParams.value.maxYDistance;
    const degree = new Map<number, number>();
    // Pairs already drawn, so the forced pass below doesn't duplicate an edge.
    const drawn = new Set<string>();
    let edges = 0;

    for (let i = 0; i < nodes.length; i++) {
        for (let j = i + 1; j < nodes.length; j++) {
            const a = nodes[i];
            const b = nodes[j];
            const key = pairKey(a.id, b.id);
            const override = overrideByPair.value.get(key);
            // Same math as the A* Point Graph: full 3D distance for maxDistance,
            // absolute Y difference for the per-axis Y limit. Both are AND, and a
            // pair that fails either one simply doesn't connect (no line drawn),
            // exactly like Unity.
            const yGap = Math.abs(a.pos.y - b.pos.y);

            // Filter 1: max distance — full 3D euclidean (0 means unlimited).
            if (maxDistance > 0 && a.pos.distanceTo(b.pos) > maxDistance) {
                continue;
            }

            // Filter 2: Y limit — absolute height difference (0 means unlimited).
            // Keeps floors apart while still linking nearby same-level nodes.
            if (maxY > 0 && yGap > maxY) {
                continue;
            }

            // Filter 3: obstacle raycast against the model.
            if (graphParams.value.checkWalls && segmentBlocked(a.pos, b.pos)) {
                continue;
            }

            drawn.add(key);

            // Filter 4: a hand-made rule wins over the automatic linking. The
            // cut edge is still drawn (in red) so it can be clicked to restore.
            if (override?.type === 'blocked') {
                addEdgeCylinder(
                    graphGroup,
                    a.pos,
                    b.pos,
                    blockedMaterial,
                    a.id,
                    b.id,
                );
                continue;
            }

            addEdgeCylinder(
                graphGroup,
                a.pos,
                b.pos,
                override?.type === 'forced' ? forcedMaterial : edgeMaterial,
                a.id,
                b.id,
            );
            addEdgeLabel(graphGroup, a.pos, b.pos);
            degree.set(a.id, (degree.get(a.id) ?? 0) + 1);
            degree.set(b.id, (degree.get(b.id) ?? 0) + 1);
            edges++;
        }
    }

    // Forced pairs that the filters above rejected (too far apart, or on
    // different floors like a staircase): Unity links them anyway, so draw them.
    for (const override of localOverrides.value) {
        const key = pairKey(override.a, override.b);

        if (override.type !== 'forced' || drawn.has(key)) {
            continue;
        }

        const a = positions.get(override.a);
        const b = positions.get(override.b);

        if (!a || !b) {
            continue;
        }

        addEdgeCylinder(
            graphGroup,
            a,
            b,
            forcedMaterial,
            override.a,
            override.b,
        );
        addEdgeLabel(graphGroup, a, b);
        degree.set(override.a, (degree.get(override.a) ?? 0) + 1);
        degree.set(override.b, (degree.get(override.b) ?? 0) + 1);
        edges++;
    }

    scene.value!.add(graphGroup);

    const isolated = nodes.filter((n) => !degree.has(n.id)).length;
    graphStats.value = { edges, isolated };
}

// Adds a small text sprite with the edge length at the midpoint of the edge,
// slightly above the green tube, so the admin can see each node-to-node distance.
function addEdgeLabel(
    group: THREE.Group,
    a: THREE.Vector3,
    b: THREE.Vector3,
): void {
    const distance = a.distanceTo(b);
    const text = `${distance.toFixed(2)} m`;

    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d')!;
    const fontSize = 48;
    ctx.font = `bold ${fontSize}px sans-serif`;
    const padding = 16;
    canvas.width = ctx.measureText(text).width + padding * 2;
    canvas.height = fontSize + padding * 2;

    // Re-set font after resizing (resizing clears the context).
    ctx.font = `bold ${fontSize}px sans-serif`;
    ctx.fillStyle = 'rgba(0,0,0,0.6)';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#ffffff';
    ctx.textBaseline = 'middle';
    ctx.fillText(text, padding, canvas.height / 2);

    const texture = new THREE.CanvasTexture(canvas);
    texture.minFilter = THREE.LinearFilter;
    const material = new THREE.SpriteMaterial({
        map: texture,
        depthTest: false,
    });
    const sprite = new THREE.Sprite(material);

    // Position at the midpoint, lifted a bit above the tube.
    const mid = a.clone().add(b).multiplyScalar(0.5);
    mid.y += 0.05;
    sprite.position.copy(mid);

    // Small fixed height (world units) so the label is discreet.
    const scale = 0.12;
    sprite.scale.set((scale * canvas.width) / canvas.height, scale, 1);

    group.add(sprite);
}

// Remove the simulated graph edges from the scene.
function clearGraph(): void {
    if (graphGroup) {
        scene.value!.remove(graphGroup);
        graphGroup.traverse((obj) => {
            if (obj instanceof THREE.Mesh) {
                obj.geometry.dispose();
                (obj.material as THREE.Material).dispose();
            } else if (obj instanceof THREE.Sprite) {
                const material = obj.material as THREE.SpriteMaterial;
                material.map?.dispose();
                material.dispose();
            }
        });
        graphGroup = null;
    }

    edgeMeshes = [];
    graphStats.value = null;
}

function selectNode(id: number): void {
    selectedId.value = id;
    // Picking a new node starts a fresh pair.
    pairSelection.value = null;
    const mesh = nodeMeshes.get(id);

    if (mesh) {
        transform.value!.attach(mesh);
    }

    refreshColors();
}

function deselect(): void {
    selectedId.value = null;
    pairSelection.value = null;
    transform.value!.detach();
    refreshColors();
}

// Keep the local node's position in sync while its gizmo is dragged.
function onGizmoMove(): void {
    if (selectedId.value === null) {
        return;
    }

    const mesh = nodeMeshes.get(selectedId.value);
    const node = localNodes.value.find((n) => n.localId === selectedId.value);

    if (mesh && node) {
        node.position_x = mesh.position.x;
        node.position_y = mesh.position.y;
        node.position_z = mesh.position.z;
        markDirty();
        requestRender();
    }
}

// Recolor every sphere based on its selected / initial / normal state. Hovering
// keeps that base color but lightens it (typical hover feedback), for any state
// including the initial (mustard) node.
function refreshColors(): void {
    requestRender();

    for (const [id, mesh] of nodeMeshes) {
        const material = mesh.material as THREE.MeshStandardMaterial;

        let base: number;

        if (id === selectedId.value) {
            base = SELECTED_COLOR;
        } else if (id === pairSelection.value) {
            base = PAIRED_COLOR;
        } else if (mesh.userData.isInitial) {
            base = INITIAL_COLOR;
        } else {
            base = NORMAL_COLOR;
        }

        material.color.setHex(base);

        // Lighten toward white on hover, keeping the state's own hue.
        if (id === hoveredId.value) {
            material.color.lerp(new THREE.Color(0xffffff), 0.4);
        }
    }
}

function addSphere(node: LocalNode): void {
    const geometry = new THREE.SphereGeometry(0.15, 16, 16);
    const material = new THREE.MeshStandardMaterial({ color: NORMAL_COLOR });
    const sphere = new THREE.Mesh(geometry, material);
    sphere.position.set(node.position_x, node.position_y, node.position_z);
    sphere.userData.localId = node.localId;
    sphere.userData.isInitial = node.is_initial;
    scene.value!.add(sphere);
    nodeMeshes.set(node.localId, sphere);
    refreshColors();
}

function renderNodes(): void {
    for (const mesh of nodeMeshes.values()) {
        scene.value!.remove(mesh);
    }

    nodeMeshes.clear();

    for (const node of localNodes.value) {
        addSphere(node);
    }

    refreshColors();
}

// Apply a manually typed coordinate to a node and move its sphere live.
function setNodeAxis(
    id: number,
    axis: 'position_x' | 'position_y' | 'position_z',
    value: number,
): void {
    if (Number.isNaN(value)) {
        return;
    }

    const node = localNodes.value.find((n) => n.localId === id);
    const mesh = nodeMeshes.get(id);

    if (!node || !mesh) {
        return;
    }

    node[axis] = value;
    mesh.position.set(node.position_x, node.position_y, node.position_z);
    markDirty();
}

// Sentinel for the "no group" option (reka-ui Select can't use an empty value).
const NO_GROUP = 'none';

// Assign the node to a group (NO_GROUP sentinel = ungrouped).
function setNodeGroup(id: number, value: string): void {
    const node = localNodes.value.find((n) => n.localId === id);

    if (!node) {
        return;
    }

    node.node_group_id = value === NO_GROUP ? null : Number(value);
    // Grouping is purely organizational: it never changes the connections.
    markUnsaved();
}

// Only one node may be the building's initial (calibration) anchor.
function toggleInitial(id: number): void {
    const node = localNodes.value.find((n) => n.localId === id);

    if (!node) {
        return;
    }

    const becomingInitial = !node.is_initial;

    for (const other of localNodes.value) {
        other.is_initial = other.localId === id ? becomingInitial : false;
        const mesh = nodeMeshes.get(other.localId);

        if (mesh) {
            mesh.userData.isInitial = other.is_initial;
        }
    }

    // Colors are refreshed above; the initial flag doesn't change any edge.
    refreshColors();
    markUnsaved();
}

function deleteNode(id: number): void {
    localNodes.value = localNodes.value.filter((n) => n.localId !== id);
    // A rule without both of its nodes is meaningless (the server cascades the
    // same way when the node is removed through the sync).
    localOverrides.value = localOverrides.value.filter(
        (override) => override.a !== id && override.b !== id,
    );
    const mesh = nodeMeshes.get(id);

    if (mesh) {
        scene.value!.remove(mesh);
        nodeMeshes.delete(id);
    }

    if (pairSelection.value === id) {
        pairSelection.value = null;
    }

    if (selectedId.value === id) {
        deselect();
    }

    markDirty();
}

// Flag unsaved changes WITHOUT rebuilding the 3D preview. For edits that can't
// change the graph (renaming, grouping, heading, initial flag). Rebuilding on
// every keystroke made typing a node name lag: the preview is O(n²) over every
// pair and allocates a canvas texture per edge label.
function markUnsaved(): void {
    dirty.value = true;
}

// Coalesce bursts of changes (typing in the distance inputs, several edits in a
// row) into a single rebuild, instead of one per change.
let graphTimer: ReturnType<typeof setTimeout> | null = null;

function scheduleGraph(): void {
    if (graphTimer !== null) {
        clearTimeout(graphTimer);
    }

    graphTimer = setTimeout(() => {
        graphTimer = null;

        // The component may have been torn down while the timer was pending.
        if (scene.value) {
            computeGraph();
        }
    }, 120);
}

function markDirty(): void {
    dirty.value = true;
    // Live preview is always on: keep the drawn graph in sync.
    scheduleGraph();
}

// Persist nodes AND the A* parameters together in one Save action. First the
// parameters (on the building), then the node set (also on the building).
function save(): void {
    saving.value = true;

    router.put(
        `/buildings/${props.building.id}/pathfinding`,
        {
            max_distance: graphParams.value.maxDistance,
            max_y_distance: graphParams.value.maxYDistance,
            thick_radius: graphParams.value.thickRadius,
            check_walls: graphParams.value.checkWalls,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => saveNodes(),
            onError: () => {
                saving.value = false;
            },
        },
    );
}

// The reka-ui Slider works with an array model; bridge it to the number heading.
const headingSlider = computed<number[]>({
    get: () => [heading.value],
    set: (value) => {
        heading.value = value[0] ?? 0;
    },
});

// Persist the whole node set for this building (called after params are saved).
function saveNodes(): void {
    router.put(
        `/buildings/${props.building.id}/nodes/sync`,
        {
            nodes: localNodes.value.map((n) => ({
                // Stable key the connection overrides below refer to, so the
                // server can resolve pairs that involve brand new nodes.
                local_id: n.localId,
                // Positive localId means an existing DB node: send it so the
                // server updates it in place and destination links are kept.
                // New nodes have a negative temp id and send no id.
                id: n.localId > 0 ? n.localId : null,
                label: n.label || null,
                position_x: n.position_x,
                position_y: n.position_y,
                position_z: n.position_z,
                is_initial: n.is_initial,
                // Only the initial node carries a heading; null for the rest.
                heading: n.is_initial ? n.heading : null,
                node_group_id: n.node_group_id,
            })),
            // Hand-made exceptions to the automatic linking, keyed by local_id.
            connection_overrides: localOverrides.value.map((override) => ({
                a: override.a,
                b: override.b,
                type: override.type,
            })),
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                dirty.value = false;
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

function onResize(): void {
    const host = canvasHost.value;

    if (!host || !camera.value || !renderer.value) {
        return;
    }

    camera.value.aspect = host.clientWidth / host.clientHeight;
    camera.value.updateProjectionMatrix();
    // updateStyle=false so the canvas keeps its CSS-driven 100% size.
    renderer.value.setSize(host.clientWidth, host.clientHeight, false);
    requestRender();
}

// True when the user is typing in a form field, so editor shortcuts must not
// fire (e.g. Delete inside a coordinate input should not remove the node).
function isTypingInField(event: KeyboardEvent): boolean {
    const target = event.target as HTMLElement | null;

    if (!target) {
        return false;
    }

    const tag = target.tagName;

    return tag === 'INPUT' || tag === 'TEXTAREA' || target.isContentEditable;
}

// Keyboard: Ctrl/Cmd+S saves, Alt enables orbit on left button (Unity style),
// W/E/R switch gizmo mode, Delete removes the selected node, and Escape leaves
// focus mode.
function onKeyDown(event: KeyboardEvent): void {
    const isSaveShortcut =
        (event.ctrlKey || event.metaKey) &&
        event.key.toLowerCase() === 's';

    if (isSaveShortcut) {
        // Prevents the browser from opening its "Save page" dialog.
        event.preventDefault();

        if (!event.repeat && dirty.value && !saving.value) {
            save();
        }

        return;
    }

    // Ignore the remaining shortcuts while typing in an input.
    if (isTypingInField(event)) {
        return;
    }

    if (event.altKey) {
        // Prevent the browser from stealing focus to the menu bar.
        event.preventDefault();
        applyAltMouseButtons();
        setCursor('orbit');
    }

    if (event.key === 'w') {
        transform.value?.setMode('translate');
    } else if (event.key === 'e') {
        transform.value?.setMode('rotate');
    } else if (event.key === 'r') {
        transform.value?.setMode('scale');
    } else if (event.key === 'Escape') {
        deselect();
    } else if (
        (event.key === 'Delete' || event.key === 'Backspace') &&
        selectedId.value !== null
    ) {
        deleteNode(selectedId.value);
    }
}

// Restore the default mouse mapping once Alt is released.
function onKeyUp(event: KeyboardEvent): void {
    if (event.key === 'Alt' || !event.altKey) {
        applyDefaultMouseButtons();
        setCursor('default');
    }
}

function animate(): void {
    animationId = requestAnimationFrame(animate);
    // camera-controls needs the frame delta (seconds) to advance its easing.
    const now = performance.now();
    const delta = (now - lastFrameTime) / 1000;
    lastFrameTime = now;
    // update() returns true while the camera is moving/easing.
    const cameraMoved = orbit.value?.update(delta) ?? false;

    // Draw only when needed: camera in motion, a node being dragged, or within a
    // requestRender() window. Idle otherwise, to save CPU/GPU.
    if (cameraMoved || transform.value?.dragging || now < renderUntil) {
        renderer.value!.render(scene.value!, camera.value!);
    }
}

onMounted(() => {
    window.addEventListener('beforeunload', handleBeforeUnload);
    initScene();
    loadModel();
    renderNodes();
    // Live preview on by default (also redrawn once the model finishes loading).
    computeGraph();
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload);
    cancelAnimationFrame(animationId);

    if (graphTimer !== null) {
        clearTimeout(graphTimer);
        graphTimer = null;
    }

    clearGraph();
    resizeObserver?.disconnect();
    window.removeEventListener('resize', onResize);
    window.removeEventListener('keydown', onKeyDown);
    window.removeEventListener('keyup', onKeyUp);
    renderer.value?.domElement.removeEventListener(
        'pointerdown',
        onPointerDown,
    );
    renderer.value?.domElement.removeEventListener('pointerup', onPointerUp);
    renderer.value?.domElement.removeEventListener(
        'pointermove',
        onPointerHover,
    );
    renderer.value?.domElement.removeEventListener(
        'pointerleave',
        onPointerLeave,
    );
    renderer.value?.domElement.removeEventListener('dblclick', onDoubleClick);
    renderer.value?.domElement.removeEventListener(
        'contextmenu',
        onContextMenu,
    );
    transform.value?.dispose();
    orbit.value?.dispose();
    renderer.value?.dispose();
});

// Expose save state/action so the page header can host the Save button. Getters
// keep the values reactive when read from the parent template.
defineExpose({
    save,
    get dirty() {
        return dirty.value;
    },
    get saving() {
        return saving.value;
    },
});
</script>

<template>
    <div class="flex h-full gap-4">
        <!-- CENTER: viewport area holding a 16:9 box sized to the height. -->
        <div class="flex min-w-0 flex-1 justify-center">
            <div
                class="relative aspect-video h-full overflow-hidden rounded-lg border"
            >
                <div ref="canvasHost" class="h-full w-full" />

                <div
                    v-if="loading"
                    class="absolute inset-0 flex items-center justify-center bg-slate-900/60 text-sm text-white"
                >
                    Cargando modelo…
                </div>
                <div
                    v-else-if="loadError"
                    class="absolute inset-x-0 top-0 bg-destructive/90 p-2 text-center text-xs text-white"
                >
                    {{ loadError }}
                </div>
                <div
                    v-else-if="!modelUrl"
                    class="absolute inset-0 flex items-center justify-center p-6 text-center text-sm text-muted-foreground"
                >
                    Este edificio no tiene un modelo .glb. Súbelo desde la
                    edición del edificio para poder colocar nodos sobre él.
                </div>

                <!-- Reset the camera to the general overview -->
                <Button
                    variant="secondary"
                    size="sm"
                    class="absolute top-2 right-2 gap-1"
                    title="Vuelve a la vista general y libera el pivote"
                    @click="resetView"
                >
                    <RotateCcw class="h-4 w-4" />
                    Restablecer vista
                </Button>

                <!-- Right-click context menu: node actions or create-here -->
                <div
                    v-if="contextMenu"
                    class="absolute z-10 min-w-36 overflow-hidden rounded-md border bg-popover py-1 shadow-md"
                    :style="{
                        left: `${contextMenu.x}px`,
                        top: `${contextMenu.y}px`,
                    }"
                >
                    <template v-if="contextMenu.kind === 'node'">
                        <button
                            class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-xs hover:bg-accent"
                            @click="duplicateNode(contextMenu.nodeId!)"
                        >
                            <Copy class="h-3.5 w-3.5" />
                            Duplicar nodo
                        </button>
                        <button
                            class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-xs text-destructive hover:bg-accent"
                            @click="deleteNodeFromMenu(contextMenu.nodeId!)"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                            Eliminar nodo
                        </button>
                    </template>
                    <button
                        v-else
                        class="block w-full px-3 py-1.5 text-left text-xs hover:bg-accent"
                        @click="createNodeFromMenu"
                    >
                        Crear nodo aquí
                    </button>
                    <button
                        class="mt-0.5 block w-full border-t px-3 py-1.5 text-left text-xs text-muted-foreground hover:bg-accent"
                        @click="contextMenu = null"
                    >
                        Cancelar
                    </button>
                </div>

                <!-- Controls help overlay -->
                <div
                    class="pointer-events-none absolute bottom-2 left-2 space-y-0.5 rounded bg-slate-900/75 px-3 py-2 text-[11px] leading-relaxed text-slate-200"
                >
                    <p><b>Click izq.:</b> seleccionar / deseleccionar nodo</p>
                    <p>
                        <b>Click der.:</b> menú (crear en vacío;
                        duplicar/eliminar sobre un nodo)
                    </p>
                    <p><b>Alt + arrastrar izq.:</b> orbitar (rodear)</p>
                    <p><b>Arrastrar rueda (botón medio):</b> desplazar (pan)</p>
                    <p><b>Rueda</b> o <b>Alt + click der.:</b> zoom</p>
                    <p><b>Doble click:</b> centrar giro/zoom ahí</p>
                    <p><b>Supr:</b> borrar nodo seleccionado</p>
                </div>
            </div>
        </div>

        <!-- RIGHT column: scene config on top, node config below. -->
        <div class="flex h-full min-h-0 w-72 shrink-0 flex-col gap-3">
            <!-- Scene config sits in a popover to keep the panel compact. -->
            <Popover>
                <PopoverTrigger as-child>
                    <Button
                        variant="outline"
                        size="sm"
                        class="w-full justify-start"
                    >
                        <SlidersHorizontal class="mr-2 h-4 w-4" />
                        Configuración de escena
                    </Button>
                </PopoverTrigger>
                <PopoverContent
                    align="start"
                    :side-offset="6"
                    class="flex max-h-[75vh] w-72 flex-col gap-2 overflow-y-auto"
                >
                    <p
                        v-if="initialCount === 0 && localNodes.length"
                        class="rounded bg-slate-500/10 px-2 py-1 text-xs text-muted-foreground"
                    >
                        Marca un nodo como inicial (★) para la calibración.
                    </p>
                    <!-- A* graph simulation (preview only) -->
                    <div class="rounded-md border p-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium">
                                Simular grafo (A*)
                            </span>
                            <div class="flex items-center gap-2">
                                <label
                                    class="flex items-center gap-1.5 text-[11px] text-muted-foreground"
                                >
                                    <Checkbox v-model="showGraph" />
                                    Ver grafo
                                </label>
                                <Waypoints
                                    class="h-4 w-4 text-muted-foreground"
                                />
                            </div>
                        </div>
                        <div class="mt-2 space-y-2">
                            <label
                                class="flex items-center justify-between gap-2 text-xs"
                            >
                                <span
                                    class="flex items-center gap-1 text-muted-foreground"
                                >
                                    Distancia máx.
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <button
                                                    type="button"
                                                    class="text-muted-foreground/70 hover:text-muted-foreground"
                                                    @click.prevent
                                                >
                                                    <Info class="h-3 w-3" />
                                                </button>
                                            </TooltipTrigger>
                                            <TooltipContent class="max-w-52">
                                                Distancia máxima entre dos nodos
                                                para conectarlos. Si están más
                                                lejos que esto, la ruta no los
                                                une.
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </span>
                                <input
                                    v-model.number="graphParams.maxDistance"
                                    type="number"
                                    step="0.5"
                                    min="0"
                                    class="w-20 rounded border bg-background px-1 py-0.5 font-mono text-[11px]"
                                />
                            </label>
                            <label
                                class="flex items-center justify-between gap-2 text-xs"
                            >
                                <span
                                    class="flex items-center gap-1 text-muted-foreground"
                                >
                                    Distancia máx. Y
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <button
                                                    type="button"
                                                    class="text-muted-foreground/70 hover:text-muted-foreground"
                                                    @click.prevent
                                                >
                                                    <Info class="h-3 w-3" />
                                                </button>
                                            </TooltipTrigger>
                                            <TooltipContent class="max-w-52">
                                                Evita que la ruta salte entre
                                                pisos. Ponla menor que la altura
                                                de un piso.
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </span>
                                <input
                                    v-model.number="graphParams.maxYDistance"
                                    type="number"
                                    step="0.5"
                                    min="0"
                                    class="w-20 rounded border bg-background px-1 py-0.5 font-mono text-[11px]"
                                />
                            </label>
                            <p
                                v-if="showGraph && graphStats"
                                class="text-[11px] text-muted-foreground"
                            >
                                {{ graphStats.edges }} conexiones ·
                                <span
                                    :class="
                                        graphStats.isolated
                                            ? 'text-amber-600'
                                            : 'text-green-600'
                                    "
                                >
                                    {{ graphStats.isolated }} aislados
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Initial node heading -->
                    <div class="rounded-md border p-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium">
                                Orientación inicial
                            </span>
                            <div class="flex items-center gap-2">
                                <label
                                    class="flex items-center gap-1.5 text-[11px] text-muted-foreground"
                                >
                                    <Checkbox v-model="showHeadingArrow" />
                                    Ver flecha
                                </label>
                                <Compass
                                    class="h-4 w-4 text-muted-foreground"
                                />
                            </div>
                        </div>

                        <p
                            v-if="!initialNode"
                            class="mt-2 text-[10px] leading-relaxed text-muted-foreground"
                        >
                            Marca un nodo como inicial (★) para definir hacia
                            dónde mira al calibrar. La flecha aparecerá sobre
                            ese nodo.
                        </p>

                        <div v-else class="mt-2 space-y-2">
                            <p
                                class="text-[10px] leading-relaxed text-muted-foreground"
                            >
                                Gira la flecha naranja (sobre el nodo inicial)
                                hacia donde mira el usuario al calibrar. Se
                                guarda con los nodos.
                            </p>
                            <div class="flex items-center gap-2">
                                <Slider
                                    v-model="headingSlider"
                                    :min="0"
                                    :max="360"
                                    :step="1"
                                    class="flex-1"
                                />
                                <div class="flex items-center gap-1">
                                    <input
                                        v-model.number="heading"
                                        type="number"
                                        min="0"
                                        max="360"
                                        step="1"
                                        class="w-14 rounded border bg-background px-1 py-0.5 font-mono text-[11px]"
                                    />
                                    <span
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        °
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </PopoverContent>
            </Popover>

            <!-- Manual connections: their own panel; the list is height-limited
                 and scrolls so many rules don't push the node list down. -->
            <div class="rounded-md border p-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium">Uniones manuales</span>
                    <Waypoints class="h-4 w-4 text-muted-foreground" />
                </div>

                <p
                    class="mt-2 text-[10px] leading-relaxed text-muted-foreground"
                >
                    Clic en una conexión para cortarla (pared). Para unir dos
                    nodos que no se conectan solos (escalera), selecciona uno y
                    haz <strong>Ctrl+clic</strong> en el otro.
                </p>

                <!-- Pair picked with Ctrl+click -->
                <div
                    v-if="selectedId !== null && pairSelection !== null"
                    class="mt-2 rounded border border-dashed p-2"
                >
                    <p class="text-[11px]">
                        {{ nodeName(selectedId) }} ·
                        {{ nodeName(pairSelection) }}
                    </p>
                    <div class="mt-1.5 flex gap-1">
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            class="h-6 flex-1 text-[10px]"
                            @click="
                                setOverride(
                                    selectedId,
                                    pairSelection,
                                    'blocked',
                                )
                            "
                        >
                            Nunca unir
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            class="h-6 flex-1 text-[10px]"
                            @click="
                                setOverride(selectedId, pairSelection, 'forced')
                            "
                        >
                            Unir siempre
                        </Button>
                    </div>
                </div>

                <ul
                    v-if="localOverrides.length"
                    class="mt-2 max-h-56 space-y-1 overflow-y-auto pr-1"
                >
                    <li
                        v-for="override in localOverrides"
                        :key="pairKey(override.a, override.b)"
                        class="flex items-center justify-between gap-2 text-[11px]"
                    >
                        <span class="flex min-w-0 items-center gap-1.5">
                            <span
                                class="h-2 w-2 shrink-0 rounded-full"
                                :class="
                                    override.type === 'blocked'
                                        ? 'bg-red-500'
                                        : 'bg-amber-500'
                                "
                            />
                            <span class="truncate">
                                {{ nodeName(override.a) }} ·
                                {{ nodeName(override.b) }}
                            </span>
                        </span>
                        <button
                            type="button"
                            class="shrink-0 text-muted-foreground hover:text-destructive"
                            :title="
                                override.type === 'blocked'
                                    ? 'Quitar corte'
                                    : 'Quitar unión forzada'
                            "
                            @click="removeOverride(override.a, override.b)"
                        >
                            <Trash2 class="h-3 w-3" />
                        </button>
                    </li>
                </ul>
                <p v-else class="mt-2 text-[10px] text-muted-foreground italic">
                    Sin reglas manuales.
                </p>
            </div>

            <!-- Node config: groups are the accordion sections; each one can be
                 renamed or deleted from its header, and new groups are created
                 here. Nodes are assigned to a group from each node card. -->
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium">Configuración de nodos</p>
                <Button size="sm" variant="outline" @click="openCreateGroup">
                    <Plus class="mr-1 h-4 w-4" />
                    Agregar grupo
                </Button>
            </div>

            <div
                class="flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto pr-1"
            >
                <p
                    v-if="!localNodes.length"
                    class="text-xs text-muted-foreground"
                >
                    Aún no hay nodos. Haz click en el modelo para colocar el
                    primero.
                </p>
                <Accordion
                    v-model="openSections"
                    type="multiple"
                    class="w-full space-y-1"
                >
                    <AccordionItem
                        v-for="section in groupedNodes"
                        :key="groupKey(section.id)"
                        :value="groupKey(section.id)"
                        class="border-none"
                    >
                        <div class="flex items-center">
                            <AccordionTrigger
                                class="flex-1 rounded px-1 py-1.5 text-xs font-medium text-muted-foreground hover:bg-accent hover:no-underline"
                            >
                                <span
                                    class="flex flex-1 items-center gap-2 truncate"
                                >
                                    <span class="truncate">
                                        {{ section.name }}
                                    </span>
                                    <span
                                        class="text-[10px] text-muted-foreground/70"
                                    >
                                        {{ section.nodes.length }}
                                    </span>
                                </span>
                            </AccordionTrigger>
                            <!-- Real groups can be renamed/deleted; the virtual
                                 "Sin grupo" section (id null) cannot. -->
                            <div
                                v-if="section.id !== null"
                                class="flex items-center gap-0.5 pl-1"
                            >
                                <button
                                    type="button"
                                    class="text-muted-foreground hover:text-foreground"
                                    title="Editar grupo"
                                    @click="
                                        openEditGroup(section.id, section.name)
                                    "
                                >
                                    <Pencil class="size-3.5" />
                                </button>
                                <button
                                    type="button"
                                    class="text-destructive hover:opacity-70"
                                    title="Eliminar grupo"
                                    @click="
                                        destroyGroup(section.id, section.name)
                                    "
                                >
                                    <Trash2 class="size-3.5" />
                                </button>
                            </div>
                        </div>
                        <AccordionContent class="pb-0">
                            <div class="flex flex-col gap-2">
                                <div
                                    v-for="node in section.nodes"
                                    :key="node.localId"
                                    class="cursor-pointer rounded-md border p-2 text-xs"
                                    :class="
                                        node.localId === selectedId
                                            ? 'border-green-500 bg-green-500/5'
                                            : 'hover:border-muted-foreground/40'
                                    "
                                    @click="focusNode(node.localId)"
                                >
                                    <div
                                        class="flex items-center justify-between gap-1"
                                    >
                                        <!-- Inline name editing: text + pencil, input only while editing. -->
                                        <div
                                            v-if="
                                                editingLabelId === node.localId
                                            "
                                            class="flex flex-1 items-center gap-1"
                                        >
                                            <input
                                                :id="`node-label-${node.localId}`"
                                                v-model="node.label"
                                                type="text"
                                                placeholder="Nombre (ej. Entrada)"
                                                class="w-full rounded border bg-background px-2 py-1 text-[11px]"
                                                @click.stop
                                                @input="markUnsaved"
                                                @keyup.enter="stopEditingLabel"
                                                @keyup.esc="stopEditingLabel"
                                                @blur="stopEditingLabel"
                                            />
                                            <button
                                                class="text-green-600 hover:opacity-70"
                                                title="Listo"
                                                @click.stop="stopEditingLabel"
                                            >
                                                <Check class="size-3.5" />
                                            </button>
                                        </div>
                                        <template v-else>
                                            <button
                                                class="flex min-w-0 flex-1 items-center gap-1 truncate text-left font-medium"
                                                @click.stop="
                                                    focusNode(node.localId)
                                                "
                                            >
                                                <span class="truncate">
                                                    {{
                                                        node.label ||
                                                        `Nodo ${nodeNumber(node)}`
                                                    }}
                                                </span>
                                                <span
                                                    v-if="node.is_initial"
                                                    class="rounded bg-amber-500/20 px-1 text-amber-600"
                                                >
                                                    inicial
                                                </span>
                                            </button>
                                            <div
                                                class="flex items-center gap-1"
                                            >
                                                <button
                                                    class="text-muted-foreground hover:text-foreground"
                                                    title="Editar nombre"
                                                    @click.stop="
                                                        startEditingLabel(
                                                            node.localId,
                                                        )
                                                    "
                                                >
                                                    <Pencil class="size-3.5" />
                                                </button>
                                                <button
                                                    class="hover:text-foreground"
                                                    :class="
                                                        node.is_initial
                                                            ? 'text-amber-500'
                                                            : 'text-muted-foreground'
                                                    "
                                                    title="Marcar como inicial (calibración)"
                                                    @click.stop="
                                                        toggleInitial(
                                                            node.localId,
                                                        )
                                                    "
                                                >
                                                    ★
                                                </button>
                                                <button
                                                    class="text-destructive hover:opacity-70"
                                                    title="Eliminar"
                                                    @click.stop="
                                                        deleteNode(node.localId)
                                                    "
                                                >
                                                    ✕
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="mt-2 grid grid-cols-3 gap-1">
                                        <label
                                            v-for="axis in axes"
                                            :key="axis.key"
                                            class="flex flex-col gap-0.5"
                                        >
                                            <span
                                                class="text-[10px] font-medium text-muted-foreground"
                                            >
                                                {{ axis.label }}
                                            </span>
                                            <input
                                                type="number"
                                                step="0.01"
                                                :value="
                                                    node[axis.key].toFixed(3)
                                                "
                                                class="w-full rounded border bg-background px-1 py-0.5 font-mono text-[11px]"
                                                @click.stop
                                                @change="
                                                    setNodeAxis(
                                                        node.localId,
                                                        axis.key,
                                                        parseFloat(
                                                            (
                                                                $event.target as HTMLInputElement
                                                            ).value,
                                                        ),
                                                    )
                                                "
                                            />
                                        </label>
                                    </div>

                                    <!-- Group assignment: a native select is used
                                         instead of the reka-ui one because a
                                         section may render 100+ node cards at
                                         once, and 100+ heavy selects are slow to
                                         mount. -->
                                    <div
                                        v-if="nodeGroups.length"
                                        class="mt-2 flex items-center gap-2 text-[11px]"
                                        @click.stop
                                    >
                                        <span class="text-muted-foreground"
                                            >Grupo</span
                                        >
                                        <select
                                            class="h-6 flex-1 rounded border bg-background px-1 text-[11px]"
                                            :value="
                                                node.node_group_id
                                                    ? String(node.node_group_id)
                                                    : NO_GROUP
                                            "
                                            @click.stop
                                            @change="
                                                setNodeGroup(
                                                    node.localId,
                                                    (
                                                        $event.target as HTMLSelectElement
                                                    ).value,
                                                )
                                            "
                                        >
                                            <option :value="NO_GROUP">
                                                Sin grupo
                                            </option>
                                            <option
                                                v-for="group in nodeGroups"
                                                :key="group.id"
                                                :value="String(group.id)"
                                            >
                                                {{ group.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </AccordionContent>
                    </AccordionItem>
                </Accordion>
            </div>
        </div>
    </div>

    <!-- Group create/edit dialog, opened from the node accordion. -->
    <Dialog v-model:open="groupDialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {{
                        editingGroupId !== null
                            ? 'Editar grupo'
                            : 'Agregar grupo'
                    }}
                </DialogTitle>
                <DialogDescription>
                    Ponle un nombre al grupo (ej. "Piso 1", "Sótano", "Zona A").
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submitGroup">
                <div class="grid gap-2">
                    <Label>Nombre</Label>
                    <Input
                        v-model="groupForm.name"
                        required
                        placeholder="Ej. Primer piso"
                    />
                    <InputError :message="groupForm.errors.name" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="ghost"
                        @click="groupDialogOpen = false"
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="groupForm.processing">
                        {{ editingGroupId !== null ? 'Guardar' : 'Agregar' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
