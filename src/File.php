<?php

namespace Shridhar\EloquentFiles;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class File extends Model {

    protected
            $guarded = [],
            $appends = [
                'exists',
                'url',
                'file_path',
                'extension',
                'type'
                    ],
            $hidden = [
                "disk",
                "attribute_name",
                "model",
                "options"
    ];

    public function exists() {
        return $this->disk->exists($this->path);
    }

    public function getExistsAttribute() {
        return $this->exists();
    }

    public function getExtensionAttribute() {
        if ($this->path) {
            $extension = pathinfo($this->path, PATHINFO_EXTENSION);
            return $extension;
        }
    }

    public function getUrlAttribute() {
        $exists = $this->exists();
        $options = $this->options;

        $default_url = array_get($options, "default_url");
        $default_asset = array_get($options, "default_asset");
        if ($exists) {
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

    public function getFilePathAttribute() {
        $path = $this->path;
        return $this->disk->getAdapter()->getPathPrefix() . $path;
    }

    public function getTypeAttribute() {
        if ($this->exists()) {
            return $this->disk->getMimetype($this->path);
        }
    }

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

    public function uploadImage($file) {
        $mime = $file->getMimeType();
        if (!starts_with($mime, "image")) {
            throw new \Exception("Uploaded file is not an image.");
        }
        return $this->upload($file);
    }

    public function delete() {

        $this->model->{$this->attribute_name} = null;

        $this->model->save();

        $this->disk->delete($this->path);

        return $this;
    }

}
