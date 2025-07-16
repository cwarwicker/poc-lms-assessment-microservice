<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use mod_quiz\plugininfo\quiz;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // Seed some test questions into a test quiz.
        $quiz = new \App\Models\Quiz();
        $quiz->name = 'Example Quiz';
        $quiz->save();

        $question = new \App\Models\Question();
        $question->name = 'Question 1';
        $question->type = 'choice';
        $question->text = 'What colour is the sky?';
        $question->points = 1;
        $question->data = json_encode([
            'choices' => [
                'blue' => ['text' => 'Blue', 'correct' => true],
                'green' => ['text' => 'Green', 'correct' => false],
                'yellow' => ['text' => 'Yellow', 'correct' => false],
            ],
            'config' => [],
        ]);
        $quiz->questions()->save($question);

        $question = new \App\Models\Question();
        $question->name = 'Question 2';
        $question->type = 'truefalse';
        $question->text = 'Chelsea FC are based in London?';
        $question->points = 1;
        $question->data = json_encode([
            'answer' => true,
            'config' => [],
        ]);
        $quiz->questions()->save($question);

        $question = new \App\Models\Question();
        $question->name = 'Question 3';
        $question->type = 'simpleinput';
        $question->text = 'Name a programming language that can be used for back-end development';
        $question->points = 3;
        $question->data = json_encode([
            'answers' => ['PHP', 'Ruby', 'Python', 'Java', 'C++', 'C#', 'Golang', 'Rust', 'Javascript'],
            'config' => [
                'casesensitive' => false,
                'exactmatch' => true,
            ],
        ]);
        $quiz->questions()->save($question);

    }
}
