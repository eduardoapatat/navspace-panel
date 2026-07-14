export interface PathfindingConfig {
    max_distance: number;
    max_y_distance: number;
    thick_radius: number;
    check_walls: boolean;
}

export interface Building {
    id: number;
    name: string;
    address: string;
    image_url: string | null;
    model_url: string | null;
    // Yaw correction (deg) Unity applies to the model; auto-set from the exporter
    // on upload (Blender glb import 180° rotated), overridable for other tools.
    model_yaw: number;
    pathfinding_config: PathfindingConfig | null;
    created_at: string;
    node_groups?: NodeGroup[];
    nodes?: Node[];
    space_types?: SpaceType[];
    destinations?: Destination[];
}

// Optional label used to group a building's nodes/destinations ("Piso 1", "Sótano").
export interface NodeGroup {
    id: number;
    building_id: number;
    name: string;
}

export interface Node {
    id: number;
    building_id: number;
    node_group_id: number | null;
    label: string | null;
    position_x: number;
    position_y: number;
    position_z: number;
    is_initial: boolean;
    heading: number | null;
}

export interface SpaceType {
    id: number;
    building_id: number;
    name: string;
    icon: string | null;
}

export interface Destination {
    id: number;
    building_id: number;
    space_type_id: number;
    node_id: number | null;
    display_name: string;
    description: string | null;
    // Fixed type: academic (shows a PDF) or attention (text schedule + contact).
    type: 'attention' | 'academic';
    schedule_file: string | null;
    schedule_text: string | null;
    responsible_name: string | null;
    responsible_phone: string | null;
    responsible_email: string | null;
    // URL of the responsible person's photo (passport style, 3:4), shown in Unity.
    responsible_photo: string | null;
    // Relations serialize to snake_case in Inertia's JSON.
    space_type?: SpaceType;
    node?: Node | null;
}

export interface ScheduleTypeOption {
    value: string;
    label: string;
}
