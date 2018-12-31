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
            
            return (new \modules\lists\controller\preview($this->container))->withListID($listID)->preview($request, $response, $args);

        } else {
            
            $students = [];
            foreach ($this->db->select('main', ['id', 'user'], ['version' => $this->settings['active_version'], 'state' => 2]) as $entry)
            $students[] = [
                'user' => (new \sup\User($this->container->base))->createFromDB($entry['user']),
                'list' => $entry['id']
            ];
            
            $response = $this->sendResponse($request, $response, "teacher/view.phtml", ["students" => $students]);
        }
        return $response;
    }
}
