<?php

namespace classess;


/**
 * Class Book
 * @package classess
 */
class Book
{
    private $bookUrl = '';
    private $bookPageUrl = 'https://lit-era.com/reader/get-page';
    private $f;
    private $key = '';


    public function __construct($url)
    {
        $this->bookUrl = $url;
    }


    public function grab()
    {
        // check auth
        $html = $this->getCurl($this->bookUrl);
        \phpQuery::newDocumentHTML($html);
        $title = pq('#reader')->text();
        $this->key = pq('meta[name="csrf-token"]')->attr('content');
        $this->makeFile($title);

        // get chapters
        $chapters = $this->chapters();

        $len = count($chapters);
        foreach ($chapters as $key => $chapter) {
            $this->write('<h2>' . $chapter['name'] . '</h2>');
            echo "Try to get chapter " . ($key + 1) . " of " . $len . " with ID#" . $chapter['id'] . "...\n";
            $this->grabChapter($chapter['id']);
        }


        $this->doneFile();
    }

    private function getCurl($url, $params = [], $method = 'get', $isAjax = false)
    {
        $curl = new \Curl();
        $curl->user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36';
        if ($isAjax) {
            $curl->headers['x-csrf-token'] = $this->key;
            $curl->headers['X-Requested-With'] = 'XMLHttpRequest';
        }
        // cookie
        $curl->cookie_file = dirname(__FILE__) . '/../cookie.txt';
        if ($method == 'get') {
            $response = $curl->get($url, $params);
        } else {
            $response = $curl->post($url, $params);
        }

        if (!$isAjax && !strstr($response, 'Павел Чернигов')) {
            throw new \Exception('No auth. Check cookie key!', 200);
        }
        return $response;
    }

    private function chapters()
    {
        $chapters = pq('select[name="chapter"] > option');
        $result = [];
        foreach ($chapters as $chapter) {
            $result[] = [
                'id' => pq($chapter)->attr('value'),
                'name' => pq($chapter)->text()
            ];
        }
        return $result;
    }

    private function write($val)
    {
        fwrite($this->f, $val);
    }

    private function makeFile($title)
    {
        $folder = dirname(__FILE__) . '/../books/';
        $file = $folder . str_replace(['<', '>', ':', '"', '/', '\\', '|', '?', '*'], '_', $title) . '.html';
        // replace file
        $this->f = fopen($file, 'w');
        $this->write('<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"><meta http-equiv="X-UA-Compatible" content="ie=edge">' .
            '<title>' . $title . '</title><style>body {font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;font-size: 18px;line-height: 1.42857143;color: #333;background-color: #fff;text-align: justify;margin-bottom: 10px;background: url(body_bg.jpg);padding: 20px;}</style>' .
            '</head><body><h1>' . $title . '</h1>');

    }

    private function doneFile()
    {
        $this->write('</body></html>');
        fclose($this->f);
    }

    private function grabChapter($id)
    {
        if (!$id > 0) {
            echo ' ERROR: NO ID IN CHAPTER!';
            return;
        }
// get first page
        $page = 1;
        while (true) {
            $params = [
                'chapterId' => $id,
                'page' => $page,
                '_csrf' => $this->key
            ];
            $response = $this->getCurl($this->bookPageUrl, $params, 'post', true);
            if (!$response || empty($response)) {
                echo "empty response. Break all \n";
                die();
            }
            $json = json_decode($response);
            if (!$json) {
                echo "Not data received - skip\n";
                return false;
            } else if ($json->status != 1) {
                echo "Book is payed- skip\n";
                $this->write("<h3 style='color: red'>Глава платная!</h3>");
                return false;
            }

            echo "--- get page " . $page . ' of ' . $json->totalPages;

            // write to file
            $this->write($json->data);

            if ($json->isLastPage) {
                break;
            }
            echo "... done. Wait for sec..\n";
            usleep(100000);
            $page++;
        }
        echo "- Chapter done\n";
    }
}
