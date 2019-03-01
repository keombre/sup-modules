<?php declare(strict_types=1);

namespace modules\subjects\controller\student;

use modules\subjects\controller\GeneratePDF;
use Slim\Http\Request;
use Slim\Http\Response;

class Generate extends GeneratePDF
{

    public function __invoke(Request $request, Response $response, $args)
    {
        $listID = \filter_var($args['id'], \FILTER_SANITIZE_STRING);

        if (!$this->db->has('main', [
            'id' => $listID,
            'version' => $this->settings['active_version'],
            'state' => 2,
            'user' => $this->container->auth->getUser()->getID()
        ])) {
            return $this->notFound($response);
        }

        $this->print = true;
        
        return parent::__invoke($request, $response, $args);
    }
    
    protected function notFound(&$response)
    {
        return $this->redirectWithMessage($response, 'subjects-student', "error", [
            $this->container->lang->g('notfound', 'student-generate')
        ]);
    }
}
