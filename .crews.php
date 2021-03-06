<?php

// if ($_GET['page'] == "nightcrews") {
//     require "error.php";
//     die();
// }

date_default_timezone_set("America/New_York");

main();

require_once ".admin_config.php";

function modifyCrewAssignment ($connection, $signature, $id, $position, $crewid, $action) {
    global $SECRET_KEY;

    if (sha1($crewid . $position . $id . $SECRET_KEY) == $signature) {
        $statement = $connection->prepare("UPDATE crews SET $position = :id WHERE id = :crewid");
        $statement->bindValue(":id", ($action == 'clear' ? 0 : $id));
        $statement->bindValue(":crewid", $crewid);
        $statement->execute();

        echo 'true';
        exit;
    } else {
        echo $crewid . ' ' . $position . ' ' . $id . ' ' . $secret;
        echo 'false';
        exit;
    }
}

function confirmCrew ($connection, $signature, $id, $position, $crewid) {
    $statement = $connection->prepare("SELECT $position FROM crews WHERE id = :crewid");
    $statement->bindParam(":crewid", $crewid);
    $statement->execute();
    $spot = $statement->fetchAll(PDO::FETCH_ASSOC)[0];

    if($spot[$position] == 0) {
        modifyCrewAssignment($connection, $signature, $id, $position, $crewid, 'confirm');
    } else {
        echo 'false';
        exit;
    }
}

function clearCrew ($connection, $signature, $id, $position, $crewid) {
    modifyCrewAssignment($connection, $signature, $id, $position, $crewid, 'clear');
}

function determineEligibility ($member, $pos, $i, $ontoday, $onthisweek, $ccton, $dton, $atton, $obson, $y, $m, $d) {
    if(!isset($member['id'])) {
        return false;
    } else if(mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')) > mktime(23, 59, 59, $m, $d, $y)) {
        return false;
    } else if($ontoday[$i] != 0) {
        return false;
    }

    if($pos == 'cc') {
        $condition3 = $member['crewchief'] == 1 || $member['firstresponsecc'] == 1 || $member['cctrainer'] == 1 || ($member['backupcc'] == 1 && $ccton[$i] == 1);

        return $condition3;
    } else if($pos == 'driver') {
        $condition3 = $member['driver'] == 1 || $member['drivertrainer'] == 1 || ($member['backupdriver'] == 1 && $dton[$i] == 1);

        return $condition3;
    } else if($pos == 'attendant' || $pos == 'observer') {
        $riderConditions = $member['dutysup'] == 1 || $member['ees'] == 1 ||
            $member['cctrainer'] == 1 || $member['firstresponsecc'] == 1 ||
            $member['drivertrainer'] == 1 || $member['crewchief'] == 1 ||
            $member['driver'] == 1 || $member['backupcc'] == 1 ||
            $member['backupdriver'] == 1 || $onthisweek == 0 ||
            ($onthisweek == 1 && mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')) >= mktime(16, 00, 00, $m, $d, $y) && $ontoday[$i] == 0);

        if(!$riderConditions) {
            return false;
        }

        if($pos == 'attendant') {
            return $member['attendant'] == 1 || $obson[$i] == 1;
        } else if($pos == 'observer') {
            return $member['attendant'] == 0 || $atton[$i] == 1;
        }
    }

    return false;
}

function populateSpot ($connection, $i, $row, $pos, $member, $ontoday, $onthisweek, $ccton, $dton, $atton, $obson) {
    global $SECRET_KEY;

    $y = substr($row['date'], 0, 4);
    $m = substr($row['date'], 5, 2);
    $d = substr($row['date'], 8, 2);

    $spot = array();
    $clear = false;
    if ($row[$pos] >= -3 && $row[$pos] != 0) {
        $spot['vacant'] = false;

        $statement = $connection->prepare("SELECT * FROM members WHERE id = :memberId LIMIT 1");
        $statement->bindParam(':memberId', $row[$pos]);
        $statement->execute();
        $spotMemberArray = $statement->fetchAll(PDO::FETCH_ASSOC)[0];

        $clear = array();

        $spot['id'] = $spotMemberArray['id'];

        if ($spotMemberArray['id'] < 0) {
            $spot['name'] = $spotMemberArray['last_name'];
        } else {
            $spot['name'] = substr($spotMemberArray['first_name'], 0, 1) . ". " . $spotMemberArray['last_name'];
        }

        if ($spotMemberArray['email'] != "") {
            $spot['email'] = strtolower($spotMemberArray['email']);
        }

        if ($spotMemberArray['id'] == $member['id'] && mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')) <= mktime(18, 00, 00, $m, $d - 2, $y)) {
            $clear = array(
                "crewid" => $row['id'],
                "position" => $pos,
                "signature" => sha1($row['id'] . $pos . $member['id'] . $SECRET_KEY),
            );
        } else {
            $clear = false;
        }
    } else if (determineEligibility($member, $pos, $i, $ontoday, $onthisweek, $ccton, $dton, $atton, $obson, $y, $m, $d)) {
        $spot["vacant"] = true;
        $spot["eligible"] = true;
        $spot["crewid"] = $row['id'];
        $spot["position"] = $pos;
        $spot["signature"] = sha1($row['id'] . $pos . $member['id'] . $SECRET_KEY);
    } else {
        $spot['vacant'] = true;
        $spot['eligible'] = false;
    }

    return array("spot" => $spot, "clear" => $clear);
}

function processTurnover ($connection) {
    if (date("D") == "Sun") {
        $oneweek = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + 7, date("Y")));

        $statement = $connection->prepare("SELECT id FROM crews WHERE date = :oneweek");
        $statement->bindValue(":oneweek", $oneweek);
        $statement->execute();

        if ($statement->fetchColumn() < 1) {
            for ($i = 7; $i < 14; $i++) {
                $statement = $connection->prepare("SELECT id FROM crews ORDER BY id DESC LIMIT 1");
                $statement->execute();
                $logid = $statement->fetchAll(PDO::FETCH_ASSOC)[0]['id'];// + 1:
                $logid += 1;

                $statement = $connection->prepare("SELECT * FROM default_crews WHERE day = :i");
                $statement->bindValue(":i", $i - 7);
                $statement->execute();
                $default = $statement->fetchAll(PDO::FETCH_ASSOC)[0];

                $date = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + $i, date('Y')));

                $statement = $connection->prepare("INSERT INTO crews (id, date, cc, driver, attendant, observer) VALUES (:logid, :date, :cc, :driver, :attendant, :observer)");
                $statement->bindValue(":logid", $logid);
                $statement->bindValue(":date", $date);
                $statement->bindValue(":cc", $default['cc']);
                $statement->bindValue(":driver", $default['driver']);
                $statement->bindValue(":attendant", $default['attendant']);
                $statement->bindValue(":observer", $default['observer']);
                $statement->execute();
            }
        }
    }
}

function main () {
    global $SECRET_KEY;

    require_once ".db_config.php";

    parse_str(file_get_contents("php://input"), $post);

    if(!isset($post['session_id'])) {
        header('Bad Request', true, 400);
        echo 'Bad Request';
        exit;
    }

    session_start($post['session_id']);

    if(!isset($_SESSION['username'])) {
        header('Unauthorized', true, 409);
        echo 'Unauthorized';
        exit;
    }

    $username = $_SESSION['username'];

    $connection = new PDO("mysql:host=$dhost:3306;dbname=$dname", $duser, $dpassword);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if(!isset($dname)) {
        $dname = 'ambulanc_web';
    }

    $connection->exec("USE `$dname`");

    $statement = $connection->prepare("SELECT * FROM members WHERE username=:username");
    $statement->bindParam(':username', $username);
    $statement->execute();
    $memberInfo = $statement->fetchAll(PDO::FETCH_ASSOC)[0];
    $memberId = $memberInfo['id'];

    processTurnover($connection);

    if (isset($post['confirmcrew'])) {
        confirmCrew($connection, $post['signature'], $memberId, $post['position'], $post['crewid']);
    }

    if (isset($post['clearcrew'])) {
        clearCrew($connection, $post['signature'], $memberId, $post['position'], $post['crewid']);
    }

    $statement = $connection->prepare("SELECT id FROM crews ORDER BY id DESC LIMIT 1");
    $statement->execute();
    $idarray = $statement->fetchAll(PDO::FETCH_ASSOC)[0];

    $prev_week = $idarray['id'] - 20;
    $next_week = $idarray['id'] - 13;

    if (isset($_GET['week'])) {
        $theday = $_GET['week'];

        $statement = $connection->prepare("SELECT date FROM crews WHERE id = :theday LIMIT 1");
        $statement->bindParam(':theday', $theday);
        $statement->execute();
        $dayofweek = $statement->fetchAll(PDO::FETCH_ASSOC);

        $dayofweek = date('D', strtotime($dayofweek['date']));

        if ($dayofweek == "Sun") {
            $prev_week = $theday - 7;
            $next_week = $theday + 7;
        }
    }

    $response = array(
        "headings" => array(
            'Night', 'Date', 'Crew Chief', 'Driver', 'Rider', 'Rider'
        ),
        "pagers" => array(),
        "crews" => array(
            "currentWeek" => array(),
            "nextWeek" => array(),
        ),
    );

    if ($prev_week >= 36 && $prev_week <= $idarray['id'] - 13) {
        $response["pagers"]["prevWeek"] = $prev_week;
    }

    if (isset($theday) && $next_week >= 36 && $next_week <= $idarray['id'] - 13) {
        $response["pagers"]["nextWeek"] = $next_week;
    }

    for ($tableloop = 0; $tableloop < 2; $tableloop++) {
        $logid = $idarray['id'] - (13 - (7 * $tableloop));

        if (isset($theday)) {
            if ($theday >= 36 && $theday < $idarray['id'] - 13 && $dayofweek == "Sun") {
                if ($tableloop == 0) {
                    $logid = $theday;
                } else if ($tableloop == 1) {
                    $logid = $theday + 7;
                }
            } else {
                header('Location: http://ambulance.union.rpi.edu/?page=members&module=1');
            }
        }

        $onthisweek = 0;
        $ontoday    = array();
        $ccton      = array();
        $dton       = array();

        if (isset($memberId)) {
            $memid  = $memberId;

            $statement = $connection->prepare("SELECT * FROM members WHERE id = :memid LIMIT 1");
            $statement->bindParam(':memid', $memid);
            $statement->execute();
            $member = $statement->fetchAll(PDO::FETCH_ASSOC)[0];

            for ($x = 0; $x < 7; $x++) {
                $ontoday[$x] = 0;
                $ccton[$x]   = 0;
                $dton[$x]    = 0;
                $atton[$x]   = 0;
                $obson[$x]   = 0;

                $statement = $connection->prepare("SELECT * FROM crews WHERE id = :crewid LIMIT 1");
                $statement->bindValue(':crewid', ($logid + $x));
                $statement->execute();
                $y = $statement->fetchAll(PDO::FETCH_ASSOC)[0];

                if ($y['cc'] == $memid || $y['driver'] == $memid || $y['attendant'] == $memid || $y['observer'] == $memid) {
                    $onthisweek  = 1;
                    $ontoday[$x] = 1;
                }

                if ($y['attendant'] != 0) {
                    $atton[$x] = 1;
                }

                if ($y['observer'] != 0) {
                    $obson[$x] = 1;
                }

                $positions = [
                    'cc', 'driver', 'attendant', 'observer'
                ];

                foreach($positions as $pos) {
                    $statement = $connection->prepare("SELECT * FROM members WHERE id = :memberid LIMIT 1");
                    $statement->bindValue(':memberid', $y['cc']);
                    $statement->execute();
                    $posMember = $statement->fetchAll(PDO::FETCH_ASSOC)[0];

                    if($posMember['cctrainer']) {
                        $ccton[$x] = 1;
                    }

                    if ($posMember['drivertrainer'] == 1) {
                        $dton[$x] = 1;
                    }
                }

                $response['positions'] = $positions;
            }
        }

        for ($i = 0; $i < 7; $i++) {
            $statement = $connection->prepare("SELECT * FROM crews WHERE id = :crewid LIMIT 1");
            $statement->bindValue(':crewid', ($logid + $i));
            $statement->execute();
            $row = $statement->fetchAll(PDO::FETCH_ASSOC)[0];

            $y = substr($row['date'], 0, 4);
            $m = substr($row['date'], 5, 2);
            $d = substr($row['date'], 8, 2);

            $nightCrew = array(
                "id" => $row["id"],
                "day" => date('l', mktime(0, 0, 0, $m, $d, $y)),
                "date" => $m . '/' . $d . '/' . substr($y, 2, 2),
                "spots" => array(),
                "clear" => array(),
            );

            $positons = [
                'cc', 'driver', 'attendant', 'observer'
            ];

            foreach($positons as $pos) {
                $spot = populateSpot($connection, $i, $row, $pos, $member, $ontoday, $onthisweek, $ccton, $dton, $atton, $obson);
                $nightCrew['spots'][$pos] = $spot['spot'];
                $nightCrew['clear'][$pos] = $spot['clear'];
            }

            $response['crews'][($tableloop == 0 ? "currentWeek" : "nextWeek")][] = $nightCrew;
        }
    }

    if($memberInfo['admin'] == 1) {
        $response['isAdmin'] = 1;
        $response['modifyScheduleSignature'] = sha1($memberInfo['username'] . $SECRET_KEY);
        $response['username'] = $memberInfo['username'];
    }

    $response['success'] = true;

    echo json_encode($response);
}
?>
