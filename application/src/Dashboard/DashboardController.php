<?php

namespace Dashboard;


use App\AbstractController;
use App\Config\Configurator;
use App\Cookie\Cookie;
use App\DB;
use App\Helper\RouteHelper;
use App\Request;
use App\Response\File;
use App\Response\Status;
use App\Response\Stream;
use App\Response\Type;
use App\Response\View;
use App\Security\Csrf;

class DashboardController extends AbstractController
{
    /**
     * @var \App\DB
     */
    private $db;

    private $csrf;

    public function __construct(\App\DB $db, Csrf $csrf)
    {
        $this->db = $db;
        $this->csrf = $csrf;
    }

    public function index()
    {
        return $this->redirect(RouteHelper::path('dashboard-test2', ['title' => 'smthing']));
    }

    public function test(string $title, Configurator $configurator, DB $db)
    {
        $params = [
            'title' => $title,
            'multiParams' => [
                's' => 'd',
            ]
        ];

        $query = [
            'page' => 5
        ];

        return $this->response(new View('dashboard/test.twig', ['title' => $title, 'params' => $params, 'query' => $query]));
    }

    public function json()
    {
        $redis = new \Redis();
        $redis->connect('redis', 6379);
        $result = $redis->time();
        var_dump($result);
        exit;
        //$result = $redis->append('cos', ['test']);


        return $this->response(['content' => $result]);
    }

    public function prepare(DB $db)
    {
        $periods = new \DatePeriod(
            new \DateTime('2018-01-01'),
            new \DateInterval('P1W'),
            new \DateTime('2019-11-04')
        );

        $dates = [];
        foreach ($periods as $period) {
            $dates[] = $period->format('Y-m-d');
        }

        $max = count($dates) - 1;
        $query = 'INSERT INTO `test2` (`day`, `t1`, `t2`, `t3`, `t4`) VALUES (?, ?, ?, ?, ?)';
        $limit = 10 ** 6;
        $db->beginTransaction();
        for ($i = 0; $i < $limit; $i++) {
            $db->query($query, [$dates[random_int(0, $max)], md5($i . uniqid()), md5($i . uniqid()), md5($i . uniqid()), md5($i . uniqid())]);

            if ($i % 10000 === 0) {
                $db->commit();
                $db->beginTransaction();
            }
        }

        $db->commit();

        return $this->response();
    }

    public function videoStream()
    {
        return $this->response((new File(UPLOAD . 'a.mp4', Type::VIDEO_MP4)));
    }

    private function fromDBGenerator(string $date): \Generator
    {
        $start = microtime(true);

        $this->db->bufferedQuery(false);
        $query = 'SELECT id, day, t1 FROM `test2` WHERE `day` = :date';
        $generator = $this->db->getOneByOne($query, ['date' => $date]);
        $a = 0;
        $buff = '';
        foreach ($generator as $row) {
            $buff .= $row['id'] . ',' . PHP_EOL;
            if (++$a === 1000) {
                $a = 0;
                yield $buff;
                $buff = '';
            }
        }

        if ($buff !== '') {
            yield $buff . PHP_EOL;
        }

        yield microtime(true) - $start;

        $this->db->bufferedQuery(true);
    }

    public function cliDbDump(string $date)
    {
        $start = microtime(true);

        $this->db->bufferedQuery(false);
        $query = 'SELECT id, day, t1, t2, t3, t4 FROM `test2` WHERE `day` = :date';
        $generator = $this->db->getOneByOne($query, ['date' => $date]);
        $a = 0;
        $sC = 0;
        $buff = '';
        $fp = fopen(UPLOAD . 'dump.json', 'w');
        foreach ($generator as $row) {
            $sC++;
            $buff .= implode(',', $row) . PHP_EOL;
            if (++$a === 10000) {
                $a = 0;
                fwrite($fp, $buff);
                $buff = '';
                echo $sC . PHP_EOL;
            }
        }

        if ($buff !== '') {
            fwrite($fp, $buff);
        }

        $this->db->bufferedQuery(true);

        $time = microtime(true) - $start;
        fwrite($fp, $time);
        fclose($fp);

        return $this->response(['content' => $time]);
    }

    //private function fromDBGenerator(string $date): \Generator
    //{
    //    ini_set('memory_limit', '1024M');
    //
    //    $start = microtime(true);
    //
    //    $query = 'SELECT `id`, `day` FROM `test2` WHERE `day` = :date';
    //
    //    $a = 0;
    //    $buff = '';
    //    $rows = $this->db->getRows($query, ['date' => $date]);
    //    foreach ($rows as $row) {
    //        $buff .= implode(',', $row) . PHP_EOL;
    //        if (++$a === 1000) {
    //            $a = 0;
    //            yield $buff;
    //            $buff = '';
    //        }
    //    }
    //
    //    if ($buff !== '') {
    //        yield $buff . PHP_EOL;
    //    }
    //
    //
    //    yield microtime(true) - $start;
    //}

    //private function fromDBGenerator(string $date): \Generator
    //{
    //    ini_set('memory_limit', '1024M');
    //
    //    $start = microtime(true);
    //
    //    $this->db->bufferedQuery(false);
    //    $db = DB::getAnotherInstance();
    //    $dateTo = date('Y-m-d 23:59:59', strtotime('+6 days', strtotime($date)));
    //    $query = 'SELECT `id` FROM `test2` WHERE `time` BETWEEN :dateFrom AND :dateTo';
    //    $generator = $this->db->getOneByOne($query, ['dateFrom' => $date, 'dateTo' => $dateTo]);
    //    $a = 0;
    //    $buff = '';
    //    foreach ($generator as $row) {
    //        $ids[] = $row['id'];
    //        if (++$a === 1000) {
    //            $rows = $db->getRows('SELECT * FROM `test2` WHERE `id` IN (' . implode(',', $ids) . ')');
    //            foreach ($rows as $row1) {
    //                yield json_encode($row1) . PHP_EOL;
    //            }
    //
    //            //yield $buff;
    //            //$buff = '';
    //            $a = 0;
    //            $ids = [];
    //        }
    //    }
    //
    //    if (!empty($ids)) {
    //        $rows = $db->getRows('SELECT * FROM `test2` WHERE `id` IN (' . implode(',', $ids) . ')');
    //        foreach ($rows as $row1) {
    //            yield json_encode($row1) . PHP_EOL;
    //        }
    //
    //        //yield $buff;
    //    }
    //
    //    $this->db->bufferedQuery(true);
    //
    //    yield PHP_EOL . microtime(true) - $start;
    //}

    public function dbList()
    {
        $periods = new \DatePeriod(
            new \DateTime('2018-01-01'),
            new \DateInterval('P1W'),
            new \DateTime('2019-11-04')
        );

        $dates = [];
        foreach ($periods as $period) {
            $dates[] = $period->format('Y-m-d');
        }

        return $this->response(new View('dashboard/dbList.twig', ['dates' => $dates]));
    }

    public function dbDateDownload(string $date)
    {
        return $this->response(new Stream($date . '.json', $this->fromDBGenerator($date), Type::APPLICATION_JSON));
    }

    public function dbSaveInFileNonBlocking(string $date)
    {
        ini_set('memory_limit', '1024M');

        $start = microtime(true);

        $this->db->bufferedQuery(false);

        $dateTo = date('Y-m-d 23:59:59', strtotime('+6 days', strtotime($date)));
        $query = 'SELECT id, day, t1, t2, t3, t4 FROM `test2` WHERE `day` BETWEEN :dateFrom AND :dateTo';
        $generator = $this->db->getOneByOne($query, ['dateFrom' => $date, 'dateTo' => $dateTo]);
        $a = 0;
        $buff = '';
        $fp = fopen(UPLOAD . 'dump.json', 'w');
        foreach ($generator as $row) {
            $buff .= json_encode($row) . PHP_EOL;
            if (++$a === 10000) {
                fwrite($fp, $buff);
                $buff = '';
                $a = 0;
            }
        }

        if ($buff !== '') {
            fwrite($fp, $buff . PHP_EOL);
        }

        fwrite($fp, microtime(true) - $start);
        fclose($fp);

        $this->db->bufferedQuery(true);
    }

    private function nonBlocking($host, $path, $postParameters = [])
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        $socket = stream_socket_client("ssl://$host:443", $errno, $errstr, 1, STREAM_CLIENT_ASYNC_CONNECT, $context);
        if (!$socket) {
            return false;
        }
        $content = http_build_query($postParameters);
        $out = "POST /$path HTTP/1.1\r\n";
        $out .= "Host: $host\r\n";
        $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out .= "Content-Length: " . strlen($content) . "\r\n";
        $out .= "Connection: close\r\n";
        $out .= "\r\n";
        fwrite($socket, $out);
        fwrite($socket, $content);

        return true;
    }

    public function dbSaveInFile(string $date)
    {
        $result = $this->nonBlocking('www.home.devv', 'db/date/saveInFileNonBlocking', ['date' => $date]);

        return $this->response(['status' => $result ? Status::OK : Status::ERROR]);
    }

    public function sqlStream()
    {
        return $this->response((new Stream('dump.sql', $this->fromDBGenerator(), Type::APPLICATION_SQL)));
    }
}
