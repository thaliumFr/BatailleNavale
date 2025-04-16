<?php
session_start();

/*
 - Nomenclature :
0 - No boat
1 - boat
2 - missed
3 - touched
4 - drowned (whole boat)

note : on aurait probablement du utiliser un enum mais c'etait plus simple pour commencer avec des id hardcodés
*/

// INITIATION
$Size = 10;
$GridA = isset($_SESSION['A']) ? json_decode($_SESSION['A'], true) : [];
$GridB = isset($_SESSION['B']) ? json_decode($_SESSION['B'], true) : [];

$debug = false;

$isTurnToB = isset($_SESSION['isTurnToB']) ? $_SESSION['isTurnToB'] : false;

enum GameStates
{
    case PlaceBoatsA;
    case PlaceBoatsB;
    case Shootings;
    case Ending;
}

$GameState = isset($_SESSION['GameState']) ? unserialize($_SESSION['GameState']) : GameStates::PlaceBoatsA;

enum BoatType
{
    case PorteAvion;
    case Cuirassé;
    case SousMarin;
    case Torpilleur;
}

class Boat{
    public array $Cells;
    public BoatType $type;

    public function __construct(BoatType $type = null) {
        $this->type = $type;
    }

    public function lenght() : int {
        switch ($this->type) {
            case BoatType::PorteAvion:
                return 5;
                break;
            
            case BoatType::Cuirassé:
                return 4;
                break;
            
            case BoatType::SousMarin:
                return 3;
                break;

            case BoatType::Torpilleur:
                return 2;
                break;
        }
    }

    public static function GetLenght(BoatType $type) : int {
        switch ($type) {
            case BoatType::PorteAvion:
                return 5;
                break;
            
            case BoatType::Cuirassé:
                return 4;
                break;
            
            case BoatType::SousMarin:
                return 3;
                break;

            case BoatType::Torpilleur:
                return 2;
                break;
            
            default:
                return 0;
                break;
        }
    }

    public static function GetBoat(string $key) : BoatType{
        switch ($key) {
            case "porte-avion":
                return BoatType::PorteAvion;
                break;
            
            case "cuirassé":
                return BoatType::Cuirassé;
                break;
            
            case "sous-marin":
                return BoatType::SousMarin;
                break;

            case "torpilleur":
                return BoatType::Torpilleur;
                break;
        }
    }

    public function __toString(){
        switch ($this->type) {
            case BoatType::PorteAvion:
                return "porte-avion";
                break;
            
            case BoatType::Cuirassé:
                return "cuirassé";
                break;
            
            case BoatType::SousMarin:
                return "sous-marin";
                break;

            case BoatType::Torpilleur:
                return "torpilleur";
                break;
        }
    }
}

$BoatsToPlaceTemplate = [
    "porte-avion" => 1,
    "cuirassé" => 1,
    "sous-marin" => 2,
    "torpilleur" => 1
];

// Bateaux placés respectivement par A et B sur leur propre grille
$BoatsA = isset($_SESSION['BoatsA']) ? unserialize($_SESSION['BoatsA']) : [];
$BoatsB = isset($_SESSION['BoatsB']) ? unserialize($_SESSION['BoatsB']) : [];
$BoatsToPlace = isset($_SESSION['BoatsToPlace']) ? $_SESSION['BoatsToPlace'] : $BoatsToPlaceTemplate;

function resetGrid($isB) {
    global $GridA, $GridB, $Size;

    for ($x=0; $x < $Size; $x++) { 
        for ($y=0; $y < $Size; $y++) { 
            if($isB){
                $GridB[$x][$y] = 0;
            }else {
                $GridA[$x][$y] = 0;
            }
            
        }
    }
}

/**reinitialise tout jeu et supprime la mémoire en détruisant la session actuelle */
function resetGame(){
    global $GameState, $BoatsToPlace, $BoatsToPlaceTemplate;
    resetGrid(true);
    resetGrid(false);
    $GameState = GameStates::PlaceBoatsA;
    $BoatsToPlace = $BoatsToPlaceTemplate;
    unset($_GET['reset']);
    session_destroy();
}

/**[deprecated] utilisé uniquement pour du débug quand la grille était en cours de developpement*/
function LogGrid( $isB) {
    global $GridA, $GridB, $Size;
    for ($x=0; $x < $Size; $x++) { 
        $line = "";
        for ($y=0; $y < $Size; $y++) { 
            $line = $line.$GridA[$x][$y];
        }
        
        echo "<div>".$line."</div>";
    }
}

/**
 * Transforme les coordonnées d'une cellule en son identifiant sur la grille
 * Retourne un int
 */
function GetId(int $x, int $y) {
    global $Size;
    return ($Size*$x+$y);
}

/**
 * Transforme l'identifiant d'une cellule en coordonnées
 * Retourne une array avec le x et le y
 */
function IDtoCoord($id) {
    global $Size;
    $y = $id%$Size;
    $x = ($id-$y)/$Size;
    return [$x,$y];
}

function Clamp($var, $min, $max) : float{
    return min(max($var, $min), $max);
}

/**
 * just shot at a cell
 */
function ShotAtCell($x, $y, $ShotAtGridB) {
    global $GridA, $GridB, $BoatsA, $BoatsB, $Size, $isTurnToB, $GameState;
    
    $x = Clamp($x, 0, $Size-1);
    $y = Clamp($y, 0, $Size-1);

    if ($ShotAtGridB) {
        $cell = $GridB[$x][$y];

        if($cell == 0){
            $GridB[$x][$y] = 2;
        }
        if($cell == 1){
            $GridB[$x][$y] = 3;

            foreach ($BoatsB as $key => $boat) {

                /**
                 * @var Boat
                 */
                $boat = $boat;
                if (in_array(GetId($x, $y), $boat->Cells)) {
                    $isDrowned = true;
                    foreach ($boat->Cells as $key => $id) {
                        $coord = IDtoCoord($id);
                        if($GridB[$coord[0]][$coord[1]] != 3) $isDrowned = false;
                    }

                    if($isDrowned){
                        foreach ($boat->Cells as $key => $id) {
                            $coord = IDtoCoord($id);
                            $GridB[$coord[0]][$coord[1]] = 4;
                        }
                    }
                }
            }
        }
        if ($cell == 0 || $cell == 1) $isTurnToB = !$isTurnToB;
    }
    else {
        $cell = $GridA[$x][$y];

        if($cell == 0){
            $GridA[$x][$y] = 2;
        }
        if($cell == 1){
            $GridA[$x][$y] = 3;

            foreach ($BoatsA as $key => $boat) {

                /**
                 * @var Boat
                 */
                $boat = $boat;
                if (in_array(GetId($x, $y), $boat->Cells)) {
                    $isDrowned = true;
                    foreach ($boat->Cells as $key => $id) {
                        $coord = IDtoCoord($id);
                        if($GridA[$coord[0]][$coord[1]] != 3) $isDrowned = false;
                    }

                    if($isDrowned){
                        foreach ($boat->Cells as $key => $id) {
                            $coord = IDtoCoord($id);
                            $GridA[$coord[0]][$coord[1]] = 4;
                        }
                    }
                }
            }
        }
        if ($cell == 0 || $cell == 1) $isTurnToB = !$isTurnToB;

        // vérifie si quelqu'un à gagné 

        $isACleared = true;
        foreach ($GridA as $key => $line) {
            if(in_array(1, $line)) $isACleared = false;
        }
        if($isACleared){
            $GameState = GameStates::Ending;
            $_SESSION['winner'] = "B";
        }

        $isBCleared = true;
        foreach ($GridB as $key => $line) {
            if(in_array(1, $line)) $isBCleared = false;
        }
        if($isBCleared){
            $GameState = GameStates::Ending;
            $_SESSION['winner'] = "A";
        }
    }
}

/**
 * Fonction qui génère la grille et attribue automatiquement la bonne classe à chaque cellule
 */
function GenerateGrid($isB) {
    global $GridA, $GridB, $Size, $GameState;
    $isBlack = false;
    for ($x=0; $x < $Size; $x++) { 
        $line = "";
        if($Size%2 == 0){
            $isBlack = !$isBlack;
        }
        for ($y=0; $y < $Size; $y++) { 

            $cell = $isB ? $GridB[$x][$y] : $GridA[$x][$y];
            $classes = "cell ";
            if($isBlack){
                $classes .= "dark ";
            }
            if ($cell == 1 && ($GameState == GameStates::PlaceBoatsA || $GameState == GameStates::PlaceBoatsB) ) {
                $classes .= "boat ";
            }
            if ($cell == 2) {
                $classes .= "missed ";
            }
            if ($cell == 3) {
                $classes .= "touched ";
            }
            if ($cell == 4) {
                $classes .= "drowned ";
            }
            $isBlack = !$isBlack;
            
            //Une foix qu'une case est touchée, on ne peut plus tirer sur cette case
            $line .= '<th class="'.$classes.'">';
            if ($cell == 0 || $cell == 1){
                $line .=  '<input type="submit" name="id" value="'.getID($x, $y).'">';
            }
            $line .= '</th>';
        }
        
        echo "<tr>".$line."</tr>";
    }
}


/** Sert à placer un navire sur la grille lors des 2 première phases */
function PlacerBateaux(int $x, int $y, BoatType $boat, bool $isVertical, bool $isB) {
    global $GridA, $GridB, $Size, $BoatsA, $BoatsB, $BoatsToPlace;
    
    $lenght = Boat::GetLenght($boat);


    // Avoid placing a boat out of the grid
    if($isVertical){
        $x = Clamp($x, 0, $Size-$lenght);
    }
    else {
        $y = Clamp($y, 0, $Size-$lenght);
    }
    
    if (CanPlaceBoat($x, $y, $lenght, $isVertical, $isB)) {
        $boat = new Boat($boat);

        for ($i=0; $i < $lenght; $i++) { 
            if ($isVertical) {
                $boat->Cells[$i] = GetId($x+$i, $y);
                if ($isB) {
                    $GridB[$x+$i][$y] = 1;
                }
                else {
                    $GridA[$x+$i][$y] = 1;
                }
            }else {
                if ($isB) {
                    $GridB[$x][$y+$i] = 1;
                }
                else {
                    $GridA[$x][$y+$i] = 1;
                }
                $boat->Cells[$i] = GetId($x, $y+$i);
            }
        }

        $BoatsToPlace[(string)$boat] -= 1;
        if ($isB){
            $BoatsB[count($BoatsB)] = $boat;
        }
        else {
            $BoatsA[count($BoatsA)] = $boat;
        }
    }
}

function CanPlaceBoat($x, $y, $lenght, $isVertical, $isB) {
    global $GridA, $GridB;
    if ($isB) {
        for ($i=0; $i < $lenght; $i++) { 
            if ($isVertical) {
                if($GridB[$x+$i][$y] != 0 ) return false;
            }else {
                if($GridB[$x][$y+$i] != 0 ) return false;
            }
        }
    }
    else {
        for ($i=0; $i < $lenght; $i++) { 
            if ($isVertical) {
                if($GridA[$x+$i][$y] != 0 ) return false;
            }else {
                if($GridA[$x][$y+$i] != 0 ) return false;
            }
        }
    }
    
    return true;
}

function GameStatePlaceBoats(bool $isB){    
    if (isset($_GET['id'])){
        $Coord = IDtoCoord($_GET['id']);
        $isVertical = $_GET['rotation'] == "vertical";
        PlacerBateaux($Coord[0], $Coord[1], Boat::GetBoat($_GET['boat']), $isVertical, $isB);
    }
}

function GameStateShootings(){
    global $isTurnToB;

    if (isset($_GET['id'])){
        $Coord = IDtoCoord($_GET['id']);
        ShotAtCell($Coord[0], $Coord[1], !$isTurnToB);
    }
}

/**Actualise le jeu à chaque tour */
function Update() {
    global $GridA, $GridB, $BoatsToPlace, $BoatsToPlaceTemplate, $GameState;

    if ($GridA == [] || $GridA == null) resetGrid(false);
    if ($GridB == [] || $GridB == null) resetGrid(true);
    if (isset($_GET['reset'])) resetGame();

    if($GameState == GameStates::PlaceBoatsA) GameStatePlaceBoats(false);
    if($GameState == GameStates::PlaceBoatsB) GameStatePlaceBoats(true);
    if($GameState == GameStates::Shootings) GameStateShootings();

    if (array_sum($BoatsToPlace) == 0) {
        $BoatsToPlace = $BoatsToPlaceTemplate;
        if($GameState == GameStates::PlaceBoatsB) $GameState = GameStates::Shootings;
        if($GameState == GameStates::PlaceBoatsA) $GameState = GameStates::PlaceBoatsB;
    }
}

/**S'éffectue après tout le reste et sert simplement à sauvegarder toutes les valeurs (et donne aussi des infos de debug en bas de page si le $debug est sur true) */
function After() {
    global $GridA, $GridB, $BoatsToPlace, $BoatsA, $BoatsB, $GameState, $debug, $isTurnToB;
    $_SESSION['A'] = json_encode($GridA);
    $_SESSION['B'] = json_encode($GridB);
    $_SESSION['BoatsA'] = serialize($BoatsA);
    $_SESSION['BoatsB'] = serialize($BoatsB);
    $_SESSION['BoatsToPlace'] = $BoatsToPlace;
    $_SESSION['isTurnToB'] = $isTurnToB;
    $_SESSION['GameState'] = serialize($GameState);

    if ($debug){
        var_dump($BoatsToPlace);
        $txt = var_export($GameState, true);
        echo("title: ".explode("GameStates::", (string)$txt ?? '')[1]);
        var_dump($isTurnToB);
        var_dump($BoatsA);
        var_dump($BoatsB);
    }
}

Update();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bataille Navale</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h3>Bataille Navale</h3>
        <h2><?php
            if($GameState == GameStates::PlaceBoatsA) echo "Placez vous navires (player A)";
            if($GameState == GameStates::PlaceBoatsB) echo "Placez vous navires (player B)";
            if($GameState == GameStates::Shootings) echo "Tire (player ".($isTurnToB ? "B" : "A").")";
        ?></h2>
    </header>
        <?php if($GameState != GameStates::Ending){ ?>
        <form method="get">
            <section class="selects">
                <?php if ($GameState == GameStates::PlaceBoatsA || $GameState == GameStates::PlaceBoatsB ) { ?>        
                <!-- pour pouvoir sélectionner les bateaux -->
                <select name="boat" id="boat">
                    <?php
                        foreach ($BoatsToPlace as $key => $value) {
                            if($value > 0) echo("<option value=".$key.">".$key. " ( x".$value." )</option>");
                        }
                    ?>
                </select>
                <!-- pour pouvoir sélectionner la direction -->
                <select name="rotation" id="rotation">
                    <option value="vertical">vertical</option>
                    <option value="horizontal">horizontal</option>
                </select>
                <?php } else { ?> 
                    <div class="colors">
                        <p>manqué</p><div class="cell missed"></div>
                        <p>touché</p><div class="cell touched"></div>
                        <p>coulé</p><div class="cell drowned"></div>
                    </div>
                <?php } ?>  
                <input type="submit" name="reset" value="reset">
            </section>
            <section>
                <table <?php if($GameState == GameStates::Shootings) echo 'class="shooting"'; ?>>
                    <?php 
                        if($GameState == GameStates::PlaceBoatsA) GenerateGrid(false);
                        if($GameState == GameStates::PlaceBoatsB) GenerateGrid(true);
                        if($GameState == GameStates::Shootings) GenerateGrid(!$isTurnToB);
                    ?>
                </table>
            </section>
        </form>
        <?php } else { ?>
            <h2>Bien joué! Le joueur <?php echo $_SESSION['winner']; ?> à gagné! Voulez-vous recommencer ?</h2>
            <form method="get">
                <input type="submit" name="reset" value="reset">
            </form>
        <?php } ?> 
</body>
</html>

<?php After(); ?>