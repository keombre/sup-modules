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

    function draws($request, $response, $args) {
        if (!is_null($request->getQueryParam('all'))) {
            $draws = $this->db->select('draws', [
                'id [Index]',
                'list [Int]',
                'time [Int]',
                'book [Int]'
            ], ['version' => $this->settings['active_version']]);
        } else {
            $draws = $this->db->select('draws', [
                'id [Index]',
                'list [Int]',
                'time [Int]',
                'book [Int]'
            ], ['version' => $this->settings['active_version'], 'time[>]' => strtotime('today midnight')]);
        }
        
        return $response->withJson(['status' => 'success', 'code' => 0, 'draws' => $draws]);
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

    function draw($request, $response, $args) {
        $list = filter_var($args['list'], FILTER_SANITIZE_STRING);
        $book = filter_var($args['book'], FILTER_SANITIZE_STRING);

        if (!$this->db->has('main', ['id' => $list], [
            'version' => $this->settings['active_version'],
            'state' => 2
        ]) || !$this->db->has('lists', ['book' => $book, 'list' => $list]))
            return $response->withJson(['status' => 'error', 'code' => 2, 'message' => 'not found'], 404);
        
        if ($this->db->has('draws', ['list' => $list, 'version' => $this->settings['active_version']]))
            return $response->withJson(['status' => 'error', 'code' => 3, 'message' => 'already drawn']);

        if (!is_null($request->getQueryParam('time'))) {
            $time = filter_var($request->getQueryParam('time'), FILTER_VALIDATE_INT);
            if (!\is_numeric($time))
                return $response->withJson(['status' => 'error', 'code' => 1, 'message' => 'bad request'], 400);
        } else {
            $time = time();
        }

        if ($this->db->insert('draws', ['list' => $list, 'time' => $time, 'book' => $book, 'version' => $this->settings['active_version']])) {
            return $response->withJson(['status' => 'successs', 'code' => 0, 'message' => 'OK']);
        } else {
            return $response->withJson(['status' => 'error', 'code' => 4, 'message' => 'database error']);
        }
    }

    function revoke($request, $response, $args) {
        $list = filter_var($args['list'], FILTER_SANITIZE_STRING);
        $book = filter_var($args['book'], FILTER_SANITIZE_STRING);

        if (
            !$this->db->has('main', ['id' => $list], [
                'version' => $this->settings['active_version'],
                'state' => 2
            ]) ||
            !$this->db->has('lists', ['book' => $book, 'list' => $list]) ||
            !$this->db->has('draws', ['book' => $book, 'list' => $list, 'version' => $this->settings['active_version']])
        )
            return $response->withJson(['status' => 'error', 'code' => 2, 'message' => 'not found'], 404);
        
        if ($this->db->delete('draws', ['list' => $list, 'book' => $book, 'version' => $this->settings['active_version']])) {
            return $response->withJson(['status' => 'successs', 'code' => 0, 'message' => 'OK']);
        } else {
            return $response->withJson(['status' => 'error', 'code' => 4, 'message' => 'database error']);
        }
        
    }
    
}

/**
 * api schema:
 * 
 * /lists -> all lists with users
 * /draws[?all] -> list of alredy drawn books
 * /{list id} -> all books from list with attributes
 * /{list id}/draw/{book id}[?time] -> mark book as drawn with time
 * /{list id}/revoke/{book id} -> revoke draw
 * 
 */
