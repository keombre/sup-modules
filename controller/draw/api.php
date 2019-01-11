<?php declare(strict_types=1);

namespace modules\lists\controller\draw;

class api extends \sup\controller {

    function __construct(\Slim\Container $container) {
        parent::__construct($container);
        $this->settings = $this->db->get("settings", "*");
    }

    function __invoke($request, $response, $args) {
        return $response->withJson(['status' => 'error', 'code' => 1, 'message' => 'bad request'], 400);
    }
    
    function lists($request, $response, $args) {
        foreach ($this->db->select('main', ['id', 'user'], [
            'version' => $this->settings['active_version'],
            'state' => 2
        ]) as $entry)
            $students[] = [
                'user' => (new \sup\User($this->container->base))->createFromDB(intval($entry['user']))->asArray(),
                'list' => $entry['id']
            ];
        return $response->withJson(['status' => 'success', 'code' => 0, 'lists' => $students]);
    }

    function books($request, $response, $args) {
        $list = filter_var($args['list'], FILTER_SANITIZE_STRING);
        if (!$this->db->has('main', ['id' => $list], [
            'version' => $this->settings['active_version'],
            'state' => 2
        ]))
            return $response->withJson(['status' => 'error', 'code' => 2, 'message' => 'not found'], 404);
        
        $books = $this->db->select('lists', ['[>]books' => ['book' => 'id']], [
            'books.id [Index]',
            'books.name [String]',
            'books.author [String]'
        ], ['books.version' => $this->settings['active_version'], 'lists.list' => $list]);

        return $response->withJson(['status' => 'success', 'code' => 0, 'books' => $books]);
    }
}
