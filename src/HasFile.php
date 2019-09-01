<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Shridhar\EloquentFiles;

use Illuminate\Support\Facades\Storage;

/**
 * @author Shridhar
 */
trait HasFile {

    /**
     * @param string $attribute_name
     * @param array $options
     * @return File
     */
    function file_info($attribute_name = "file_path", $options = []) {

        $file = new File([
            "path" => $this->{$attribute_name},
            "attribute_name" => $attribute_name,
            "disk" => $this->files_disk(),
            "options" => $options,
            "model" => $this
        ]);

        return $file;
    }

    /**
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    function files_disk() {
        if (isset(static::$files_disk)) {
            $disk_name = static::$files_disk;
        } else {
            $disk_name = "public";
        }
        return Storage::disk($disk_name);
    }

}
