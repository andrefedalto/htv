<?php
/**
 * Created by PhpStorm.
 * User: André
 * Date: 27/06/2015
 * Time: 08:21
 */


ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(-1);

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Content-Type: application/json; charset=utf-8');

//header('Content-Type: text/html; charset=utf-8');
//

require_once('./libs/mysqli/MysqliDb.php');

require_once('libs/Slim/Slim.php');
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
$app->response->headers->set('Content-Type', 'application/json');
$app->response->headers->set('charset', 'utf-8');


$db = new Mysqlidb('db.utfapp.com', 'htv', '123qweasd', 'htv');
if(!$db) die("Database error");


require_once('./functions/functions.php');

require_once('./classes/Log.php');
$log = Log::getInstance();

require_once('./classes/DataManager.php');


$app->get(
    '/',
    function () use ($app, $db, $log)
    {
        $app->response->headers->set('Content-Type', 'text/html');


        echo "HTV!";

    }
);


$app->get(
    '/searchByLocation',
    function () use ($app, $db, $log)
    {


        if ($app->request()->params('radius') == null)
            $rad = 10;
        else
            $rad = $app->request()->params('radius');

        if ($app->request()->params('lat') == null)
            $lat = 1;
        else
            $lat = $app->request()->params('lat');

        if ($app->request()->params('lon') == null)
            $lon = 1;
        else
            $lon = $app->request()->params('lon');



        $cols = Array ("*", "( 6371 * acos( cos( radians(".$lat.") ) * cos( radians( lat ) ) * cos( radians( lon ) - radians(".$lon.") ) + sin( radians(".$lat.") ) * sin(radians(lat)) ) ) AS distance");
        $db->where ("( 6371 * acos( cos( radians(".$lat.") ) * cos( radians( lat ) ) * cos( radians( lon ) - radians(".$lon.") ) + sin( radians(".$lat.") ) * sin(radians(lat)) ) )", $rad, "<");
        $db->orderBy("distance","asc");

        $pictures = $db->get ("picture", null, $cols);

        $_output = array();
        foreach ($pictures as $picture)
        {
            $tmp = array();
            $tmp['url'] = 'https://htv.utfapp.com/index/index.php/thumb?path='.$picture['path'];
            $tmp['distance'] = $picture['distance'];
            $tmp['address'] = $picture['address'];
            $tmp['city'] = $picture['city'];
            $tmp['country'] = $picture['country'];

            $db->where("idPicture", $picture['idPicture']);
            $tags = $db->rawQuery("SELECT t.tag FROM picturetag pt LEFT JOIN tag t ON t.idTag = pt.idTag WHERE idPicture = ".$picture['idPicture']);
            $t = array();
            foreach ($tags as $tag)
            {
                array_push($t, $tag['tag']);
            }

            $tmp['tags'] = implode(",", $t);

            array_push($_output, $tmp);
        }


        $data = $_output;

        $app->response()->status(200);

        buildOutput($data, $app->request()->params('debug'));

    }
);

$app->get(
    '/searchByTags',
    function () use ($app, $db, $log)
    {

        $app->request()->params('tags');


        $tags = explode(",", $app->request()->params('tags'));
        $ts = array();
        foreach($tags as $tag)
        {
            $t = "'".trim($tag)."'";
            array_push($ts, $t);
        }
        $query = "
            SELECT * FROM (select p.IdPicture as 'Id', p.path as 'Path', count(t.idTag) as 'Count', pt.probs as 'Probability' from picture p
            INNER JOIN picturetag pt on p.idPicture = pt.idPicture
            INNER JOIN tag t on pt.idTag = t.idTag
            WHERE (t.tag IN (".implode(',', $ts).") OR p.address LIKE '%".trim($tags[0])."%') AND pt.probs > 0.9
            GROUP BY p.IdPicture) as temp
            ORDER BY temp.Count DESC, temp.Id ASC
        ";

        $pictures = $db->rawQuery($query);


        $_output = array();
        foreach ($pictures as $picture)
        {
            $tmp = array();
            $tmp['url'] = 'https://htv.utfapp.com/index/index.php/thumb?path='.$picture['Path'];
            $tmp['tagCount'] = $picture['Count'];
//            $tmp['address'] = $picture['address'];
//            $tmp['city'] = $picture['city'];
//            $tmp['country'] = $picture['country'];
//
//            $db->where("idPicture", $picture['idPicture']);
//            $t = array();
//            foreach ($tags as $tag)
//            {
//                array_push($t, $tag['tag']);
//            }
//
//            $tmp['tags'] = implode(",", $t);

            array_push($_output, $tmp);
        }


        $data = $_output;

        $app->response()->status(200);

        buildOutput($data, $app->request()->params('debug'));

    }
);



$app->run();


