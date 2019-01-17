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
        $books = $this->getBooks();
        $lists = $this->getLists();

        if (is_file(__DIR__ . '/archive.zip'))
            unlink(__DIR__ . '/archive.zip');
        
        $this->createZip($books, $lists);
        return $this->sendFile($response, __DIR__ . '/archive.zip');
    }

    private function createZip($books, $lists) {
        $zip = new \ZipArchive();
        $filename = __DIR__ . "/archive.zip";

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
            $user = (new \sup\User($this->container->base))->createFromDB($list['user']);
            
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
