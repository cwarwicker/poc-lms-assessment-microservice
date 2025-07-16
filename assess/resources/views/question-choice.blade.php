<select class="form-select" aria-label="Default select example" name="question[{{$question->id}}]">
    <option value="">Choose...</option>
    @foreach ($question->data()->choices as $value => $choice)
        <option value="{{ $value }}">{{ $choice->text }}</option>
    @endforeach
</select>
