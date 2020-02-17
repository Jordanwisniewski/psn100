<?php
if (!isset($accountId)) {
    header("Location: /player/", true, 303);
    die();
}

$query = $database->prepare("SELECT * FROM player WHERE account_id = :account_id");
$query->bindParam(":account_id", $accountId, PDO::PARAM_INT);
$query->execute();
$player = $query->fetch();

$title = $player["online_id"] . "'s Trophy Log ~ PSN100.net";
require_once("player_header.php");

$url = $_SERVER["REQUEST_URI"];
$url_parts = parse_url($url);
// If URL doesn't have a query string.
if (isset($url_parts["query"])) { // Avoid 'Undefined index: query'
    parse_str($url_parts["query"], $params);
} else {
    $params = array();
}

$query = $database->prepare("SELECT COUNT(*) FROM trophy_earned te WHERE te.account_id = :account_id");
$query->bindParam(":account_id", $player["account_id"], PDO::PARAM_INT);
$query->execute();
$result_count = $query->fetchColumn();

$page = max(isset($_GET["page"]) && is_numeric($_GET["page"]) ? $_GET["page"] : 1, 1);
$limit = 50;
$offset = ($page - 1) * $limit;
?>
        <div class="row">
            <div class="col-2 text-center">
                <h5><a href="/player/<?= $player["online_id"]; ?>">Games</a></h5>
            </div>
            <div class="col-2 text-center">
                <h5>Log</h5>
            </div>
            <div class="col-2 text-center">
                <h5><a href="/player/<?= $player["online_id"]; ?>/advisor">Trophy Advisor</a></h5>
            </div>
            <div class="col-2 text-center">
                <h5><a href="/game?sort=completion&player=<?= $player["online_id"]; ?>">Game Advisor</a></h5>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <table class="table table-responsive table-striped">
                    <tr class="table-primary">
                        <th scope="col">Game Icon</th>
                        <th scope="col">Trophy Icon</th>
                        <th scope="col" width="100%">Description</th>
                        <th scope="col">Earned</th>
                        <th scope="col"><a href="?sort=rarity">Rarity</a></th>
                        <th scope="col">Type</th>
                    </tr>

                    <?php
                    if (isset($_GET["sort"])) {
                        $query = $database->prepare("SELECT te.*, tg.name AS group_name, tg.icon_url AS group_icon_url, t.id AS trophy_id, t.type, t.name AS trophy_name, t.detail AS trophy_detail, t.icon_url AS trophy_icon_url, t.rarity_percent, t.status AS trophy_status, tt.id AS game_id, tt.name AS game_name, tt.status AS game_status FROM trophy_earned te LEFT JOIN trophy_group tg ON tg.np_communication_id = te.np_communication_id AND tg.group_id = te.group_id LEFT JOIN trophy t ON t.np_communication_id = te.np_communication_id AND t.group_id = te.group_id and t.order_Id = te.order_id LEFT JOIN trophy_title tt ON tt.np_communication_id = te.np_communication_id WHERE te.account_id = :account_id ORDER BY t.rarity_percent LIMIT :offset, :limit");
                    } else {
                        $query = $database->prepare("SELECT te.*, tg.name AS group_name, tg.icon_url AS group_icon_url, t.id AS trophy_id, t.type, t.name AS trophy_name, t.detail AS trophy_detail, t.icon_url AS trophy_icon_url, t.rarity_percent, t.status AS trophy_status, tt.id AS game_id, tt.name AS game_name, tt.status AS game_status FROM trophy_earned te LEFT JOIN trophy_group tg ON tg.np_communication_id = te.np_communication_id AND tg.group_id = te.group_id LEFT JOIN trophy t ON t.np_communication_id = te.np_communication_id AND t.group_id = te.group_id and t.order_Id = te.order_id LEFT JOIN trophy_title tt ON tt.np_communication_id = te.np_communication_id WHERE te.account_id = :account_id ORDER BY te.earned_date DESC LIMIT :offset, :limit");
                    }
                    $query->bindParam(":account_id", $player["account_id"], PDO::PARAM_INT);
                    $query->bindParam(":offset", $offset, PDO::PARAM_INT);
                    $query->bindParam(":limit", $limit, PDO::PARAM_INT);
                    $query->execute();
                    $trophies = $query->fetchAll();

                    if (count($trophies) === 0) {
                        ?>
                        <tr>
                            <td colspan="6" class="text-center"><h3>This player seems to have a private profile.</h3></td>
                        </tr>
                        <?php
                    } else {
                        foreach ($trophies as $trophy) {
                            if ($trophy["game_status"] == 1) {
                                echo "<tr class=\"table-warning\" title=\"This game is delisted and the trophy will not be accounted for on any leaderboard.\">";
                            } elseif ($trophy["trophy_status"] == 1) {
                                echo "<tr class=\"table-warning\" title=\"This trophy is unobtainable and not accounted for on any leaderboard.\">";
                            } else {
                                echo "<tr>";
                            } ?>
                                <td>
                                    <a href="/game/<?= $trophy["game_id"] ."-". slugify($trophy["game_name"]); ?>/<?= $player["online_id"]; ?>">
                                        <img src="/img/group/<?= $trophy["group_icon_url"]; ?>" alt="<?= $trophy["group_name"]; ?>" title="<?= $trophy["group_name"]; ?>" width="100" />
                                    </a>
                                </td>
                                <td>
                                    <img src="/img/trophy/<?= $trophy["trophy_icon_url"]; ?>" alt="<?= $trophy["trophy_name"]; ?>" title="<?= $trophy["trophy_name"]; ?>" width="44" />
                                </td>
                                <td style="width: 100%;">
                                    <a href="/trophy/<?= $trophy["trophy_id"] ."-". slugify($trophy["trophy_name"]); ?>/<?= $player["online_id"]; ?>">
                                        <b><?= $trophy["trophy_name"]; ?></b>
                                    </a>
                                    <br>
                                    <?= $trophy["trophy_detail"]; ?>
                                </td>
                                <td class="text-center" style="white-space: nowrap;">
                                    <?= str_replace(" ", "<br>", $trophy["earned_date"]); ?>
                                </td>
                                <td class="text-center">
                                    <?= $trophy["rarity_percent"]; ?>%<br>
                                    <?php
                                    if ($trophy["trophy_status"] == 1) {
                                        echo "Unobtainable";
                                    } elseif ($trophy["rarity_percent"] <= 1.00) {
                                        echo "Legendary";
                                    } elseif ($trophy["rarity_percent"] <= 5.00) {
                                        echo "Epic";
                                    } elseif ($trophy["rarity_percent"] <= 20.00) {
                                        echo "Rare";
                                    } elseif ($trophy["rarity_percent"] <= 50.00) {
                                        echo "Uncommon";
                                    } else {
                                        echo "Common";
                                    } ?>
                                </td>
                                <td>
                                    <img src="/img/playstation/<?= $trophy["type"]; ?>.png" alt="<?= ucfirst($trophy["type"]); ?>" title="<?= ucfirst($trophy["type"]); ?>" />
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php
                        if ($page > 1) {
                            $params["page"] = $page - 1; ?>
                            <li class="page-item"><a class="page-link" href="?<?= http_build_query($params); ?>">Prev</a></li>
                            <?php
                        }

                        if ($page > 3) {
                            $params["page"] = 1; ?>
                            <li class="page-item"><a class="page-link" href="?<?= http_build_query($params); ?>">1</a></li>
                            <li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">~</a></li>
                            <?php
                        }

                        if ($page-2 > 0) {
                            $params["page"] = $page - 2; ?>
                            <li class="page-item"><a class="page-link" href="?<?= http_build_query($params); ?>"><?= $page-2; ?></a></li>
                            <?php
                        }

                        if ($page-1 > 0) {
                            $params["page"] = $page - 1; ?>
                            <li class="page-item"><a class="page-link" href="?<?= http_build_query($params); ?>"><?= $page-1; ?></a></li>
                            <?php
                        }
                        ?>

                        <?php
                        $params["page"] = $page;
                        ?>
                        <li class="page-item active" aria-current="page"><a class="page-link" href="?<?= http_build_query($params); ?>"><?= $page; ?></a></li>

                        <?php
                        if ($page+1 < ceil($result_count / $limit)+1) {
                            $params["page"] = $page + 1; ?>
                            <li class="page-item"><a class="page-link" href="?<?= http_build_query($params); ?>"><?= $page+1; ?></a></li>
                            <?php
                        }

                        if ($page+2 < ceil($result_count / $limit)+1) {
                            $params["page"] = $page + 2; ?>
                            <li class="page-item"><a class="page-link" href="?<?= http_build_query($params); ?>"><?= $page+2; ?></a></li>
                            <?php
                        }

                        if ($page < ceil($result_count / $limit)-2) {
                            $params["page"] = ceil($result_count / $limit); ?>
                            <li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">~</a></li>
                            <li class="page-item"><a class="page-link" href="?<?= http_build_query($params); ?>"><?= ceil($result_count / $limit); ?></a></li>
                            <?php
                        }

                        if ($page < ceil($result_count / $limit)) {
                            $params["page"] = $page + 1; ?>
                            <li class="page-item"><a class="page-link" href="?<?= http_build_query($params); ?>">Next</a></li>
                            <?php
                        }
                        ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</main>
<?php
require_once("footer.php");
?>
