<?php
session_start();
/*
 - Nomenclature :
0 - No boat
1 - boat
2 - missed
3 - touched
*/

// INITIATION
$Size = 10;
$GridA = isset($_SESSION['A']) ? $_SESSION['A'] : [];
$GridB = isset($_SESSION['B']) ? $_SESSION['B'] : [];

$IsInitPhase = true;
$isTurnToB = false;

$BoatsToPlace = [
    "porte avion" => 1,
    "cuirrassé" => 1,
    "sous-marin" => 2,
    "torpilleur" => 1
];

$boats = [
    "porte avion" => 5,
    "cuirrassé" => 4,
    "sous-marin" => 3,
    "torpilleur" => 2
];

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

function GetId($x, $y) {
    global $Size;
    return ($Size*$x+$y);
}

function Clamp($var, $min, $max) : float{
    return min(max($var, $min), $max);
}

/**
 * just shot at a cell
 */
function ShotAtCell($x, $y) {
    global $GridA, $Size;
    
    $x = Clamp($x, 0, $Size-1);
    $y = Clamp($y, 0, $Size-1);
    $cell = $GridA[$x][$y];

    if($cell == 0){
        $GridA[$x][$y] = 2;
    }
    if($cell == 1){
        $GridA[$x][$y] = 3;
    }
}

/**
 * Fonction qui génère la grille et attribue automatiquement la bonne classe à chaque cellule
 */
function GenerateGridA() {
    global $GridA, $Size;
    $isBlack = false;
    for ($x=0; $x < $Size; $x++) { 
        $line = "";
        if($Size%2 == 0){
            $isBlack = !$isBlack;
        }
        for ($y=0; $y < $Size; $y++) { 

            $cell = $GridA[$x][$y];
            $classes = "cell ";
            if($isBlack){
                $classes .= "dark ";
            }
            if ($cell == 1) {
                $classes .= "boat ";
            }
            if ($cell == 2) {
                $classes .= "missed ";
            }
            if ($cell == 3) {
                $classes .= "touched ";
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

//Calcule les coordonnées
function IDtoCoord($id) {
    global $Size;
    $y = $id%$Size;
    $x = ($id-$y)/$Size;
    return [$x,$y];
}

function PlacerBateaux($x, $y, $lenght, $isVertical, $isB) {
    global $GridA, $GridB, $Size;
    
    if($isVertical){
        $x = Clamp($x, 0, $Size-$lenght);
    }
    else {
        $y = Clamp($y, 0, $Size-$lenght);
    }
    
    if (CanPlaceBoat($x, $y, $lenght, $isVertical, $isB)) {
        for ($i=0; $i < $lenght; $i++) { 
            if ($isVertical) {
                $GridA[$x+$i][$y] = 1;
            }else {
                $GridA[$x][$y+$i] = 1;
            }
        }
    }
    
}

function CanPlaceBoat($x, $y, $lenght, $isVertical, $isB) {
    global $GridA, $GridB, $Size;
    for ($i=0; $i < $lenght; $i++) { 
        if ($isVertical) {
            if($GridA[$x+$i][$y] != 0 ) return false;
        }else {
            if($GridA[$x][$y+$i] != 0 ) return false;
        }
    }
    
    return true;
}

function Update() {
    global $IsInitPhase, $boats, $GridA, $GridB;

    if ($GridA == [] || $GridA == null) {
        resetGrid(false);
    }
    if ($GridB == [] || $GridB == null) {
        resetGrid(true);
    }
    if($IsInitPhase){
        if (isset($_GET['id'])){
            $Coord = IDtoCoord($_GET['id']);
            $isVertical = $_GET['rotation'] == "vertical";
            PlacerBateaux($Coord[0], $Coord[1], $boats[$_GET['boat']], $isVertical, false);
        } 
    }
    


}

function After() {
    global $GridA, $GridB;
    $_SESSION['A'] = $GridA;
    $_SESSION['B'] = $GridB;
}

//SETUP
Update();

After();
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
    <h3>Bataille Navale : </h3>
</header>
<footer>
    <div>
        <form method="get">
        <!-- pour pouvoir sélectionner les bateaux -->
            <select name="boat" id="boat">
                <option value="porte avion">porte avion</option>
                <option value="cuirrassé">cuirrassé</option>
                <option value="sous marin">sous marin</option>
                <option value="torpilleur">torpilleur</option>
            </select>
        <!-- pour pouvoir sélectionner la direction -->
            <select name="rotation" id="rotation">
                <option value="vertical">vertical</option>
                <option value="horizontal">horizontal</option>
            </select>
            <table>
                <?php GenerateGridA(); ?>
            </table>
        </form>
        
    </div>
</footer>
</body>
</html>