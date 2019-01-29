<?php declare(strict_types=1);

namespace modules\subjects\controller\teacher;

use modules\subjects\controller\GeneratePDF;
use Slim\Http\Request;
use Slim\Http\Response;

class Generate extends GeneratePDF
{
    protected function notFound(&$response)
    {
        return $this->redirectWithMessage($response, 'subjects-teacher', "error", [
            $this->container->lang->g('notfound', 'teacher-generate')
        ]);
    }
}
