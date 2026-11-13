<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait FileUpload
{
    public function tambahFile($model, $column, $path)
    {
        if ($this->$column) {
            $namaFile = $model.now()->format('YmdHis').'.'.$this->$column->extension();
            $this->$column->storeAs($path, $namaFile);
            $model->$column = $namaFile;
        }
    }

    public function updateFile($model, $column, $path)
    {

        if ($this->$column != $model->$column) {

            if (! is_null($model->$column)) {
                Storage::delete($path.'/'.$model->$column);
            }

            $namaFile = $model.now()->format('YmdHis').'.'.$this->$column->extension();
            $this->$column->storeAs($path, $namaFile);
            $model->$column = $namaFile;

            $this->reset($column);
        }
    }

    public function hapusFile($model, $column, $path)
    {
        if (! is_null($model->$column)) {
            Storage::delete($path.'/'.$model->$column);
        }
    }
}
