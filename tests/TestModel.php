<?php


namespace Tests;


use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class TestModel extends Model
{
    use Searchable;

    public function toSearchableArray()
    {
        return [
            'first_name' => 'Steve',
            'last_name' => 'Broski',
        ];
    }
}
