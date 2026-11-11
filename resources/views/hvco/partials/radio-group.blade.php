<fieldset class="choice-group">
    <legend>{{ $question }}</legend>
    <div class="choice-grid choice-grid--radio">
        @foreach($items as $value => $label)
            <label class="choice"><input type="radio" name="{{ $name }}" value="{{ $value }}" @checked($field($name) === $value)><span>{{ $label }}</span></label>
        @endforeach
    </div>
    @error($name)<p class="error">{{ $message }}</p>@enderror
</fieldset>
