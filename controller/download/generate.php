<?php declare(strict_types=1);

namespace modules\lists\controller\download;

class generate extends \sup\controller
{
    public function __construct(\Slim\Container $container)
    {
        parent::__construct($container);
        
        $this->settings = $this->db->get("settings", "*");
    }

    public function __invoke($request, $response, $args)
    {
        if (!is_string($this->settings['active_version']))
            return $this->redirectWithMessage($response, 'lists', "error", ["Nejdřív zvolte verzi"]);
        
        $books = $this->getBooks();
        $lists = $this->getLists();
        
        $fname = sys_get_temp_dir() .  '/sup-archive.zip';
        
        if (is_file($fname))
            unlink($fname);
        
        $this->createZip($books, $lists, $fname);
        return $this->sendFile($response, $fname, 'archive.zip');
    }

    private function createZip($books, $lists, $fname) {
        $zip = new \ZipArchive();
        $filename = $fname;

        if ($zip->open($filename, \ZipArchive::CREATE)!==TRUE) {
            exit("cannot open <$filename>\n");
        }

        $zip->addFromString('kanon.csv', $books);
        foreach ($lists as $list) {
            $filename = is_null($list['class']) ? '': strtolower($list['class']) . '/';
            $filename .= preg_replace('/[[:^print:]]/', '', str_replace(' ', '-', \strtolower($list['user']))) . '.gms';

            $zip->addFromString('users/' . $filename, $list['books']);
        }
        $zip->close();
    }

    private function getLists():array {
        $ret = [];
        foreach ($this->db->select('main', [
            'id [Index]',
            'user [Int]'
        ], ['state' => 2, 'version' => $this->settings['active_version']]) as $list) {
            $user = $this->container->factory->userFromID($list['user']);
            //$user = (new \sup\User($this->container->base))->createFromDB($list['user']);
            
            $name = $user->getName();
            if ($name == ' ')
                $name = $user->getUName();

            $file = \fopen('php://temp', 'rw');
            \fwrite($file, $name . PHP_EOL);
            
            $this->fwriteList($file, $list['id']);
            \rewind($file);

            $ret[] = [
                'user' => $name,
                'class' => $user->getAttribute('class'),
                'books' => \stream_get_contents($file)
            ];
        }
        return $ret;
    }

    private function fwriteList(&$handle, int $list):void {
        foreach ($this->db->select('lists', ['[>]books' => ['book' => 'id']], [
            'books.id [Index]',
            'books.region [Int]',
            'books.author [String]',
            'books.name [String]'
        ], ['version' => $this->settings['active_version'], 'lists.list' => $list]) as $book) {
            \fwrite($handle, 'A');
            \fputcsv($handle, $book, ';');
        }
    }

    private function getBooks():string {
        $csv = \fopen('php://temp', 'rw');

        foreach ($this->db->select('books', [
            'id [Index]',
            'region [Int]',
            'author [String]',
            'name [String]'
        ], ['version' => $this->settings['active_version']]) as $book) {
            \fputcsv($csv, $book, ';');
        }

        \rewind($csv);
        return \stream_get_contents($csv);
    }
}
