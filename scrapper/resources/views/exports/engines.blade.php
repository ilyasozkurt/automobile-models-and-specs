<?xml version="1.0" encoding="utf-8"?>
<engines>
    @foreach($engines as $engine)
    <engine>
        @foreach($engine->getAttributes() as $attribute => $value)
            <{{ $attribute }}>{{ $value }}</{{ $attribute }}>
        @endforeach
    </engine>
    @endforeach
</engines>
