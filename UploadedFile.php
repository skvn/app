<?php

namespace Skvn\App;

use Skvn\Base\Traits\AppHolder;
use Skvn\Base\Helpers\Str;
use Skvn\Base\Helpers\File;

class UploadedFile
{
    use AppHolder;

    public $fileInfo;
    protected $type;
    protected $tmpdir;
    protected $validator;

    function __construct($file = [], $type = null)
    {
        $this->fileInfo = $file;
        $this->type = $type;
    }

    static function createUrl($app, $url)
    {
        $url = filter_var($url, FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE);
        $obj = new self([], "url");
        $obj->setApp($app);
        if (!empty($url)) {
            $obj->loadFromUrl($url);
        }
        return $obj;
    }

    function loadFromUrl($url)
    {
        try {
            $content = $this->app->urlLoader->load($url, [], [
                'header' => 0,
                'binarytransfer' => 1
            ]);
        } catch (\Exception $e) {
            $content = "";
        }
        if (!empty($content)) {
            $this->fileInfo['tmp_name'] = $this->getTmpDir() . "/" . uniqid("f");
            file_put_contents($this->fileInfo['tmp_name'], $content);
            $this->fileInfo['error'] = 0;
            $this->fileInfo['name'] = basename($url);
            $this->fileInfo['size'] = filesize($this->fileInfo['tmp_name']);
            if (Str:: pos("?", $this->fileInfo['name']) !== false) {
                $parts = explode("?", $this->fileInfo['name']);
                $this->fileInfo['name'] = $parts[0];
            }
        }
    }

    static function createMultipart($app, $file)
    {
        $obj = new self($file, "multipart");
        $obj->setApp($app);
        return $obj;
    }

    static function createXhr($app, $name)
    {
        $obj = new self([], "xhr");
        $obj->setApp($app);
        if (!empty($name)) {
            $obj->loadFromXhr($name);
        }
        return $obj;
    }

    function setValidator($validator)
    {
        $this->validator = $validator;
    }

    function loadFromXhr($name)
    {
        $input = fopen("php://input", "r");
        $this->fileInfo['tmp_name'] = $this->getTmpDir() . "/" . uniqid("f");
        $tmp = fopen($this->fileInfo['tmp_name'], "w");
        stream_copy_to_stream($input, $tmp);
        fclose($input);
        fclose($tmp);
        $this->fileInfo['name'] = $name;
        $this->fileInfo['size'] = filesize($this->fileInfo['tmp_name']);
        $this->fileInfo['error'] = file_exists($this->fileInfo['tmp_name']) ? 0 : 1;
    }


    function getTmpDir()
    {
        if (empty($this->tmpdir)) {
            $this->tmpdir = $this->app->getPath('tmp') . '/' . str_replace(".", "", uniqid("u", true));
        }
        if (!file_exists($this->tmpdir)) {
            mkdir($this->tmpdir, 0777, true);
        }
        return $this->tmpdir;
    }


    function isValid($rules)
    {
        if (empty($this->fileInfo)) {
            return false;
        }
        if (!empty($this->fileInfo['error'])) {
            return false;
        }
        if (is_callable($this->validator)) {
            return call_user_func($this->validator, $this->getName(), $this->fileInfo['tmp_name'], $rules);
        }
        return true;
    }

    function getName()
    {
        return isset($this->fileInfo['name']) ? $this->fileInfo['name'] : "";
    }

    function getTmpName()
    {
        return $this->fileInfo['tmp_name'];
    }

    function save($target, $create_ext = false)
    {
        if ($create_ext) {
            $info = pathinfo($this->getName());
            $target .= "." . $info['extension'];
        }
        if ($this->type == "multipart") {
            move_uploaded_file($this->fileInfo['tmp_name'], $target);
        } else {
            rename($this->fileInfo['tmp_name'], $target);
        }
        return $target;
    }

    function handle($target, $create_ext = false, $rules = [])
    {
        if ($this->isValid($rules)) {
            $fname = $this->save($target, $create_ext);
            $this->destroy();
            return $fname;
        } else {
            $this->destroy();
            return false;
        }
    }

    function destroy()
    {
        if (!empty($this->tmpdir)) {
            File:: rm($this->tmpdir);
        }
    }
}