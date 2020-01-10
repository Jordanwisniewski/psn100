<?php
require_once("../init.php");
?>
<!doctype html>
<html lang="en">
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Admin ~ Game Message</title>
    </head>
    <body>
        <?php
        $query = $database->prepare("SELECT p.online_id AS player_name, tt.id AS game_id, tt.name AS game_name FROM player p JOIN trophy_earned te USING (account_id) JOIN trophy_title tt USING (np_communication_id) WHERE (
        (te.np_communication_id = 'NPWR05066_00' AND te.group_id = 'default' AND te.order_id = 2) OR
        (te.np_communication_id = 'NPWR05066_00' AND te.group_id = 'default' AND te.order_id = 9)
        ) AND p.status = 0 GROUP BY player_name ORDER BY player_name");
        $query->execute();
        $possibleCheaters = $query->fetchAll();

        foreach ($possibleCheaters as $possibleCheater) {
            echo "<a href=\"/game/". $possibleCheater["game_id"] ."-". str_replace(" ", "-", $possibleCheater["game_name"]) ."/". $possibleCheater["player_name"] ."\">". $possibleCheater["player_name"] ."</a><br>";
        }
        ?>
    </body>
</html>
