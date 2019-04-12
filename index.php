<?php
// On ne veut pas de cache sur cette page
$dateFormat = 'D, d M Y H:i:s';
$ttl = 60 * 5; // 5 min
header('Expires: '.gmdate($dateFormat, time() - $ttl).' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0');
header('Pragma: no-cache');

session_start();

// on force les header afin de spécifier que le contenu retourné est encodé en utf8
if ( ! empty($_POST['index'])) {
    // Appel ajax contenu formaté en JSON
    header('Content-Type: application/json; charset=utf-8');
} else {
    // par défaut le contenu est du HTML
    header('Content-Type: text/html; charset=utf-8');
}


// On implémente notre classe de jeu
require_once('class/Memory_game.php');
$memory = new Memory_game();

if (empty($_POST) and empty($_GET)) {
    $memory->initGame();
}

if ( ! empty($_POST) and $_POST['card_index'] >= 0) {

    // On vérifie le résultat du choix utilisateur
    $result = $memory->checkCard((int)$_POST['card_index']);

    // On retourne le résultat au format json (appel Ajax qui attend ce format)
    die(json_encode($result));
}
if ( ! empty($_GET['new_game']) or ! empty($_POST['new_game'])) {

    // On réinitialise le jeu
    $memory->resetGame();
    // on redirige vers l'application
    header('location: index.php');

}

// On initialize le jeu
$cards = $memory->getCards();

// On récupère les scores
$score_list = $memory->getScores();

// On initialize le dernier mouvement
unset($_SESSION['last_card_index']);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>O'Clock Memory</title>
    <meta name="description" content="Jeu de mémoire à destination de formation">
    <meta name="robots" content="index, follow">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link href="assets/css/main.css" rel="stylesheet">
</head>
<body id="memory_game">

<div id="panel">
    <h1>Jeu de <span>mémoire</span></h1>
    <nav>
        <ul>
            <li><a href="index.php?new_game=1" id="new_game">Nouvelle partie</a></li>
            <li><a href="index.php" id="pause_game">Pause</a></li>
        </ul>
    </nav>
    <p>
        La partie commence dès la première carte retourné.
    </p>
    <?php if ( ! empty($score_list)) { ?>
        <div id="score">

            <table>
                <thead>
                <tr>
                    <td colspan="2">
                        <h2>Meilleurs scores</h2>
                    </td>
                </tr>
                <tr>
                    <td>Score</td>
                    <td>Temps</td>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($score_list as $score) { ?>
                    <tr class="<?php if ($score['score_game_won'] == 1) { ?>won<? } else { ?>lost<? } ?>">
                        <td><?php echo $score['score_pair_found'] ?>/<?php echo $score['score_pair_total'] ?></td>
                        <td class="text-right"><?php echo $score['score_elapsed_time'] ?> sec</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</div>

<div id="content">
    <div id="progress">
        <div id="time_elapsed" style="width:<?php echo $memory->getTimerPercent(); ?>%"></div>
    </div>
    <ul id="card_list">
        <?php
        foreach ($cards as $card) {
            $class = "back";
            $style = "";
            if ($card['ok'] == 1) {
                $class = "front success";
                $bg_offset = (($card['pair_id'] - 1) * 100);
                $style = 'style="background-position-y: -'.$bg_offset.'px;"';
            }
            echo sprintf('<li class="%s" %s></li>', $class, $style);
        }
        ?>
    </ul>
</div>

<script type="text/javascript">
    var timer = <?php echo $memory->getTimer(); ?>;
    var maxtime = <?php echo $memory->getGameTime(); ?>;
</script>
<script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>
<script type="text/javascript" src="assets/js/main.js"></script>

<div id="countdown"></div>

</body>
</html>