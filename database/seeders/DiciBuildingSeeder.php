<?php

namespace Database\Seeders;

use App\Models\Building;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Seeds the DICI building (Departamento de Informática) with its node groups,
 * space types, every navigation node (including the initial/calibration node)
 * and its destinations, wiring each destination to its space type and node.
 *
 * The building picture and the .glb model ship with the seeder so a fresh
 * install is navigable right away. Schedule images and responsible photos are
 * still uploaded from the panel.
 *
 * Run: php artisan db:seed --class=DiciBuildingSeeder
 */
class DiciBuildingSeeder extends Seeder
{
    public function run(): void
    {
        $building = Building::create([
            'name' => 'Departamento Informática',
            'address' => 'Av. General Velásquez 1775, Arica',
            'image_path' => $this->storeAsset('dici.jpg', 'buildings'),
            'model_path' => $this->storeAsset('dici.glb', 'models'),
            // Fixed instead of auto-detected: this is the value the headset was
            // calibrated against.
            'model_yaw' => 180,
            'pathfinding_config' => [
                'max_distance' => 2,
                'max_y_distance' => 1,
                'thick_radius' => 0,
                'check_walls' => false,
            ],
        ]);

        // Node groups (original id => name); keep a map to the new ids.
        $groupNames = [
            1 => 'Primer piso Alumnos',
            2 => 'Segundo Piso Alumnos',
            3 => 'Segundo Piso Académicos',
            4 => 'Tercer Piso Alumnos',
        ];
        $groups = [];
        foreach ($groupNames as $oldId => $name) {
            $groups[$oldId] = $building->nodeGroups()->create(['name' => $name])->id;
        }

        // Space types (original id => name).
        $spaceTypeNames = [
            1 => 'Salas',
            3 => 'Oficinas',
        ];
        $spaceTypes = [];
        foreach ($spaceTypeNames as $oldId => $name) {
            $spaceTypes[$oldId] = $building->spaceTypes()->create(['name' => $name])->id;
        }

        // Nodes: [oldId, label, x, y, z, is_initial, heading, oldGroupId].
        $nodeData = [
            [49, null, 0, 0.3, 0, true, 180, null],
            [50, null, 0, 0.3, 1.7041168523426, false, null, null],
            [51, null, 0, 0.3, 3.2918793081842, false, null, null],
            [52, null, 1.4300126391144, 0.3, 3.3654471204607, false, null, null],
            [53, null, 2.179634140603, 0.3, 4.4936745506292, false, null, null],
            [54, null, 2.2396133416737, 0.3, 5.9864449066434, false, null, null],
            [55, null, 4.624197882477, 0.3, 6.4155087294901, false, null, null],
            [56, 'Licancabur', 6.0865453111029, 0.3, 6.3784238389144, false, null, 1],
            [57, 'Socompa', 6.1387929199292, 0.32234520328262, 7.7936628931091, false, null, 1],
            [58, null, 4.6685723117603, 0.3, 7.7475804631608, false, null, null],
            [59, null, 3.2477550802762, 0.3, 6.3736642785265, false, null, null],
            [60, null, 3.2662899389692, 0.3, 7.649880082344, false, null, null],
            [61, null, 1.5592512679487, 0.3, 7.2846959237739, false, null, null],
            [62, null, 0.80409014756679, 0.3, 5.9137305665663, false, null, null],
            [63, null, 0.44636549970904, 0.3, 7.2801241128601, false, null, null],
            [64, null, -0.74328099793044, 0.3, 7.4579301728242, false, null, null],
            [65, null, 0, 0.3, 4.9102162527762, false, null, null],
            [66, null, 0, 0.3, 6.2894431306659, false, null, null],
            [67, null, 2.0301980295531, 0.3, 1.8665045912106, false, null, null],
            [68, null, 0.86714731272015, 0.3, 2.5500345705167, false, null, null],
            [69, null, 2.7871515034954, 0.3, 1.9815916888699, false, null, null],
            [70, null, 2.2690439091281, 0.3, 3.4081296776266, false, null, null],
            [71, null, 0.47289881547516, 0.40166308055355, 3.9665010154687, false, null, null],
            [72, null, 6.1410903530806, 0.3, 5.0728618866185, false, null, null],
            [73, null, 6.12721566079, 1.0589322034975, 4.0128646940031, false, null, null],
            [74, null, 6.1565365185049, 1.7877763296446, 2.4127426533207, false, null, null],
            [75, null, 5.5580914893003, 1.7912694865832, 1.8603404956448, false, null, null],
            [76, null, 4.3088225206547, 2.4093947287509, 1.7958076021444, false, null, null],
            [77, null, 3.0192217974986, 3.2097707156643, 1.9086389561907, false, null, null],
            [78, null, 2.062326886764, 3.2097707156643, 2.3991431026319, false, null, null],
            [79, null, 2.0998423710313, 3.2097707156643, 3.785142910377, false, null, null],
            [80, null, 2.0892733063096, 3.2097707156643, 5.1478826213728, false, null, null],
            [81, null, 2.3286769032045, 3.2097707156643, 6.2259127244056, false, null, null],
            [82, null, 3.5716560641037, 3.2097707156643, 6.3290244959368, false, null, null],
            [83, null, 4.7234810605482, 3.2097707156643, 6.3898156601113, false, null, null],
            [84, 'Guallatire', 6.1734200829256, 3.2097707156643, 6.3941634123293, false, null, 2],
            [391, null, 1.9357260078005, 3.2097707156643, 7.108921856674, false, null, null],
            [392, null, 1.6753485179311, 3.2097707156643, 8.236341891624, false, null, null],
            [393, null, 3.5608587863932, 3.2097707156643, 7.3753434128509, false, null, null],
            [394, null, 4.7308383175021, 3.2097707156643, 7.447524253724, false, null, null],
            [395, 'Azufre', 5.9751111320473, 3.2097707156643, 7.8462335181334, false, null, 2],
            [396, null, 0.89176991432976, 3.2097707156643, 6.0243444273759, false, null, null],
            [397, null, -0.46747985766998, 3.2097707156643, 7.3921202790681, false, null, null],
            [398, null, 0.80678972787051, 3.2097707156643, 7.4557955377399, false, null, null],
            [399, null, -2.3542017372284, 3.21, 7.3725038187921, false, null, null],
            [400, null, -3.3024375321336, 3.21, 7.3554718162379, false, null, null],
            [401, null, -3.8229610019264, 3.21, 6.3334282511179, false, null, null],
            [402, null, -5.3337855183467, 3.21, 5.7442274533509, false, null, null],
            [403, 'Oficina Aracena', -3.3779468136934, 3.21, 4.8397503842407, false, null, 3],
            [404, null, -4.6248705030584, 3.21, 5.1965050570224, false, null, null],
            [405, 'Oficina Sanchez', -5.9538492886389, 3.21, 4.8105928610579, false, null, 3],
            [406, null, -4.6614583854089, 3.21, 7.2676528933078, false, null, null],
            [407, 'Oficina Espinosa', -3.3706653351398, 3.21, 10.133501450149, false, null, 3],
            [408, 'Oficina Arriagada', -5.9350890274983, 3.21, 10.127724064173, false, null, null],
            [409, 'Oficina Herrera', -6.7847154136436, 3.21, 10.224546093139, false, null, 3],
            [410, 'Oficina Ossandon', -9.2378170835459, 3.21, 10.297026891295, false, null, 3],
            [411, 'Oficina Ramirez', -9.6499047668918, 3.21, 9.9456527963672, false, null, 3],
            [412, null, -9.2098871315158, 3.21, 8.4406758328972, false, null, null],
            [413, null, -9.1753164221715, 3.21, 6.8217586256458, false, null, null],
            [414, 'Oficina Valdivia', -9.4821161102813, 3.21, 4.9584491044683, false, null, 3],
            [415, null, -7.9092767863637, 3.21, 5.0796529009258, false, null, null],
            [416, null, -6.9356736609255, 3.21, 4.7978872071607, false, null, null],
            [417, null, -9.1663572592682, 3.21, 4.5400310465583, false, null, null],
            [418, null, -7.9363317926251, 3.21, 10.061640113778, false, null, null],
            [419, null, -4.7892698626127, 3.21, 10.094294227002, false, null, null],
            [420, null, -3.2753250512364, 3.21, 8.7103637867665, false, null, null],
            [421, null, -4.1799528706547, 3.21, 8.7574204014975, false, null, null],
            [422, null, -4.7979613795716, 3.21, 6.3455655368728, false, null, null],
            [423, null, -5.247410326543, 3.21, 8.2785367808658, false, null, null],
            [424, null, -5.5949642888408, 3.21, 7.2431138769232, false, null, null],
            [425, null, -5.5767574489953, 3.21, 9.2267407264361, false, null, null],
            [426, null, -1.7981454287978, 0.3, 7.4929645473395, false, null, null],
            [427, null, -3.2638748896595, 0.3, 7.4317193507327, false, null, null],
            [428, null, -3.4917703412402, 0.3, 8.9211043501164, false, null, null],
            [429, null, -5.8105077366752, 0.3, 9.9373244803386, false, null, null],
            [430, null, -4.2698528666939, 0.3, 9.5701323522734, false, null, null],
            [431, null, -5.0381951920967, 0.3, 8.3585267020403, false, null, null],
            [432, null, -4.4553190697949, 0.3, 7.318715647857, false, null, null],
            [433, null, -3.6796158973716, 0.3, 5.9309555483079, false, null, null],
            [434, null, -2.7735100678316, 0.3, 8.1250906063261, false, null, null],
            [435, null, -2.816066626411, 0.3, 6.5895414743303, false, null, null],
            [436, null, -6.9917638566679, 0.3, 9.9545927126519, false, null, null],
            [437, null, -7.7275405972242, 0.3, 9.0562327470868, false, null, null],
            [438, null, -8.8578843092762, 0.3, 9.4597006564013, false, null, null],
            [439, null, -6.970587123282, 0.3, 8.4543412121766, false, null, null],
            [440, null, -6.2136336493397, 0.3, 7.4798772703406, false, null, null],
            [441, null, -5.7096298955738, 0.3, 4.9398159506726, false, null, null],
            [442, null, -4.3318917110399, 0.3, 5.2622479625638, false, null, null],
            [443, null, -6.6077882879928, 0.3, 4.94, false, null, null],
            [444, null, -8.3782054330796, 0.3, 8.0180782587412, false, null, null],
            [445, null, -9.7844856502275, 0.3, 4.94, false, null, null],
            [446, null, -5.0361139082738, 0.3, 6.3086196594795, false, null, null],
            [447, null, -6.1019319052511, 0.3, 6.3263425950834, false, null, null],
            [448, null, 6.1524681063296, 3.21, 5.0675759234215, false, null, null],
            [449, null, 6.2044854681263, 3.9489097508592, 3.754973770355, false, null, null],
            [450, null, 6.1323388842908, 4.4438992467404, 2.0064333947612, false, null, null],
            [451, null, 4.6623480788861, 5.0986776329387, 1.9470252174285, false, null, null],
            [452, null, 3.5646471001426, 5.834979026612, 1.9280445341886, false, null, null],
            [453, null, 2.5351973687836, 6, 1.9395800019848, false, null, null],
            [454, null, 2.0059817681228, 6, 2.9647015893914, false, null, null],
            [455, null, 2.0138976267023, 6, 4.2241321154413, false, null, null],
            [456, null, 2.0506705394204, 6, 5.4049194510657, false, null, null],
            [457, null, 2.8076240133626, 6, 6.1618729250079, false, null, null],
            [458, null, 3.9596589666578, 6, 6.30374878469, false, null, null],
            [459, 'Parinacota', 6.1498152893992, 6, 6.3430491421664, false, null, 4],
            [460, 'Pomerape', 6.1003676250404, 6, 7.7793859807736, false, null, 4],
            [461, null, 4.8987759601852, 6, 7.3330592405062, false, null, null],
            [462, null, 4.9778934089493, 6, 6.3141269150916, false, null, null],
            [463, null, 3.2850118887909, 6, 7.5685872009182, false, null, null],
            [464, 'Sala Conferencias', 1.6988556658423, 6, 8.1639284876526, false, null, null],
            [465, null, 2.0104859891787, 6, 6.8303175664873, false, null, null],
            [466, null, 0.2145952368275, 6, 7.5872710404296, false, null, null],
            [467, null, 0.46959106002838, 6, 6.4664149782931, false, null, null],
            [468, null, 0.36829351090644, 6, 5.5466694577835, false, null, null],
            [469, 'Baños', 1.7732296398812, 3.2097707156643, 1.5597508294341, false, null, null],
        ];
        $nodes = [];
        foreach ($nodeData as [$oldId, $label, $x, $y, $z, $isInitial, $heading, $oldGroup]) {
            $nodes[$oldId] = $building->nodes()->create([
                'label' => $label,
                'position_x' => $x,
                'position_y' => $y,
                'position_z' => $z,
                'is_initial' => $isInitial,
                'heading' => $heading,
                'node_group_id' => $oldGroup !== null ? $groups[$oldGroup] : null,
            ])->id;
        }

        // Destinations: [name, description, type, schedule_text, respName,
        // respPhone, respEmail, oldSpaceTypeId, oldNodeId].
        $destData = [
            ['Licancabur', null, 'academic', null, null, null, null, 1, 56],
            ['Socompa', null, 'academic', null, null, null, null, 1, 57],
            ['Guallatire', '3 meses
1 data', 'academic', null, null, null, null, 1, 84],
            ['Parinacota', null, 'academic', null, null, null, null, 1, 459],
            ['Azufre', null, 'academic', null, null, null, null, 1, 395],
            ['Pomerape', null, 'academic', null, null, null, null, 1, 460],
            ['Oficina Raúl Herrera', null, 'attention', null, null, null, null, 3, 409],
            ['Oficina Ricardo Valdivia', null, 'attention', null, null, null, null, 3, 414],
            ['Oficina Ibar Ramírez', null, 'attention', null, null, null, null, 3, 411],
        ];
        foreach ($destData as [$name, $desc, $type, $sched, $rName, $rPhone, $rEmail, $oldSt, $oldNode]) {
            // A destination never mixes both cases: academic keeps only the
            // schedule image (uploaded later), attention keeps the text schedule
            // and the responsible person.
            $isAcademic = $type === 'academic';
            $building->destinations()->create([
                'display_name' => $name,
                'description' => $desc,
                'type' => $type,
                'schedule_text' => $isAcademic ? null : $sched,
                'responsible_name' => $isAcademic ? null : $rName,
                'responsible_phone' => $isAcademic ? null : $rPhone,
                'responsible_email' => $isAcademic ? null : $rEmail,
                'space_type_id' => $spaceTypes[$oldSt],
                'node_id' => $nodes[$oldNode],
            ]);
        }
    }

    /**
     * Copies a file bundled with the seeder into the public disk, mirroring
     * where the panel stores its uploads, and returns the stored path.
     */
    private function storeAsset(string $filename, string $folder): string
    {
        $path = Storage::disk('public')->putFileAs(
            $folder,
            new File(database_path("seeders/assets/{$filename}")),
            $filename,
        );

        if ($path === false) {
            throw new RuntimeException("Unable to copy the seeded asset {$filename} to the public disk.");
        }

        return $path;
    }
}
