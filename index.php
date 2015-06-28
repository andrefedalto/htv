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
            //$tmp['url2'] = 'https://htv.utfapp.com/index/index.php/thumb?path='.base64_encode($picture['path']);
            $tmp['url'] = 'https://htv.utfapp.com/index/index.php/thumb2?path=/&id='.($picture['idPicture']);
            $tmp['distance'] = $picture['distance'];
            $tmp['address'] = $picture['address'];
            $tmp['city'] = $picture['city'];
            $tmp['country'] = $picture['country'];
            $tmp['date'] = $picture['date'];

            $tags = $db->rawQuery("SELECT tag, api FROM gallery WHERE idPicture = ".$picture['idPicture']." ORDER BY probs DESC");

            $t = array();
            foreach ($tags as $tag)
            {
                if (!isset($t[$tag['api']]))
                    $t[$tag['api']] = array();

                array_push($t[$tag['api']], $tag['tag']);
            }

            $tmp['tags'] = ($t['clarifai']);
            if (isset($t['imagga']))
                $tmp['tagsImagga'] = ($t['imagga']);

            $year = date("Y", strtotime($tmp['date']));

            if (!isset($_output[$year]))
                $_output[$year] = array();

            array_push($_output[$year], $tmp);
        }



        $_OUT = array();
        foreach($_output as $k => $v)
        {
            $_o = array();
            $_o['year'] = $k;
            $_o['pictures'] = $v;

            array_push($_OUT, $_o);
        }

        $data = $_OUT;

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

        $tagsearch = "";
        if (sizeof($tags) > 1)
            $tagsearch = "AND tag IN (".implode(",", $ts).")";


        $query = "
            SELECT COUNT(idTag) as tags, path, address, city, country, idPicture, date FROM gallery
            WHERE address LIKE '%".trim($tags[0])."%' ".$tagsearch."
            GROUP BY idPicture
            ORDER BY tags DESC, probs DESC
            LIMIT 15
        ";


        $pictures = $db->rawQuery($query);




        $_output = array();
        foreach ($pictures as $picture)
        {
            $tmp = array();
            $tmp['url'] = 'https://htv.utfapp.com/index/index.php/thumb?path='.str_replace("/", "/", str_replace("picture", "Picture", $picture['path']));
            //$tmp['url2'] = 'https://htv.utfapp.com/index/index.php/thumb?path='.base64_encode($picture['path']);
            $tmp['url'] = 'https://htv.utfapp.com/index/index.php/thumb2?path=/&id='.($picture['idPicture']);
            $tmp['tagCount'] = $picture['tags'];
            $tmp['address'] = $picture['address'];
            $tmp['city'] = $picture['city'];
            $tmp['country'] = $picture['country'];
            $tmp['date'] = $picture['date'];

            $tags = $db->rawQuery("SELECT tag, api FROM gallery WHERE idPicture = ".$picture['idPicture']." ORDER BY probs DESC");

            $t = array();
            foreach ($tags as $tag)
            {
                if (!isset($t[$tag['api']]))
                    $t[$tag['api']] = array();

                array_push($t[$tag['api']], $tag['tag']);
            }

            $tmp['tags'] = ($t['clarifai']);
            if (isset($t['imagga']))
                $tmp['tagsImagga'] = ($t['imagga']);

            array_push($_output, $tmp);
        }


        $data = $_output;

        $app->response()->status(200);

        buildOutput($data, $app->request()->params('debug'));

    }
);



$app->run();


