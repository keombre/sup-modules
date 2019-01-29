<?php declare(strict_types=1);

namespace modules\subjects\controller;

use Slim\Http\Request;
use Slim\Http\Response;
use modules\subjects\controller\Controller;

abstract class GeneratePDF extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {

        $listID = \filter_var($args['id'], \FILTER_SANITIZE_STRING);
        
        $userID = $this->db->get('main', 'user', [
            'id' => $listID,
            'version' => $this->settings['active_version'],
            'state[!]' => 0
        ]);
        
        if (is_null($userID)) {
            return $this->notFound($response);
        }

        $user = $this->container->factory->userFromID((int) $userID);

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
            'lists.list' => $listID
        ]);

        $qrURL = (string) $request
            ->getUri()
            ->withPath($this->container->router->pathFor("subjects-teacher-accept", ["id" => $listID]))
            ->withQuery(http_build_query(['b' => base64_encode(implode('-', array_column($subjects, 'id')))]))
            ->withFragment("");

        $subjects = \array_map(function ($e) {
            return [
                $e['code'],
                $e['name'],
                ['Volitelný předmět', 'Náhradní předmět', 'Dobrovolný seminář'][$e['level']]
            ];
        }, $subjects);

        $generator = new \SUP\PDF\Generate($this->container, 'Volitelné předměty', '2019');
        $generator->setContent(substr_replace($listID, ' - ', 3, 0), 'C' . $listID . '-U' . $user->getUName(), $qrURL, $user);
        $generator->setData(['Kód', 'Předmět', 'Typ zápisu'], $subjects, [10, 50, 40]);

        return $generator->generate($response);
        
    }

    protected abstract function notFound(&$response);
}
