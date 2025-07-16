@extends('layouts.example')

@section('content')

    <div class="container">
        <h1>Proof of concept assignment</h1>
        <p>This is a pre-configured set of questions for the PoC, but obviously, in a real system this would all be configurable by the teacher.</p>

        <form action="{{ route('submit') }}" method="post">
            @csrf

            <input type="hidden" name="user_id" value="{{ $user_id }}">
            <input type="hidden" name="quiz_id" value="{{ $quiz->id }}">
            <input type="hidden" name="service_url" value="{{ $service_url }}">
            <input type="hidden" name="sourcedid" value="{{ $sourcedid }}">

            <h2>{{ $quiz->name }}</h2>

            @foreach($quiz->questions as $question)
                <div class="row mb-3">
                    <h3>{{ $question->name }} ({{ $question->type }})</h3>
                    <p>{{ $question->text }}</p>
                    <div class="mb-3">
                        {{ $question->render() }}
                    </div>
                    @error('question.' . $question->id)
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                    <div>
                        <small>{{ $question->points }} points</small>
                    </div>
                </div>
                <hr>
            @endforeach

            <p><input type="submit" class="btn btn-primary"></p>

        </form>
    </div>

@endsection
