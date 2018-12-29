<?php

namespace modules\lists\controller\teacher;

class view extends \sup\controller {

    protected $settings;

    function __construct(\Slim\Container $container) {
        parent::__construct($container);
        
        $this->settings = $this->db->get("settings", "*");
    }

    function __invoke($request, $response, $args) {
        if (array_key_exists('id', $args)) {
            $listID = filter_var($args['id'], FILTER_SANITIZE_STRING);
            
            return (new \controller\lists\preview($this->container))->withListID($listID)->preview($request, $response, $args);

        } else {
            $students = $this->db->select('userinfo', ['[>]users' => ['id']], [
                'userinfo.id [Int]',
                'users.name(code) [Int]',
                'name' => [
                    'userinfo.givenname(given) [String]',
                    'userinfo.surname(sur) [String]'
                ],
                'userinfo.class [String]'
            ], ['users.role[~]' => ROLE_STUDENT]);
            foreach ($students as $id => $student)
                $students[$id]['list'] = $this->db->get('main', 'id', [
                    'version' => $this->settings['active_version'],
                    'user' => $student['id'],
                    'state' => 2
                ]);
            
            $response = $this->sendResponse($request, $response, "teacher/view.phtml", ["students" => $students]);
        }
        return $response;
    }
}
