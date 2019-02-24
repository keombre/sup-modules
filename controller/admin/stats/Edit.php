<?php declare(strict_types=1);

namespace modules\subjects\controller\admin\stats;

use modules\subjects\controller\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

class Edit extends Controller
{
    public function __invoke(Request $request, Response $response, $args)
    {   
        $version = $this->sanitizePost($request, 'version', \FILTER_VALIDATE_INT);

        if ($version === false || !$this->db->has('versions', ['id' => $version])) {
            return $this->redirectWithMessage($response, 'subjects-admin-stats-dash', "error", [
                $this->container->lang->g('error-subject-notfound', 'admin-manage')
            ]);
        }

        if ($request->isPut()) {
            
            $subject = $this->sanitizePost($request, 'subject', \FILTER_VALIDATE_INT);
            
            if ($subject === false || !$this->db->has('subjects', [
                'id' => $subject,
                'version' => $version,
                'state' => 1
            ])) {
                return $this->redirectWithMessage($response, 'subjects-admin-stats-dash', "error", [
                    $this->container->lang->g('error-subject-notfound', 'admin-manage')
                ]);
            }

            $this->db->update('subjects', ['state' => 0], ['id' => $subject]);
            
            return $this->redirectWithMessage($response, 'subjects-admin-stats-dash', "status", [
                $this->container->lang->g('success-saved', 'admin-manage')
            ]);

        } else if ($request->isDelete()) {

            $subject = $this->sanitizePost($request, 'subject', \FILTER_VALIDATE_INT);
            
            if ($subject === false || !$this->db->has('subjects', [
                'id' => $subject,
                'version' => $version,
                'state' => 0
            ])) {
                return $this->redirectWithMessage($response, 'subjects-admin-stats-dash', "error", [
                    $this->container->lang->g('error-subject-notfound', 'admin-manage')
                ]);
            }

            $this->db->update('subjects', ['state' => 1], ['id' => $subject]);
            
            return $this->redirectWithMessage($response, 'subjects-admin-stats-dash', "status", [
                $this->container->lang->g('success-saved', 'admin-manage')
            ]);
        }
    }
}
