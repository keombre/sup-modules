<?php declare(strict_types=1);

namespace modules\subjects\controller;

use Slim\Http\Request;
use Slim\Http\Response;
use modules\subjects\controller\Controller;

abstract class GeneratePDF extends Controller
{

    protected $print = false;

    public function __invoke(Request $request, Response $response, $args)
    {

        $listID = \filter_var($args['id'], \FILTER_SANITIZE_STRING);
        
        $listInfo = $this->db->get('main', ['user [Int]', 'version [Int]'], [
            'id' => $listID,
            'version' => [
                $this->settings['active_version_7'],
                $this->settings['active_version_8']
            ],
            'state[!]' => 0
        ]);
        
        if ($listInfo == false || count($listInfo) == 0) {
            return $this->notFound($response);
        }

        $user = $this->container->factory->userFromID($listInfo['user']);

        if (is_null($user)) {
            return $this->notFound($response);
        }

        $subjects = $this->db->select('lists', [
            '[>]subjects' => ['subject' => 'id']
        ], [
            'subjects.id',
            'subjects.code',
            'subjects.name',
            'lists.level'
        ], [
            'ORDER' => 'subjects.id',
            'lists.list' => $listID,
            'subjects.state' => 0
        ]);
        
        
        $qrURL = $listID . ':' . implode('-', array_column($subjects, 'id'));

        $subjects = \array_map(function ($e) {
            return [
                $e['code'],
                $e['name'],
                $this->container->lang->g('type-' . $e['level'], 'pdf')
            ];
        }, $subjects);

        //$versionName = $this->db->get('versions', 'name', ['id' => $listInfo['version']]);

        $versionName = date("Y");

        $generator = new \SUP\PDF\Generate($this->container, $this->container->lang->g('title', 'pdf'), $versionName);
        $generator->setContent(substr_replace($listID, ' - ', 3, 0), $listID, $qrURL, $user);
        $generator->setData([
            $this->container->lang->g('table-code', 'pdf'),
            $this->container->lang->g('table-subject', 'pdf'),
            $this->container->lang->g('table-type', 'pdf')
        ], $subjects, [10, 50, 40]);

        if ($this->print) {
            $generator->print();
        }

        return $generator->generate($response);
        
    }

    protected abstract function notFound(&$response);
}
