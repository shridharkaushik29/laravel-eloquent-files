<?php

namespace Shridhar\EloquentFiles;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\File as HttpFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @property string path
 * @property string url
 * @property string exists
 * @property string attribute_name
 * @property array options
 * @property Model model
 * @property FilesystemAdapter disk
 */
class File extends Model {

    protected

        $guarded = [],

        $appends = [
        'url',
        'extension',
        'type',
        'exists'
    ],
        $hidden = [
        "disk",
        "attribute_name",
        "model",
        "options"
    ];

    /**
     * @return bool
     */
    public function getExistsAttribute() {
        return $this->path && $this->disk->exists($this->path);
    }

    /**
     * @return string
     */
    public function getExtensionAttribute() {
        if ($this->path) {
            $extension = pathinfo($this->path, PATHINFO_EXTENSION);
            return $extension;
        }
        return "";
    }

    /**
     * @return mixed|string|null
     */
    public function getUrlAttribute() {
        $options = $this->options;

        $default_url = Arr::get($options, "default_url");
        $default_asset = Arr::get($options, "default_asset");

        if ($this->path) {
            $url = $this->disk->url($this->path);
        } elseif ($default_url) {
            $url = $default_url;
        } elseif ($default_asset) {
            $url = asset($default_asset);
        } else {
            $url = null;
        }

        return $url;
    }

    /**
     * @return string
     * @throws FileNotFoundException
     */
    public function getTypeAttribute() {
        if ($this->exists) {
            return $this->disk->getMimetype($this->path);
        } else {
            return "";
        }
    }

    /**
     * @param $file
     * @param null $name
     * @return $this
     */
    public function upload($file, $name = null) {

        if ($file) {
            if ($name) {
                $path = $this->disk->putFileAs("", $file, $name);
            } else {
                $path = $this->disk->putFile("", $file);
            }

            $old = $this->model->{$this->attribute_name};

            $this->model->{$this->attribute_name} = $path;

            try {
                $this->model->save();
                $this->disk->delete($old);
            } catch (QueryException $exc) {
                $this->disk->delete($path);
                throw $exc;
            }
        }

        return $this;
    }

    /**
     * @param HttpFile|UploadedFile $file
     * @return File
     * @throws Exception
     */
    public function uploadImage($file) {
        $mime = $file->getMimeType();
        if (!Str::startsWith($mime, "image")) {
            throw new Exception("Uploaded file is not an image.");
        }
        return $this->upload($file);
    }

    /**
     * @return $this
     */
    public function deleteFile() {
        $this->disk->delete($this->path);
        return $this;
    }

    /**
     * @return $this
     */
    public function delete() {

        $this->deleteFile();

        $this->model->{$this->attribute_name} = null;

        $this->model->save();

        return $this;
    }

}
