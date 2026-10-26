<?php
\App\Models\User::select('level', \DB::raw('count(*) as total'))
    ->groupBy('level')
    ->get()
    ->each(function ($row) {
        echo $row->level . ': ' . $row->total . PHP_EOL;
    });