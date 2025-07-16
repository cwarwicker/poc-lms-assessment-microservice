<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use stdClass;

class Question extends Model
{
    public function render() {
        return view('question-' . $this->type, ['question' => $this]);
    }

    public function data(): stdClass {
        return json_decode($this->data);
    }

}
