<fieldset class="choice-group">
    <legend>{{ $question }}</legend>
    <div class="choice-grid">
        @foreach($items as $value => $label)
            <label class="choice"><input type="checkbox" name="{{ $name }}[]" value="{{ $value }}" @checked($selected($name, $value)) @isset($max) data-max-group="{{ $name }}" data-max="{{ $max }}" @endisset><span>{{ $label }}</span></label>
        @endforeach
    </div>
    @error($name)<p class="error">{{ $message }}</p>@enderror
    @error($name.'.*')<p class="error">{{ $message }}</p>@enderror
</fieldset>
