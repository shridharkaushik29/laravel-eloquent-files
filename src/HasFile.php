<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Shridhar\EloquentFiles;

use Illuminate\Support\Facades\Storage;
use Shridhar\EloquentFiles\File as FileModel;

/**
 *
 * @author Shridhar
 */
trait HasFile {

    function file_info($attribute_name = "file_path", $options = []) {

        $file = new FileModel([
            "path" => $this->{$attribute_name},
            "attribute_name" => $attribute_name,
            "disk" => $this->files_disk(),
            "options" => $options,
            "model" => $this
        ]);

        return $file;
    }

    function files_disk() {
        if (isset(static::$files_disk)) {
            $disk_name = static::$files_disk;
        } else {
            $disk_name = "public";
        }
        return Storage::disk($disk_name);
    }

    static function deleteFileOnModelDelete($attribute_name = "file_path") {
        static::deleted(function($model) use($attribute_name) {
            $model->file_delete($attribute_name);
        });
    }

}
