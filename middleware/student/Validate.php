<?php

namespace modules\subjects\middleware\student;

use \modules\subjects\middleware\Middleware;
use Slim\Http\Request;
use Slim\Http\Response;

class Validate extends Middleware
{
    public function __invoke($request, $response, $next)
    {
        $listID = $request->getAttributes()['route']->getArgument('id');

        if (is_null($listID)) {
            return $this->notFound($response);
        }

        $state = $this->container->db->get('main', ['state [Int]'], [
            'user' => $this->container->auth->getUser()->getID(),
            'id' => $listID,
            'version' => $this->settings['active_version']
        ]);

        if ($state === false) {
            return $this->notFound($response);
        } else if ($state['state'] != 0) {
            return $next($request, $response);
        }

        $limits = (int) $this->container->db->get('versions', 'limit', ['id' => $this->settings['active_version']]);
        
        $selected = $this->container->db->select('lists', [
            'subject [Index]',
            'level [Int]'
        ], ['list' => $listID]);

        $selectedCount = 0;
        $spareCount = 0;

        foreach ($selected as $id => $subject) {
            if (($subject['level'] ?? false) === 0) {
                $selectedCount++;
            } else if (($subject['level'] ?? false) >= 1) {
                $spareCount++;
            }
        }

        if ($selectedCount != $limits || $spareCount != 2) {
            return $this->redirectWithMessage($response, 'subjects-student-edit', "error", [
                $this->container->lang->g('error-notvalid', 'student-validate')
            ], ['id' => $listID]);
        } else {
            return $next($request, $response);
        }
    }

    private function notFound(&$response) {
        return $this->redirectWithMessage($response, 'subjects-student', "error", [
            $this->container->lang->g('error-notfound', 'student-validate')
        ]);
    }
}
