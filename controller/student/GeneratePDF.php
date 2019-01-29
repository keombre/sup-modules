<?php declare(strict_types=1);

namespace modules\subjects\controller\student;

use Slim\Http\Request;
use Slim\Http\Response;
use modules\subjects\controller\Controller;

class GeneratePDF extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {

        $listID = \filter_var($args['id'], \FILTER_SANITIZE_STRING);
        $userID = $this->container->auth->getUser()->getID();
        $uname  = $this->container->auth->getUser()->getUName();

        if (!$this->db->has('main', [
            'id' => $listID,
            'version' => $this->settings['active_version'],
            'user' => $userID,
            'state[!]' => 0
        ])) {
            return $response->withRedirect($this->container->router->pathFor('subjects-student'), 301);
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
            return [$e['code'], $e['name'], ['Volitelný předmět', 'Náhradní předmět', 'Dobrovolný seminář'][$e['level']]];
        }, $subjects);

        $generator = new \SUP\PDF\Generate($this->container, 'Volitelné předměty', '2019');
        $generator->setContent(substr_replace($listID, ' - ', 3, 0), 'C' . $listID . '-U' . $uname, $qrURL, $this->container->auth->getUser());
        $generator->setData(['Kód', 'Předmět', 'Typ zápisu'], $subjects, [10, 50, 40]);

        return $generator->generate($response);
        
    }
}
