<?php declare(strict_types=1);

namespace modules\lists\controller;

use Slim\Http\Request;
use Slim\Http\Response;
use sup\controller;

abstract class GeneratePDF extends Controller
{

    protected $print = false;

    function __construct(\Slim\Container $container) {
        parent::__construct($container);
        
        $this->settings = $this->db->get("settings", "*");
    }

    public function __invoke(Request $request, Response $response, $args)
    {

        $listID = \filter_var($args['id'], \FILTER_SANITIZE_STRING);
        
        $listInfo = $this->db->get('main', ['user [Int]', 'version [Int]'], [
            'id' => $listID,
            'version' => $this->settings['active_version'],
            'state[!]' => 0
        ]);
        
        if ($listInfo == false || count($listInfo) == 0) {
            return $this->notFound($response);
        }

        $user = $this->container->factory->userFromID($listInfo['user']);

        if (is_null($user)) {
            return $this->notFound($response);
        }

        $lists = $this->db->select('lists', [
            '[>]books' => ['book' => 'id']
        ], [
            'books.id [Index]',
            'books.author [String]',
            'books.name [String]'
        ], [
            'ORDER' => 'books.id',
            'lists.list' => $listID
        ]);

        $qrURL = (string) $request
            ->getUri()
            ->withPath($this->container->router->pathFor("lists-teacher-accept", ["id" => $listID]))
            ->withQuery(http_build_query(['b' => base64_encode(implode('-', array_column($lists, 'id')))]))
            ->withFragment("");

        $lists = \array_map(function ($e) {
            return [
                $e['author'],
                $e['name']
            ];
        }, $lists);

        $versionName = $this->db->get('versions', 'name', ['id' => $listInfo['version']]);

        $generator = new \SUP\PDF\Generate($this->container, $this->container->lang->g('title', 'pdf'), $versionName);
        $generator->setContent(substr_replace($listID, ' - ', 3, 0), $listID, $qrURL, $user);
        $generator->setData([
            $this->container->lang->g('table-author', 'pdf'),
            $this->container->lang->g('table-name', 'pdf'),
        ], $lists, [30, 70]);

        if ($this->print) {
            $generator->print();
        }

        return $generator->generate($response);
        
    }

    protected abstract function notFound(&$response);
}
