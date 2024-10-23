<?xml version="1.0" encoding="utf-8"?>
<automobiles>
    @foreach($automobiles as $automobile)
    <automobile>
        @foreach($automobile->getAttributes() as $attribute => $value)
            <{{ $attribute }}>{{ $value }}</{{ $attribute }}>
        @endforeach
    </automobile>
    @endforeach
</automobiles>
