<?php declare(strict_types=1);

namespace modules\subjects\controller\student;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Edit extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {
        $this->sendResponse($request, $response, "student/edit.phtml");
    }
}
